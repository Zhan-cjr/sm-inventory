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
        Schema::create('c_p_testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('role')->nullable();
            $table->text('content');
            $table->string('avatar_url')->nullable();
            $table->integer('rating')->default(5);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_p_testimonials');
    }
};
