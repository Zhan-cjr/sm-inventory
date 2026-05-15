<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function metrics(Request $request)
    {
        $user = $request->user();
        // Ensure only MANAGER can access
        if ($user->role !== 'MANAGER') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $branchId = $user->branch_id;
        $today = Carbon::today();

        // 1. Total Penjualan Hari Ini
        $todaySales = Transaction::where('branch_id', $branchId)
            ->whereDate('transaction_date', $today)
            ->sum('total_amount');

        $todayCount = Transaction::where('branch_id', $branchId)
            ->whereDate('transaction_date', $today)
            ->count();

        // 2. Produk Terlaris (Bulan Ini)
        $topProducts = TransactionItem::join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->where('transactions.branch_id', $branchId)
            ->whereMonth('transactions.transaction_date', Carbon::now()->month)
            ->select('products.name', DB::raw('SUM(transaction_items.quantity) as total_sold'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        // 3. Grafik Transaksi (7 Hari Terakhir)
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $last7Days[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('D'),
                'sales' => 0,
            ];
        }

        $weeklySales = Transaction::where('branch_id', $branchId)
            ->whereDate('transaction_date', '>=', Carbon::today()->subDays(6))
            ->select(DB::raw('DATE(transaction_date) as date'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        foreach ($last7Days as &$day) {
            if (isset($weeklySales[$day['date']])) {
                $day['sales'] = (int) $weeklySales[$day['date']]->total;
            }
        }

        return response()->json([
            'summary' => [
                'todaySales' => $todaySales,
                'todayTransactions' => $todayCount,
            ],
            'topProducts' => $topProducts,
            'weeklyChart' => $last7Days
        ]);
    }
}
