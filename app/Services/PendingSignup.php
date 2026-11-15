<?php

namespace App\Services;

use App\Models\IdentityVerification;
use App\Models\PersonalInformation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * A sign-up that is NOT a user yet.
 *
 * Sign-up (form or Google) only creates this server-side session draft.
 * Onboarding (personal information → identity verification) fills it in,
 * and the User / Tenant / PersonalInformation / IdentityVerification rows
 * are created together — in one transaction — only when the last step is
 * submitted. If the person stops halfway, nothing exists in the database.
 *
 * Uploaded photos wait on the PRIVATE disk (pending-signups/{token}/) and are
 * moved to their usual public paths when the account is created. Abandoned
 * uploads are removed by `php artisan signup:purge-pending` (scheduled).
 */
class PendingSignup
{
    private const KEY = 'signup_pending';
    public const DIR = 'pending-signups';

    public static function exists(): bool
    {
        return ! empty(session(self::KEY)['email']);
    }

    public static function get(): ?array
    {
        return self::exists() ? session(self::KEY) : null;
    }

    /** Start a new draft (replaces any previous one). */
    public static function start(array $account): void
    {
        self::discard();
        session([self::KEY => [
            'token'             => (string) Str::uuid(),
            'source'            => $account['source'],            // 'form' | 'google'
            'name'              => $account['name'],
            'email'             => strtolower($account['email']),
            'phone'             => $account['phone'] ?? null,
            'password_hash'     => $account['password_hash'],
            'terms_accepted_at' => now()->toIso8601String(),
            'personal'          => null,
            'started_at'        => now()->toIso8601String(),
        ]]);
    }

    public static function savePersonal(array $fields, ?UploadedFile $photo): void
    {
        $draft = self::get();
        $previous = $draft['personal']['profile_picture'] ?? null;

        if ($photo) {
            $fields['profile_picture'] = self::storeTemp($photo, $draft['token'], 'profile');
            if ($previous && $previous !== $fields['profile_picture']) {
                Storage::disk('local')->delete($previous);
            }
        } else {
            $fields['profile_picture'] = $previous;
        }

        $draft['personal'] = $fields;
        session([self::KEY => $draft]);
    }

    /** Absolute path of a pending upload (for the onboarding preview), or null. */
    public static function filePath(string $field): ?string
    {
        $draft = self::get();
        $rel = match ($field) {
            'profile' => $draft['personal']['profile_picture'] ?? null,
            default   => null,
        };

        return $rel && Storage::disk('local')->exists($rel) ? Storage::disk('local')->path($rel) : null;
    }

    /**
     * Final step: create the account and everything that belongs to it.
     *
     * @return User the new, persisted user
     * @throws ValidationException if the email was taken in the meantime
     */
    public static function complete(string $idType, UploadedFile $idFront, ?UploadedFile $idBack): User
    {
        $draft = self::get();
        $personal = $draft['personal'];

        $moved = [];
        try {
            $user = DB::transaction(function () use ($draft, $personal, $idType, $idFront, $idBack, &$moved) {
                // Someone may have registered this email while this draft was open
                if (User::where('email', $draft['email'])->lockForUpdate()->exists()) {
                    throw ValidationException::withMessages([
                        'id_front' => 'An account with ' . $draft['email'] . ' was created meanwhile. Please log in instead.',
                    ]);
                }

                $user = User::create([
                    'name'              => $personal['full_name'] ?? $draft['name'],
                    'email'             => $draft['email'],
                    'phone'             => $personal['contact_number'] ?? $draft['phone'],
                    'password'          => $draft['password_hash'],
                    'role'              => 'tenant',
                    'terms_accepted_at' => $draft['terms_accepted_at'],
                ]);
                Tenant::ensureForUser($user);

                $profilePath = null;
                if (! empty($personal['profile_picture'])) {
                    $profilePath = self::moveToPublic($personal['profile_picture'], 'profiles/' . $user->id);
                    $moved[] = $profilePath;
                }

                PersonalInformation::create([
                    'user_id'          => $user->id,
                    'full_name'        => $personal['full_name'],
                    'email'            => $personal['email'],
                    'contact_number'   => $personal['contact_number'],
                    'current_address'  => $personal['current_address'] ?? null,
                    'emergency_name'   => $personal['emergency_name'],
                    'emergency_number' => $personal['emergency_number'],
                    'profile_picture'  => $profilePath,
                ]);

                $front = $idFront->store('ids/' . $user->id, 'public');
                $moved[] = $front;
                $back = $idBack ? $idBack->store('ids/' . $user->id, 'public') : null;
                if ($back) $moved[] = $back;

                IdentityVerification::create([
                    'user_id'  => $user->id,
                    'id_type'  => $idType,
                    'id_front' => $front,
                    'id_back'  => $back,
                    'status'   => 'pending',
                ]);

                return $user;
            });
        } catch (\Throwable $e) {
            // Roll back files that were already copied to public storage
            foreach ($moved as $path) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }

        self::discard();

        return $user;
    }

    /** Forget the draft and delete its temporary uploads. */
    public static function discard(): void
    {
        $token = session(self::KEY)['token'] ?? null;
        if ($token && Str::isUuid($token)) {
            Storage::disk('local')->deleteDirectory(self::DIR . '/' . $token);
        }
        session()->forget(self::KEY);
    }

    private static function storeTemp(UploadedFile $file, string $token, string $name): string
    {
        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        return $file->storeAs(self::DIR . '/' . $token, $name . '-' . Str::random(8) . '.' . $ext, 'local');
    }

    private static function moveToPublic(string $privatePath, string $publicDir): ?string
    {
        if (! Storage::disk('local')->exists($privatePath)) {
            return null;
        }
        $target = $publicDir . '/' . basename($privatePath);
        Storage::disk('public')->put($target, Storage::disk('local')->get($privatePath));
        return $target;
    }
}
