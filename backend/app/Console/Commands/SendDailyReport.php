<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Branch;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\EcommerceOrder;

#[Signature('app:send-daily-report')]
#[Description('Send daily sales report to Telegram per branch')]
class SendDailyReport extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $token = env('TELEGRAM_BOT_TOKEN');
        
        if (!$token) {
            $this->error('TELEGRAM_BOT_TOKEN is not set in .env');
            return;
        }

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        
        $message = "📊 *Laporan Penjualan Harian*\n";
        $message .= "Tanggal: " . $today->format('d M Y') . "\n\n";

        $grandTotalSales = 0;
        $grandTotalProfit = 0;
        $grandTotalTransactions = 0;
        
        foreach ($branches as $branch) {
            // 1. OFFLINE (POS)
            $transactions = Transaction::where('branch_id', $branch->id)
                ->whereDate('transaction_date', $today)
                ->where('is_voided', false)
                ->with('items.product') // Eager load for cogs calculation
                ->get();

            $offlineTransactions = $transactions->count();
            $offlineSales = $transactions->sum('final_amount');
            $offlineProfit = $transactions->sum(function($trx) {
                return $trx->gross_profit;
            });

            // 2. ONLINE (E-Commerce)
            $ecommerceOrders = EcommerceOrder::where('branch_id', $branch->id)
                ->whereDate('created_at', $today)
                ->where('status', 'COMPLETED')
                ->with('items.product')
                ->get();

            $onlineTransactions = $ecommerceOrders->count();
            $onlineSales = $ecommerceOrders->sum('total_amount');
            $onlineProfit = $ecommerceOrders->sum(function($order) {
                $cogs = $order->items->sum(function($item) {
                    return $item->product ? ($item->product->cost_price * $item->quantity) : 0;
                });
                return $order->total_amount - $cogs;
            });
            
            $totalTransactions = $offlineTransactions + $onlineTransactions;
            
            if ($totalTransactions == 0) {
                continue; // Skip branches with no transactions today
            }
            
            $totalSales = $offlineSales + $onlineSales;
            $totalProfit = $offlineProfit + $onlineProfit;

            $grandTotalSales += $totalSales;
            $grandTotalProfit += $totalProfit;
            $grandTotalTransactions += $totalTransactions;

            $message .= "🏪 *Cabang: {$branch->name}*\n";
            $message .= "🛒 *Offline (POS)*\n";
            $message .= "  - Trx: " . number_format($offlineTransactions, 0, ',', '.') . "\n";
            $message .= "  - Omset: Rp " . number_format($offlineSales, 0, ',', '.') . "\n";
            $message .= "  - Laba Kotor: Rp " . number_format($offlineProfit, 0, ',', '.') . "\n";
            
            if ($onlineTransactions > 0) {
                $message .= "🌐 *Online (E-Commerce)*\n";
                $message .= "  - Trx: " . number_format($onlineTransactions, 0, ',', '.') . "\n";
                $message .= "  - Omset: Rp " . number_format($onlineSales, 0, ',', '.') . "\n";
                $message .= "  - Laba Kotor: Rp " . number_format($onlineProfit, 0, ',', '.') . "\n";
            }
            
            $message .= "---------------------------\n";
        }

        if ($grandTotalTransactions == 0) {
            $this->info('No transactions today. No report sent.');
            return;
        }

        $message .= "\n📈 *TOTAL KESELURUHAN*\n";
        $message .= "Total Trx: " . number_format($grandTotalTransactions, 0, ',', '.') . "\n";
        $message .= "Total Omset: Rp " . number_format($grandTotalSales, 0, ',', '.') . "\n";
        $message .= "Total Laba Kotor: Rp " . number_format($grandTotalProfit, 0, ',', '.') . "\n";

        // Fetch users to send report to
        // Assuming Super Admin, Manager or Owner gets the report
        $recipients = User::whereNotNull('telegram_chat_id')
            ->whereIn('role', ['superadmin', 'super_admin', 'super-admin', 'owner', 'manager'])
            ->get();

        $organization = \App\Models\Organization::first();
        $groupId = $organization ? $organization->telegram_group_daily_report : null;

        if ($recipients->isEmpty() && !$groupId) {
            $this->warn('No eligible users or group found with a configured Telegram Chat ID.');
            return;
        }

        $successCount = 0;
        
        // Send to Group if configured
        if ($groupId) {
            try {
                $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $groupId,
                    'text' => $message,
                    'parse_mode' => 'Markdown'
                ]);

                if ($response->successful()) {
                    $successCount++;
                } else {
                    Log::error("Failed to send daily report to group {$groupId}: " . $response->body());
                }
            } catch (\Exception $e) {
                Log::error("Exception while sending daily report to group: " . $e->getMessage());
            }
        }

        foreach ($recipients as $user) {
            try {
                $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $user->telegram_chat_id,
                    'text' => $message,
                    'parse_mode' => 'Markdown'
                ]);

                if ($response->successful()) {
                    $successCount++;
                } else {
                    Log::error("Failed to send daily report to user {$user->id}: " . $response->body());
                }
            } catch (\Exception $e) {
                Log::error("Exception while sending daily report to user {$user->id}: " . $e->getMessage());
            }
        }
        
        $this->info("Daily report sent successfully to {$successCount} recipients.");
    }
}
