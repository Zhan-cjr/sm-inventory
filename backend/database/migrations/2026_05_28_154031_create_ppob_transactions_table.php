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
        Schema::create('ppob_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->nullable()->constrained('transactions')->onDelete('cascade');
            $table->string('ref_id')->unique()->comment('Reference ID to Digiflazz');
            $table->string('customer_no')->comment('Target Phone/Meter Number');
            $table->string('buyer_sku_code')->comment('Digiflazz SKU Code');
            $table->decimal('price', 15, 2)->default(0)->comment('Price from Digiflazz');
            $table->string('status')->default('Pending')->comment('Pending, Sukses, Gagal');
            $table->string('rc')->nullable()->comment('Response Code');
            $table->string('sn')->nullable()->comment('Serial Number / Token');
            $table->text('message')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppob_transactions');
    }
};
