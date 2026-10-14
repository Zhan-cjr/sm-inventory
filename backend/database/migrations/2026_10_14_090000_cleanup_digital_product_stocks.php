<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Hapus data stok yang terlanjur terbuat untuk produk bertipe digital
        $digitalProductIds = DB::table('products')
            ->where('product_type', 'digital')
            ->pluck('id')
            ->toArray();

        if (!empty($digitalProductIds)) {
            $stockIds = DB::table('stocks')
                ->whereIn('product_id', $digitalProductIds)
                ->pluck('id')
                ->toArray();

            if (!empty($stockIds)) {
                if (Schema::hasTable('stock_stock_opname_rack')) {
                    DB::table('stock_stock_opname_rack')->whereIn('stock_id', $stockIds)->delete();
                }
                DB::table('stocks')->whereIn('id', $stockIds)->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op karena produk digital tidak membutuhkan data stok fisik
    }
};
