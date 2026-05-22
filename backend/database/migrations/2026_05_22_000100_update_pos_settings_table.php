<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Delete redundant allow_negative_stock key
        DB::table('pos_settings')->where('key_name', 'allow_negative_stock')->delete();

        // 2. Assign any NULL organization_id to the first organization
        $firstOrg = DB::table('organizations')->first();
        if ($firstOrg) {
            DB::table('pos_settings')
                ->whereNull('organization_id')
                ->update(['organization_id' => $firstOrg->id]);
        }

        Schema::table('pos_settings', function (Blueprint $table) {
            // 3. Drop table-wide unique constraint on key_name
            $table->dropUnique('pos_settings_key_name_unique');

            // 4. Add compound unique index on organization_id and key_name
            $table->unique(['organization_id', 'key_name'], 'pos_settings_org_key_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_settings', function (Blueprint $table) {
            $table->dropUnique('pos_settings_org_key_unique');
            $table->unique('key_name', 'pos_settings_key_name_unique');
        });
    }
};
