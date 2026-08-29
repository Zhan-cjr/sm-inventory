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
        Schema::table('ppob_transactions', function (Blueprint $table) {
            $table->string('provider')->default('digiflazz')->after('id')->comment('PPOB Provider used for this transaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ppob_transactions', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
    }
};
