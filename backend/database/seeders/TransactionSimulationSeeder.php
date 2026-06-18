<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\User;
use App\Models\Branch;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TransactionSimulationSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::first();
        if (!$organization) {
            $this->command->error("Organization not found.");
            return;
        }

        $branch = Branch::first();
        if (!$branch) {
            $this->command->error("Branch not found.");
            return;
        }

        $cashier = User::whereHas('roles', function($q) {
            $q->where('name', 'kasir');
        })->first() ?? User::first();

        $customers = Customer::all();
        $products = Product::where('is_active', true)->inRandomOrder()->limit(50)->get();

        if ($products->count() < 10) {
            $this->command->error("Not enough active products (need at least 10).");
            return;
        }

        // Create some predefined pairs to generate strong Apriori associations
        $pair1 = [$products[0], $products[1]]; // Pair A
        $pair2 = [$products[2], $products[3]]; // Pair B
        $pair3 = [$products[4], $products[5]]; // Pair C

        $totalTransactions = 500;
        
        $this->command->info("Generating {$totalTransactions} simulation transactions...");

        for ($i = 0; $i < $totalTransactions; $i++) {
            // Random date in the last 30 days
            $daysAgo = rand(0, 30);
            
            // Random time between 08:00 and 21:00
            $hour = rand(8, 20);
            $minute = rand(0, 59);
            $second = rand(0, 59);
            
            $transactionDate = Carbon::now()
                ->subDays($daysAgo)
                ->setTime($hour, $minute, $second);

            // Give weekend days a slight boost by sometimes picking them intentionally,
            // but for a pure seeder, random distribution is fine.

            $customer = null;
            if (rand(1, 100) <= 60 && $customers->count() > 0) { // 60% chance to have a customer
                $customer = $customers->random();
            }

            $transactionId = (string) Str::uuid();

            $transaction = Transaction::create([
                'id' => $transactionId,
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'terminal_id' => null,
                'shift_id' => null, // Skip shift for simulation
                'transaction_type' => 'SALE',
                'transaction_date' => $transactionDate,
                'cashier_id' => $cashier->id,
                'customer_id' => $customer ? $customer->id : null,
                'total_amount' => 0,
                'discount_amount' => 0,
                'manual_discount' => 0,
                'promo_discount' => 0,
                'final_amount' => 0,
                'payment_method' => rand(1, 100) <= 70 ? 'CASH' : 'DEBIT',
                'received_amount' => 0,
                'change_amount' => 0,
                'is_voided' => false,
                'sync_status' => 'SYNCED',
                'receipt_number' => 'SIM-' . $transactionDate->format('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
            ]);

            $itemsToBuy = [];
            
            // Randomly pick a pair to ensure Apriori finds something
            $pairChance = rand(1, 100);
            if ($pairChance <= 20) {
                $itemsToBuy[] = $pair1[0];
                $itemsToBuy[] = $pair1[1];
            } elseif ($pairChance <= 40) {
                $itemsToBuy[] = $pair2[0];
                $itemsToBuy[] = $pair2[1];
            } elseif ($pairChance <= 60) {
                $itemsToBuy[] = $pair3[0];
                $itemsToBuy[] = $pair3[1];
            } else {
                // Completely random 1-5 items
                $itemCount = rand(1, 5);
                for ($j = 0; $j < $itemCount; $j++) {
                    $itemsToBuy[] = $products->random();
                }
            }

            // Remove duplicates just in case
            $itemsToBuy = collect($itemsToBuy)->unique('id');

            $totalAmount = 0;

            foreach ($itemsToBuy as $product) {
                $qty = rand(1, 3);
                $price = $product->selling_price;
                $subtotal = $qty * $price;

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'discount_per_item' => 0,
                    'promotion_id' => null,
                ]);

                $totalAmount += $subtotal;
            }

            $transaction->update([
                'total_amount' => $totalAmount,
                'final_amount' => $totalAmount,
                'received_amount' => $totalAmount,
            ]);
        }

        $this->command->info("Successfully generated {$totalTransactions} transactions!");
    }
}
