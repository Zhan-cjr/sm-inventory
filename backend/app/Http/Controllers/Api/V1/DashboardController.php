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
        // If user has no branch_id (like ADMIN) or is an admin, allow them to specify branch_id
        $branchId = $user->branch_id;
        $isAdmin = in_array(strtoupper($user->role), ['ADMIN', 'SUPER_ADMIN', 'SUPERADMIN', 'MANAGER']);
        if (!$branchId || $isAdmin) {
            $branchId = $request->query('branch_id') ?: $branchId;
        }

        if (!$branchId) {
            $branchId = \App\Models\Branch::first()?->id;
        }

        if (!$branchId) {
            return response()->json(['message' => 'Silakan pilih cabang terlebih dahulu.'], 400);
        }
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();

        // 1. Total Penjualan & Transaksi Hari Ini
        $todayAgg = Transaction::where('branch_id', $branchId)
            ->whereBetween('transaction_date', [$todayStart, $todayEnd])
            ->where('is_voided', false)
            ->selectRaw('COALESCE(SUM(final_amount), 0) as today_sales, COUNT(id) as today_count')
            ->first();

        $todaySales = (float) ($todayAgg->today_sales ?? 0);
        $todayCount = (int) ($todayAgg->today_count ?? 0);

        // 2. Hitung COGS Hari Ini dari transaksi yang aktif
        $todayTransactions = Transaction::where('branch_id', $branchId)
            ->whereBetween('transaction_date', [$todayStart, $todayEnd])
            ->where('is_voided', false)
            ->with(['items.product'])
            ->get();
        $todayCogs = (float) $todayTransactions->sum('cogs');

        $grossProfit = $todaySales - $todayCogs;
        $profitMargin = $todaySales > 0 ? ($grossProfit / $todaySales) * 100 : 0;

        // 2. Produk Terlaris (Bulan Ini)
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $topProducts = TransactionItem::join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->where('transactions.branch_id', $branchId)
            ->where('transactions.is_voided', false)
            ->whereBetween('transactions.transaction_date', [$monthStart, $monthEnd])
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

        $sevenDaysAgo = Carbon::today()->subDays(6)->startOfDay();
        $weeklySales = Transaction::where('branch_id', $branchId)
            ->where('is_voided', false)
            ->where('transaction_date', '>=', $sevenDaysAgo)
            ->select(DB::raw('DATE(transaction_date) as date'), DB::raw('SUM(final_amount) as total'))
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        foreach ($last7Days as &$day) {
            if (isset($weeklySales[$day['date']])) {
                $day['sales'] = (int) $weeklySales[$day['date']]->total;
            }
        }

        // 4. Comparative Sales (Versus) - Using index-friendly date boundaries
        $yesterdayStart = Carbon::yesterday()->startOfDay();
        $yesterdayEnd = Carbon::yesterday()->endOfDay();
        $yesterdaySales = Transaction::where('branch_id', $branchId)
            ->whereBetween('transaction_date', [$yesterdayStart, $yesterdayEnd])
            ->where('is_voided', false)
            ->sum('final_amount');

        $sameDayLastWeekStart = Carbon::today()->subWeek()->startOfDay();
        $sameDayLastWeekEnd = Carbon::today()->subWeek()->endOfDay();
        $sameDayLastWeekSales = Transaction::where('branch_id', $branchId)
            ->whereBetween('transaction_date', [$sameDayLastWeekStart, $sameDayLastWeekEnd])
            ->where('is_voided', false)
            ->sum('final_amount');

        $sameDateLastMonthStart = Carbon::today()->subMonthNoOverflow()->startOfDay();
        $sameDateLastMonthEnd = Carbon::today()->subMonthNoOverflow()->endOfDay();
        $sameDateLastMonthSales = Transaction::where('branch_id', $branchId)
            ->whereBetween('transaction_date', [$sameDateLastMonthStart, $sameDateLastMonthEnd])
            ->where('is_voided', false)
            ->sum('final_amount');

        // 6. Stock Health (Low Stock Count)
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
                'yesterdaySales' => (int) $yesterdaySales,
                'sameDayLastWeekSales' => (int) $sameDayLastWeekSales,
                'sameDateLastMonthSales' => (int) $sameDateLastMonthSales,
            ],
            'topProducts' => $topProducts,
            'weeklyChart' => $last7Days
        ]);
    }

    public function lowStockProducts(Request $request)
    {
        $user = $request->user();
        $branchId = $user->branch_id;
        $isAdmin = in_array(strtoupper($user->role), ['ADMIN', 'SUPER_ADMIN', 'SUPERADMIN', 'MANAGER']);
        if (!$branchId || $isAdmin) {
            $branchId = $request->query('branch_id') ?: $branchId;
        }

        if (!$branchId) {
            $branchId = \App\Models\Branch::first()?->id;
        }

        if (!$branchId) {
            return response()->json([]);
        }

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
