<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_checks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_order_id');
            $table->uuid('branch_id');
            $table->unsignedBigInteger('checked_by');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('checked_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_checks');
    }
};
