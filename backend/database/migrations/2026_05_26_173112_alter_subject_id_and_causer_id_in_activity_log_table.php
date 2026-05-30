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
        Schema::table('activity_log', function (Blueprint $table) {
            // Drop indexes first, change type, and add them back if necessary
            $table->dropIndex('subject');
            $table->dropIndex('causer');

            $table->string('subject_id', 36)->nullable()->change();
            $table->string('causer_id', 36)->nullable()->change();

            $table->index(['subject_type', 'subject_id'], 'subject');
            $table->index(['causer_type', 'causer_id'], 'causer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('subject');
            $table->dropIndex('causer');

            $table->unsignedBigInteger('subject_id')->nullable()->change();
            $table->unsignedBigInteger('causer_id')->nullable()->change();

            $table->index(['subject_type', 'subject_id'], 'subject');
            $table->index(['causer_type', 'causer_id'], 'causer');
        });
    }
};
