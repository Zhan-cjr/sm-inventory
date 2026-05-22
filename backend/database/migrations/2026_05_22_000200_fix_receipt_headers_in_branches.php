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
        // 1. Change schema default values to the correct order
        Schema::table('branches', function (Blueprint $table) {
            $table->string('receipt_header_line2')->default('{branch_name}')->change();
            $table->string('receipt_header_line3')->default('{branch_address}')->change();
            $table->string('receipt_header_line4')->default('Telp: {branch_phone}')->change();
        });

        // 2. Fix existing rows in the branches table
        DB::table('branches')->update([
            'receipt_header_line2' => '{branch_name}',
            'receipt_header_line3' => '{branch_address}',
            'receipt_header_line4' => 'Telp: {branch_phone}',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('receipt_header_line2')->default('{branch_address}')->change();
            $table->string('receipt_header_line3')->default('Telp: {branch_phone}')->change();
            $table->string('receipt_header_line4')->default('{branch_name}')->change();
        });
    }
};
