<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // -----------------------------------------------
        // 1. Tabel Rak / Lokasi per Cabang
        // -----------------------------------------------
        Schema::create('stock_opname_racks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('rack_code', 50)->comment('Kode unik rak, mis: RAK-A01');
            $table->string('rack_name', 100)->comment('Nama deskriptif rak');
            $table->text('location_description')->nullable()->comment('Deskripsi lokasi');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['branch_id', 'rack_code']);
        });

        // -----------------------------------------------
        // 2. Tabel Sesi Stok Opname
        // -----------------------------------------------
        Schema::create('stock_opname_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('session_number', 50)->unique()->comment('Nomor sesi: OP-20260522-001');
            $table->foreignUuid('branch_id')->constrained('branches');
            $table->foreignUuid('organization_id')->constrained('organizations');
            $table->date('opname_date');
            $table->enum('status', [
                'DRAFT',        // Baru dibuat, belum dimulai
                'COUNTING',     // Penghitung 1 sedang menghitung
                'CHECKING',     // Pengecek 2 sedang mengecek
                'FINAL_CHECK',  // SPV sedang review selisih
                'COMPLETED',    // Selesai, stok sudah diupdate
                'CANCELLED',
            ])->default('DRAFT');
            $table->string('session_token', 64)->unique()->comment('Token QR untuk pengecek 2');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // -----------------------------------------------
        // 3. Pivot: Rak yang diikutkan dalam sesi + token rak
        // -----------------------------------------------
        Schema::create('stock_opname_rack_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('session_id')->constrained('stock_opname_sessions')->cascadeOnDelete();
            $table->foreignUuid('rack_id')->constrained('stock_opname_racks')->cascadeOnDelete();
            $table->string('rack_token', 64)->unique()->comment('Token QR per rak untuk penghitung 1');
            $table->enum('count1_status', ['PENDING', 'DONE'])->default('PENDING');
            $table->enum('count2_status', ['PENDING', 'DONE'])->default('PENDING');
            $table->string('count1_by_name', 100)->nullable()->comment('Nama bebas penghitung 1');
            $table->string('count2_by_name', 100)->nullable()->comment('Nama bebas pengecek 2');
            $table->timestamp('count1_at')->nullable();
            $table->timestamp('count2_at')->nullable();
            $table->timestamps();
            $table->unique(['session_id', 'rack_id']);
        });

        // -----------------------------------------------
        // 4. Item hasil hitungan per rak per produk
        // -----------------------------------------------
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('session_id')->constrained('stock_opname_sessions')->cascadeOnDelete();
            $table->foreignUuid('rack_session_id')->constrained('stock_opname_rack_sessions')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products');

            // Qty sistem (total cabang, diambil saat sesi dimulai, hanya tampil di akhir)
            $table->decimal('system_quantity', 15, 4)->default(0);

            // Penghitung 1 (publik)
            $table->decimal('count1_quantity', 15, 4)->nullable();
            $table->timestamp('count1_at')->nullable();

            // Pengecek 2 (publik, count1 disembunyikan saat pengisian)
            $table->decimal('count2_quantity', 15, 4)->nullable();
            $table->timestamp('count2_at')->nullable();

            // Selisih antar pengecek (count1 vs count2), dihitung per produk lintas rak
            $table->decimal('discrepancy_1_2', 15, 4)->nullable()->comment('Selisih count1 vs count2 di rak ini');

            // Final oleh SPV (hanya jika DISCREPANCY)
            $table->decimal('final_quantity', 15, 4)->nullable();
            $table->unsignedBigInteger('final_by')->nullable();
            $table->foreign('final_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('final_at')->nullable();
            $table->text('final_notes')->nullable();

            $table->enum('status', [
                'PENDING',       // Belum dihitung
                'COUNT1_DONE',   // Sudah dihitung penghitung 1
                'COUNT2_DONE',   // Sudah dicek pengecek 2, tidak ada selisih
                'DISCREPANCY',   // Ada selisih count1 vs count2
                'FINAL_DONE',    // SPV sudah input final quantity
            ])->default('PENDING');

            $table->timestamps();

            // Satu produk hanya muncul 1x per rak per sesi
            $table->unique(['rack_session_id', 'product_id'], 'unique_product_per_rack_session');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opname_rack_sessions');
        Schema::dropIfExists('stock_opname_sessions');
        Schema::dropIfExists('stock_opname_racks');
    }
};
