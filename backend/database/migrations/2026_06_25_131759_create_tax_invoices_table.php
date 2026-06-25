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
        Schema::create('tax_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(\App\Models\Organization::class)->constrained()->cascadeOnDelete();
            
            // 'masukan' = Pembelian (Pajak Masukan), 'keluaran' = Penjualan (Pajak Keluaran)
            $table->enum('type', ['masukan', 'keluaran'])->index();
            
            $table->string('nomor_faktur', 50)->unique();
            $table->date('tanggal_faktur');
            $table->string('masa_pajak', 7)->comment('Format: MM-YYYY');
            
            $table->string('npwp_lawan', 50)->nullable();
            $table->string('nama_lawan', 150)->nullable();
            
            $table->decimal('dpp', 15, 2)->default(0)->comment('Dasar Pengenaan Pajak');
            $table->decimal('ppn', 15, 2)->default(0)->comment('Nilai PPN');
            
            $table->enum('status', ['draft', 'reported'])->default('draft');
            
            // Relasi ke Transaksi (Sales) atau GoodsReceipt (Pembelian)
            $table->uuid('reference_id')->nullable()->index();
            $table->string('reference_type')->nullable()->index();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_invoices');
    }
};
