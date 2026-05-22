<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add fields to organizations table
        Schema::table('organizations', function (Blueprint $table) {
            $table->decimal('point_redemption_value', 12, 2)->default(1.00)->after('point_conversion_rate');
            $table->integer('minimum_points_to_redeem')->default(100)->after('point_redemption_value');
        });

        // 2. Add fields to transactions table
        Schema::table('transactions', function (Blueprint $table) {
            $table->integer('points_redeemed')->default(0)->after('is_voided');
            $table->decimal('points_redeemed_discount', 12, 2)->default(0.00)->after('points_redeemed');
        });

        // 3. Add fields to ecommerce_orders table
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->integer('points_redeemed')->default(0)->after('total_amount');
            $table->decimal('points_redeemed_discount', 15, 2)->default(0.00)->after('points_redeemed');
        });

        // 4. Create point_histories table
        Schema::create('point_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('customers')->onDelete('cascade');
            $table->integer('points');
            $table->integer('before_points');
            $table->integer('after_points');
            $table->string('reference_type'); // TRANSACTION, ECOMMERCE_ORDER, ADJUSTMENT, CANCEL
            $table->string('reference_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // 5. Modify stock_batch_deductions table
        Schema::table('stock_batch_deductions', function (Blueprint $table) {
            $table->uuid('transaction_item_id')->nullable()->change();
            $table->foreignUuid('ecommerce_order_item_id')->nullable()->after('transaction_item_id')->constrained('ecommerce_order_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_batch_deductions', function (Blueprint $table) {
            $table->dropForeign(['ecommerce_order_item_id']);
            $table->dropColumn('ecommerce_order_item_id');
            $table->uuid('transaction_item_id')->nullable(false)->change();
        });

        Schema::dropIfExists('point_histories');

        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropColumn(['points_redeemed', 'points_redeemed_discount']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['points_redeemed', 'points_redeemed_discount']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['point_redemption_value', 'minimum_points_to_redeem']);
        });
    }
};
