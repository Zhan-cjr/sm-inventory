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
        Schema::create('purchase_payment_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_payment_id')->constrained('purchase_payments')->cascadeOnDelete();
            $table->foreignUuid('goods_receipt_id')->constrained('goods_receipts')->restrictOnDelete();
            $table->decimal('amount_paid', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_payment_items');
    }
};
