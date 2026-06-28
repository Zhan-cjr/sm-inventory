<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all products that are clearly digital/PPOB to have product_type = 'digital'
        $digitalKeywords = [
            'DANA%', 'OVO%', 'GOPAY%', 'SHOPEE%', 'LINKAJA%', 
            'PLN%', 'TOKEN%', 'PULSA%', 'TELKOMSEL%', 'INDOSAT%', 
            'XL%', 'AXIS%', 'SMARTFREN%', 'TRI%', 'THREE%', 'BY.U%', 'DATA %'
        ];

        foreach ($digitalKeywords as $keyword) {
            DB::table('products')
                ->where('name', 'like', $keyword)
                ->where('product_type', '!=', 'digital')
                ->update(['product_type' => 'digital']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration needed for data fixing
    }
};
