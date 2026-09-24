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
        Schema::table('products', function (Blueprint $table) {
            $table->string('listing_status', 30)->default('REGULAR')->after('product_type')->index();
            $table->decimal('listing_fee', 15, 2)->default(0)->after('listing_status');
            $table->date('trial_start_date')->nullable()->after('listing_fee');
            $table->date('trial_end_date')->nullable()->after('trial_start_date');
            $table->json('allowed_branch_ids')->nullable()->after('trial_end_date');
            $table->text('listing_notes')->nullable()->after('allowed_branch_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['listing_status']);
            $table->dropColumn([
                'listing_status',
                'listing_fee',
                'trial_start_date',
                'trial_end_date',
                'allowed_branch_ids',
                'listing_notes',
            ]);
        });
    }
};
