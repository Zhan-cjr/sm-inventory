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
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->string('original_transaction_id')->nullable()->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('received_amount', 15, 2)->change();
            $table->decimal('change_amount', 15, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            // Need to drop and recreate or use raw SQL if changing from string back to unsignedBigInteger, 
            // but we'll try standard change first.
            $table->unsignedBigInteger('original_transaction_id')->nullable()->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('received_amount', 12, 2)->change();
            $table->decimal('change_amount', 12, 2)->change();
        });
    }
};
