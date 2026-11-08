<?php
// app/Http/Controllers/Tenant/TenantSettingsController.php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Mail\SettingsVerificationCodeMail;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
        $personalInfo = $user->personalInformation;

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
        ]);

        $user->update(['name' => $validated['name']]);
        
        if ($personalInfo) {
            $personalInfo->update(['full_name' => $validated['name']]);
        } else {
            $user->personalInformation()->create([
                'full_name' => $validated['name'],
                'email' => $user->email,
                'contact_number' => $user->phone,
            ]);
        }

        if ($request->hasFile('profile_picture') && $personalInfo) {
            if ($personalInfo->profile_picture && Storage::disk('public')->exists($personalInfo->profile_picture)) {
                Storage::disk('public')->delete($personalInfo->profile_picture);
            }
            $path = $request->file('profile_picture')->store('profiles/' . $user->id, 'public');
            $personalInfo->update(['profile_picture' => $path]);
        }

        AuditLogger::log([
            'action'       => 'UPDATE',
            'module'       => 'TENANTS',
            'description'  => 'Updated basic profile settings for ' . $user->name,
            'subject_type' => \App\Models\User::class,
            'subject_id'   => $user->id,
        ]);

        return redirect()->route('tenants.settings')
            ->with('success', 'Profile updated successfully!')
            ->with('settings_tab', 'profile');
    }

    public function requestVerification(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $actionType = $request->input('action_type'); // email, phone, password

        $rules = [];
        if ($actionType === 'email') {
            $rules['email'] = ['required', 'email', 'max:255', 'unique:users,email,' . $user->id];
        } elseif ($actionType === 'phone') {
            $rules['phone'] = ['required', 'string', 'max:20'];
        } elseif ($actionType === 'password') {
            $rules['current_password'] = ['required', 'current_password'];
            $rules['password'] = ['required', 'confirmed', Password::min(8)];
        } else {
            abort(400);
        }

        $validated = $request->validate($rules);

        if ($actionType === 'password' && Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages(['password' => 'New password must be different from your current password.']);
        }

        // Generate 6-digit code
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $payload = [];
        if ($actionType === 'email') $payload['email'] = $validated['email'];
        if ($actionType === 'phone') $payload['phone'] = $validated['phone'];
        if ($actionType === 'password') $payload['password'] = Hash::make($validated['password']);

        // Store in Cache for 15 minutes instead of database
        Cache::put("settings_verify_{$user->id}", [
            'code' => $code,
            'action_type' => $actionType,
            'payload' => $payload
        ], now()->addMinutes(15));

        // Send to CURRENT email
        Mail::to($user->email)->send(new SettingsVerificationCodeMail($code, $user->name, ucfirst($actionType)));

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent to your current email.',
        ]);
    }

    public function confirmVerification(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'size:6']]);
        
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $cachedData = Cache::get("settings_verify_{$user->id}");

        if (!$cachedData || $cachedData['code'] !== $request->code) {
            return response()->json(['message' => 'Invalid or expired code.'], 422);
        }

        $payload = $cachedData['payload'];
        $actionType = $cachedData['action_type'];
        
        // Initialize message to avoid undefined variable warning
        $msg = 'Settings updated successfully!';

        switch ($actionType) {
            case 'email':
                $user->update(['email' => $payload['email']]);
                $personalInfo = $user->personalInformation;
                if ($personalInfo) {
                    $personalInfo->update(['email' => $payload['email']]);
                }
                $msg = 'Email updated successfully!';
                break;

            case 'phone':
                $user->update(['phone' => $payload['phone']]);
                $personalInfo = $user->personalInformation;
                if ($personalInfo) {
                    $personalInfo->update(['contact_number' => $payload['phone']]);
                }
                $msg = 'Phone number updated successfully!';
                break;

            case 'password':
                $user->update(['password' => $payload['password']]);
                $msg = 'Password updated successfully!';
                break;
        }

        // Remove the code from cache so it can't be reused
        Cache::forget("settings_verify_{$user->id}");

        AuditLogger::log([
            'action'       => 'UPDATE',
            'module'       => 'TENANTS',
            'description'  => "Updated {$actionType} for {$user->name} via email verification",
            'subject_type' => \App\Models\User::class,
            'subject_id'   => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => $msg,
        ]);
    }
}