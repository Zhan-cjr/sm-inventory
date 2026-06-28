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
        Schema::table('ppob_transactions', function (Blueprint $table) {
            $table->uuid('ecommerce_order_id')->nullable()->after('transaction_id')->comment('Order E-Commerce ID jika transaksi dari e-commerce');
            // Ubah transaction_id agar bisa null, karena bisa saja transaksinya murni dari E-commerce
            $table->uuid('transaction_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ppob_transactions', function (Blueprint $table) {
            $table->dropColumn('ecommerce_order_id');
            // Hati-hati, jika dikembalikan tidak boleh null, pastikan data yang null dihapus/diupdate dulu.
            // Di sini kita biarkan saja nullable jika di down
        });
    }
};
