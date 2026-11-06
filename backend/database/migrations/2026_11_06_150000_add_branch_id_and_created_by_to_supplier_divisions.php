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
        Schema::table('supplier_divisions', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_divisions', 'branch_id')) {
                $table->foreignUuid('branch_id')->nullable()->after('supplier_id')->constrained('branches')->nullOnDelete();
            }
            if (!Schema::hasColumn('supplier_divisions', 'created_by')) {
                $table->foreignUuid('created_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_divisions', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_divisions', 'branch_id')) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            }
            if (Schema::hasColumn('supplier_divisions', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
        });
    }
};
