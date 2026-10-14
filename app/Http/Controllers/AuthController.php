<?php
// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PersonalInformation;
use App\Models\IdentityVerification;
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

            // ✅ Use Inertia::location() because /admin and /tenants are
            //    Blade routes, NOT Inertia pages. A plain redirect() would
            //    cause Inertia to inject the Blade HTML into the current
            //    page, producing the "floating modal" mashup.
            if ($user->isAdmin()) {
                return Inertia::location('/admin');
            }

            return Inertia::location('/tenants');
        }

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
            'role'     => 'tenant', // new signups are always tenants
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        // ✅ Redirect into the onboarding flow (Inertia page). Both are
        //    Inertia pages, so a normal redirect() works here. But
        //    Inertia::location also works and keeps behavior consistent.
        return redirect()->route('personal-information');
    }

    // ============ LOGOUT ============
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ✅ Full reload back to the landing page (Inertia page).
        return Inertia::location('/');
    }

    // ============ ONBOARDING: PERSONAL INFO ============
    public function showPersonalInformation()
    {
        return Inertia::render('Onboarding/PersonalInformation', [
            'user' => Auth::user(),
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
        ]);

        /** @var User $user */
        $user = Auth::user();

        PersonalInformation::updateOrCreate(
            ['user_id' => $user->id],
            [
                'full_name'        => $validated['fullname'],
                'email'            => $validated['email'],
                'contact_number'   => $validated['contact_number'],
                'current_address'  => $validated['current_address'] ?? null,
                'emergency_name'   => $validated['emergency_name'],
                'emergency_number' => $validated['emergency_number'],
            ]
        );

        // Also update user's core name/phone
        $user->update([
            'name'  => $validated['fullname'],
            'phone' => $validated['contact_number'],
        ]);

        // ✅ Both are Inertia pages, so a standard redirect works.
        return redirect()->route('identity-verification');
    }

    // ============ ONBOARDING: IDENTITY ============
    public function showIdentityVerification()
    {
        return Inertia::render('Onboarding/IdentityVerification');
    }

    public function storeIdentityVerification(Request $request)
    {
        $validated = $request->validate([
            'id_type'                => ['required', 'string'],
            'id_front'               => ['required', 'file', 'mimes:png,jpg,jpeg', 'max:10240'],
            'id_back'                => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:10240'],
            'verification_agreement' => ['accepted'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $frontPath = $request->file('id_front')->store('ids/' . $user->id, 'public');
        $backPath  = $request->hasFile('id_back')
            ? $request->file('id_back')->store('ids/' . $user->id, 'public')
            : null;

        IdentityVerification::updateOrCreate(
            ['user_id' => $user->id],
            [
                'id_type'  => $validated['id_type'],
                'id_front' => $frontPath,
                'id_back'  => $backPath,
                'status'   => 'pending',
            ]
        );

        // ✅ /tenants/dashboard is a Blade route (Route::view), so it needs
        //    a full browser reload. Inertia::location() forces that.
        return Inertia::location('/tenants');
    }
}