<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BIDashboardController extends Controller
{
    /**
     * Get transaction heatmap data for the last 30 days.
     */
    public function heatmap(Request $request)
    {
        $branchId = $request->input('branch_id'); // Optional filter

        $query = Transaction::where('transaction_type', 'SALE')
            ->where('is_voided', false)
            ->where('transaction_date', '>=', Carbon::now()->subDays(30));

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // Get count of transactions grouped by day of week (1=Monday) and hour (0-23)
        // Adjust syntax based on database (MySQL vs PostgreSQL vs SQLite)
        // Here we use Carbon on the collection to ensure cross-db compatibility since it's only ~500-1000 rows for 30 days.
        $transactions = $query->select('transaction_date')->get();

        $heatmapRaw = [];
        // Initialize 7 days x 12 hours (8-19)
        $days = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        for ($d = 0; $d < 7; $d++) {
            for ($h = 8; $h <= 19; $h++) {
                $heatmapRaw[$d][$h] = 0;
            }
        }

        foreach ($transactions as $trx) {
            $date = Carbon::parse($trx->transaction_date);
            // ISO day of week: 1 = Mon, 7 = Sun. Array index is 0-6.
            $dayIndex = $date->isIsoWeekday() ? $date->dayOfWeekIso - 1 : $date->dayOfWeekIso - 1; 
            $hour = $date->hour;

            if ($hour >= 8 && $hour <= 19) {
                if (isset($heatmapRaw[$dayIndex][$hour])) {
                    $heatmapRaw[$dayIndex][$hour]++;
                }
            }
        }

        $formattedData = [];
        foreach ($days as $dIdx => $dayName) {
            for ($h = 8; $h <= 19; $h++) {
                $formattedData[] = [
                    'day' => $dayName,
                    'hour' => "{$h}:00",
                    'dayIndex' => $dIdx,
                    'hourIndex' => $h,
                    'value' => $heatmapRaw[$dIdx][$h],
                ];
            }
        }

        return response()->json($formattedData);
    }

    /**
     * Get apriori (frequently bought together) rules from the last 30 days.
     */
    public function apriori(Request $request)
    {
        $branchId = $request->input('branch_id'); // Optional filter

        // We want to find pairs of items frequently bought together.
        // 1. Get all transaction IDs from the last 30 days
        $query = Transaction::where('transaction_type', 'SALE')
            ->where('is_voided', false)
            ->where('transaction_date', '>=', Carbon::now()->subDays(30));

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $transactionIds = $query->pluck('id');

        if ($transactionIds->isEmpty()) {
            return response()->json([]);
        }

        // 2. Fetch all items in those transactions, group by transaction_id
        $itemsQuery = TransactionItem::with('product:id,name')
            ->whereIn('transaction_id', $transactionIds)
            ->select('transaction_id', 'product_id')
            ->get();

        $groupedItems = $itemsQuery->groupBy('transaction_id');

        // 3. Simple Apriori: Count frequencies of item pairs
        $pairCounts = [];
        $itemCounts = []; // Support for individual items

        foreach ($groupedItems as $trxId => $items) {
            // Only care about transactions with more than 1 item
            if ($items->count() < 2) continue;

            $productIds = $items->pluck('product_id')->unique()->values()->all();
            
            // Count individual items
            foreach ($productIds as $pid) {
                if (!isset($itemCounts[$pid])) $itemCounts[$pid] = 0;
                $itemCounts[$pid]++;
            }

            // Generate pairs
            for ($i = 0; $i < count($productIds); $i++) {
                for ($j = $i + 1; $j < count($productIds); $j++) {
                    $p1 = $productIds[$i];
                    $p2 = $productIds[$j];
                    
                    // Enforce order so (A,B) and (B,A) map to the same key
                    if ($p1 > $p2) {
                        $temp = $p1;
                        $p1 = $p2;
                        $p2 = $temp;
                    }

                    $key = "{$p1}|{$p2}";
                    if (!isset($pairCounts[$key])) {
                        $pairCounts[$key] = 0;
                    }
                    $pairCounts[$key]++;
                }
            }
        }

        // 4. Calculate Confidence and sort by occurrences
        arsort($pairCounts);
        
        // Take top 4 pairs
        $topPairs = array_slice($pairCounts, 0, 4, true);
        
        $rules = [];
        // Need product names mapping
        $allProductIds = collect(array_keys($itemCounts));
        $productNames = \App\Models\Product::whereIn('id', $allProductIds)->pluck('name', 'id');

        foreach ($topPairs as $key => $count) {
            list($p1, $p2) = explode('|', $key);
            
            // Confidence = count(A & B) / count(A)
            // Example: count of (p1 & p2) / count(p1)
            $confidence = round(($count / $itemCounts[$p1]) * 100);
            
            $rules[] = [
                'item1' => $productNames[$p1] ?? 'Produk ' . substr($p1, 0, 5),
                'item2' => $productNames[$p2] ?? 'Produk ' . substr($p2, 0, 5),
                'confidence' => $confidence . '%',
                'occurrences' => $count
            ];
        }

        return response()->json($rules);
    }
}
