<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('branches', 'is_legacy_system')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->boolean('is_legacy_system')->default(false)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('branches', 'is_legacy_system')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn('is_legacy_system');
            });
        }
    }
};
