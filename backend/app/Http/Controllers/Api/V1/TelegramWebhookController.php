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

            if (isset($update['callback_query'])) {
                return $this->handleCallbackQuery($update['callback_query']);
            }

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

                $aiUrl = env('AI_SERVICE_URL', 'http://ai-service:8001');
                $aiResponse = Http::timeout(60)->post($aiUrl . '/api/v1/ai/ask', [
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

    private function answerCallbackQuery($token, $callbackQueryId, $text = null, $showAlert = false)
    {
        if (!$token) return;
        $payload = ['callback_query_id' => $callbackQueryId];
        if ($text) {
            $payload['text'] = $text;
            $payload['show_alert'] = $showAlert;
        }
        Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", $payload);
    }

    private function editMessageText($token, $chatId, $messageId, $text, $replyMarkup = null)
    {
        if (!$token) return;
        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];
        if ($replyMarkup) {
            $payload['reply_markup'] = $replyMarkup;
        }
        Http::post("https://api.telegram.org/bot{$token}/editMessageText", $payload);
    }

    private function handleCallbackQuery($callbackQuery)
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $callbackQueryId = $callbackQuery['id'];
        $data = $callbackQuery['data'] ?? '';
        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];
        $chatType = $callbackQuery['message']['chat']['type'];
        $telegramUserId = $callbackQuery['from']['id'];
        $telegramName = $callbackQuery['from']['first_name'] ?? 'User';
        if (!empty($callbackQuery['from']['last_name'])) {
            $telegramName .= ' ' . $callbackQuery['from']['last_name'];
        }

        if (!str_starts_with($data, 'action:') && !str_starts_with($data, 'pos_auth:')) {
            return response()->json(['status' => 'ok']);
        }

        $parts = explode(':', $data);
        if (count($parts) < 3) return response()->json(['status' => 'ok']);
        
        $action = $parts[1];
        $id = $parts[2];

        if (str_starts_with($data, 'pos_auth:')) {
            return $this->handlePosAuthCallback($callbackQuery, $callbackQueryId, $action, $id, $botToken, $chatId, $messageId, $telegramUserId, $telegramName);
        }

        $approval = \App\Models\Approval::with(['approvable'])->find($id);
        
        if (!$approval || $approval->status !== 'pending') {
            $this->answerCallbackQuery($botToken, $callbackQueryId, "Permintaan persetujuan tidak ditemukan atau sudah diproses.", true);
            $this->editMessageText($botToken, $chatId, $messageId, $callbackQuery['message']['text'] . "\n\n<i>Dokumen sudah diproses atau tidak ditemukan.</i>");
            return response()->json(['status' => 'ok']);
        }

        $model = $approval->approvable;
        $modelClass = get_class($model);
        
        $requiredAuth = 'APPROVE_STOCK_ADJUSTMENT';
        if ($modelClass === \App\Models\PurchaseOrder::class) {
            $requiredAuth = 'APPROVE_PO';
        } elseif ($modelClass === \App\Models\WarehouseCheck::class) {
            $requiredAuth = 'APPROVE_GR_OVERQUANTITY';
        }

        // Authorization Check
        $user = User::where('telegram_chat_id', (string) $telegramUserId)->first();
        $isAuthorized = false;
        $approverId = null;
        $approverName = $telegramName;

        if ($user && in_array($requiredAuth, $user->custom_authorizations ?? [])) {
            $isAuthorized = true;
            $approverId = $user->id;
            $approverName = $user->name;
        } else {
            // Check if it's an official group
            if (in_array($chatType, ['group', 'supergroup'])) {
                $isKnownGroup = \App\Models\Organization::where('telegram_group_po_approval', (string) $chatId)
                    ->orWhere('telegram_group_stock_correction', (string) $chatId)
                    ->orWhere('telegram_group_warehouse_check', (string) $chatId)
                    ->exists();
                
                if ($isKnownGroup) {
                    $isAuthorized = true;
                    if ($user) {
                        $approverId = $user->id;
                        $approverName = $user->name;
                    }
                }
            }
        }

        if (!$isAuthorized) {
            $this->answerCallbackQuery($botToken, $callbackQueryId, "Maaf, Anda tidak memiliki akses untuk menyetujui dokumen ini.", true);
            return response()->json(['status' => 'unauthorized']);
        }

        // Handle Actions
        if ($action === 'review') {
            if ($modelClass === \App\Models\PurchaseOrder::class) {
                $model->load(['items.product', 'supplier']);
                
                // Filter items: only those exceeding suggested qty
                $overItems = $model->items->filter(function($item) {
                    return (float)$item->quantity_ordered > (float)$item->quantity_suggested;
                });

                $text = $callbackQuery['message']['text'] . "\n\n<b>🔍 Rincian Item (Over Order):</b>\n";
                
                if ($overItems->isEmpty()) {
                    $text .= "<i>Semua item (" . $model->items->count() . ") sesuai dengan saran order.</i>";
                } else {
                    $count = 0;
                    foreach ($overItems as $item) {
                        if ($count >= 15) {
                            $text .= "<i>...dan " . ($overItems->count() - 15) . " item lainnya.</i>\n";
                            break;
                        }
                        $text .= "- " . ($item->product->name ?? 'Unknown') . " | Qty: " . (float)$item->quantity_ordered . " (Saran: " . (float)$item->quantity_suggested . ")\n";
                        $count++;
                    }
                }

                $replyMarkup = json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => '✅ Setuju', 'callback_data' => "action:approve:{$approval->id}"],
                            ['text' => '❌ Tolak', 'callback_data' => "action:reject:{$approval->id}"]
                        ]
                    ]
                ]);

                $this->editMessageText($botToken, $chatId, $messageId, $text, $replyMarkup);
                $this->answerCallbackQuery($botToken, $callbackQueryId);
                return response()->json(['status' => 'ok']);
            }
            
            // For Stock Adjustment / Warehouse Check Review
            $this->answerCallbackQuery($botToken, $callbackQueryId, "Review di Telegram saat ini hanya mendukung Purchase Order.", true);
            return response()->json(['status' => 'ok']);
        }

        if ($action === 'approve' || $action === 'reject') {
            $notes = "Disetujui dari Telegram oleh {$approverName}";
            if ($action === 'reject') {
                $notes = "Ditolak dari Telegram oleh {$approverName}";
                $model->reject($approverId, $notes);
                $statusText = "❌ <b>Ditolak</b> oleh {$approverName}";
            } else {
                $model->approve($approverId, $notes);
                $statusText = "✅ <b>Disetujui</b> oleh {$approverName}";
            }

            $newText = $callbackQuery['message']['text'] . "\n\n" . $statusText;
            $this->editMessageText($botToken, $chatId, $messageId, $newText); // No reply_markup means buttons are removed
            $this->answerCallbackQuery($botToken, $callbackQueryId, "Berhasil memproses dokumen!");
            return response()->json(['status' => 'ok']);
        }

        return response()->json(['status' => 'ok']);
    }

    private function handlePosAuthCallback($callbackQuery, $callbackQueryId, $action, $authRequestId, $botToken, $chatId, $messageId, $telegramUserId, $telegramName)
    {
        $authRequest = \App\Models\AuthorizationRequest::with(['cashier'])->find($authRequestId);
        
        if (!$authRequest || $authRequest->status !== 'PENDING') {
            $this->answerCallbackQuery($botToken, $callbackQueryId, "Permintaan otorisasi tidak ditemukan atau sudah diproses.", true);
            $this->editMessageText($botToken, $chatId, $messageId, $callbackQuery['message']['text'] . "\n\n<i>Otorisasi sudah diproses atau tidak ditemukan.</i>");
            return response()->json(['status' => 'ok']);
        }

        // Authorization Check
        $user = User::where('telegram_chat_id', (string) $telegramUserId)->first();
        $isAuthorized = false;

        if ($user && is_array($user->pos_authorizations) && in_array($authRequest->action, $user->pos_authorizations)) {
            // Also check branch authorization
            if ($user->branch_id === null || $user->branch_id === $authRequest->branch_id) {
                $isAuthorized = true;
            }
        }

        if (!$isAuthorized) {
            $this->answerCallbackQuery($botToken, $callbackQueryId, "Maaf, Anda tidak memiliki izin otorisasi untuk aksi ini di cabang tersebut.", true);
            return response()->json(['status' => 'unauthorized']);
        }

        if ($action === 'review') {
            $text = $callbackQuery['message']['text'] . "\n\n<b>🔍 Rincian Permintaan:</b>\n";
            
            if (!empty($authRequest->details)) {
                foreach ($authRequest->details as $key => $val) {
                    // Prettify keys
                    $prettyKey = ucwords(str_replace('_', ' ', $key));
                    if (is_numeric($val)) {
                        if ($key === 'discount_amount' || $key === 'amount' || $key === 'total') {
                            $val = "Rp " . number_format($val, 0, ',', '.');
                        }
                    }
                    $text .= "- {$prettyKey}: {$val}\n";
                }
            } else {
                $text .= "<i>Tidak ada rincian tambahan.</i>";
            }

            $replyMarkup = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '✅ Setuju', 'callback_data' => "pos_auth:approve:{$authRequest->id}"],
                        ['text' => '❌ Tolak', 'callback_data' => "pos_auth:reject:{$authRequest->id}"]
                    ]
                ]
            ]);

            $this->editMessageText($botToken, $chatId, $messageId, $text, $replyMarkup);
            $this->answerCallbackQuery($botToken, $callbackQueryId);
            return response()->json(['status' => 'ok']);
        }

        if ($action === 'approve' || $action === 'reject') {
            $status = $action === 'approve' ? 'APPROVED' : 'REJECTED';
            
            $authRequest->update([
                'status' => $status,
                'supervisor_id' => $user->id
            ]);

            $statusText = $action === 'approve' ? "✅ <b>Disetujui</b> oleh {$user->name} (Telegram)" : "❌ <b>Ditolak</b> oleh {$user->name} (Telegram)";
            $newText = $callbackQuery['message']['text'] . "\n\n" . $statusText;
            
            $this->editMessageText($botToken, $chatId, $messageId, $newText);
            $this->answerCallbackQuery($botToken, $callbackQueryId, "Otorisasi berhasil diproses!");
            return response()->json(['status' => 'ok']);
        }

        return response()->json(['status' => 'ok']);
    }
}
