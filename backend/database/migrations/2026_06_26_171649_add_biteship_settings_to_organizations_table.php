<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('biteship_api_key')->nullable();
            $table->enum('logistics_markup_type', ['NONE', 'FIXED', 'PERCENTAGE'])->default('NONE');
            $table->decimal('logistics_markup_value', 15, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['biteship_api_key', 'logistics_markup_type', 'logistics_markup_value']);
        });
    }
};
