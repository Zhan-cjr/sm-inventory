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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'supplier_division_id')) {
                $table->foreignUuid('supplier_division_id')
                    ->nullable()
                    ->after('supplier_id')
                    ->constrained('supplier_divisions')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'supplier_division_id')) {
                $table->dropForeign(['supplier_division_id']);
                $table->dropColumn('supplier_division_id');
            }
        });
    }
};
