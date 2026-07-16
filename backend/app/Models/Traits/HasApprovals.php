<?php

namespace App\Models\Traits;

use App\Models\Approval;

trait HasApprovals
{
    public function approvals()
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function requestApproval($notes = null, $level = 1)
    {
        $this->update(['status' => 'pending_approval']);
        $approval = $this->approvals()->create([
            'status' => 'pending',
            'notes' => $notes,
            'level' => $level,
        ]);
        
        $this->sendTelegramNotification($approval);
        
        return $approval;
    }

    protected function sendTelegramNotification($approval = null)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        if (!$token) return;

        $branchId = $this->branch_id ?? null;
        
        $modelClass = class_basename($this);
        
        $requiredAuth = 'APPROVE_STOCK_ADJUSTMENT';
        if ($modelClass === 'PurchaseOrder') {
            $requiredAuth = 'APPROVE_PO';
        } elseif ($modelClass === 'WarehouseCheck') {
            $requiredAuth = 'APPROVE_GR_OVERQUANTITY';
        }
        
        $supervisors = \App\Models\User::whereNotNull('telegram_chat_id')
            ->whereJsonContains('custom_authorizations', $requiredAuth)
            ->get();

        $branchName = $this->branch ? $this->branch->name : 'Pusat';
        
        $type = 'Koreksi Stok';
        if ($modelClass === 'PurchaseOrder') {
            $type = 'Purchase Order';
        } elseif ($modelClass === 'WarehouseCheck') {
            $type = 'Pengecekan Gudang (Kelebihan Barang)';
        }
        
        $docNumber = $this->po_number ?? $this->adjustment_number ?? '-';
        if ($modelClass === 'WarehouseCheck' && $this->purchaseOrder) {
            $docNumber = 'Cek PO: ' . $this->purchaseOrder->po_number;
        }
        
        $creatorName = 'System';
        if ($modelClass === 'PurchaseOrder') {
            $creatorName = $this->creator?->name ?? 'System';
        } elseif ($modelClass === 'WarehouseCheck') {
            $creatorName = $this->checker?->name ?? 'System';
        } else {
            $creatorName = $this->recorder?->name ?? 'System';
        }
        
        $message = "📄 <b>Permintaan Persetujuan Dokumen</b>\n\n";
        $message .= "<b>Tipe:</b> {$type}\n";
        $message .= "<b>No Dokumen:</b> {$docNumber}\n";
        $message .= "<b>Cabang:</b> {$branchName}\n";
        $message .= "<b>Dibuat oleh:</b> {$creatorName}\n";

        $replyMarkup = null;
        if ($approval) {
            $replyMarkup = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '✅ Setuju', 'callback_data' => "action:approve:{$approval->id}"],
                        ['text' => '❌ Tolak', 'callback_data' => "action:reject:{$approval->id}"]
                    ],
                    [
                        ['text' => '🔍 Tampilkan Rincian (Review)', 'callback_data' => "action:review:{$approval->id}"]
                    ]
                ]
            ]);
        }

        $sentMessages = [];

        // 1. Send to individuals (Supervisors)
        foreach ($supervisors as $spv) {
            $payload = [
                'chat_id' => $spv->telegram_chat_id,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ];
            if ($replyMarkup) {
                $payload['reply_markup'] = $replyMarkup;
            }
            $response = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/sendMessage", $payload);
            if ($response->successful() && $approval) {
                $result = $response->json('result');
                if (isset($result['message_id']) && isset($result['chat']['id'])) {
                    $sentMessages[] = [
                        'chat_id' => $result['chat']['id'],
                        'message_id' => $result['message_id']
                    ];
                }
            }
        }

        // 2. Send to Specific Telegram Group
        $organizationId = $this->organization_id ?? $this->branch?->organization_id ?? \App\Models\Organization::first()?->id;
        if ($organizationId) {
            $org = \App\Models\Organization::find($organizationId);
            if ($org) {
                $groupId = null;
                if ($modelClass === 'PurchaseOrder') {
                    $groupId = $org->telegram_group_po_approval;
                } elseif ($modelClass === 'WarehouseCheck') {
                    $groupId = $org->telegram_group_warehouse_check;
                } else {
                    $groupId = $org->telegram_group_stock_correction;
                }

                if ($groupId) {
                    $payload = [
                        'chat_id' => $groupId,
                        'text' => "👥 <b>Pemberitahuan Grup</b>\n\n" . $message,
                        'parse_mode' => 'HTML',
                        'disable_web_page_preview' => true,
                    ];
                    if ($replyMarkup) {
                        $payload['reply_markup'] = $replyMarkup;
                    }
                    $response = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/sendMessage", $payload);
                    if ($response->successful() && $approval) {
                        $result = $response->json('result');
                        if (isset($result['message_id']) && isset($result['chat']['id'])) {
                            $sentMessages[] = [
                                'chat_id' => $result['chat']['id'],
                                'message_id' => $result['message_id']
                            ];
                        }
                    }
                }
            }
        }

        if ($approval && !empty($sentMessages)) {
            $approval->update(['telegram_messages' => $sentMessages]);
        }
    }

    public function approve($userId, $notes = null)
    {
        $approvals = $this->approvals()->where('status', 'pending')->get();
        foreach ($approvals as $approval) {
            $approval->update([
                'status' => 'approved',
                'user_id' => $userId,
                'notes' => $notes,
            ]);
            $this->updateTelegramMessageStatus($approval, 'approve', $userId);
        }
        
        $this->update(['status' => 'approved']);

        if (method_exists($this, 'onApproved')) {
            $this->onApproved();
        }
    }

    public function reject($userId, $notes = null)
    {
        $approvals = $this->approvals()->where('status', 'pending')->get();
        foreach ($approvals as $approval) {
            $approval->update([
                'status' => 'rejected',
                'user_id' => $userId,
                'notes' => $notes,
            ]);
            $this->updateTelegramMessageStatus($approval, 'reject', $userId);
        }
        
        $this->update(['status' => 'rejected']);
    }

    public function cancelPendingApprovals()
    {
        $approvals = $this->approvals()->where('status', 'pending')->get();
        foreach ($approvals as $approval) {
            $approval->update([
                'status' => 'cancelled',
                'notes' => 'Otomatis dibatalkan karena dokumen telah diubah.',
            ]);
            $this->updateTelegramMessageStatus($approval, 'cancel');
        }
    }

    protected function updateTelegramMessageStatus($approval, $action, $userId = null)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        if (!$token) return;

        $messages = $approval->telegram_messages;
        if (empty($messages) || !is_array($messages)) return;

        $userName = 'Sistem';
        if ($userId) {
            $user = \App\Models\User::find($userId);
            if ($user) $userName = $user->name;
        }

        $statusText = "";
        if ($action === 'approve') {
            $statusText = "✅ <b>Disetujui</b> oleh {$userName} (via Sistem)";
        } elseif ($action === 'reject') {
            $statusText = "❌ <b>Ditolak</b> oleh {$userName} (via Sistem)";
        } elseif ($action === 'cancel') {
            $statusText = "🚫 <b>Persetujuan dibatalkan</b> karena dokumen telah diubah.";
        }

        foreach ($messages as $msg) {
            $chatId = $msg['chat_id'] ?? null;
            $messageId = $msg['message_id'] ?? null;
            
            if ($chatId && $messageId) {
                // Fetch the original message text if possible, but Telegram API doesn't allow fetching a single message directly by bot.
                // We will just append the status to a generic text, or better, we can't easily fetch it.
                // Wait! If we use editMessageReplyMarkup with empty markup, it just removes buttons and keeps original text!
                // Let's do that first to remove buttons, then we don't necessarily have to change the text.
                // Actually, editMessageText requires 'text', which would overwrite the original message.
                // To just remove the buttons, we can use `editMessageReplyMarkup`!
                // But the user requested to SEE who approved it.
                // If we must append text, we would need to store the original text too.
                // Or we can just build the original text again?
                // Yes, we can rebuild the original text exactly like in `sendTelegramNotification`!
                
                $branchName = $this->branch ? $this->branch->name : 'Pusat';
                $modelClass = class_basename($this);
                $type = 'Koreksi Stok';
                if ($modelClass === 'PurchaseOrder') $type = 'Purchase Order';
                elseif ($modelClass === 'WarehouseCheck') $type = 'Pengecekan Gudang';
                
                $docNumber = $this->po_number ?? $this->adjustment_number ?? '-';
                
                $creatorName = 'System';
                if ($modelClass === 'PurchaseOrder') $creatorName = $this->creator?->name ?? 'System';
                elseif ($modelClass === 'WarehouseCheck') $creatorName = $this->checker?->name ?? 'System';
                else $creatorName = $this->recorder?->name ?? 'System';

                $baseText = "📄 <b>Permintaan Persetujuan Dokumen</b>\n\n";
                $baseText .= "<b>Tipe:</b> {$type}\n";
                $baseText .= "<b>No Dokumen:</b> {$docNumber}\n";
                $baseText .= "<b>Cabang:</b> {$branchName}\n";
                $baseText .= "<b>Dibuat oleh:</b> {$creatorName}\n\n";
                
                $finalText = $baseText . $statusText;

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
