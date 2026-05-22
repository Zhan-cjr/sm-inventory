<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    public static function sendMessage(string $target, string $message): bool
    {
        $org = \App\Models\Organization::first();
        
        $type = $org?->wa_gateway_type ?? 'fonnte';
        $token = $org?->wa_gateway_token ?? env('FONNTE_TOKEN');
        $domain = $org?->wa_gateway_domain;

        // Bersihkan nomor telepon (contoh: ubah 08xxx ke 62xxx atau biarkan jika sudah sesuai)
        $target = self::formatPhoneNumber($target);

        if (empty($token) || $token === 'your_fonnte_token_here') {
            Log::info("=== MOCK WHATSAPP SEND (No Token/Config) ===");
            Log::info("Gateway Type: {$type}");
            Log::info("Target: {$target}");
            Log::info("Message: {$message}");
            Log::info("==========================================");
            return true;
        }

        try {
            if ($type === 'local') {
                if (empty($domain)) {
                    Log::error("WHATSAPP FAIL: Local WA Gateway domain/endpoint is not configured.");
                    return false;
                }

                // Format URL: append /send-message if not present
                $url = (string) $domain;
                if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                    $url = 'http://' . $url;
                }
                if (!str_ends_with(rtrim($url, '/'), '/send-message')) {
                    $url = rtrim($url, '/') . '/send-message';
                }

                $payload = [
                    'api_key' => $token,
                    'sender' => $org?->wa_gateway_sender,
                    'number' => $target,
                    'message' => $message,
                ];

                $response = Http::timeout(30)
                    ->asJson()
                    ->post($url, $payload);

                Log::info('WhatsappService: Local Gateway response log', [
                    'http' => $response->status(),
                    'response' => $response->body(),
                    'url' => $url,
                    'payload' => array_merge($payload, ['api_key' => '***']), // Hide api key in normal logs
                ]);
            } else {
                // Fonnte API Gateway
                $response = Http::withHeaders([
                    'Authorization' => $token,
                ])->post('https://api.fonnte.com/send', [
                    'target' => $target,
                    'message' => $message,
                ]);
            }

            if ($response->successful()) {
                Log::info("WHATSAPP SUCCESS to {$target} (Gateway: {$type})");
                return true;
            }

            Log::error("WHATSAPP FAIL to {$target} (Gateway: {$type}): " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("WHATSAPP EXCEPTION to {$target} (Gateway: {$type}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Format phone number to international format (62 for Indonesia)
     */
    private static function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        return $phone;
    }
}
