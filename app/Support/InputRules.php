<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Central validation rules + length limits for every user-facing input.
 *
 * Limits follow common standards (RFC 5321 for email, E.164 for phone,
 * OWASP/NIST for passwords) and never exceed the DB column they're stored
 * in. Keep in sync with resources/js/utils/inputLimits.js (frontend
 * maxLength / min / max attributes).
 */
final class InputRules
{
    // ── Text lengths ────────────────────────────────────────────
    public const NAME          = 100;   // person full name
    public const EMAIL         = 254;   // RFC 5321 max address length
    public const PHONE         = 20;    // E.164 (15 digits) + "+", spaces, dashes
    public const PASSWORD_MIN  = 8;     // NIST SP 800-63B
    public const PASSWORD_MAX  = 64;    // OWASP recommended cap (bcrypt truncates at 72 bytes)
    public const TITLE         = 150;   // announcement / request / scene titles
    public const SUBJECT       = 150;   // message subject
    public const CATEGORY      = 100;
    public const SHORT_TEXT    = 255;   // generic single-line text
    public const ADDRESS       = 255;
    public const ROOM_LABEL    = 50;    // "Room 101 - Bed A"
    public const ROOM_NUMBER   = 20;
    public const REFERENCE_NO  = 50;    // payment reference / transaction no.
    public const TIME_TEXT     = 20;    // "08:30", "2:00 PM"
    public const TIME_WINDOW   = 50;    // "10:00 AM - 12:00 PM"
    public const ID_TYPE       = 50;
    public const NOTES         = 1000;  // admin notes, reservation notes
    public const LONG_TEXT     = 2000;  // descriptions, reasons
    public const MESSAGE_BODY  = 5000;  // chat message
    public const CONTENT       = 5000;  // announcement body
    public const COMPANIONS    = 2000;  // joined list of companion names
    public const SEARCH        = 100;
    public const URL           = 500;   // tour_hotspots.media_url column
    public const OTP_LENGTH    = 6;

    // ── Numbers ────────────────────────────────────────────────
    public const MONEY_MAX     = 1000000;  // ₱1,000,000 per field
    public const MAX_FLOORS    = 50;
    public const MAX_BEDS      = 50;
    public const MAX_OCCUPANTS = 20;

    // ── Files (kilobytes) ───────────────────────────────────────
    public const AVATAR_KB     = 5120;   // 5 MB  — profile pictures
    public const IMAGE_KB      = 10240;  // 10 MB — receipts, IDs, thumbnails (phone camera photos)
    public const PANORAMA_KB   = 20480;  // 20 MB — 360° scene images
    public const ATTACHMENT_KB = 10240;  // 10 MB — message attachments
    public const MAX_ATTACHMENTS = 5;

    public const IMAGE_MIMES      = 'jpg,jpeg,png,webp';
    public const ATTACHMENT_MIMES = 'jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,txt,zip,rar';

    // ── Patterns ───────────────────────────────────────────────
    /** Letters (any language), spaces, dots, apostrophes, hyphens: "Ma. Clara O'Neil-Cruz Jr." */
    public const NAME_REGEX = "/^[\pL\pM][\pL\pM\s.'\-]*$/u";

    /** Optional "+", then digits with optional spaces / dashes / parentheses. */
    public const PHONE_REGEX = '/^\+?[0-9][0-9\s\-()]{5,18}[0-9]$/';

    /** Alphanumeric references, allowing dashes, spaces and slashes inside. */
    public const REFERENCE_REGEX = '/^[A-Za-z0-9][A-Za-z0-9\-\/ ]*$/';

    /** "08:30", "8:30 AM", "08:30:00". */
    public const TIME_REGEX = '/^\d{1,2}:\d{2}(:\d{2})?(\s?[AaPp]\.?[Mm]\.?)?$/';

    /** Rejects HTML/script tags (but still allows "<3" or "a < b"). */
    public const NO_HTML_REGEX = '/<\s*\/?\s*[a-zA-Z!][^>]*>/';

    // ── Rule builders ──────────────────────────────────────────

    private static function presence(bool $required): string
    {
        return $required ? 'required' : 'nullable';
    }

    public static function name(bool $required = true): array
    {
        return [self::presence($required), 'string', 'min:2', 'max:' . self::NAME, 'regex:' . self::NAME_REGEX];
    }

    public static function email(bool $required = true, int $max = self::EMAIL): array
    {
        return [self::presence($required), 'string', 'email:rfc', 'max:' . $max];
    }

    public static function phone(bool $required = true): array
    {
        return [self::presence($required), 'string', 'max:' . self::PHONE, 'regex:' . self::PHONE_REGEX];
    }

    /** New password: 8–64 chars, upper + lower case and a number. */
    public static function newPassword(): array
    {
        return [
            'required',
            'string',
            'confirmed',
            Password::min(self::PASSWORD_MIN)->max(self::PASSWORD_MAX)->mixedCase()->numbers(),
        ];
    }

    /** Free text (single or multi-line) with a length cap and no HTML tags. */
    public static function text(int $max, bool $required = true): array
    {
        return [self::presence($required), 'string', 'max:' . $max, 'not_regex:' . self::NO_HTML_REGEX];
    }

    /** Same as text() but for PATCH-style updates where the field may be absent. */
    public static function optionalText(int $max): array
    {
        return ['sometimes', 'string', 'max:' . $max, 'not_regex:' . self::NO_HTML_REGEX];
    }

    public static function reference(bool $required = true): array
    {
        return [self::presence($required), 'string', 'max:' . self::REFERENCE_NO, 'regex:' . self::REFERENCE_REGEX];
    }

    public static function time(bool $required = false): array
    {
        return [self::presence($required), 'string', 'max:' . self::TIME_TEXT, 'regex:' . self::TIME_REGEX];
    }

    public static function money(bool $required = true, int $max = self::MONEY_MAX): array
    {
        return [self::presence($required), 'numeric', 'decimal:0,2', 'min:0', 'max:' . $max];
    }

    public static function image(bool $required = false, int $kb = self::IMAGE_KB): array
    {
        return [self::presence($required), 'file', 'image', 'mimes:' . self::IMAGE_MIMES, 'max:' . $kb];
    }

    public static function attachments(): array
    {
        return [
            'attachments'   => ['nullable', 'array', 'max:' . self::MAX_ATTACHMENTS],
            'attachments.*' => ['file', 'mimes:' . self::ATTACHMENT_MIMES, 'max:' . self::ATTACHMENT_KB],
        ];
    }

    public static function otp(): array
    {
        return ['required', 'digits:' . self::OTP_LENGTH];
    }

    public static function search(): array
    {
        return ['nullable', 'string', 'max:' . self::SEARCH];
    }

    public static function url(bool $required = false): array
    {
        return [self::presence($required), 'string', 'max:' . self::URL, 'url:http,https'];
    }
}
