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
        if (!Schema::hasTable('product_monthly_sales_histories')) {
            Schema::create('product_monthly_sales_histories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('year');
                $table->unsignedTinyInteger('month'); // 1-12
                $table->decimal('quantity_sold', 12, 2)->default(0);
                $table->string('source', 20)->default('MIGRATION'); // 'MIGRATION', 'POS_MANUAL', etc.
                $table->string('notes')->nullable();
                $table->timestamps();

                $table->unique(['branch_id', 'product_id', 'year', 'month'], 'uk_branch_prod_period');
                $table->index(['branch_id', 'year', 'month']);
                $table->index(['product_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_monthly_sales_histories');
    }
};
