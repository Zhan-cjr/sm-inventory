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
        Schema::table('stocks', function (Blueprint $table) {
            $table->uuid('supplier_id')->nullable()->after('product_id');
            $table->uuid('supplier_division_id')->nullable()->after('supplier_id');

            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('supplier_division_id')->references('id')->on('supplier_divisions')->nullOnDelete();

            $table->index(['branch_id', 'supplier_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['supplier_division_id']);
            $table->dropIndex(['branch_id', 'supplier_id']);
            $table->dropColumn(['supplier_id', 'supplier_division_id']);
        });
    }
};
