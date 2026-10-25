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
    public static function send(string $phoneNumber, string $message): bool
    {
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