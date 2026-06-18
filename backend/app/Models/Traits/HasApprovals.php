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
        
        $this->sendTelegramNotification();
        
        return $approval;
    }

    protected function sendTelegramNotification()
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        if (!$token) return;

        $branchId = $this->branch_id ?? null;
        
        $requiredAuth = class_basename($this) === 'PurchaseOrder' ? 'APPROVE_PO' : 'APPROVE_STOCK_ADJUSTMENT';
        
        $supervisors = \App\Models\User::whereNotNull('telegram_chat_id')
            ->where(function($q) use ($branchId) {
                if ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
                } else {
                    $q->whereNull('branch_id');
                }
            })
            ->whereJsonContains('custom_authorizations', $requiredAuth)
            ->get();

        $branchName = $this->branch ? $this->branch->name : 'Pusat';
        $type = class_basename($this) === 'PurchaseOrder' ? 'Purchase Order' : 'Koreksi Stok';
        $docNumber = $this->po_number ?? $this->adjustment_number ?? '-';
        
        $creatorName = 'System';
        if (class_basename($this) === 'PurchaseOrder') {
            $creatorName = $this->creator?->name ?? 'System';
        } else {
            $creatorName = $this->recorder?->name ?? 'System';
        }

        $baseUrl = env('FRONTEND_URL', request()->getSchemeAndHttpHost());
        $link = rtrim($baseUrl, '/') . "/mobile/auth";
        
        $message = "📄 <b>Permintaan Persetujuan Dokumen</b>\n\n";
        $message .= "<b>Tipe:</b> {$type}\n";
        $message .= "<b>No Dokumen:</b> {$docNumber}\n";
        $message .= "<b>Cabang:</b> {$branchName}\n";
        $message .= "<b>Dibuat oleh:</b> {$creatorName}\n\n";
        $message .= "Tautan: " . $link;

        foreach ($supervisors as $spv) {
            \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $spv->telegram_chat_id,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);
        }
    }

    public function approve($userId, $notes = null)
    {
        $this->approvals()->where('status', 'pending')->update([
            'status' => 'approved',
            'user_id' => $userId,
            'notes' => $notes,
        ]);
        
        $this->update(['status' => 'approved']);

        if (method_exists($this, 'onApproved')) {
            $this->onApproved();
        }
    }

    public function reject($userId, $notes = null)
    {
        $this->approvals()->where('status', 'pending')->update([
            'status' => 'rejected',
            'user_id' => $userId,
            'notes' => $notes,
        ]);
        
        $this->update(['status' => 'rejected']);
    }
}
