<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\TransactionController;

use App\Http\Controllers\Api\V1\EcommerceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PosDeviceController;
use App\Http\Controllers\Api\V1\AuthorizationController;
use App\Http\Controllers\Api\V1\StockOpnameApiController;

// E-Commerce Routes
Route::prefix('v1/ecommerce')->group(function () {
    Route::get('settings', [EcommerceController::class, 'getSettings']);
    Route::get('products', [EcommerceController::class, 'getProducts']);
    Route::get('check-price', [EcommerceController::class, 'checkPrice']);
    Route::get('nearest-branch', [EcommerceController::class, 'findNearestBranch']);
    Route::get('branches', [EcommerceController::class, 'getBranches']);
    Route::post('orders', [EcommerceController::class, 'createOrder']);
    Route::post('orders/{id}/refresh-payment', [EcommerceController::class, 'refreshPaymentToken']);
    Route::post('payment/notification', [EcommerceController::class, 'paymentNotification']);
    Route::post('members', [EcommerceController::class, 'registerMember']);
    Route::post('members/login', [EcommerceController::class, 'memberLogin']);
    Route::get('members/history', [EcommerceController::class, 'getMemberHistory']);
    Route::get('members/profile', [EcommerceController::class, 'getMemberProfile']);
    Route::post('members/forgot-password', [EcommerceController::class, 'forgotPassword']);
    Route::get('members/debug-otp', [EcommerceController::class, 'debugLastOtp']);
    Route::post('members/reset-password', [EcommerceController::class, 'resetPassword']);
    Route::put('customer/profile', [EcommerceController::class, 'updateMemberProfile']);
});

Route::prefix('v1')->group(function () {
    
    // Public Routes
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/devices/handshake', [PosDeviceController::class, 'handshake']);
    Route::post('/webhook/digiflazz', [\App\Http\Controllers\Api\DigiflazzWebhookController::class, 'handle']);



    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        
        // POS Authorization
        Route::get('/pos-authorizers', [\App\Http\Controllers\Api\V1\PosAuthController::class, 'getAuthorizers']);
        Route::post('/authorize-action', [\App\Http\Controllers\Api\V1\PosAuthController::class, 'authorizeAction']);
        
        // Remote Authorization
        Route::post('/authorizations', [AuthorizationController::class, 'requestAuthorization']);
        Route::get('/authorizations/pending', [AuthorizationController::class, 'getPendingRequests']);
        Route::get('/authorizations/{id}', [AuthorizationController::class, 'checkStatus']);
        Route::post('/authorizations/{id}/approve', [AuthorizationController::class, 'approveRequest']);
        Route::post('/authorizations/{id}/reject', [AuthorizationController::class, 'rejectRequest']);
        
        // Document Approvals (PO & Stock Adjustments)
        Route::get('/document-approvals/pending', [\App\Http\Controllers\Api\V1\DocumentApprovalController::class, 'pending']);
        Route::get('/document-approvals/{id}/details', [\App\Http\Controllers\Api\V1\DocumentApprovalController::class, 'details']);
        Route::post('/document-approvals/{id}/{action}', [\App\Http\Controllers\Api\V1\DocumentApprovalController::class, 'action']);
        
        // E-Commerce Management for Staff/Admin
        Route::get('/ecommerce/pending', [EcommerceController::class, 'getPendingOrders']);
        Route::post('/ecommerce/{id}/process', [EcommerceController::class, 'processOrder']);
        
        // Transaction retrieval for POS Return
        Route::get('/transactions/latest', [\App\Http\Controllers\Api\V1\TransactionController::class, 'getLatestTransaction']);
        Route::get('/transactions/receipt/{receipt}', [\App\Http\Controllers\Api\V1\TransactionController::class, 'getTransactionByReceipt']);
        Route::get('/transactions/ppob/today', [\App\Http\Controllers\Api\V1\TransactionController::class, 'getTodayPpobTransactions']);
        Route::post('/transactions/ppob/{ppobTransactionId}/check-status', [\App\Http\Controllers\Api\V1\TransactionController::class, 'checkPpobStatus']);

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
                    'branch_code' => $user->branch?->code,
                    'branch_address' => $user->branch?->address,
                    'organization_id' => $user->organization_id,
                    'organization_name' => $user->organization?->name,
                    'point_conversion_rate' => $user->organization?->point_conversion_rate ?? 1000,
                    'point_redemption_value' => $user->organization?->point_redemption_value ?? 1,
                    'minimum_points_to_redeem' => $user->organization?->minimum_points_to_redeem ?? 100,
                    'point_redemption_enabled' => (bool) ($user->organization?->point_redemption_enabled ?? true),
                    'allow_minus_stock' => (bool) ($user->organization?->allow_minus_stock ?? true),
                    'scale_barcode_enabled' => (bool) ($user->organization?->scale_barcode_enabled ?? false),
                    'scale_barcode_prefix' => $user->organization?->scale_barcode_prefix ?? '20',
                    'scale_barcode_item_code_length' => $user->organization?->scale_barcode_item_code_length ?? 5,
                    'scale_barcode_weight_length' => $user->organization?->scale_barcode_weight_length ?? 5,
                    'scale_barcode_weight_decimal_places' => $user->organization?->scale_barcode_weight_decimal_places ?? 3,
                    'pos_authorizations' => $user->pos_authorizations,
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
            $isAdmin = in_array(strtoupper($user->role), ['ADMIN', 'SUPER_ADMIN']);
            if (!$branchId || $isAdmin) {
                $branchId = $request->query('branch_id') ?: $branchId;
            }
            
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
                    'stocks.cost_price_tax as branch_cost_price_tax',
                    'stocks.selling_price as branch_selling_price',
                    'stocks.harga_jual_1 as branch_harga_jual_1',
                    'stocks.qty_min_gol_1 as branch_qty_min_gol_1',
                    'stocks.harga_jual_2 as branch_harga_jual_2',
                    'stocks.qty_min_gol_2 as branch_qty_min_gol_2',
                    'stocks.harga_jual_3 as branch_harga_jual_3',
                    'stocks.qty_min_gol_3 as branch_qty_min_gol_3',
                    'stocks.quantity_on_hand'
                ])
                ->get()
                ->map(function ($product) {
                    // Override with branch specific prices if set
                    if ($product->branch_selling_price !== null && $product->branch_selling_price > 0) {
                        $product->selling_price = $product->branch_selling_price;
                    }
                    if ($product->branch_cost_price !== null && $product->branch_cost_price > 0) {
                        $product->cost_price = $product->branch_cost_price;
                    }
                    if ($product->branch_harga_jual_1 !== null && $product->branch_harga_jual_1 > 0) {
                        $product->harga_jual_1 = $product->branch_harga_jual_1;
                        $product->qty_min_gol_1 = $product->branch_qty_min_gol_1;
                    }
                    if ($product->branch_harga_jual_2 !== null && $product->branch_harga_jual_2 > 0) {
                        $product->harga_jual_2 = $product->branch_harga_jual_2;
                        $product->qty_min_gol_2 = $product->branch_qty_min_gol_2;
                    }
                    if ($product->branch_harga_jual_3 !== null && $product->branch_harga_jual_3 > 0) {
                        $product->harga_jual_3 = $product->branch_harga_jual_3;
                        $product->qty_min_gol_3 = $product->branch_qty_min_gol_3;
                    }
                    
                    return $product;
                });

            return response()->json($products);
        });

        // Get Active Promotions
        Route::get('/promotions', function (Request $request) {
            $now = now();
            $user = $request->user();
            $branchId = $user->branch_id;
            
            return response()->json(
                \App\Models\Promotion::where('is_active', true)
                    ->where('valid_from', '<=', $now)
                    ->where('valid_until', '>=', $now)
                    ->where(function ($query) use ($branchId) {
                        $query->whereDoesntHave('branches');
                        if ($branchId) {
                            $query->orWhereHas('branches', function ($q) use ($branchId) {
                                $q->where('branches.id', $branchId);
                            });
                        }
                    })
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

        // Voucher Validation
        Route::get('/vouchers/validate', function (Request $request) {
            $code = $request->query('code');
            if (!$code) {
                return response()->json(['valid' => false, 'message' => 'Kode voucher tidak valid'], 400);
            }
            
            $voucher = \App\Models\Voucher::where('code', $code)->first();
            
            if (!$voucher) {
                return response()->json(['valid' => false, 'message' => 'Voucher tidak ditemukan'], 404);
            }
            
            if ($voucher->is_used) {
                return response()->json(['valid' => false, 'message' => 'Voucher sudah digunakan'], 400);
            }
            
            if ($voucher->valid_until && $voucher->valid_until < now()) {
                return response()->json(['valid' => false, 'message' => 'Voucher sudah kadaluarsa'], 400);
            }
            
            return response()->json([
                'valid' => true,
                'voucher' => [
                    'id' => $voucher->id,
                    'code' => $voucher->code,
                    'name' => $voucher->name,
                    'nominal_value' => $voucher->nominal_value,
                ]
            ]);
        });


        // Get POS Settings
        Route::get('/pos-settings', function (\Illuminate\Http\Request $request) {
            $user = $request->user();
            return response()->json(
                \App\Models\PosSetting::where('organization_id', $user->organization_id)
                    ->where('is_active', true)
                    ->get()
            );
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
        Route::post('/shifts/cash-movement', [\App\Http\Controllers\Api\V1\ShiftController::class, 'cashMovement']);

        // Mobile Stock Opname
        Route::get('/stock-opname/active', [StockOpnameApiController::class, 'getActiveSessions']);
        Route::post('/stock-opname/scan', [StockOpnameApiController::class, 'scanProduct']);
        Route::post('/stock-opname/lock-rack', [StockOpnameApiController::class, 'lockRack']);

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


use App\Http\Controllers\API\CompanyProfileController;

Route::prefix('company-profile')->group(function () {
    Route::get('/settings', [CompanyProfileController::class, 'settings']);
    Route::get('/branches', [CompanyProfileController::class, 'branches']);
    Route::get('/facilities', [CompanyProfileController::class, 'facilities']);
});
