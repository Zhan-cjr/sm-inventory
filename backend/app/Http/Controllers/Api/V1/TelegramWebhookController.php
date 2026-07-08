<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {
            $update = $request->all();

            // We only care about text messages
            if (!isset($update['message']['text'])) {
                return response()->json(['status' => 'ok']); // Acknowledge non-text messages
            }

            $messageText = trim($update['message']['text']);
            $chatId = $update['message']['chat']['id'];
            $telegramUsername = $update['message']['from']['username'] ?? 'Unknown';

            // 1. Verify Authorization
            $user = User::where('telegram_chat_id', (string) $chatId)->first();
            $branchId = null;
            $botToken = env('TELEGRAM_BOT_TOKEN');

            if (!$user) {
                // Cek apakah chat berasal dari Grup yang terdaftar di Perusahaan
                $organization = \App\Models\Organization::where('telegram_group_po_approval', (string) $chatId)
                    ->orWhere('telegram_group_stock_correction', (string) $chatId)
                    ->orWhere('telegram_group_warehouse_check', (string) $chatId)
                    ->first();

                if (!$organization) {
                    if ($chatId < 0) {
                        return response()->json(['status' => 'unauthorized_group_ignored']);
                    }
                    $this->sendMessage($botToken, $chatId, "⚠️ Akses ditolak. ID Telegram Anda ({$chatId}) tidak terdaftar di sistem Toserba Selamat.");
                    return response()->json(['status' => 'unauthorized']);
                }

                // Jika di dalam Grup, jangan balas setiap chat manusia.
                // Hanya balas jika pesan diawali dengan "/bot" atau kata "bot "
                $isCommandForBot = str_starts_with(strtolower($messageText), '/bot') || str_starts_with(strtolower($messageText), 'bot ');
                if (!$isCommandForBot) {
                    return response()->json(['status' => 'group_chat_ignored']);
                }

                // Bersihkan kata awalan agar tidak membingungkan AI
                $messageText = trim(str_ireplace(['/bot', 'bot '], '', $messageText));
                if (empty($messageText)) {
                    $this->sendMessage($botToken, $chatId, "Halo Grup! Ada yang bisa saya bantu? Ketik pertanyaan Anda setelah kata '/bot'.");
                    return response()->json(['status' => 'empty_command']);
                }

            } else {
                $branchId = $user->branch_id;
            }

            // 2. Tampilkan indikator "Typing..."
            $this->sendChatAction($botToken, $chatId, 'typing');

            // 3. Teruskan pesan ke AI Service
            try {
                $cacheKey = 'telegram_chat_history_' . $chatId;
                $chatHistory = \Illuminate\Support\Facades\Cache::get($cacheKey, []);
                
                // Tambahkan pesan user saat ini ke history sementara untuk dikirim sebagai konteks
                $chatHistoryForAi = $chatHistory;
                $chatHistoryForAi[] = ['role' => 'user', 'content' => $messageText];

                $aiResponse = Http::timeout(60)->post('http://localhost:8001/api/v1/ai/ask', [
                    'question' => $messageText,
                    'branch_id' => $branchId,
                    'chat_history' => $chatHistoryForAi, // Kirim history
                ]);

                if ($aiResponse->successful()) {
                    $reply = $aiResponse->json('response');
                    
                    // Simpan percakapan ke cache (maksimal 10 percakapan terakhir)
                    $chatHistory[] = ['role' => 'user', 'content' => $messageText];
                    $chatHistory[] = ['role' => 'assistant', 'content' => $reply];
                    
                    if (count($chatHistory) > 10) {
                        $chatHistory = array_slice($chatHistory, -10);
                    }
                    \Illuminate\Support\Facades\Cache::put($cacheKey, $chatHistory, now()->addMinutes(60));
                    
                    // Format response jika ada data tabel
                    $data = $aiResponse->json('data');
                    if (!empty($data) && is_array($data)) {
                        $reply .= "\n\n📊 *Data Pendukung:*\n";
                        foreach (array_slice($data, 0, 5) as $row) {
                            $reply .= "• ";
                            $rowItems = [];
                            foreach ($row as $key => $val) {
                                $rowItems[] = "{$val}";
                            }
                            $reply .= implode(" - ", $rowItems) . "\n";
                        }
                        if (count($data) > 5) {
                            $reply .= "_(...dan " . (count($data) - 5) . " data lainnya)_";
                        }
                    }

                } else {
                    $reply = "❌ Maaf, AI Service sedang mengalami gangguan atau membalas dengan error.";
                    Log::error("AI Service Error", ['response' => $aiResponse->body()]);
                }
            } catch (\Exception $e) {
                $reply = "❌ Maaf, tidak dapat terhubung ke AI Service (Server Offline).";
                Log::error("AI Service Connection Error", ['error' => $e->getMessage()]);
            }

            // 4. Kirim balasan ke Telegram
            $this->sendMessage($botToken, $chatId, $reply);

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            Log::error("Telegram Webhook Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function sendMessage($token, $chatId, $text)
    {
        if (!$token) return;

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);
    }

    private function sendChatAction($token, $chatId, $action)
    {
        if (!$token) return;

        Http::post("https://api.telegram.org/bot{$token}/sendChatAction", [
            'chat_id' => $chatId,
            'action' => $action,
        ]);
    }
}
