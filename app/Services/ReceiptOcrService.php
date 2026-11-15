<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reads the payment reference number from a GCash / payment receipt photo
 * using OCR.space. Shared by every tenant payment screen (Pay Now,
 * Reservations, Room Change, Stay Extension).
 *
 * OCR.space's free plan rejects files over 1 MB (HTTP 413), and phone
 * screenshots/photos are usually bigger, so large images are downscaled
 * first (GD). The browser also compresses before upload.
 */
class ReceiptOcrService
{
    /** OCR.space free-plan upload limit is 1024 KB; stay safely under it. */
    private const MAX_BYTES = 950 * 1024;
    private const MAX_EDGE  = 2000;

    /** @return array{success: bool, reference_no: ?string, raw_text?: string, error?: string} */
    public function scan(UploadedFile $file): array
    {
        $apiKey = config('ocr-space.api_key') ?: env('OCR_SPACE_API_KEY');
        if (empty($apiKey)) {
            Log::error('[ReceiptOcr] OCR API key is missing.');
            return $this->fail('Receipt scanning is not set up. Please type the reference number.');
        }

        [$bytes, $name] = $this->prepare($file);
        if ($bytes === null) {
            return $this->fail('This image is too large to scan. Please type the reference number.');
        }

        $text = '';
        // Engine 2 is strong on digits; engine 3 as a second opinion
        foreach (['2', '3'] as $engine) {
            $result = $this->callApi($apiKey, $bytes, $name, $engine);
            if ($result === null) {
                continue;
            }
            $text = $result;
            if ($ref = $this->extractReference($text)) {
                return ['success' => true, 'reference_no' => $ref, 'raw_text' => $text];
            }
        }

        if ($text === '') {
            return $this->fail("We couldn't read this receipt. Please type the reference number.");
        }

        // Read fine, but no reference number found
        return ['success' => true, 'reference_no' => null, 'raw_text' => $text];
    }

    /**
     * Pull the reference / transaction number out of the receipt text.
     * Handles "Ref No. 2013 482 951736", "Reference No: …", "Ref #…",
     * "Transaction ID …", "Trace No …", and ignores PH mobile numbers.
     */
    public function extractReference(string $text): ?string
    {
        $labelled = '/(?:ref(?:erence)?\.?\s*(?:no\.?|num(?:ber)?|#)?|transaction\s*(?:id|no\.?|number|#)|trace\s*(?:no\.?|number)|txn\s*(?:id|no\.?))\s*[:#.]?\s*([A-Z0-9][A-Z0-9 \-\/|]{5,30}[A-Z0-9])/i';

        if (preg_match_all($labelled, $text, $m)) {
            foreach ($m[1] as $candidate) {
                // Stop at the first word that isn't part of the number ("2013 482 951736 Nov 15")
                $candidate = preg_split('/\s+(?=[A-Za-z]{2,})/', trim($candidate))[0];
                $clean = $this->fixDigitMisreads(preg_replace('/[\s\-]+/', '', $candidate));
                // Numeric refs (GCash: 13 digits) or alphanumeric codes with enough digits
                $digits = preg_match_all('/\d/', $clean);
                if ($digits >= 5 && strlen($clean) >= 6 && strlen($clean) <= 30
                    && ctype_alnum($clean) && ! $this->looksLikePhone($clean)) {
                    return strtoupper($clean);
                }
            }
        }

        // Fallback: a standalone 10–16 digit number (GCash refs are 13), allowing spaced groups
        // (not right after a digit, "+", or a decimal point/comma: "550.00 8031…")
        if (preg_match_all('/(?<![\d+.,])(\d[\d ]{8,18}\d)(?!\d)/', $text, $m)) {
            foreach ($m[1] as $candidate) {
                $clean = preg_replace('/\s+/', '', $candidate);
                $len = strlen($clean);
                if ($len >= 10 && $len <= 16 && ! $this->looksLikePhone($clean)) {
                    return $clean;
                }
            }
        }

        return null;
    }

    /**
     * OCR often confuses digits with look-alikes (7→"/", 0→"O", 1→"I"/"l"/"|",
     * 5→"S", 8→"B"). Only applied when the result is purely numeric, so real
     * alphanumeric references are left untouched.
     */
    private function fixDigitMisreads(string $candidate): string
    {
        $mapped = strtr($candidate, ['/' => '7', 'O' => '0', 'o' => '0', 'I' => '1', 'l' => '1', '|' => '1', 'S' => '5', 'B' => '8']);
        return ctype_digit($mapped) && preg_match_all('/\d/', $candidate) >= strlen($candidate) - 2 ? $mapped : $candidate;
    }

    private function looksLikePhone(string $digits): bool
    {
        return (bool) preg_match('/^(?:09\d{9}|639\d{9}|9\d{9})$/', $digits);
    }

    /** @return array{0: ?string, 1: string} bytes + filename (null bytes = can't shrink enough) */
    private function prepare(UploadedFile $file): array
    {
        $bytes = file_get_contents($file->getRealPath());
        $name  = $file->getClientOriginalName() ?: 'receipt.jpg';

        if (strlen($bytes) <= self::MAX_BYTES) {
            return [$bytes, $name];
        }
        if (! extension_loaded('gd')) {
            return [null, $name];
        }

        $src = @imagecreatefromstring($bytes);
        if (! $src) {
            return [null, $name];
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $edge = self::MAX_EDGE;

        // Shrink until it fits (rarely needs more than one pass)
        do {
            $scale = min(1, $edge / max($w, $h));
            $dst = imagecreatetruecolor(max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale)));
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, imagesx($dst), imagesy($dst), $w, $h);
            ob_start();
            imagejpeg($dst, null, 82);
            $out = ob_get_clean();
            $edge = (int) ($edge * 0.75);
        } while (strlen($out) > self::MAX_BYTES && $edge > 600);

        return strlen($out) <= self::MAX_BYTES ? [$out, 'receipt.jpg'] : [null, $name];
    }

    private function callApi(string $apiKey, string $bytes, string $name, string $engine): ?string
    {
        try {
            $response = Http::timeout(30)
                ->asMultipart()
                ->attach('file', $bytes, $name)
                ->post(config('ocr-space.api_url', 'https://api.ocr.space/parse/image'), [
                    'apikey'    => $apiKey,
                    'language'  => 'eng',
                    'OCREngine' => $engine,
                    'scale'     => 'true',
                    'isTable'   => 'false',
                ]);

            if ($response->failed()) {
                Log::warning('[ReceiptOcr] HTTP ' . $response->status(), ['engine' => $engine, 'body' => substr($response->body(), 0, 300)]);
                return null;
            }

            $data = $response->json();
            if (! empty($data['IsErroredOnProcessing'])) {
                Log::warning('[ReceiptOcr] processing error', ['engine' => $engine, 'errors' => $data['ErrorMessage'] ?? null]);
                return null;
            }

            return (string) ($data['ParsedResults'][0]['ParsedText'] ?? '');
        } catch (\Throwable $e) {
            Log::warning('[ReceiptOcr] ' . $e->getMessage(), ['engine' => $engine]);
            return null;
        }
    }

    private function fail(string $message): array
    {
        return ['success' => false, 'reference_no' => null, 'error' => $message];
    }
}
