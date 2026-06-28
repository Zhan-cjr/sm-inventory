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
        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            $table->foreignUuid('kontrabon_id')->nullable()->constrained('kontrabons')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            $table->dropForeign(['kontrabon_id']);
            $table->dropColumn('kontrabon_id');
        });
    }
};
