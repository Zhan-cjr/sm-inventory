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
            $table->string('product_type')->default('physical')->after('metadata')->comment('physical or digital');
            $table->string('ppob_sku')->nullable()->after('product_type')->comment('SKU Code from Digiflazz/PPOB provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['product_type', 'ppob_sku']);
        });
    }
};
