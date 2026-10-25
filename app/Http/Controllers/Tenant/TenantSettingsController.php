<?php
// app/Http/Controllers/Tenant/TenantSettingsController.php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class TenantSettingsController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $personalInfo = $user->personalInformation;

        return view('tenants.settings', compact('user', 'personalInfo'));
    }

    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone'           => ['nullable', 'string', 'max:20'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
        ]);

        $user->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        $personalInfo = $user->personalInformation()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'full_name'      => $user->name,
                'email'          => $user->email,
                'contact_number' => $user->phone,
            ]
        );

        $personalInfo->full_name = $validated['name'];
        $personalInfo->email = $validated['email'];
        $personalInfo->contact_number = $validated['phone'] ?? null;

        if ($request->hasFile('profile_picture')) {
            if ($personalInfo->profile_picture && Storage::disk('public')->exists($personalInfo->profile_picture)) {
                Storage::disk('public')->delete($personalInfo->profile_picture);
            }

            $path = $request->file('profile_picture')->store('profiles/' . $user->id, 'public');
            $personalInfo->profile_picture = $path;
        }

        $personalInfo->save();

        AuditLogger::log([
            'action'       => 'UPDATE',
            'module'       => 'TENANTS',
            'description'  => 'Updated profile settings for ' . $user->name,
            'subject_type' => \App\Models\User::class,
            'subject_id'   => $user->id,
        ]);

        return redirect()
            ->route('tenants.settings')
            ->with('success', 'Profile updated successfully!')
            ->with('settings_tab', 'profile');
    }

    public function updatePassword(Request $request)
    {
        // Validate with clear error messages
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required'      => 'Please enter your current password.',
            'current_password.current_password' => 'Your current password is incorrect.',
            'password.required'              => 'Please enter a new password.',
            'password.confirmed'             => 'New passwords do not match.',
            'password.min'                   => 'Password must be at least 8 characters.',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        // Make sure the new password is different from the old one
        if (Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Your new password must be different from your current password.',
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        AuditLogger::log([
            'action'       => 'UPDATE',
            'module'       => 'AUTHENTICATION',
            'description'  => 'Changed account password',
            'subject_type' => \App\Models\User::class,
            'subject_id'   => $user->id,
        ]);

        return redirect()
            ->route('tenants.settings')
            ->with('success', 'Password updated successfully!')
            ->with('settings_tab', 'security');
    }
}