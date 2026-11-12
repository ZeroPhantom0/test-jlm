<?php
// app/Services/SmsService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send an SMS via IPROG SMS.
     *
     * @param string $phoneNumber The recipient's number (e.g., 639171234567)
     * @param string $message The message body
     * @return bool
     */
    /**
     * Normalise a PH mobile number to the 63XXXXXXXXXX format IPROG expects.
     * Returns null when nothing usable is left.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phone);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            return '63' . substr($digits, 1);
        }
        if (str_starts_with($digits, '63')) {
            return $digits;
        }
        return '63' . $digits;
    }

    /**
     * False only when the number belongs to a user who switched SMS Alerts
     * off. Numbers are compared by their last 10 digits so 0917…, 63917…
     * and +63 917… all match the same account.
     */
    public static function recipientAllowsSms(?string $phone): bool
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phone);
        if (strlen($digits) < 10) {
            return true;
        }
        $last10 = substr($digits, -10);

        try {
            $optedOut = \App\Models\User::query()
                ->where('sms_notifications', false)
                ->whereNotNull('phone')
                ->get(['phone'])
                ->contains(fn ($u) => substr(preg_replace('/[^0-9]/', '', (string) $u->phone), -10) === $last10);
        } catch (\Throwable $e) {
            return true; // never block on a lookup failure
        }

        return ! $optedOut;
    }

    public static function send(string $phoneNumber, string $message): bool
    {
        // Respect the recipient's "SMS Alerts" preference. Every SMS this app
        // sends is a notification (verification codes go by email), so the
        // gate lives here and covers all callers.
        if (! self::recipientAllowsSms($phoneNumber)) {
            Log::info('SMS skipped — recipient turned off SMS Alerts: ' . $phoneNumber);
            return false;
        }

        $apiToken = config('services.iprog_sms.token'); // We'll add this to config/services.php

        if (empty($apiToken)) {
            Log::error('IPROG SMS API token is not configured.');
            return false;
        }

        try {
            $response = Http::post('https://www.iprogsms.com/api/v1/sms_messages', [
                'api_token'    => $apiToken,
                'phone_number' => $phoneNumber,
                'message'      => $message,
            ]);

            if ($response->successful()) {
                Log::info('SMS sent successfully to ' . $phoneNumber);
                return true;
            }

            Log::error('IPROG SMS Error: ' . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error('SMS Sending Failed: ' . $e->getMessage());
            return false;
        }
    }
}