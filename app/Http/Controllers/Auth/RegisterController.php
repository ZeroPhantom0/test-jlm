<?php
// app/Http/Controllers/Auth/RegisterController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PersonalInformation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'phone'    => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'role'     => 'tenant',
            ]);

            // Optional: seed personal info
            PersonalInformation::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name'      => $user->name,
                    'email'          => $user->email,
                    'contact_number' => $user->phone,
                ]
            );

            // 🔑 IMPORTANT: create the Tenant shell row NOW, not on approval
            Tenant::ensureForUser($user);

            return $user;
        });

        Auth::login($user);

        return redirect()
            ->route('tenants.dashboard')
            ->with('status', 'Welcome! You can now message building management.');
    }
}