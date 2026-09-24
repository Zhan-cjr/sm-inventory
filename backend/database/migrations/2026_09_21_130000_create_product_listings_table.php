<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_listings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->nullable();
            $table->string('listing_number', 50)->unique();
            $table->uuid('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->uuid('supplier_division_id')->nullable()->constrained('supplier_divisions')->nullOnDelete();
            $table->decimal('listing_fee', 15, 2)->default(0);
            $table->date('trial_start_date')->nullable();
            $table->date('trial_end_date')->nullable();
            $table->json('allowed_branch_ids')->nullable();
            $table->string('status', 30)->default('TRIAL')->index();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->uuid('product_listing_id')->nullable()->after('listing_notes');
            $table->foreign('product_listing_id')->references('id')->on('product_listings')->nullOnDelete();
        });

        // Migrasi data produk listing yang sudah ada jika ada
        $existingListingProducts = DB::table('products')
            ->where('listing_status', '!=', 'REGULAR')
            ->get();

        foreach ($existingListingProducts as $p) {
            $listingId = (string) Str::uuid();
            $listingNumber = 'LST-' . date('ymd', strtotime($p->created_at ?? 'now')) . '-' . rand(100, 999);

            DB::table('product_listings')->insert([
                'id' => $listingId,
                'organization_id' => $p->organization_id,
                'listing_number' => $listingNumber,
                'supplier_id' => $p->supplier_id,
                'supplier_division_id' => $p->supplier_division_id,
                'listing_fee' => $p->listing_fee ?? 0,
                'trial_start_date' => $p->trial_start_date ?? date('Y-m-d'),
                'trial_end_date' => $p->trial_end_date ?? date('Y-m-d', strtotime('+3 months')),
                'allowed_branch_ids' => $p->allowed_branch_ids,
                'status' => $p->listing_status ?? 'TRIAL',
                'notes' => $p->listing_notes,
                'created_at' => $p->created_at ?? now(),
                'updated_at' => $p->updated_at ?? now(),
            ]);

            DB::table('products')
                ->where('id', $p->id)
                ->update(['product_listing_id' => $listingId]);

            // Hubungkan deduction ke listing jika ada
            DB::table('supplier_deductions')
                ->where('reference_id', $p->id)
                ->where('deduction_type', 'LISTING_FEE')
                ->update(['reference_id' => $listingId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_listing_id']);
            $table->dropColumn(['product_listing_id']);
        });

        Schema::dropIfExists('product_listings');
    }
};
