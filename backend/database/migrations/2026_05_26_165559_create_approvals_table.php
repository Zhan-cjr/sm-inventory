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
        Schema::create('approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('approvable'); // This creates approvable_id (uuid) and approvable_type
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // The user who approved/rejected
            $table->string('status'); // 'pending', 'approved', 'rejected'
            $table->text('notes')->nullable();
            $table->integer('level')->default(1); // Approval level (e.g., 1 for Manager, 2 for Director)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
