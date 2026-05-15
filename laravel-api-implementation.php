<?php

/**
 * LARAVEL 11 API IMPLEMENTATION
 * Production-ready controllers dan services untuk Hypermarket Inventory & POS
 */

// ============================================================================
// 1. TRANSACTION CONTROLLER
// ============================================================================

namespace App\Http\Controllers\Api\V1;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Stock;
use App\Models\InventoryLog;
use App\Models\Promotion;
use App\Http\Requests\CreateTransactionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TransactionController extends Controller
{
    /**
     * POST /api/v1/transactions
     * Create single transaction (online POS)
     */
    public function store(CreateTransactionRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();

        $transaction = null;

        try {
            DB::transaction(function () use ($validated, $user, &$transaction) {
                // Create transaction header
                $transaction = Transaction::create([
                    'organization_id' => $user->organization_id,
                    'branch_id' => $user->branch_id,
                    'transaction_type' => 'SALES',
                    'transaction_date' => now(),
                    'cashier_id' => $user->id,
                    'total_amount' => $validated['total_amount'] ?? 0,
                    'discount_amount' => $validated['discount_amount'] ?? 0,
                    'final_amount' => $validated['final_amount'] ?? 0,
                    'payment_method' => $validated['payment_method'],
                    'sync_status' => 'SYNCED',
                ]);

                // Process items dan deduct stock
                foreach ($validated['items'] as $item) {
                    // Create transaction item
                    TransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount_per_item' => $item['discount_per_item'] ?? 0,
                    ]);

                    // Deduct stock (with pessimistic locking)
                    $stock = Stock::lockForUpdate()
                        ->where('branch_id', $user->branch_id)
                        ->where('product_id', $item['product_id'])
                        ->firstOrFail();

                    $newQty = $stock->quantity_on_hand - $item['quantity'];
                    if ($newQty < 0) {
                        throw new \Exception("Insufficient stock for product {$item['product_id']}");
                    }

                    $stock->update([
                        'quantity_on_hand' => $newQty,
                        'version' => DB::raw('version + 1'),
                    ]);

                    // Log inventory change
                    InventoryLog::create([
                        'branch_id' => $user->branch_id,
                        'product_id' => $item['product_id'],
                        'log_type' => 'SALE',
                        'quantity_change' => -$item['quantity'],
                        'reference_doc_type' => 'TRANSACTION',
                        'reference_doc_id' => $transaction->id,
                        'recorded_by' => $user->id,
                    ]);
                }

                // Cache invalidation
                Cache::tags(['inventory', "branch:{$user->branch_id}"])->flush();
            });

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully',
                'transaction' => [
                    'id' => $transaction->id,
                    'transaction_date' => $transaction->transaction_date,
                    'total_amount' => $transaction->total_amount,
                    'final_amount' => $transaction->final_amount,
                ]
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Transaction creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * POST /api/v1/transactions/{id}/void
     * Void existing transaction (manager only)
     */
    public function void(string $id, Request $request)
    {
        $user = auth()->user();

        // Authorization
        if (!in_array($user->role, ['MANAGER', 'SUPERVISOR'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $transaction = Transaction::findOrFail($id);

        // Validation
        if ($transaction->is_voided) {
            return response()->json(['error' => 'Transaction already voided'], 400);
        }

        if ($transaction->transaction_date->diffInHours(now()) > 24) {
            return response()->json(['error' => 'Only transactions within 24h can be voided'], 400);
        }

        try {
            DB::transaction(function () use ($transaction, $user, $request) {
                // Reverse stock
                foreach ($transaction->items as $item) {
                    $stock = Stock::lockForUpdate()
                        ->where('branch_id', $transaction->branch_id)
                        ->where('product_id', $item->product_id)
                        ->firstOrFail();

                    $stock->update([
                        'quantity_on_hand' => $stock->quantity_on_hand + $item->quantity,
                        'version' => DB::raw('version + 1'),
                    ]);

                    // Log reversal
                    InventoryLog::create([
                        'branch_id' => $transaction->branch_id,
                        'product_id' => $item->product_id,
                        'log_type' => 'VOID_REVERSAL',
                        'quantity_change' => $item->quantity,
                        'reason_code' => 'VOID_TX',
                        'reference_doc_type' => 'TRANSACTION',
                        'reference_doc_id' => $transaction->id,
                        'recorded_by' => $user->id,
                        'notes' => $request->input('reason'),
                    ]);
                }

                // Mark voided
                $transaction->update([
                    'is_voided' => true,
                    'void_reason' => $request->input('reason'),
                    'void_date' => now(),
                    'voided_by' => $user->id,
                ]);

                // Cache invalidation
                Cache::tags(['inventory', "branch:{$transaction->branch_id}"])->flush();
            });

            return response()->json([
                'success' => true,
                'message' => 'Transaction voided successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/v1/branches/{branchId}/transactions?date_from=...&date_to=...
     * Get transaction history
     */
    public function index(string $branchId, Request $request)
    {
        $user = auth()->user();

        // Branch access control
        if ($user->branch_id && $user->branch_id !== $branchId) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $query = Transaction::where('branch_id', $branchId)
            ->with(['items.product', 'cashier'])
            ->orderBy('transaction_date', 'desc');

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('transaction_date', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->where('transaction_date', '<=', $request->input('date_to'));
        }

        // Exclude voided if param not set
        if (!$request->input('include_voided')) {
            $query->where('is_voided', false);
        }

        $transactions = $query->paginate(50);

        return response()->json([
            'data' => $transactions->items(),
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'total_pages' => $transactions->lastPage(),
            ]
        ]);
    }
}

// ============================================================================
// 2. INVENTORY CONTROLLER
// ============================================================================

namespace App\Http\Controllers\Api\V1;

use App\Models\Stock;
use App\Models\Product;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class InventoryController extends Controller
{
    /**
     * GET /api/v1/branches/{branchId}/stocks?search=...
     * Get stock with search & pagination
     */
    public function getStocks(string $branchId, Request $request)
    {
        $search = $request->input('search', '');
        $limit = $request->input('limit', 50);

        // Use Redis cache untuk reduce database load
        $cacheKey = "stocks:{$branchId}:search:{$search}:{$limit}";
        
        $stocks = Cache::remember($cacheKey, 300, function () use ($branchId, $search, $limit) {
            $query = Stock::where('branch_id', $branchId)
                ->with(['product']);

            if ($search) {
                // Search di products table
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('sku', 'ILIKE', "%{$search}%")
                        ->orWhere('barcode', 'ILIKE', "%{$search}%");
                });
            }

            return $query->paginate($limit);
        });

        return response()->json([
            'data' => $stocks->items(),
            'pagination' => [
                'total' => $stocks->total(),
                'per_page' => $stocks->perPage(),
                'current_page' => $stocks->currentPage(),
            ]
        ]);
    }

    /**
     * GET /api/v1/branches/{branchId}/inventory-logs
     * Get inventory audit trail
     */
    public function getAuditTrail(string $branchId, Request $request)
    {
        $logType = $request->input('type');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = InventoryLog::where('branch_id', $branchId)
            ->with('recordedBy')
            ->orderBy('created_at', 'desc');

        if ($logType) {
            $query->where('log_type', $logType);
        }

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        $logs = $query->paginate(100);

        return response()->json([
            'data' => $logs->items(),
            'pagination' => [
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
            ]
        ]);
    }

    /**
     * POST /api/v1/branches/{branchId}/stock-adjustment
     * Manual stock adjustment (stock opname)
     */
    public function adjustStock(string $branchId, Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['MANAGER', 'INVENTORY_STAFF'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'product_id' => 'required|uuid|exists:products,id',
            'new_quantity' => 'required|integer|min:0',
            'reason' => 'required|string',
        ]);

        $stock = Stock::where('branch_id', $branchId)
            ->where('product_id', $validated['product_id'])
            ->lockForUpdate()
            ->firstOrFail();

        $oldQty = $stock->quantity_on_hand;
        $newQty = $validated['new_quantity'];
        $diff = $newQty - $oldQty;

        $stock->update([
            'quantity_on_hand' => $newQty,
            'last_count_date' => now(),
            'version' => DB::raw('version + 1'),
        ]);

        // Log adjustment
        InventoryLog::create([
            'branch_id' => $branchId,
            'product_id' => $validated['product_id'],
            'log_type' => 'ADJUSTMENT',
            'quantity_change' => $diff,
            'reason_code' => 'MANUAL_ADJUSTMENT',
            'recorded_by' => $user->id,
            'notes' => $validated['reason'],
        ]);

        // Cache invalidation
        Cache::tags(['inventory', "branch:{$branchId}"])->flush();

        return response()->json([
            'success' => true,
            'message' => 'Stock adjusted',
            'old_quantity' => $oldQty,
            'new_quantity' => $newQty,
            'difference' => $diff,
        ]);
    }
}

// ============================================================================
// 3. PROMO CONTROLLER
// ============================================================================

namespace App\Http\Controllers\Api\V1;

use App\Models\Promotion;
use Illuminate\Support\Facades\Cache;

class PromoController extends Controller
{
    /**
     * GET /api/v1/promos/active
     * Get all active promotions (untuk POS, di-cache di Redis)
     */
    public function getActive()
    {
        $orgId = auth()->user()->organization_id;

        // Cache dalam Redis selama 5 menit
        $promos = Cache::remember(
            "promos:active:{$orgId}",
            300,
            function () use ($orgId) {
                return Promotion::where('organization_id', $orgId)
                    ->where('is_active', true)
                    ->where('valid_from', '<=', now())
                    ->where('valid_until', '>=', now())
                    ->get()
                    ->map(function ($promo) {
                        return [
                            'id' => $promo->id,
                            'name' => $promo->name,
                            'promo_type' => $promo->promo_type,
                            'discount_value' => $promo->discount_value,
                            'min_purchase_amount' => $promo->min_purchase_amount,
                            'applicable_to' => $promo->applicable_to,
                            'target_ids' => $promo->target_ids,
                            'max_discount_per_transaction' => $promo->max_discount_per_transaction,
                            'promo_config' => $promo->promo_config,
                            'valid_from' => $promo->valid_from,
                            'valid_until' => $promo->valid_until,
                        ];
                    });
            }
        );

        return response()->json([
            'count' => $promos->count(),
            'promos' => $promos
        ]);
    }

    /**
     * POST /api/v1/promos
     * Create new promotion (admin only)
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'ADMIN') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'promo_type' => 'required|in:PERCENTAGE,FIXED,BUNDLING,TIERED,MEMBER_BASED,FLASH_SALE',
            'discount_value' => 'required|numeric',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'applicable_to' => 'required|in:ALL,CATEGORY,PRODUCT,MEMBER',
            'target_ids' => 'nullable|array',
            'promo_config' => 'nullable|json',
        ]);

        $promo = Promotion::create([
            'organization_id' => $user->organization_id,
            ...$validated,
        ]);

        // Invalidate cache
        Cache::forget("promos:active:{$user->organization_id}");

        return response()->json([
            'success' => true,
            'promo' => $promo
        ], 201);
    }
}

// ============================================================================
// 4. SUGGESTED ORDER SERVICE
// ============================================================================

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SuggestedOrderService
{
    const LOOKBACK_DAYS = 90;
    const FORECAST_DAYS = 30;

    /**
     * Calculate suggested order berdasarkan forecasting
     */
    public function calculateForBranch(string $branchId, array $filters = []): array
    {
        $minSuggestedQty = $filters['minSuggestedQty'] ?? 0;
        
        $products = Product::where('is_active', true)
            ->limit(500)
            ->get();

        $suggestions = [];

        foreach ($products as $product) {
            $stock = Stock::where('branch_id', $branchId)
                ->where('product_id', $product->id)
                ->first();

            if (!$stock) continue;

            $suggestion = $this->calculateForProduct(
                $product,
                $stock,
                $branchId
            );

            if ($suggestion['suggestedQty'] >= $minSuggestedQty) {
                $suggestions[] = $suggestion;
            }
        }

        // Sort by suggested qty (desc)
        usort($suggestions, fn($a, $b) => $b['suggestedQty'] <=> $a['suggestedQty']);

        return array_slice($suggestions, 0, 100);
    }

    private function calculateForProduct(Product $product, Stock $stock, string $branchId): array
    {
        // Get sales history
        $salesHistory = $this->getSalesHistory($product->id, $branchId);
        
        if (empty($salesHistory)) {
            return [
                'productId' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'suggestedQty' => 0,
                'reason' => 'Tidak ada data penjualan'
            ];
        }

        // Calculate metrics
        $avgDailySales = array_sum($salesHistory) / count($salesHistory);
        $stdDev = $this->calculateStdDev($salesHistory);
        $safetyStock = 1.65 * $stdDev * sqrt($product->lead_time_days ?? 5);
        $reorderPoint = ($avgDailySales * ($product->lead_time_days ?? 5)) + $safetyStock;

        $suggestedQty = 0;
        $reason = '';

        if ($stock->quantity_on_hand <= $reorderPoint) {
            $eoq = $this->calculateEOQ($product, $avgDailySales);
            $forecastedDemand = $avgDailySales * self::FORECAST_DAYS;
            $suggestedQty = max($eoq, (int)ceil($forecastedDemand));
            $reason = 'Stok mencapai reorder point. Order untuk mencukupi forecast.';
        } else {
            $reason = 'Stok masih optimal.';
        }

        return [
            'productId' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'currentQty' => $stock->quantity_on_hand,
            'avgDailySales' => round($avgDailySales, 2),
            'reorderPoint' => round($reorderPoint, 2),
            'safetyStock' => round($safetyStock, 2),
            'suggestedQty' => $suggestedQty,
            'estimatedCost' => $suggestedQty * $product->cost_price,
            'reason' => $reason
        ];
    }

    private function getSalesHistory(string $productId, string $branchId): array
    {
        $startDate = Carbon::now()->subDays(self::LOOKBACK_DAYS);

        $sales = DB::table('transaction_items as ti')
            ->join('transactions as t', 'ti.transaction_id', '=', 't.id')
            ->where('ti.product_id', $productId)
            ->where('t.branch_id', $branchId)
            ->where('t.transaction_date', '>=', $startDate)
            ->where('t.is_voided', false)
            ->select(
                DB::raw('DATE(t.transaction_date) as sale_date'),
                DB::raw('SUM(ti.quantity) as daily_sales')
            )
            ->groupBy('sale_date')
            ->pluck('daily_sales')
            ->toArray();

        return $sales;
    }

    private function calculateEOQ(Product $product, float $avgDailySales): int
    {
        $annualDemand = 365 * $avgDailySales;
        $orderCost = 50000;
        $holdingCost = $product->cost_price * 0.25;

        if ($holdingCost === 0) return 50;

        $eoq = sqrt((2 * $annualDemand * $orderCost) / $holdingCost);
        return (int)ceil($eoq);
    }

    private function calculateStdDev(array $data): float
    {
        if (empty($data)) return 0;
        
        $mean = array_sum($data) / count($data);
        $variance = array_reduce(
            $data,
            fn($sum, $val) => $sum + pow($val - $mean, 2),
            0
        ) / count($data);

        return sqrt($variance);
    }
}

// ============================================================================
// 5. OFFLINE TRANSACTION SYNC
// ============================================================================

namespace App\Http\Controllers\Api\V1;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Stock;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    /**
     * POST /api/v1/transactions/batch-sync
     * Sinkronisasi batch transactions dari offline POS
     */
    public function batchSync(Request $request)
    {
        $validated = $request->validate([
            'transactions' => 'required|array|min:1',
            'deviceId' => 'required|string',
            'branchId' => 'required|uuid',
        ]);

        $user = auth()->user();
        $syncedIds = [];
        $conflicts = [];

        DB::transaction(function () use ($validated, $user, &$syncedIds, &$conflicts) {
            foreach ($validated['transactions'] as $txData) {
                try {
                    // Check if already synced
                    $existing = Transaction::where('local_transaction_id', $txData['localId'])
                        ->first();

                    if ($existing) {
                        $syncedIds[] = $txData['localId'];
                        continue;
                    }

                    // Validate & create
                    $tx = Transaction::create([
                        'organization_id' => $user->organization_id,
                        'branch_id' => $validated['branchId'],
                        'transaction_type' => 'SALES',
                        'transaction_date' => now(),
                        'cashier_id' => $user->id,
                        'total_amount' => $txData['totalAmount'],
                        'discount_amount' => $txData['discountAmount'] ?? 0,
                        'final_amount' => $txData['finalAmount'] ?? $txData['totalAmount'],
                        'payment_method' => $txData['paymentMethod'],
                        'sync_status' => 'SYNCED',
                        'local_transaction_id' => $txData['localId'],
                    ]);

                    // Process items
                    foreach ($txData['items'] as $item) {
                        TransactionItem::create([
                            'transaction_id' => $tx->id,
                            'product_id' => $item['productId'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unitPrice'],
                        ]);

                        // Deduct stock
                        Stock::where('branch_id', $validated['branchId'])
                            ->where('product_id', $item['productId'])
                            ->decrement('quantity_on_hand', $item['quantity']);

                        // Log
                        InventoryLog::create([
                            'branch_id' => $validated['branchId'],
                            'product_id' => $item['productId'],
                            'log_type' => 'SALE',
                            'quantity_change' => -$item['quantity'],
                            'reference_doc_type' => 'TRANSACTION',
                            'reference_doc_id' => $tx->id,
                            'recorded_by' => $user->id,
                        ]);
                    }

                    $syncedIds[] = $txData['localId'];

                } catch (\Exception $e) {
                    $conflicts[] = [
                        'localId' => $txData['localId'],
                        'error' => $e->getMessage()
                    ];
                }
            }
        });

        return response()->json([
            'success' => true,
            'syncedIds' => $syncedIds,
            'conflicts' => $conflicts,
            'syncedCount' => count($syncedIds),
            'conflictCount' => count($conflicts),
        ]);
    }
}
