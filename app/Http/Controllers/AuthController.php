<?php
// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PersonalInformation;
use App\Models\IdentityVerification;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class AuthController extends Controller
{
    // ============ LOGIN ============
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            /** @var User $user */
            $user = Auth::user();

            AuditLogger::log([
                'action'      => 'LOGIN',
                'module'      => 'AUTHENTICATION',
                'description' => ucfirst($user->role) . ' logged in successfully',
            ]);

            // Admins → admin dashboard (no onboarding).
            if ($user->isAdmin()) {
                return Inertia::location('/admin');
            }

            // Tenants:
            //   - If BOTH onboarding steps are done → tenant dashboard
            //   - Otherwise → START FROM STEP 1 (Personal Information), always
            $hasPersonal = $user->personalInformation()->exists();
            $hasIdentity = $user->identityVerification()->exists();

            if ($hasPersonal && $hasIdentity) {
                return Inertia::location('/tenants');
            }

            // Fresh login for a not-fully-onboarded user → always Step 1.
            return Inertia::location('/personal-information');
        }

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
        /** @var User|null $user */
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

    // ============ ONBOARDING: PERSONAL INFO ============
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

        $profilePath = $user->personalInformation?->profile_picture;
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

        AuditLogger::log([
            'action'       => 'UPDATE',
            'module'       => 'TENANTS',
            'description'  => 'Completed personal information for ' . $user->name,
            'subject_type' => User::class,
            'subject_id'   => $user->id,
        ]);

        return redirect()->route('identity-verification');
    }

    // ============ ONBOARDING: IDENTITY ============
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

        AuditLogger::log([
            'action'       => 'SUBMIT',
            'module'       => 'TENANTS',
            'description'  => 'Submitted identity verification (' . $validated['id_type'] . ')',
            'subject_type' => IdentityVerification::class,
            'subject_id'   => $verification->id,
        ]);

        return Inertia::location('/tenants');
    }
}