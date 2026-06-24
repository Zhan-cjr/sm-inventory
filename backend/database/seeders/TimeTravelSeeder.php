<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Organization;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Stock;

class TimeTravelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $org = Organization::first();
        if (!$org) {
            $this->command->error('No organization found. Please run main seeder first.');
            return;
        }

        $branch = Branch::where('organization_id', $org->id)->first();
        if (!$branch) {
            $this->command->error('No branch found.');
            return;
        }

        $cashierId = Str::uuid()->toString(); // Mock cashier

        // ---------------------------------------------------------
        // Skenario A: Dead Stock (High Stock, No recent sales)
        // Expected outcome: Dynamic Pricing triggers a Flash Sale
        // ---------------------------------------------------------
        $prodA = Product::create([
            'organization_id' => $org->id,
            'sku' => 'TEST-A-' . time(),
            'name' => 'Sabun Cuci A (Simulasi Dead Stock)',
            'cost_price' => 10000,
            'selling_price' => 15000,
            'is_active' => true,
        ]);

        Stock::create([
            'branch_id' => $branch->id,
            'product_id' => $prodA->id,
            'quantity_on_hand' => 500, // Very high stock
            'safety_stock' => 10,
            'min_qty' => 5,
            'cost_price' => 10000,
            'selling_price' => 15000,
            'is_active' => true,
        ]);

        // Insert just 1 sale 4 months ago
        $txIdA = Str::uuid()->toString();
        DB::table('transactions')->insert([
            'id' => $txIdA,
            'organization_id' => $org->id,
            'branch_id' => $branch->id,
            'transaction_type' => 'SALES',
            'transaction_date' => Carbon::now()->subDays(120),
            'cashier_id' => $cashierId,
            'total_amount' => 15000,
            'final_amount' => 15000,
            'created_at' => Carbon::now()->subDays(120),
        ]);
        DB::table('transaction_items')->insert([
            'id' => Str::uuid()->toString(),
            'transaction_id' => $txIdA,
            'product_id' => $prodA->id,
            'quantity' => 1,
            'unit_price' => 15000,
        ]);


        // ---------------------------------------------------------
        // Skenario B: Barang Mati 1 Tahun (0 Stock, 0 Sales)
        // Expected outcome: Auto Discontinue sets is_active to false
        // ---------------------------------------------------------
        $prodB = Product::create([
            'organization_id' => $org->id,
            'sku' => 'TEST-B-' . time(),
            'name' => 'Kecap XYZ (Simulasi Discontinue)',
            'cost_price' => 8000,
            'selling_price' => 10000,
            'is_active' => true,
            'created_at' => Carbon::now()->subDays(365), // old product
        ]);

        Stock::create([
            'branch_id' => $branch->id,
            'product_id' => $prodB->id,
            'quantity_on_hand' => 0, // 0 stock
            'safety_stock' => 5,
            'min_qty' => 5,
            'cost_price' => 8000,
            'selling_price' => 10000,
            'is_active' => true,
            'created_at' => Carbon::now()->subDays(365), // old stock
        ]);
        // No transactions, no inventory logs for product B recently


        // ---------------------------------------------------------
        // Skenario C: Barang Rutin (10 pcs/day)
        // Expected outcome: Demand Forecasting correctly predicts ADS=10
        // ---------------------------------------------------------
        $prodC = Product::create([
            'organization_id' => $org->id,
            'sku' => 'TEST-C-' . time(),
            'name' => 'Beras C (Simulasi Fast Moving)',
            'cost_price' => 50000,
            'selling_price' => 60000,
            'is_active' => true,
            'lead_time_days' => 3, // Requires 30 stock for safety
        ]);

        Stock::create([
            'branch_id' => $branch->id,
            'product_id' => $prodC->id,
            'quantity_on_hand' => 25, // Under safety stock!
            'safety_stock' => 30,
            'min_qty' => 10,
            'cost_price' => 50000,
            'selling_price' => 60000,
            'is_active' => true,
        ]);

        // Create 10 sales per day for the last 30 days
        for ($i = 30; $i >= 1; $i--) {
            $txIdC = Str::uuid()->toString();
            $date = Carbon::now()->subDays($i)->setHour(10);
            
            DB::table('transactions')->insert([
                'id' => $txIdC,
                'organization_id' => $org->id,
                'branch_id' => $branch->id,
                'transaction_type' => 'SALES',
                'transaction_date' => $date,
                'cashier_id' => $cashierId,
                'total_amount' => 600000,
                'final_amount' => 600000,
                'created_at' => $date,
            ]);
            DB::table('transaction_items')->insert([
                'id' => Str::uuid()->toString(),
                'transaction_id' => $txIdC,
                'product_id' => $prodC->id,
                'quantity' => 10,
                'unit_price' => 60000,
                'created_at' => $date,
            ]);
        }

        $this->command->info('Time Travel Seeder completed successfully!');
        $this->command->info('Run: php artisan inventory:auto-discontinue --dry-run');
        $this->command->info('Run: php artisan inventory:auto-pricing --dry-run');
    }
}
