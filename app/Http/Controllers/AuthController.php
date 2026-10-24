<?php
// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use App\Mail\LoginVerificationCodeMail;
use App\Models\IdentityVerification;
use App\Models\LoginVerificationCode;
use App\Models\PersonalInformation;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    // ============ LOGIN (STEP 1) ============
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

        // Go to email code step
        return $this->beginVerification($user, $remember, 'password');
    }

    // ============ GOOGLE OAUTH ============
    public function redirectToGoogle(Request $request)
    {
        $request->session()->put('google_remember', $request->boolean('remember'));

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('login')->with('error', 'Google sign-in failed. Please try again.');
        }

        $email = $googleUser->getEmail();

        if (! $email) {
            return redirect()->route('login')->with('error', 'Google did not return an email address.');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name'     => $googleUser->getName() ?: explode('@', $email)[0],
                'email'    => $email,
                'phone'    => null,
                'password' => Hash::make(\Illuminate\Support\Str::random(32)),
                'role'     => 'tenant',
            ]);

            AuditLogger::log([
                'action'       => 'CREATE',
                'module'       => 'TENANTS',
                'description'  => 'New tenant registered via Google: ' . $email,
                'subject_type' => User::class,
                'subject_id'   => $user->id,
            ]);
        }

        $remember = (bool) $request->session()->pull('google_remember', false);

        return $this->beginVerification($user, $remember, 'google');
    }

    // ============ VERIFICATION CODE (STEP 2) ============
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
            // If email fails, we log the error but STILL redirect to the verify page
            // so the user can request a resend.
        }

        return redirect()->route('login.verify');
    }

    public function showVerify(Request $request)
    {
        $pending = session('login_pending');

        if (! $pending) {
            return redirect()->route('login');
        }

        $user = User::find($pending['user_id']);

        if (! $user) {
            $request->session()->forget('login_pending');
            return redirect()->route('login');
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

    private function redirectAfterLogin(User $user)
    {
        if ($user->isAdmin()) {
            return Inertia::location('/admin');
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
            'name'     => $validated['fullname'],
            'email'    => $validated['email'],
            'phone'    => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role'     => 'tenant',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        AuditLogger::log([
            'action'       => 'CREATE',
            'module'       => 'TENANTS',
            'description'  => 'New tenant account registered: ' . $user->email,
            'subject_type' => User::class,
            'subject_id'   => $user->id,
        ]);

        return redirect()->route('personal-information');
    }

    // ============ LOGOUT ============
    public function logout(Request $request)
    {
        $user = Auth::user();

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

        return Inertia::location('/');
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
}