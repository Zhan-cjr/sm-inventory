<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Branch;
use App\Models\Stock;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

class GenerateInitialStockJournal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'journal:generate-initial-stock {--branch= : Optional Branch ID to generate for specific branch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate initial stock journal entries (Modal Awal) for existing active stocks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Initial Stock Journal Generation...');

        $branchId = $this->option('branch');
        $query = Branch::query();
        if ($branchId) {
            $query->where('id', $branchId);
        }
        $branches = $query->get();

        if ($branches->isEmpty()) {
            $this->error('No branches found.');
            return;
        }

        DB::beginTransaction();
        try {
            foreach ($branches as $branch) {
                $this->info("Processing Branch: {$branch->name}...");
                $organizationId = $branch->organization_id ?? \App\Models\Organization::first()->id;

                // 1. Get Accounts
                $persediaanAccount = Account::where('organization_id', $organizationId)
                    ->where('account_code', '1140')
                    ->first();

                if (!$persediaanAccount) {
                    $this->error("Persediaan account (1140) not found for organization {$organizationId}. Skipping branch.");
                    continue;
                }

                $modalAwalAccount = Account::firstOrCreate([
                    'organization_id' => $organizationId,
                    'account_code' => '3110'
                ], [
                    'name' => 'Modal Awal / Saldo Awal',
                    'type' => 'equity',
                    'description' => 'Modal awal dari migrasi atau input saldo awal persediaan',
                    'is_active' => true,
                ]);

                // 2. Calculate Total Valuation
                $stocks = Stock::where('branch_id', $branch->id)
                    ->where('is_active', true)
                    ->where('quantity_on_hand', '>', 0)
                    ->get();

                $totalValuation = 0;
                foreach ($stocks as $stock) {
                    $cost = $stock->cost_price_tax > 0 ? (float) $stock->cost_price_tax : (float) $stock->cost_price;
                    $totalValuation += $cost * $stock->quantity_on_hand;
                }

                if ($totalValuation <= 0) {
                    $this->warn("Total active stock valuation is 0 for branch {$branch->name}. Skipping.");
                    continue;
                }

                // 3. Create Journal Entry
                $existingJournal = JournalEntry::where('branch_id', $branch->id)
                    ->where('reference_number', 'like', 'JV-INIT-STOCK-%')
                    ->first();

                if ($existingJournal) {
                    $this->warn("Initial stock journal already exists for branch {$branch->name} (Ref: {$existingJournal->reference_number}). Skipping to prevent duplicate.");
                    continue;
                }

                $referenceNumber = 'JV-INIT-STOCK-' . strtoupper(substr(uniqid(), -6));
                
                $journal = JournalEntry::create([
                    'organization_id' => $organizationId,
                    'branch_id' => $branch->id,
                    'reference_number' => $referenceNumber,
                    'entry_date' => now(),
                    'description' => 'Saldo Awal Persediaan Barang - Cabang ' . $branch->name,
                    'status' => 'posted',
                    'created_by' => null, // System
                ]);

                // DEBIT: Persediaan Barang (1140)
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $persediaanAccount->id,
                    'description' => 'Nilai persediaan awal barang',
                    'debit' => $totalValuation,
                    'credit' => 0,
                ]);

                // KREDIT: Modal Awal / Saldo Awal (3110)
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $modalAwalAccount->id,
                    'description' => 'Pencatatan modal/saldo awal persediaan',
                    'debit' => 0,
                    'credit' => $totalValuation,
                ]);

                $this->info("Created Journal {$referenceNumber} for Branch {$branch->name} with total valuation: " . number_format($totalValuation, 2));
            }
            DB::commit();
            $this->info('Completed generating initial stock journals!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('An error occurred: ' . $e->getMessage());
        }
    }
}
