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
        if (!Schema::hasTable('tax_invoice_items')) {
            Schema::create('tax_invoice_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tax_invoice_id')->constrained('tax_invoices')->cascadeOnDelete();
                $table->string('name');
                $table->decimal('harga_satuan', 15, 2)->default(0);
                $table->decimal('jumlah_barang', 15, 2)->default(1);
                $table->decimal('harga_total', 15, 2)->default(0);
                $table->decimal('diskon', 15, 2)->default(0);
                $table->decimal('dpp', 15, 2)->default(0);
                $table->decimal('ppn', 15, 2)->default(0);
                $table->decimal('ppnbm', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_invoice_items');
    }
};
