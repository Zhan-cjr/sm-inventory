<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Filament\Notifications\Notification;
use App\Models\Organization;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class TutupBukuTahunan extends Page implements HasForms
{
    use InteractsWithForms;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';
    protected static string|\UnitEnum|null $navigationGroup = 'AKUNTANSI';
    protected static ?string $navigationLabel = 'Tutup Buku Tahunan';
    protected static ?string $title = 'Proses Tutup Buku Tahunan';
    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.tutup-buku-tahunan';

    public ?string $organization_id = null;
    public ?string $year = null;

    public function mount()
    {
        $user = auth()->user();
        if ($user && $user->organization_id) {
            $this->organization_id = $user->organization_id;
        } else {
            $this->organization_id = Organization::first()?->id;
        }
        
        $this->year = date('Y') - 1; // Default to previous year
        
        $this->form->fill([
            'organization_id' => $this->organization_id,
            'year' => $this->year,
        ]);
    }

    public function form(Schema $form): Schema
    {
        // Get years from existing journals
        $years = JournalEntry::selectRaw('YEAR(entry_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year', 'year')
            ->toArray();
            
        if (empty($years)) {
            $years = [date('Y') => date('Y')];
        }

        return $form->schema([
            Select::make('organization_id')
                ->label('Perusahaan')
                ->options(Organization::pluck('name', 'id'))
                ->disabled(!(auth()->user()?->hasRole('super_admin')))
                ->live()
                ->required(),
                
            Select::make('year')
                ->label('Tahun Tutup Buku')
                ->options($years)
                ->helperText('Seluruh saldo akun Pendapatan dan Pengeluaran di tahun ini akan dipindahkan ke Laba Ditahan.')
                ->required(),
        ])->columns(2);
    }

    public function prosesTutupBuku()
    {
        $data = $this->form->getState();
        $orgId = $data['organization_id'];
        $year = $data['year'];
        
        $startDate = "{$year}-01-01";
        $endDate = "{$year}-12-31";
        
        // Find Retained Earnings Account
        $retainedEarningsAcc = Account::where('organization_id', $orgId)
            ->where('account_code', '3120') // Laba Ditahan
            ->first();
            
        if (!$retainedEarningsAcc) {
            Notification::make()->title('Gagal')->body('Akun Laba Ditahan (3120) tidak ditemukan!')->danger()->send();
            return;
        }

        DB::beginTransaction();
        try {
            // Delete previous closing entry if any
            $refNumber = "TUTUP-BUKU-{$year}";
            $existing = JournalEntry::where('organization_id', $orgId)->where('reference_number', $refNumber)->first();
            if ($existing) {
                $existing->lines()->delete();
                $existing->delete();
            }
            
            // Get all Revenue and Expense balances for the year
            // Exclude the closing entry itself (which we just deleted anyway)
            $balances = DB::table('journal_entry_lines')
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
                ->where('journal_entries.organization_id', $orgId)
                ->where('journal_entries.status', 'posted')
                ->whereBetween('journal_entries.entry_date', [$startDate, $endDate])
                ->whereIn('accounts.type', ['revenue', 'expense'])
                ->select(
                    'accounts.id',
                    'accounts.type',
                    'accounts.name',
                    DB::raw('SUM(journal_entry_lines.debit) as total_debit'),
                    DB::raw('SUM(journal_entry_lines.credit) as total_credit')
                )
                ->groupBy('accounts.id', 'accounts.type', 'accounts.name')
                ->get();
                
            if ($balances->isEmpty()) {
                DB::rollBack();
                Notification::make()->title('Info')->body("Tidak ada transaksi Pendapatan/Pengeluaran di tahun {$year}")->info()->send();
                return;
            }

            // Create Closing Journal Entry on Dec 31 of that year
            $journal = JournalEntry::create([
                'organization_id' => $orgId,
                'reference_number' => $refNumber,
                'entry_date' => "{$year}-12-31",
                'description' => "Jurnal Penutup Tahun {$year}",
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            $totalRetainedEarningsCredit = 0;
            $totalRetainedEarningsDebit = 0;

            foreach ($balances as $acc) {
                $netBalance = $acc->total_credit - $acc->total_debit; // For revenue, positive is credit balance. For expense, negative is debit balance.
                
                if ($netBalance == 0) continue;
                
                if ($acc->type === 'revenue') {
                    if ($netBalance > 0) {
                        // Revenue has credit balance, need to DEBIT to close it
                        JournalEntryLine::create([
                            'journal_entry_id' => $journal->id,
                            'account_id' => $acc->id,
                            'description' => 'Penutupan akun pendapatan',
                            'debit' => $netBalance,
                            'credit' => 0,
                        ]);
                        $totalRetainedEarningsCredit += $netBalance;
                    } else {
                        // Abnormal revenue balance (debit), need to CREDIT to close it
                        JournalEntryLine::create([
                            'journal_entry_id' => $journal->id,
                            'account_id' => $acc->id,
                            'description' => 'Penutupan akun pendapatan',
                            'debit' => 0,
                            'credit' => abs($netBalance),
                        ]);
                        $totalRetainedEarningsDebit += abs($netBalance);
                    }
                } else if ($acc->type === 'expense') {
                    if ($netBalance < 0) {
                        // Expense has debit balance, need to CREDIT to close it
                        JournalEntryLine::create([
                            'journal_entry_id' => $journal->id,
                            'account_id' => $acc->id,
                            'description' => 'Penutupan akun pengeluaran',
                            'debit' => 0,
                            'credit' => abs($netBalance),
                        ]);
                        $totalRetainedEarningsDebit += abs($netBalance);
                    } else {
                        // Abnormal expense balance (credit), need to DEBIT to close it
                        JournalEntryLine::create([
                            'journal_entry_id' => $journal->id,
                            'account_id' => $acc->id,
                            'description' => 'Penutupan akun pengeluaran',
                            'debit' => $netBalance,
                            'credit' => 0,
                        ]);
                        $totalRetainedEarningsCredit += $netBalance;
                    }
                }
            }

            // Finally, plug the difference to Retained Earnings
            $netProfit = $totalRetainedEarningsCredit - $totalRetainedEarningsDebit;
            
            if ($netProfit > 0) {
                // Profit, credit Retained Earnings
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $retainedEarningsAcc->id,
                    'description' => "Laba Bersih Tahun {$year}",
                    'debit' => 0,
                    'credit' => $netProfit,
                ]);
            } else if ($netProfit < 0) {
                // Loss, debit Retained Earnings
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $retainedEarningsAcc->id,
                    'description' => "Rugi Bersih Tahun {$year}",
                    'debit' => abs($netProfit),
                    'credit' => 0,
                ]);
            }
            
            DB::commit();
            
            Notification::make()
                ->title('Berhasil')
                ->body("Tutup Buku Tahun {$year} berhasil! Laba/Rugi sebesar Rp " . number_format(abs($netProfit), 0, ',', '.') . " telah dipindahkan ke Laba Ditahan.")
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
        }
    }
}
