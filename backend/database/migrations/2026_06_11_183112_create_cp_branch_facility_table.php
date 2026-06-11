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
        Schema::create('cp_branch_facility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('c_p_branch_id')->constrained('cp_branches')->onDelete('cascade');
            $table->foreignId('c_p_facility_id')->constrained('cp_facilities')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_branch_facility');
    }
};
