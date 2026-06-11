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
        Schema::create('cp_facilities', function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->unique(); // e.g., supermarket, fashion
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // Lucide icon name
            $table->string('image_url')->nullable(); // For the facilities page visual
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_facilities');
    }
};
