<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\TransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Api\V1\AuthController;

Route::prefix('v1')->group(function () {
    
    // Public Routes
    Route::post('/login', [AuthController::class, 'login']);



    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        
        // POS Authorization
        Route::get('/pos-authorizers', [\App\Http\Controllers\Api\V1\PosAuthController::class, 'getAuthorizers']);
        Route::post('/authorize-action', [\App\Http\Controllers\Api\V1\PosAuthController::class, 'authorizeAction']);
        
        // Transaction retrieval for POS Return
        Route::get('/transactions/receipt/{receipt}', [\App\Http\Controllers\Api\V1\TransactionController::class, 'getTransactionByReceipt']);

        Route::get('/user', function (Request $request) {
            $user = $request->user();
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'branch_id' => $user->branch_id,
                    'branch_name' => $user->branch?->name,
                    'organization_id' => $user->organization_id,
                    'organization_name' => $user->organization?->name,
                ]
            ]);
        });

        // Offline-first Sync Endpoint
        Route::post('/transactions/batch-sync', [SyncController::class, 'batchSync']);
        
        // Standard Transactions
        Route::post('/transactions', [TransactionController::class, 'store']);

        // Get Products for POS Terminal (Filtered by Branch & Branch-specific pricing)
        Route::get('/products', function (Request $request) {
            $user = $request->user();
            $branchId = $user->branch_id;
            
            if (!$branchId) {
                return response()->json([]);
            }

            $products = \App\Models\Product::query()
                ->join('stocks', 'products.id', '=', 'stocks.product_id')
                ->where('stocks.branch_id', $branchId)
                ->where('products.is_active', true)
                ->select([
                    'products.*',
                    'stocks.cost_price as branch_cost_price',
                    'stocks.selling_price as branch_selling_price',
                    'stocks.quantity_on_hand'
                ])
                ->get()
                ->map(function ($product) {
                    // Override with branch specific prices if set
                    if ($product->branch_selling_price !== null) {
                        $product->selling_price = $product->branch_selling_price;
                    }
                    if ($product->branch_cost_price !== null) {
                        $product->cost_price = $product->branch_cost_price;
                    }
                    return $product;
                });

            return response()->json($products);
        });

        // Get Active Promotions
        Route::get('/promotions', function (Request $request) {
            $now = now();
            return response()->json(
                \App\Models\Promotion::where('is_active', true)
                    ->where('valid_from', '<=', $now)
                    ->where('valid_until', '>=', $now)
                    ->get()
            );
        });

        // Get Active Banks
        Route::get('/banks', function () {
            return response()->json(\App\Models\Bank::where('is_active', true)->get());
        });
        
        // Get Customers for POS
        Route::get('/customers', function () {
            return response()->json(\App\Models\Customer::where('is_active', true)->get());
        });

        // Get Active Services for POS (Jasa/Layanan)
        Route::get('/services', function (Request $request) {
            $orgId = $request->query('organization_id');
            $query = \App\Models\Service::where('is_active', true);
            if ($orgId) {
                $query->where('organization_id', $orgId);
            }
            return response()->json($query->orderBy('name')->get());
        });

        // Get POS Settings
        Route::get('/pos-settings', function () {
            return response()->json(\App\Models\PosSetting::where('is_active', true)->get());
        });

        // Get Branches
        Route::get('/branches', function (\Illuminate\Http\Request $request) {
            $user = $request->user();
            if ($user->branch_id) {
                return response()->json(\App\Models\Branch::where('id', $user->branch_id)->get());
            }
            return response()->json(\App\Models\Branch::all());
        });

        // Get Terminals
        Route::get('/terminals', function (\Illuminate\Http\Request $request) {
            $branchId = $request->query('branch_id');
            $query = \App\Models\Terminal::with('branch')->where('is_active', true);
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
            return response()->json($query->get());
        });

        // Shift Management
        Route::get('/shifts/active', [\App\Http\Controllers\Api\V1\ShiftController::class, 'getActiveShift']);
        Route::post('/shifts/open', [\App\Http\Controllers\Api\V1\ShiftController::class, 'openShift']);
        Route::post('/shifts/close', [\App\Http\Controllers\Api\V1\ShiftController::class, 'closeShift']);

        // Manager Dashboard Metrics
        Route::get('/dashboard/metrics', [\App\Http\Controllers\Api\V1\DashboardController::class, 'metrics']);
        Route::get('/dashboard/low-stock', [\App\Http\Controllers\Api\V1\DashboardController::class, 'lowStockProducts']);

        // Get Server Time for Sync
        Route::get('/server-time', function () {
            $now = now();
            return response()->json([
                'timestamp' => $now->getPreciseTimestamp(3),
                'formatted' => $now->format('H:i:s'),
                'timezone' => config('app.timezone'),
                'wib_timestamp' => $now->timezone('Asia/Jakarta')->getPreciseTimestamp(3)
            ]);
        });
        // Get Suppliers
        Route::get('/suppliers', function () {
            return response()->json(\App\Models\Supplier::where('is_active', true)->orderBy('name')->get());
        });

        // Suggested Orders
        Route::get('/suggested-orders', [\App\Http\Controllers\Api\V1\SuggestedOrderController::class, 'index']);
        Route::post('/purchase-orders/create-from-suggestion', [\App\Http\Controllers\Api\V1\PurchaseOrderController::class, 'createFromSuggestion']);
        Route::post('/purchase-orders/create-bulk-from-suggestions', [\App\Http\Controllers\Api\V1\PurchaseOrderController::class, 'createBulkFromSuggestions']);
    });
});
