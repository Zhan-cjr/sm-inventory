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
        Schema::table('promotions', function (Blueprint $table) {
            $table->uuid('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->decimal('supplier_sponsorship_percent', 5, 2)->default(0);
            $table->boolean('is_settled')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'supplier_sponsorship_percent', 'is_settled']);
        });
    }
};
