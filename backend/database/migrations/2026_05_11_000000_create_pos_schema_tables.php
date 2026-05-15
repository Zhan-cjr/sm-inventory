<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Organizations
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('timezone')->default('Asia/Jakarta');
            $table->char('currency_code', 3)->default('IDR');
            $table->timestamps();
            $table->engine = 'InnoDB';
        });

        // 2. Branches
        Schema::create('branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name');
            $table->string('code');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->uuid('manager_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->unique(['organization_id', 'code']);
            $table->index('organization_id');
            $table->engine = 'InnoDB';
        });

        // 3. Products
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
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
            $table->json('metadata')->nullable(); // JSON Column as requested
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->unique(['organization_id', 'sku']);
            
            // Important Indexes as requested
            $table->index('sku');
            $table->index('barcode');
            $table->index('organization_id');
            $table->engine = 'InnoDB';
        });

        // 4. Stocks
        Schema::create('stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_id');
            $table->uuid('product_id');
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->date('last_count_date')->nullable();
            $table->integer('min_qty')->default(10);
            $table->integer('max_qty')->default(500);
            $table->integer('version')->default(1); // Optimistic locking
            $table->timestamps();
            
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->unique(['branch_id', 'product_id']);
            
            $table->index(['branch_id', 'product_id']);
            $table->index('quantity_on_hand');
            $table->engine = 'InnoDB';
        });

        // 5. Transactions
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('branch_id');
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
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->unique(['organization_id', 'local_transaction_id']);
            
            // Important Indexes as requested
            $table->index(['branch_id', 'transaction_date']);
            $table->index('transaction_date');
            $table->index('cashier_id');
            $table->index('sync_status');
            $table->index('is_voided');
            $table->engine = 'InnoDB';
        });

        // 6. Transaction Items
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaction_id');
            $table->uuid('product_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_per_item', 12, 2)->default(0);
            $table->timestamps();
            
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            
            $table->index('transaction_id');
            $table->index('product_id');
            $table->engine = 'InnoDB';
        });

        // 7. Promotions
        Schema::create('promotions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name');
            $table->string('promo_type');
            $table->decimal('discount_value', 10, 2);
            $table->decimal('min_purchase_amount', 12, 2)->nullable();
            $table->string('applicable_to');
            $table->json('target_ids')->nullable(); // JSON Array
            $table->dateTime('valid_from');
            $table->dateTime('valid_until');
            $table->decimal('max_discount_per_transaction', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('promo_config')->nullable(); // JSON Column as requested for dynamic rules
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->index(['is_active', 'valid_from', 'valid_until']);
            $table->engine = 'InnoDB';
        });

        // 8. Inventory Logs
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_id');
            $table->uuid('product_id');
            $table->string('log_type'); // ADJUSTMENT, SALE, TRANSFER
            $table->integer('quantity_change');
            $table->string('reason_code')->nullable();
            $table->string('reference_doc_type')->nullable();
            $table->uuid('reference_doc_id')->nullable();
            $table->uuid('recorded_by');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            
            $table->index('created_at');
            $table->index(['product_id', 'branch_id']);
            $table->index('log_type');
            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('products');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('organizations');
    }
};
