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
        Schema::create('member_tiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Bronze, Silver, Gold, Platinum
            $table->integer('min_points')->default(0); // Minimum points to reach this tier
            $table->decimal('discount_percent', 5, 2)->default(0); // Benefit discount
            $table->string('color_hex', 7)->nullable(); // For UI styling e.g. #C0C0C0
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_tiers');
    }
};
