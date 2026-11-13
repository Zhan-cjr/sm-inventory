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
        // 1. Add soft deletes to stock_opname_racks if not exists
        Schema::table('stock_opname_racks', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_opname_racks', 'deleted_at')) {
                $table->softDeletes()->after('is_active');
            }
        });

        // 2. Change foreign key on stock_opname_rack_sessions from CASCADE to RESTRICT
        Schema::table('stock_opname_rack_sessions', function (Blueprint $table) {
            $table->dropForeign('stock_opname_rack_sessions_rack_id_foreign');
            $table->foreign('rack_id')
                ->references('id')
                ->on('stock_opname_racks')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_opname_rack_sessions', function (Blueprint $table) {
            $table->dropForeign(['rack_id']);
            $table->foreign('rack_id')
                ->references('id')
                ->on('stock_opname_racks')
                ->cascadeOnDelete();
        });

        Schema::table('stock_opname_racks', function (Blueprint $table) {
            if (Schema::hasColumn('stock_opname_racks', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
