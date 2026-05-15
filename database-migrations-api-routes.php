/**
 * LARAVEL 11 DATABASE MIGRATIONS & API ROUTES
 * Complete schema setup dan API endpoints definition
 */

// ============================================================================
// 1. MIGRATION: CREATE_ORGANIZATIONS_TABLE
// ============================================================================

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Str::uuid());
            $table->string('name');
            $table->string('code')->unique();
            $table->string('timezone')->default('Asia/Jakarta');
            $table->char('currency_code', 3)->default('IDR');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};

// ============================================================================
// 2. MIGRATION: CREATE_BRANCHES_TABLE
// ============================================================================

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Str::uuid());
            $table->uuid('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->string('name');
            $table->string('code');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->uuid('manager_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unique(['organization_id', 'code']);
            $table->timestamps();
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};

// ============================================================================
// 3. MIGRATION: CREATE_PRODUCTS_TABLE
// ============================================================================

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Str::uuid());
            $table->uuid('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->string('sku');
            $table->string('barcode')->unique()->nullable();
            $table->string('name');
            $table->uuid('category_id')->nullable();
            $table->uuid('supplier_id')->nullable();
            $table->decimal('cost_price', 12, 2);
            $table->decimal('selling_price', 12, 2);
            $table->string('unit_of_measure')->default('pcs');
            $table->integer('reorder_point')->default(10);
            $table->integer('reorder_qty')->default(50);
            $table->integer('lead_time_days')->default(5);
            $table->boolean('is_active')->default(true);
            $table->unique(['organization_id', 'sku']);
            $table->timestamps();
            
            // Indexes
            $table->index('organization_id');
            $table->index('sku');
            $table->index('barcode');
            $table->fullText('name', 'sku'); // For full-text search fallback
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

// ============================================================================
// 4. MIGRATION: CREATE_STOCKS_TABLE
// ============================================================================

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Str::uuid());
            $table->uuid('branch_id');
            $table->uuid('product_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->date('last_count_date')->nullable();
            $table->integer('min_qty')->default(10);
            $table->integer('max_qty')->default(500);
            $table->integer('version')->default(1); // Optimistic locking
            $table->unique(['branch_id', 'product_id']);
            $table->timestamps();
            
            // Indexes
            $table->index(['branch_id', 'product_id']);
            $table->index('quantity_on_hand');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};

// ============================================================================
// 5. MIGRATION: CREATE_TRANSACTIONS_TABLE
// ============================================================================

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Str::uuid());
            $table->uuid('organization_id');
            $table->uuid('branch_id');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->string('transaction_type'); // SALES, RETURN, ADJUSTMENT
            $table->dateTime('transaction_date')->useCurrent();
            $table->uuid('cashier_id');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('final_amount', 12, 2);
            $table->string('payment_method')->nullable(); // CASH, CARD, EWALLET
            $table->boolean('is_voided')->default(false);
            $table->text('void_reason')->nullable();
            $table->dateTime('void_date')->nullable();
            $table->uuid('voided_by')->nullable();
            $table->string('sync_status')->default('PENDING'); // PENDING, SYNCED, CONFLICT
            $table->string('local_transaction_id')->nullable(); // For offline POS
            $table->unique(['organization_id', 'local_transaction_id']);
            $table->timestamps();
            
            // Indexes
            $table->index(['branch_id', 'transaction_date']);
            $table->index('cashier_id');
            $table->index('sync_status');
            $table->index('is_voided');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

// ============================================================================
// 6. MIGRATION: CREATE_TRANSACTION_ITEMS_TABLE
// ============================================================================

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Str::uuid());
            $table->uuid('transaction_id');
            $table->uuid('product_id');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_per_item', 12, 2)->default(0);
            $table->timestamps();
            
            // Indexes
            $table->index('transaction_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};

// ============================================================================
// 7. MIGRATION: CREATE_PROMOTIONS_TABLE
// ============================================================================

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Str::uuid());
            $table->uuid('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->string('name');
            $table->string('promo_type'); // PERCENTAGE, FIXED, BUNDLING, TIERED, FLASH
            $table->decimal('discount_value', 10, 2);
            $table->decimal('min_purchase_amount', 12, 2)->nullable();
            $table->string('applicable_to'); // ALL, CATEGORY, PRODUCT, MEMBER
            $table->uuid('target_ids')->nullable(); // JSON array of target IDs
            $table->dateTime('valid_from');
            $table->dateTime('valid_until');
            $table->decimal('max_discount_per_transaction', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('promo_config')->nullable(); // Flexible schema
            $table->timestamps();
            
            // Indexes
            $table->index(['is_active', 'valid_from', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};

// ============================================================================
// 8. MIGRATION: CREATE_INVENTORY_LOGS_TABLE
// ============================================================================

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Str::uuid());
            $table->uuid('branch_id');
            $table->uuid('product_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->string('log_type'); // ADJUSTMENT, SALE, TRANSFER, COUNT, RETURN, VOID_REVERSAL
            $table->integer('quantity_change');
            $table->string('reason_code')->nullable();
            $table->string('reference_doc_type')->nullable();
            $table->uuid('reference_doc_id')->nullable();
            $table->uuid('recorded_by');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('created_at');
            $table->index(['product_id', 'branch_id']);
            $table->index('log_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
    }
};

// ============================================================================
// 9. API ROUTES DEFINITION (routes/api.php)
// ============================================================================

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\{
    TransactionController,
    InventoryController,
    PromoController,
    SuggestedOrderController,
    SyncController
};

Route::prefix('v1')->middleware('auth:api')->group(function () {
    
    // ===== TRANSACTIONS =====
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::post('/transactions/{id}/void', [TransactionController::class, 'void']);
    Route::get('/branches/{branchId}/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions/batch-sync', [SyncController::class, 'batchSync']);

    // ===== INVENTORY =====
    Route::get('/branches/{branchId}/stocks', [InventoryController::class, 'getStocks']);
    Route::post('/branches/{branchId}/stock-adjustment', [InventoryController::class, 'adjustStock']);
    Route::get('/branches/{branchId}/inventory-logs', [InventoryController::class, 'getAuditTrail']);

    // ===== PROMOTIONS =====
    Route::get('/promos/active', [PromoController::class, 'getActive']);
    Route::post('/promos', [PromoController::class, 'store']);
    Route::put('/promos/{id}', [PromoController::class, 'update']);
    Route::delete('/promos/{id}', [PromoController::class, 'destroy']);

    // ===== SUGGESTED ORDERS =====
    Route::get('/branches/{branchId}/suggested-orders', [SuggestedOrderController::class, 'index']);
    Route::post('/branches/{branchId}/suggested-orders/auto-create', [SuggestedOrderController::class, 'autoCreate']);

    // ===== SEARCH (Meilisearch) =====
    Route::get('/products/search', function (Request $request) {
        $search = $request->input('q', '');
        $products = Product::search($search)->take(50)->get();
        return response()->json($products);
    });
});

// ============================================================================
// 10. RESPONSE EXAMPLES
// ============================================================================

/**
 * POST /api/v1/transactions
 * 
 * Request:
 * {
 *   "items": [
 *     {
 *       "product_id": "uuid",
 *       "quantity": 2,
 *       "unit_price": 100000,
 *       "discount_per_item": 5000
 *     }
 *   ],
 *   "total_amount": 195000,
 *   "discount_amount": 10000,
 *   "final_amount": 185000,
 *   "payment_method": "CASH"
 * }
 * 
 * Response (201):
 * {
 *   "success": true,
 *   "message": "Transaction created successfully",
 *   "transaction": {
 *     "id": "uuid",
 *     "transaction_date": "2026-05-11T10:30:00Z",
 *     "total_amount": 195000,
 *     "final_amount": 185000
 *   }
 * }
 */

/**
 * POST /api/v1/transactions/batch-sync (Offline Sync)
 * 
 * Request:
 * {
 *   "transactions": [
 *     {
 *       "localId": "device-001-1234567890",
 *       "branchId": "uuid",
 *       "items": [...],
 *       "totalAmount": 500000,
 *       "discountAmount": 50000,
 *       "paymentMethod": "CASH",
 *       "checksum": "sha256_hash"
 *     }
 *   ],
 *   "deviceId": "pos-device-001",
 *   "branchId": "uuid"
 * }
 * 
 * Response (200):
 * {
 *   "success": true,
 *   "syncedIds": ["device-001-1234567890"],
 *   "conflicts": [],
 *   "latestStocks": [...],
 *   "latestPromos": [...]
 * }
 */

/**
 * GET /api/v1/branches/{branchId}/suggested-orders
 * 
 * Query Params:
 * - method: FORECASTING | MIN_MAX_BUFFER | BOTH
 * - minSuggestedQty: 10
 * 
 * Response (200):
 * {
 *   "branchId": "uuid",
 *   "method": "FORECASTING",
 *   "totalSuggestions": 45,
 *   "estimatedTotalCost": 25000000,
 *   "suggestions": [
 *     {
 *       "productId": "uuid",
 *       "sku": "SKU-001",
 *       "name": "Product Name",
 *       "currentQty": 5,
 *       "avgDailySales": 3.5,
 *       "forecastedDemand": 105,
 *       "reorderPoint": 22.5,
 *       "suggestedQty": 150,
 *       "estimatedCost": 750000,
 *       "reason": "Stok mencapai reorder point"
 *     }
 *   ]
 * }
 */

/**
 * POST /api/v1/transactions/{id}/void
 * 
 * Request:
 * {
 *   "reason": "Wrong transaction / Kesalahan input"
 * }
 * 
 * Response (200):
 * {
 *   "success": true,
 *   "message": "Transaction voided successfully"
 * }
 */

/**
 * GET /api/v1/promos/active
 * 
 * Response (200):
 * {
 *   "count": 15,
 *   "promos": [
 *     {
 *       "id": "uuid",
 *       "name": "Diskon Flash Sale",
 *       "promo_type": "FLASH_SALE",
 *       "discount_value": 40,
 *       "applicable_to": "PRODUCT",
 *       "target_ids": ["uuid1", "uuid2"],
 *       "valid_from": "2026-05-11T10:00:00Z",
 *       "valid_until": "2026-05-11T14:00:00Z",
 *       "promo_config": { ... }
 *     }
 *   ]
 * }
 */
