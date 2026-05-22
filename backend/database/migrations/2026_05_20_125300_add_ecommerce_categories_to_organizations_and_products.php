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
            $table->json('ecommerce_categories')->nullable()->after('ecommerce_announcement');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('ecommerce_category')->nullable()->after('is_ecommerce_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('ecommerce_categories');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('ecommerce_category');
        });
    }
};
