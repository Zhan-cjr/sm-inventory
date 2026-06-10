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
        // 1. Add point_redemption_enabled to organizations table
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('point_redemption_enabled')->default(true)->after('minimum_points_to_redeem');
        });

        // 2. Create rewards table
        Schema::create('rewards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('points_required')->default(0);
            $table->integer('stock')->default(0);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
        });

        // 3. Create reward_redemptions table
        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id');
            $table->uuid('reward_id');
            $table->uuid('branch_id')->nullable();
            $table->integer('points_redeemed')->default(0);
            $table->integer('quantity')->default(1);
            $table->string('status')->default('COMPLETED'); // COMPLETED, CANCELLED, PENDING
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('reward_id')->references('id')->on('rewards')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_redemptions');
        Schema::dropIfExists('rewards');

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('point_redemption_enabled');
        });
    }
};
