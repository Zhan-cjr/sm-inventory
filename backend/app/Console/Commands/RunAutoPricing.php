<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Organization;

class RunAutoPricing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:auto-pricing {--dry-run : Run in simulation mode without updating database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically suggest and draft flash sales for overstocked items by querying AI service.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting Dynamic Pricing check...");
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn("RUNNING IN DRY-RUN MODE (SIMULATION). Promos will not be saved.");
        }

        try {
            // Get data from Python AI Service
            $this->info("Querying AI Service for pricing suggestions...");
            
            // In a real env, this URL should be in config/env
            $aiServiceUrl = env('AI_SERVICE_URL', 'http://ai-service:8001');
            $response = Http::timeout(60)->get("{$aiServiceUrl}/api/v1/ai/dynamic-pricing");

            if (!$response->successful()) {
                $this->error("Failed to connect to AI Service: " . $response->body());
                return 1;
            }

            $result = $response->json();
            $suggestions = $result['data'] ?? [];

            if (empty($suggestions)) {
                $this->info("No dynamic pricing suggestions returned from AI.");
                return 0;
            }

            // Group suggestions by branch to create branch-specific promos
            $suggestionsByBranch = [];
            foreach ($suggestions as $suggestion) {
                $suggestionsByBranch[$suggestion['branch_id']][] = $suggestion;
            }

            // Just need one active organization for the promo if not specified
            $org = Organization::first();
            if (!$org) {
                $this->error("No organization found.");
                return 1;
            }

            $createdCount = 0;

            foreach ($suggestionsByBranch as $branchId => $branchSuggestions) {
                $this->info("Processing " . count($branchSuggestions) . " suggestions for branch: {$branchId}");

                $report = [];
                $productIds = [];

                foreach ($branchSuggestions as $s) {
                    $report[] = [
                        $s['sku'],
                        $s['product_name'],
                        $s['current_stock'],
                        $s['sold_30d'],
                        number_format($s['original_price'], 2),
                        number_format($s['final_price'], 2)
                    ];
                    $productIds[] = $s['product_id'];
                }

                $this->table(
                    ['SKU', 'Name', 'Stock', 'Sold (30d)', 'Original Price', 'Suggested Price'],
                    $report
                );

                if (!$isDryRun) {
                    // Create a draft promo for this branch
                    // We'll create a simple PERCENTAGE or FIXED promo. 
                    // Since final_price varies per product, a BUNDLING or specific product fixed price is better.
                    // For simplicity, we'll just draft one promo per product or a JSON config promo.

                    foreach ($branchSuggestions as $s) {
                        $discountAmount = $s['original_price'] - $s['final_price'];
                        
                        // Check if promo already exists for this product in this branch to avoid duplicates
                        $existingPromo = DB::table('promotions')
                            ->where('name', 'LIKE', '%Flash Sale AI%')
                            ->where('applicable_to', 'PRODUCT')
                            ->where('is_active', false) // pending
                            ->whereJsonContains('target_ids', $s['product_id'])
                            ->exists();

                        if (!$existingPromo) {
                            $promoId = Str::uuid()->toString();
                            
                            DB::table('promotions')->insert([
                                'id' => $promoId,
                                'organization_id' => $org->id,
                                'name' => 'Draft Flash Sale AI - ' . $s['product_name'],
                                'promo_type' => 'FIXED', // Fixed amount off
                                'discount_value' => $discountAmount,
                                'applicable_to' => 'PRODUCT',
                                'target_ids' => json_encode([$s['product_id']]),
                                'valid_from' => Carbon::now()->addDay()->startOfDay(),
                                'valid_until' => Carbon::now()->addDays(7)->endOfDay(),
                                'is_active' => false, // DRAFT MODE! Needs manual approval
                                'promo_config' => json_encode([
                                    'suggested_by' => 'AI',
                                    'branch_id' => $branchId,
                                    'original_price' => $s['original_price'],
                                    'suggested_price' => $s['final_price']
                                ]),
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);

                            // Attach to branch
                            DB::table('branch_promotion')->insert([
                                'branch_id' => $branchId,
                                'promotion_id' => $promoId
                            ]);
                            
                            $createdCount++;
                        }
                    }
                }
            }

            if ($isDryRun) {
                $this->info("[SIMULATION] Would have created {$createdCount} draft promos.");
            } else {
                $this->info("Successfully created {$createdCount} draft promos pending approval.");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Error running auto-pricing: " . $e->getMessage());
            return 1;
        }
    }
}
