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

        // 1. Total Penjualan & Transaksi Hari Ini
        $todayTransactions = Transaction::where('branch_id', $branchId)
            ->whereDate('transaction_date', $today)
            ->get();

        $todaySales = $todayTransactions->sum('total_amount');
        $todayCount = $todayTransactions->count();

        // 2. Total Cost (COGS) & Gross Profit Hari Ini
        // We join with stocks to get the cost_price
        $todayCogs = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('stocks', function($join) use ($branchId) {
                $join->on('transaction_items.product_id', '=', 'stocks.product_id')
                     ->where('stocks.branch_id', '=', $branchId);
            })
            ->where('transactions.branch_id', $branchId)
            ->whereDate('transactions.transaction_date', $today)
            ->sum(DB::raw('transaction_items.quantity * stocks.cost_price'));

        $grossProfit = $todaySales - $todayCogs;
        $profitMargin = $todaySales > 0 ? ($grossProfit / $todaySales) * 100 : 0;

        // 3. Produk Terlaris (Bulan Ini)
        $topProducts = TransactionItem::join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->where('transactions.branch_id', $branchId)
            ->whereMonth('transactions.transaction_date', Carbon::now()->month)
            ->select('products.name', DB::raw('SUM(transaction_items.quantity) as total_sold'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        // 4. Grafik Transaksi (7 Hari Terakhir)
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

        // 5. Stock Health (Low Stock Count)
        $lowStockCount = DB::table('stocks')
            ->where('branch_id', $branchId)
            ->whereColumn('quantity_on_hand', '<=', 'min_qty')
            ->count();

        return response()->json([
            'summary' => [
                'todaySales' => (int) $todaySales,
                'todayTransactions' => $todayCount,
                'todayCogs' => (int) $todayCogs,
                'grossProfit' => (int) $grossProfit,
                'profitMargin' => round($profitMargin, 2),
                'lowStockCount' => $lowStockCount,
            ],
            'topProducts' => $topProducts,
            'weeklyChart' => $last7Days
        ]);
    }

    public function lowStockProducts(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'MANAGER') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $branchId = $user->branch_id;

        $products = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->where('stocks.branch_id', $branchId)
            ->whereColumn('stocks.quantity_on_hand', '<=', 'stocks.min_qty')
            ->select('products.name', 'stocks.quantity_on_hand', 'stocks.min_qty', 'products.unit_of_measure as unit')
            ->orderBy('stocks.quantity_on_hand', 'asc')
            ->get();

        return response()->json($products);
    }
}
