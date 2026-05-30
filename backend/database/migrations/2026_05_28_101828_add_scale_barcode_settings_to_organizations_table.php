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
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('scale_barcode_enabled')->default(false)->after('po_approval_max_qty_enabled');
            $table->string('scale_barcode_prefix', 10)->default('20')->after('scale_barcode_enabled');
            $table->integer('scale_barcode_item_code_length')->default(5)->after('scale_barcode_prefix');
            $table->integer('scale_barcode_weight_length')->default(5)->after('scale_barcode_item_code_length');
            $table->integer('scale_barcode_weight_decimal_places')->default(3)->after('scale_barcode_weight_length');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'scale_barcode_enabled',
                'scale_barcode_prefix',
                'scale_barcode_item_code_length',
                'scale_barcode_weight_length',
                'scale_barcode_weight_decimal_places',
            ]);
        });
    }
};
