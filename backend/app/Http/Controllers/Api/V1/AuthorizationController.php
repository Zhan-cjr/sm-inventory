<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuthorizationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class AuthorizationController extends Controller
{
    public function requestAuthorization(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'action' => 'required|string',
            'details' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $authRequest = AuthorizationRequest::create([
            'organization_id' => $user->organization_id,
            'branch_id' => $user->branch_id,
            'cashier_id' => $user->id,
            'action' => $request->action,
            'details' => $request->details,
            'status' => 'PENDING',
            'expires_at' => Carbon::now()->addMinutes(10)
        ]);

        // Send Telegram Notification
        $token = env('TELEGRAM_BOT_TOKEN');
        if ($token) {
            $supervisors = \App\Models\User::whereNotNull('telegram_chat_id')
                ->where(function($q) use ($user) {
                    $q->where('branch_id', $user->branch_id)
                      ->orWhereNull('branch_id');
                })
                ->whereJsonContains('pos_authorizations', $request->action)
                ->get();

            $branchName = $user->branch ? $user->branch->name : 'Pusat';
            $actionLabel = str_replace('_', ' ', strtoupper($request->action));
            
            $baseUrl = env('FRONTEND_URL', request()->getSchemeAndHttpHost());
            $link = rtrim($baseUrl, '/') . "/mobile/auth";
            
            $message = "🔔 <b>Permintaan Otorisasi Baru</b>\n\n";
            $message .= "<b>Cabang:</b> {$branchName}\n";
            $message .= "<b>Kasir:</b> {$user->name}\n";
            $message .= "<b>Tindakan:</b> {$actionLabel}\n\n";
            
            // Add some details directly to the message if available
            if (!empty($request->details)) {
                if (isset($request->details['product_name'])) {
                    $message .= "<b>Produk:</b> {$request->details['product_name']}\n";
                }
                if (isset($request->details['discount_amount'])) {
                    $message .= "<b>Diskon:</b> Rp " . number_format($request->details['discount_amount'], 0, ',', '.') . "\n";
                }
                if (isset($request->details['amount'])) {
                    $message .= "<b>Nominal:</b> Rp " . number_format($request->details['amount'], 0, ',', '.') . "\n";
                }
                $message .= "\n";
            }
            
            $message .= "Buka PWA: " . $link;

            $replyMarkup = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '✅ Setuju', 'callback_data' => "pos_auth:approve:{$authRequest->id}"],
                        ['text' => '❌ Tolak', 'callback_data' => "pos_auth:reject:{$authRequest->id}"]
                    ],
                    [
                        ['text' => '🔍 Rincian', 'callback_data' => "pos_auth:review:{$authRequest->id}"]
                    ]
                ]
            ]);

            $sentMessages = [];
            foreach ($supervisors as $spv) {
                $response = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $spv->telegram_chat_id,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                    'reply_markup' => $replyMarkup,
                ]);
                
                if ($response->successful()) {
                    $result = $response->json('result');
                    if (isset($result['message_id']) && isset($result['chat']['id'])) {
                        $sentMessages[] = [
                            'chat_id' => $result['chat']['id'],
                            'message_id' => $result['message_id']
                        ];
                    }
                }
            }
            
            if (!empty($sentMessages)) {
                $authRequest->update(['telegram_messages' => $sentMessages]);
            }
        }

        return response()->json(['data' => $authRequest], 201);
    }

    public function checkStatus($id)
    {
        $authRequest = AuthorizationRequest::find($id);
        if (!$authRequest) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($authRequest->status === 'PENDING' && $authRequest->expires_at < Carbon::now()) {
            $authRequest->update(['status' => 'REJECTED']); // Auto-reject if expired
        }

        return response()->json(['data' => $authRequest]);
    }

    public function getPendingRequests(Request $request)
    {
        $user = $request->user();
        
        $hasPosAuth = is_array($user->pos_authorizations) && count($user->pos_authorizations) > 0;

        // Ensure user has pos_authorizations to view pending requests
        if (!$hasPosAuth) {
            return response()->json(['error' => 'Unauthorized: Tidak memiliki hak akses otorisasi POS'], 403);
        }

        $query = AuthorizationRequest::with(['cashier:id,name', 'branch:id,name'])
            ->where('status', 'PENDING')
            ->whereIn('action', $user->pos_authorizations)
            ->where('expires_at', '>', Carbon::now());

        $branchId = $user->branch_id;
        
        // If user has no branch assigned (Pusat), they can filter by branch
        if (!$branchId) {
            $branchId = $request->query('branch_id');
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $requests = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $requests]);
    }

    public function approveRequest(Request $request, $id)
    {
        $user = $request->user();
        $authRequest = AuthorizationRequest::find($id);
        
        if (!$authRequest) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($user->branch_id !== null && $authRequest->branch_id !== $user->branch_id) {
            return response()->json(['error' => 'Akses ditolak: Anda tidak dapat menyetujui otorisasi di luar cabang Anda'], 403);
        }

        if (!$user->pos_authorizations || !in_array($authRequest->action, $user->pos_authorizations)) {
            return response()->json(['error' => 'Akses ditolak: Anda tidak memiliki izin otorisasi untuk aksi ini'], 403);
        }

        if ($authRequest->status !== 'PENDING') {
            return response()->json(['error' => 'Request already processed'], 400);
        }

        $authRequest->update([
            'status' => 'APPROVED',
            'supervisor_id' => $user->id
        ]);
        
        $this->updateTelegramMessageStatus($authRequest, 'approve', $user->name);

        return response()->json(['data' => $authRequest]);
    }

    public function rejectRequest(Request $request, $id)
    {
        $user = $request->user();
        $authRequest = AuthorizationRequest::find($id);
        
        if (!$authRequest) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($user->branch_id !== null && $authRequest->branch_id !== $user->branch_id) {
            return response()->json(['error' => 'Akses ditolak: Anda tidak dapat menolak otorisasi di luar cabang Anda'], 403);
        }

        if (!$user->pos_authorizations || !in_array($authRequest->action, $user->pos_authorizations)) {
            return response()->json(['error' => 'Akses ditolak: Anda tidak memiliki izin otorisasi untuk aksi ini'], 403);
        }

        if ($authRequest->status !== 'PENDING') {
            return response()->json(['error' => 'Request already processed'], 400);
        }

        $authRequest->update([
            'status' => 'REJECTED',
            'supervisor_id' => $user->id
        ]);
        
        $this->updateTelegramMessageStatus($authRequest, 'reject', $user->name);

        return response()->json(['data' => $authRequest]);
    }

    protected function updateTelegramMessageStatus($authRequest, $action, $userName)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        if (!$token) return;

        $messages = $authRequest->telegram_messages;
        if (empty($messages) || !is_array($messages)) return;

        $statusText = "";
        if ($action === 'approve') {
            $statusText = "✅ <b>Disetujui</b> oleh {$userName} (via Sistem)";
        } elseif ($action === 'reject') {
            $statusText = "❌ <b>Ditolak</b> oleh {$userName} (via Sistem)";
        }

        $branchName = $authRequest->branch ? $authRequest->branch->name : 'Pusat';
        $cashierName = $authRequest->cashier ? $authRequest->cashier->name : 'Kasir';
        $actionLabel = str_replace('_', ' ', strtoupper($authRequest->action));

        $baseText = "🔔 <b>Permintaan Otorisasi Baru</b>\n\n";
        $baseText .= "<b>Cabang:</b> {$branchName}\n";
        $baseText .= "<b>Kasir:</b> {$cashierName}\n";
        $baseText .= "<b>Tindakan:</b> {$actionLabel}\n\n";
        
        if (!empty($authRequest->details)) {
            if (isset($authRequest->details['product_name'])) {
                $baseText .= "<b>Produk:</b> {$authRequest->details['product_name']}\n";
            }
            if (isset($authRequest->details['discount_amount'])) {
                $baseText .= "<b>Diskon:</b> Rp " . number_format($authRequest->details['discount_amount'], 0, ',', '.') . "\n";
            }
            if (isset($authRequest->details['amount'])) {
                $baseText .= "<b>Nominal:</b> Rp " . number_format($authRequest->details['amount'], 0, ',', '.') . "\n";
            }
            $baseText .= "\n";
        }

        $finalText = $baseText . $statusText;

        foreach ($messages as $msg) {
            $chatId = $msg['chat_id'] ?? null;
            $messageId = $msg['message_id'] ?? null;
            
            if ($chatId && $messageId) {
                \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/editMessageText", [
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'text' => $finalText,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);
            }
        }
    }
}
