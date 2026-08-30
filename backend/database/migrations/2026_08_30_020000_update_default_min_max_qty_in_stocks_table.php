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
        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('min_qty', 12, 2)->default(3)->change();
            $table->decimal('max_qty', 12, 2)->default(15)->change();
        });

        // Update existing stocks in database where max_qty = 500 to min_qty = 3 and max_qty = 15
        DB::table('stocks')
            ->where('max_qty', 500)
            ->update([
                'min_qty' => 3,
                'max_qty' => 15,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('min_qty', 12, 2)->default(10)->change();
            $table->decimal('max_qty', 12, 2)->default(500)->change();
        });
    }
};
