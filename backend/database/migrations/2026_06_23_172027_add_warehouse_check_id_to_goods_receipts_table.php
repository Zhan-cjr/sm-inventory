<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->uuid('warehouse_check_id')->nullable()->after('id');
            $table->foreign('warehouse_check_id')->references('id')->on('warehouse_checks')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropForeign(['warehouse_check_id']);
            $table->dropColumn('warehouse_check_id');
        });
    }
};
