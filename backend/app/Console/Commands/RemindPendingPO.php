<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\PurchaseOrder;
use App\Models\Organization;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

#[Signature('app:remind-pending-po')]
#[Description('Remind pending purchase orders that are more than 2 days old')]
class RemindPendingPO extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        if (!$token) {
            $this->error('TELEGRAM_BOT_TOKEN is not set in .env');
            return;
        }

        $twoDaysAgo = Carbon::now()->subDays(2);
        
        // Fetch POs that are pending approval for more than 2 days
        $pendingPOs = PurchaseOrder::where('status', 'pending_approval')
            ->where('updated_at', '<=', $twoDaysAgo)
            ->with(['branch', 'creator'])
            ->get();

        if ($pendingPOs->isEmpty()) {
            $this->info('No pending POs older than 2 days found.');
            return;
        }

        $organization = Organization::first();
        $groupId = $organization ? $organization->telegram_group_po_approval : null;
        
        if (!$groupId) {
            $this->warn('No telegram_group_po_approval configured in Organization. Falling back to individual supervisors.');
        }

        $supervisors = \App\Models\User::whereNotNull('telegram_chat_id')
            ->whereJsonContains('custom_authorizations', 'APPROVE_PO')
            ->get();

        if (!$groupId && $supervisors->isEmpty()) {
            $this->warn('No eligible users or group found to send PO reminders.');
            return;
        }

        $message = "⚠️ *PENGINGAT PERSETUJUAN PO* ⚠️\n\n";
        $message .= "Terdapat " . $pendingPOs->count() . " Purchase Order yang masih berstatus pending selama lebih dari 2 hari.\n\n";

        foreach ($pendingPOs as $po) {
            $branchName = $po->branch ? $po->branch->name : 'Pusat';
            $creatorName = $po->creator ? $po->creator->name : 'System';
            $poDate = $po->po_date ? $po->po_date->format('d M Y') : '-';
            
            $message .= "🛒 *PO:* {$po->po_number}\n";
            $message .= "Cabang: {$branchName}\n";
            $message .= "Dibuat: {$creatorName} ({$poDate})\n";
            $message .= "---------------------------\n";
        }

        $baseUrl = env('FRONTEND_URL', request()->getSchemeAndHttpHost());
        $link = rtrim($baseUrl, '/') . "/mobile/auth";
        $message .= "\nSegera proses persetujuan melalui sistem.\n";
        $message .= "Tautan: " . $link;

        $successCount = 0;

        // Send to Group if configured
        if ($groupId) {
            try {
                $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $groupId,
                    'text' => $message,
                    'parse_mode' => 'Markdown',
                    'disable_web_page_preview' => true,
                ]);

                if ($response->successful()) {
                    $successCount++;
                } else {
                    Log::error("Failed to send PO reminder to group {$groupId}: " . $response->body());
                }
            } catch (\Exception $e) {
                Log::error("Exception while sending PO reminder to group: " . $e->getMessage());
            }
        }

        // Send to individual supervisors if group is not configured, or in addition to group
        // If we want to only fallback, we could do `if (!$groupId)` here. 
        // Let's send to individuals if group is not set.
        if (!$groupId) {
            foreach ($supervisors as $spv) {
                try {
                    $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                        'chat_id' => $spv->telegram_chat_id,
                        'text' => $message,
                        'parse_mode' => 'Markdown',
                        'disable_web_page_preview' => true,
                    ]);

                    if ($response->successful()) {
                        $successCount++;
                    } else {
                        Log::error("Failed to send PO reminder to user {$spv->id}: " . $response->body());
                    }
                } catch (\Exception $e) {
                    Log::error("Exception while sending PO reminder to user {$spv->id}: " . $e->getMessage());
                }
            }
        }

        $this->info("PO Reminder sent successfully (Messages sent: {$successCount}).");
    }
}
