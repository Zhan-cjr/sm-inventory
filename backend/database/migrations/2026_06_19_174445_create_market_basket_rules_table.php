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
        Schema::create('market_basket_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('antecedent_id');
            $table->uuid('consequent_id');
            $table->string('antecedent_name');
            $table->string('consequent_name');
            $table->decimal('support', 8, 4);
            $table->decimal('confidence', 8, 4);
            $table->decimal('lift', 8, 4);
            $table->timestamps();

            // Foreign keys
            $table->foreign('antecedent_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('consequent_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_basket_rules');
    }
};
