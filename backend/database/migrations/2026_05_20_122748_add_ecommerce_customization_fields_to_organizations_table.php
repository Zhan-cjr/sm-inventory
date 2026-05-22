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
            $table->string('ecommerce_banner_title')->nullable()->after('logo_path');
            $table->text('ecommerce_banner_subtitle')->nullable()->after('ecommerce_banner_title');
            $table->string('ecommerce_banner_image')->nullable()->after('ecommerce_banner_subtitle');
            $table->string('ecommerce_banner_cta_text')->nullable()->after('ecommerce_banner_image');
            $table->text('ecommerce_announcement')->nullable()->after('ecommerce_banner_cta_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'ecommerce_banner_title',
                'ecommerce_banner_subtitle',
                'ecommerce_banner_image',
                'ecommerce_banner_cta_text',
                'ecommerce_announcement',
            ]);
        });
    }
};
