<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\Branch;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

class SaldoAwalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::first();
        if (!$organization) {
            $this->command->warn('No organization found. Skipping Saldo Awal seeding.');
            return;
        }

        // Clean up previous saldo awal
        JournalEntry::where('organization_id', $organization->id)
            ->where('reference_number', 'LIKE', 'SALDO-AWAL%')
            ->delete(); // Cascades lines via DB foreign key or we manually delete lines

        // Accounts
        $kasAccount = Account::where('organization_id', $organization->id)->where('account_code', '1110')->first();
        $bankAccount = Account::where('organization_id', $organization->id)->where('account_code', '1120')->first();
        $modalAccount = Account::where('organization_id', $organization->id)->where('account_code', '3110')->first();

        if (!$kasAccount || !$modalAccount) {
            $this->command->warn('Required accounts (1110, 3110) not found. Run ChartOfAccountsSeeder first.');
            return;
        }

        $branches = Branch::where('organization_id', $organization->id)->get();
        
        DB::beginTransaction();
        try {
            // 1. Saldo Awal Pusat
            $journalPusat = JournalEntry::create([
                'organization_id' => $organization->id,
                'branch_id' => null,
                'reference_number' => 'SALDO-AWAL-PUSAT',
                'entry_date' => date('Y-01-01'),
                'description' => 'Pencatatan Saldo Awal Pusat',
                'status' => 'posted',
                'created_by' => 1,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journalPusat->id,
                'account_id' => $kasAccount->id,
                'description' => 'Saldo Awal Kas Pusat',
                'debit' => 50000000,
                'credit' => 0,
            ]);
            
            if ($bankAccount) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journalPusat->id,
                    'account_id' => $bankAccount->id,
                    'description' => 'Saldo Awal Bank Pusat',
                    'debit' => 150000000,
                    'credit' => 0,
                ]);
            }

            JournalEntryLine::create([
                'journal_entry_id' => $journalPusat->id,
                'account_id' => $modalAccount->id,
                'description' => 'Modal Awal Pusat',
                'debit' => 0,
                'credit' => 200000000, // 50m + 150m
            ]);

            // 2. Saldo Awal Cabang-cabang
            $amounts = [
                0 => 10000000, // Cabang 1 gets 10 juta
                1 => 15000000, // Cabang 2 gets 15 juta
            ];

            foreach ($branches as $index => $branch) {
                $amount = $amounts[$index] ?? 5000000;
                
                $journalBranch = JournalEntry::create([
                    'organization_id' => $organization->id,
                    'branch_id' => $branch->id,
                    'reference_number' => 'SALDO-AWAL-' . $branch->code,
                    'entry_date' => date('Y-01-01'),
                    'description' => 'Pencatatan Saldo Awal ' . $branch->name,
                    'status' => 'posted',
                    'created_by' => 1,
                ]);

                JournalEntryLine::create([
                    'journal_entry_id' => $journalBranch->id,
                    'account_id' => $kasAccount->id,
                    'description' => 'Saldo Awal Kas ' . $branch->name,
                    'debit' => $amount,
                    'credit' => 0,
                ]);

                JournalEntryLine::create([
                    'journal_entry_id' => $journalBranch->id,
                    'account_id' => $modalAccount->id,
                    'description' => 'Penyertaan Modal ' . $branch->name,
                    'debit' => 0,
                    'credit' => $amount,
                ]);
            }

            DB::commit();
            $this->command->info('Saldo Awal Seeder completed successfully for Pusat and ' . $branches->count() . ' Cabang!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error: ' . $e->getMessage());
        }
    }
}
