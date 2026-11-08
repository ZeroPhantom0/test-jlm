<?php
// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use App\Mail\AccountRegisteredMail;
use App\Mail\LoginVerificationCodeMail;
use App\Mail\PasswordResetMail;
use App\Models\IdentityVerification;
use App\Models\LoginVerificationCode;
use App\Models\PersonalInformation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    // ============ TENANT LOGIN (STEP 1) ============
    public function showLogin()
    {
        return Inertia::render('Auth/Login', [
            'flash' => [
                'error' => session('error'),
            ],
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::validate($credentials)) {
            AuditLogger::log([
                'action'      => 'LOGIN FAILED',
                'module'      => 'AUTHENTICATION',
                'description' => 'Failed login attempt for ' . $request->input('email'),
                'status'      => 'error',
                'user_name'   => 'Unknown',
                'user_role'   => 'Unknown',
            ]);

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        $user = User::where('email', $credentials['email'])->firstOrFail();

        // 🛑 BLOCK ADMINS from using the tenant login
        if ($user->isAdmin()) {
            return back()->withErrors([
                'email' => 'Administrators must use the admin login portal.',
            ])->onlyInput('email');
        }

        return $this->beginVerification($user, $remember, 'password');
    }

    // ============ ADMIN LOGIN (STEP 1) ============
    public function showAdminLogin()
    {
        return Inertia::render('Auth/AdminLogin', [
            'flash' => [
                'error' => session('error'),
            ],
        ]);
    }

    public function adminLogin(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::validate($credentials)) {
            AuditLogger::log([
                'action'      => 'LOGIN FAILED',
                'module'      => 'AUTHENTICATION',
                'description' => 'Failed admin login attempt for ' . $request->input('email'),
                'status'      => 'error',
                'user_name'   => 'Unknown',
                'user_role'   => 'Unknown',
            ]);

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        $user = User::where('email', $credentials['email'])->firstOrFail();

        // 🛑 BLOCK NON-ADMINS from using the admin login
        if (! $user->isAdmin()) {
            return back()->withErrors([
                'email' => 'You do not have administrator access.',
            ])->onlyInput('email');
        }

        return $this->beginAdminVerification($user);
    }

    // ============ GOOGLE OAUTH ============
    public function redirectToGoogle(Request $request)
    {
        $request->session()->put('google_remember', $request->boolean('remember'));

        try {
            return Socialite::driver('google')->redirect();
        } catch (\Laravel\Socialite\Exceptions\DriverMissingConfigurationException $e) {
            report($e);
            return redirect()->route('login')->with(
                'error',
                'Google login is not configured. Please contact the administrator.'
            );
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('login')->with(
                'error',
                'Could not start Google sign-in. Please try again.'
            );
        }
    }

    // ⬇️ CHANGED — brand-new users are NOT persisted until Terms are accepted
    public function handleGoogleCallback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('login')->with(
                'error',
                'Google sign-in was cancelled.'
            );
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('login')->with(
                'error',
                'Google sign-in failed. Please try again.'
            );
        }

        $email = $googleUser->getEmail();

        if (! $email) {
            return redirect()->route('login')->with(
                'error',
                'Google did not return an email address.'
            );
        }

        $remember = (bool) $request->session()->pull('google_remember', false);

        $user = User::where('email', $email)->first();

        // ────────────────────────────────────────────────────────
        // EXISTING user → straight to OTP (no terms gate)
        // ────────────────────────────────────────────────────────
        if ($user) {
            if ($user->isAdmin()) {
                return redirect()->route('admin.login')->with(
                    'error',
                    'Administrators must log in via the admin portal.'
                );
            }

            // Safety: if an existing user somehow has no terms_accepted_at,
            // send them back through the gate rather than skipping it.
            if (Schema::hasColumn('users', 'terms_accepted_at') && $user->terms_accepted_at === null) {
                session([
                    'google_pending_terms' => [
                        'remember' => $remember,
                        'user_id'  => $user->id,
                        'profile'  => [
                            'name'  => $user->name,
                            'email' => $user->email,
                        ],
                    ],
                ]);

                return redirect()->route('auth.google.terms');
            }

            return $this->beginVerification($user, $remember, 'google');
        }

        // ────────────────────────────────────────────────────────
        // NEW user → stash profile in session only. NO DB writes.
        // The user/tenant rows are created in acceptGoogleTerms().
        // ────────────────────────────────────────────────────────
        session([
            'google_pending_terms' => [
                'remember' => $remember,
                'profile'  => [
                    'name'  => $googleUser->getName() ?: explode('@', $email)[0],
                    'email' => $email,
                ],
            ],
        ]);

        return redirect()->route('auth.google.terms');
    }

    // ⬇️ NEW — show the Terms acceptance gate
    public function showGoogleTerms(Request $request)
    {
        $pending = session('google_pending_terms');

        if (! $pending || empty($pending['profile']['email'])) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/GoogleTerms', [
            'name'  => $pending['profile']['name'],
            'email' => $this->maskEmail($pending['profile']['email']),
        ]);
    }

    // ⬇️ NEW — create the account only after Terms are accepted
    public function acceptGoogleTerms(Request $request)
    {
        $request->validate([
            'accepted' => ['accepted'],
        ]);

        $pending = session('google_pending_terms');

        if (! $pending || empty($pending['profile']['email'])) {
            return redirect()->route('login')->with(
                'error',
                'Your session expired. Please sign in again.'
            );
        }

        $email    = $pending['profile']['email'];
        $name     = $pending['profile']['name'];
        $remember = (bool) ($pending['remember'] ?? false);

        // Resolve an existing user (race condition safeguard) or create one.
        $user = User::where('email', $email)->first();

        $isNewAccount = false;

        if (! $user) {
            $user = User::create([
                'name'              => $name,
                'email'             => $email,
                'phone'             => null,
                'password'          => Hash::make(Str::random(32)),
                'role'              => 'tenant',
                'terms_accepted_at' => now(),
            ]);

            // 🔑 Create the Tenant shell row now that the account is real
            Tenant::ensureForUser($user);

            AuditLogger::log([
                'action'       => 'CREATE',
                'module'       => 'TENANTS',
                'description'  => 'New tenant registered via Google: ' . $email,
                'subject_type' => User::class,
                'subject_id'   => $user->id,
            ]);

            $this->sendWelcomeEmail($user);

            $isNewAccount = true;
        } else {
            // Existing user reached the gate (legacy terms-accepted was null)
            if (Schema::hasColumn('users', 'terms_accepted_at') && $user->terms_accepted_at === null) {
                $user->forceFill(['terms_accepted_at' => now()])->save();
            }

            Tenant::ensureForUser($user); // idempotent
        }

        AuditLogger::log([
            'action'       => 'SUBMIT',
            'module'       => 'AUTHENTICATION',
            'description'  => 'Accepted Terms via Google ' . ($isNewAccount ? 'signup' : 're-acceptance') . ': ' . $email,
            'subject_type' => User::class,
            'subject_id'   => $user->id,
        ]);

        $request->session()->forget('google_pending_terms');

        return $this->beginVerification($user, $remember, 'google');
    }

    // ⬇️ NEW — clear the pending session. Nothing is in the DB yet.
    public function cancelGoogleTerms(Request $request)
    {
        $request->session()->forget('google_pending_terms');

        return redirect()->route('login')->with(
            'error',
            'You must accept the Terms and Conditions to create an account.'
        );
    }

    // ============ TENANT VERIFICATION CODE (STEP 2) ============
    private function beginVerification(User $user, bool $remember, string $method)
    {
        $verification = LoginVerificationCode::generateFor($user);

        session([
            'login_pending' => [
                'user_id'  => $user->id,
                'remember' => $remember,
                'method'   => $method,
                'token'    => $verification->token,
            ],
        ]);

        try {
            Mail::to($user->email)->send(new LoginVerificationCodeMail($verification));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('login.verify');
    }

    public function showVerify(Request $request)
    {
        $pending = session('login_pending');

        if (! $pending) {
            return redirect()->route('login')->with('error', 'Your session expired. Please sign in again.');
        }

        $user = User::find($pending['user_id']);

        if (! $user) {
            $request->session()->forget('login_pending');
            return redirect()->route('login')->with('error', 'User not found. Please sign in again.');
        }

        return Inertia::render('Auth/VerifyCode', [
            'email'  => $this->maskEmail($user->email),
            'method' => $pending['method'],
        ]);
    }

    public function verify(Request $request)
    {
        $pending = session('login_pending');

        if (! $pending) {
            return redirect()->route('login')->with('error', 'Your session expired. Please sign in again.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $verification = LoginVerificationCode::where('token', $pending['token'])
            ->where('user_id', $pending['user_id'])
            ->first();

        if (! $verification || ! $verification->isValid()) {
            return back()->withErrors(['code' => 'This code has expired. Please request a new one.']);
        }

        if (! hash_equals($verification->code, $validated['code'])) {
            return back()->withErrors(['code' => 'The code you entered is incorrect.']);
        }

        $verification->update(['verified_at' => now()]);

        $user = User::findOrFail($pending['user_id']);
        $remember = (bool) ($pending['remember'] ?? false);

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $request->session()->forget('login_pending');

        AuditLogger::log([
            'action'      => 'LOGIN',
            'module'      => 'AUTHENTICATION',
            'description' => ucfirst($user->role) . ' logged in successfully' . ($pending['method'] === 'google' ? ' (Google)' : ''),
        ]);

        return $this->redirectAfterLogin($user);
    }

    public function resendCode(Request $request)
    {
        $pending = session('login_pending');

        if (! $pending) {
            return redirect()->route('login')->with('error', 'Your session expired. Please sign in again.');
        }

        $user = User::find($pending['user_id']);

        if (! $user) {
            $request->session()->forget('login_pending');
            return redirect()->route('login');
        }

        $verification = LoginVerificationCode::generateFor($user);

        session([
            'login_pending' => [
                ...$pending,
                'token' => $verification->token,
            ],
        ]);

        try {
            Mail::to($user->email)->send(new LoginVerificationCodeMail($verification));
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Could not send a new code. Please try again.');
        }

        return back()->with('success', 'A new code has been sent to your email.');
    }

    public function cancelVerification(Request $request)
    {
        $request->session()->forget('login_pending');
        return redirect()->route('login');
    }

    // ============ ADMIN VERIFICATION CODE (STEP 2) ============
    private function beginAdminVerification(User $user)
    {
        $verification = LoginVerificationCode::generateFor($user);

        session([
            'admin_login_pending' => [
                'user_id'  => $user->id,
                'token'    => $verification->token,
            ],
        ]);

        try {
            Mail::to($user->email)->send(new LoginVerificationCodeMail($verification));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('admin.login.verify');
    }

    public function showAdminVerify(Request $request)
    {
        $pending = session('admin_login_pending');

        if (! $pending) {
            return redirect()->route('admin.login')->with('error', 'Your session expired. Please sign in again.');
        }

        $user = User::find($pending['user_id']);

        if (! $user) {
            $request->session()->forget('admin_login_pending');
            return redirect()->route('admin.login')->with('error', 'User not found. Please sign in again.');
        }

        return Inertia::render('Auth/AdminVerifyCode', [
            'email'  => $this->maskEmail($user->email),
        ]);
    }

    public function adminVerify(Request $request)
    {
        $pending = session('admin_login_pending');

        if (! $pending) {
            return redirect()->route('admin.login')->with('error', 'Your session expired. Please sign in again.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $verification = LoginVerificationCode::where('token', $pending['token'])
            ->where('user_id', $pending['user_id'])
            ->first();

        if (! $verification || ! $verification->isValid()) {
            return back()->withErrors(['code' => 'This code has expired. Please request a new one.']);
        }

        if (! hash_equals($verification->code, $validated['code'])) {
            return back()->withErrors(['code' => 'The code you entered is incorrect.']);
        }

        $verification->update(['verified_at' => now()]);

        $user = User::findOrFail($pending['user_id']);

        Auth::login($user, false); // No remember me for admin
        $request->session()->regenerate();
        $request->session()->forget('admin_login_pending');

        AuditLogger::log([
            'action'      => 'LOGIN',
            'module'      => 'AUTHENTICATION',
            'description' => 'Admin logged in successfully',
        ]);

        return Inertia::location('/admin');
    }

    public function adminResendCode(Request $request)
    {
        $pending = session('admin_login_pending');

        if (! $pending) {
            return redirect()->route('admin.login')->with('error', 'Your session expired. Please sign in again.');
        }

        $user = User::find($pending['user_id']);

        if (! $user) {
            $request->session()->forget('admin_login_pending');
            return redirect()->route('admin.login');
        }

        $verification = LoginVerificationCode::generateFor($user);

        session([
            'admin_login_pending' => [
                ...$pending,
                'token' => $verification->token,
            ],
        ]);

        try {
            Mail::to($user->email)->send(new LoginVerificationCodeMail($verification));
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Could not send a new code. Please try again.');
        }

        return back()->with('success', 'A new code has been sent to your email.');
    }

    public function adminCancelVerification(Request $request)
    {
        $request->session()->forget('admin_login_pending');
        return redirect()->route('admin.login');
    }

    private function redirectAfterLogin(User $user)
    {
        if ($user->isAdmin()) {
            Auth::logout();
            session()->flash('error', 'Please use the Administrator login page.');
            return Inertia::location('/admin/login');
        }

        $hasPersonal = $user->personalInformation()->exists();
        $hasIdentity = $user->identityVerification()->exists();

        if ($hasPersonal && $hasIdentity) {
            return Inertia::location('/tenants');
        }

        return Inertia::location('/personal-information');
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $len = mb_strlen($local);

        if ($len <= 2) {
            $masked = $local[0] . str_repeat('*', max(1, $len - 1));
        } else {
            $masked = $local[0] . str_repeat('*', $len - 2) . $local[$len - 1];
        }

        return $masked . '@' . $domain;
    }

    // ============ SIGNUP ============
    public function showSignup()
    {
        return Inertia::render('Auth/Signup');
    }

    public function signup(Request $request)
    {
        $validated = $request->validate([
            'fullname' => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'terms'    => ['accepted'],
        ]);

        $user = User::create([
            'name'              => $validated['fullname'],
            'email'             => $validated['email'],
            'phone'             => $validated['phone'],
            'password'          => Hash::make($validated['password']),
            'role'              => 'tenant',
            'terms_accepted_at' => now(),
        ]);

        // 🔑 Create the shell Tenant row immediately so messaging works
        Tenant::ensureForUser($user);

        Auth::login($user);
        $request->session()->regenerate();

        AuditLogger::log([
            'action'       => 'CREATE',
            'module'       => 'TENANTS',
            'description'  => 'New tenant account registered: ' . $user->email,
            'subject_type' => User::class,
            'subject_id'   => $user->id,
        ]);

        $this->sendWelcomeEmail($user);

        return redirect()->route('personal-information');
    }

    // ============ LOGOUT ============
    public function logout(Request $request)
    {
        $user = Auth::user();

        $wasAdmin = $user?->role === 'admin';

        if (! $wasAdmin) {
            $referer = $request->headers->get('referer', '');
            $wasAdmin = str_contains($referer, '/admin');
        }

        if ($user) {
            AuditLogger::log([
                'action'      => 'LOGOUT',
                'module'      => 'AUTHENTICATION',
                'description' => ucfirst($user->role) . ' logged out',
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $redirectTo = $wasAdmin
            ? route('admin.login')
            : route('login');

        $antiCache = [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
            'Expires'       => 'Sat, 01 Jan 2000 00:00:00 GMT',
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'success'     => true,
                'redirect_to' => $redirectTo,
            ], 200, $antiCache);
        }

        return redirect($redirectTo)->withHeaders($antiCache);
    }

    // ============ PASSWORD RESET ============
    public function showForgotPassword()
    {
        return Inertia::render('Auth/ForgotPassword', [
            'flash' => [
                'success' => session('success'),
                'error'   => session('error'),
            ],
        ]);
    }

    public function sendPasswordResetLink(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return back()->withErrors([
                'email' => 'We could not find an account associated with this email address.',
            ])->onlyInput('email');
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token'      => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $resetUrl = url('/password/reset/' . $token)
            . '?email=' . urlencode($user->email);

        try {
            Mail::to($user->email)->send(
                new PasswordResetMail($user, $token, $resetUrl, 60)
            );
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors([
                'email' => 'We are currently unable to send the reset email. Please try again later.',
            ])->onlyInput('email');
        }

        AuditLogger::log([
            'action'       => 'SUBMIT',
            'module'       => 'AUTHENTICATION',
            'description'  => 'Password reset requested for ' . $user->email,
            'subject_type' => User::class,
            'subject_id'   => $user->id,
        ]);

        return back()->with(
            'success',
            'A secure password reset link has been sent to your email address.'
        );
    }

    public function showResetPassword(Request $request, string $token)
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email', ''),
            'flash' => [
                'error' => session('error'),
            ],
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'token'    => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        if (! $record) {
            return back()->withErrors([
                'email' => 'This password reset link is invalid or has expired.',
            ]);
        }

        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();
            return back()->withErrors([
                'email' => 'This password reset link has expired. Please request a new one.',
            ]);
        }

        if (! Hash::check($validated['token'], $record->token)) {
            return back()->withErrors([
                'email' => 'This password reset link is invalid.',
            ]);
        }

        $user = User::where('email', $validated['email'])->first();
        if (! $user) {
            return back()->withErrors([
                'email' => 'We could not find an account with that email.',
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        AuditLogger::log([
            'action'       => 'UPDATE',
            'module'       => 'AUTHENTICATION',
            'description'  => 'Password reset completed for ' . $user->email,
            'subject_type' => User::class,
            'subject_id'   => $user->id,
        ]);

        return redirect()
            ->route('login')
            ->with('success', 'Your password has been reset. You can now log in.');
    }

    // ============ ONBOARDING ============
    public function showPersonalInformation()
    {
        /** @var User $user */
        $user = Auth::user();
        return Inertia::render('Onboarding/PersonalInformation', [
            'user'         => $user,
            'personalInfo' => $user->personalInformation,
        ]);
    }

    public function storePersonalInformation(Request $request)
    {
        $validated = $request->validate([
            'fullname'         => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email'],
            'contact_number'   => ['required', 'string', 'max:20'],
            'current_address'  => ['nullable', 'string'],
            'emergency_name'   => ['required', 'string', 'max:255'],
            'emergency_number' => ['required', 'string', 'max:20'],
            'profile_picture'  => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        $existing = $user->personalInformation;

        $hasChanges = false;
        $textFields = ['full_name', 'email', 'contact_number', 'current_address', 'emergency_name', 'emergency_number'];
        $incoming = [
            'full_name'        => $validated['fullname'],
            'email'            => $validated['email'],
            'contact_number'   => $validated['contact_number'],
            'current_address'  => $validated['current_address'] ?? null,
            'emergency_name'   => $validated['emergency_name'],
            'emergency_number' => $validated['emergency_number'],
        ];

        if (! $existing) {
            $hasChanges = true;
        } else {
            foreach ($textFields as $field) {
                if ((string) $existing->$field !== (string) $incoming[$field]) {
                    $hasChanges = true;
                    break;
                }
            }
        }

        if ($request->hasFile('profile_picture')) {
            $hasChanges = true;
        }

        $profilePath = $existing?->profile_picture;
        if ($request->hasFile('profile_picture')) {
            $profilePath = $request->file('profile_picture')->store('profiles/' . $user->id, 'public');
        }

        PersonalInformation::updateOrCreate(
            ['user_id' => $user->id],
            [
                'full_name'        => $validated['fullname'],
                'email'            => $validated['email'],
                'contact_number'   => $validated['contact_number'],
                'current_address'  => $validated['current_address'] ?? null,
                'emergency_name'   => $validated['emergency_name'],
                'emergency_number' => $validated['emergency_number'],
                'profile_picture'  => $profilePath,
            ]
        );

        $user->update([
            'name'  => $validated['fullname'],
            'phone' => $validated['contact_number'],
        ]);

        if ($hasChanges) {
            AuditLogger::log([
                'action'       => 'UPDATE',
                'module'       => 'TENANTS',
                'description'  => 'Completed personal information for ' . $user->name,
                'subject_type' => User::class,
                'subject_id'   => $user->id,
            ]);
        }

        return redirect()->route('identity-verification');
    }

    public function showIdentityVerification()
    {
        /** @var User $user */
        $user = Auth::user();
        return Inertia::render('Onboarding/IdentityVerification', [
            'identity' => $user->identityVerification,
        ]);
    }

    public function storeIdentityVerification(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $existing = $user->identityVerification;

        $validated = $request->validate([
            'id_type'                => ['required', 'string'],
            'id_front'               => [$existing?->id_front ? 'nullable' : 'required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
            'id_back'                => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
            'verification_agreement' => ['accepted'],
        ]);

        $hasChanges = false;

        if (! $existing) {
            $hasChanges = true;
        } elseif ($existing->id_type !== $validated['id_type']) {
            $hasChanges = true;
        }

        if ($request->hasFile('id_front') || $request->hasFile('id_back')) {
            $hasChanges = true;
        }

        $frontPath = $existing?->id_front;
        if ($request->hasFile('id_front')) {
            $frontPath = $request->file('id_front')->store('ids/' . $user->id, 'public');
        }

        $backPath = $existing?->id_back;
        if ($request->hasFile('id_back')) {
            $backPath = $request->file('id_back')->store('ids/' . $user->id, 'public');
        }

        $verification = IdentityVerification::updateOrCreate(
            ['user_id' => $user->id],
            [
                'id_type'  => $validated['id_type'],
                'id_front' => $frontPath,
                'id_back'  => $backPath,
                'status'   => 'pending',
            ]
        );

        if ($hasChanges) {
            AuditLogger::log([
                'action'       => 'SUBMIT',
                'module'       => 'TENANTS',
                'description'  => 'Submitted identity verification (' . $validated['id_type'] . ')',
                'subject_type' => IdentityVerification::class,
                'subject_id'   => $verification->id,
            ]);
        }

        return Inertia::location('/tenants');
    }

    // ============ HELPERS ============
    private function sendWelcomeEmail(User $user): void
    {
        try {
            Mail::to($user->email)->send(new AccountRegisteredMail($user));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}