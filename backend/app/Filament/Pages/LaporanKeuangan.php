<?php
// OPCACHE INVALIDATE 1

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\JournalEntryLine;
use App\Models\Organization;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class LaporanKeuangan extends Page implements HasForms
{
    use InteractsWithForms;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Laporan Keuangan';
    protected static ?string $title = 'Laporan Keuangan';
    protected static string|\UnitEnum|null $navigationGroup = 'AKUNTANSI';
    protected static ?int $navigationSort = 3;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('print')
                ->label('Cetak Laporan')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->action(function () {
                    $data = $this->form->getState();
                    $url = route('print.report', [
                        'type' => 'laporan_keuangan',
                        'start_date' => $data['start_date'] ?? $this->start_date,
                        'end_date' => $data['end_date'] ?? $this->end_date,
                        'branch_id' => $data['branch_id'] ?? $this->branch_id,
                    ]);
                    $this->js("window.open('{$url}', '_blank');");
                })
        ];
    }

    protected string $view = 'filament.pages.laporan-keuangan';

    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?string $organization_id = null;
    public ?string $branch_id = null;

    public function mount()
    {
        $this->start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->end_date = Carbon::now()->endOfMonth()->format('Y-m-d');
        
        $user = auth()->user();
        if ($user && $user->organization_id) {
            $this->organization_id = $user->organization_id;
        } else {
            $this->organization_id = Organization::first()?->id;
        }
        
        if ($user && $user->branch_id) {
            $this->branch_id = $user->branch_id;
        }
        
        $this->form->fill([
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Select::make('organization_id')
                ->label('Perusahaan')
                ->options(Organization::pluck('name', 'id'))
                ->disabled(!(auth()->user()?->hasRole('super_admin')))
                ->live(),
            Select::make('branch_id')
                ->label('Cabang (Opsional)')
                ->options(\App\Models\Branch::pluck('name', 'id'))
                ->disabled(auth()->user() && auth()->user()->branch_id !== null)
                ->live(),
            DatePicker::make('start_date')
                ->label('Dari Tanggal')
                ->live()
                ->required(),
            DatePicker::make('end_date')
                ->label('Sampai Tanggal')
                ->live()
                ->required(),
        ])->columns(4);
    }

    public function updateFilter()
    {
        $data = $this->form->getState();
        $this->start_date = $data['start_date'] ?? $this->start_date;
        $this->end_date = $data['end_date'] ?? $this->end_date;
        $this->organization_id = $data['organization_id'] ?? $this->organization_id;
        $this->branch_id = $data['branch_id'] ?? null;
    }

    public function printReport()
    {
        // Pastikan state form terbaru
        $data = $this->form->getState();
        $url = route('print.report', [
            'type' => 'laporan_keuangan',
            'start_date' => $data['start_date'] ?? $this->start_date,
            'end_date' => $data['end_date'] ?? $this->end_date,
            'branch_id' => $data['branch_id'] ?? $this->branch_id,
        ]);
        
        $this->dispatch('open-print-url', url: $url);
    }

    protected function getViewData(): array
    {
        if (!$this->organization_id) {
            return ['accountBalances' => [], 'netProfit' => 0];
        }

        // Ambil semua akun untuk organisasi ini (termasuk non-aktif agar balance tidak selisih jika ada riwayat)
        $accounts = Account::where('organization_id', $this->organization_id)
            ->orderBy('account_code')
            ->get();

        // Hitung Saldo per Akun berdasarkan Journal Entries
        $accountBalances = [];
        $netProfit = 0;
        $retainedEarnings = 0;

        foreach ($accounts as $account) {
            // 1. Current Period (untuk Laba Rugi)
            $linesCurrent = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($query) {
                    $query->where('status', 'posted');
                    if ($this->start_date) {
                        $query->whereDate('entry_date', '>=', $this->start_date);
                    }
                    if ($this->end_date) {
                        $query->whereDate('entry_date', '<=', $this->end_date);
                    }
                    if ($this->branch_id) {
                        $query->where('branch_id', $this->branch_id);
                    }
                })
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();

            $debitCurrent = $linesCurrent->total_debit ?? 0;
            $creditCurrent = $linesCurrent->total_credit ?? 0;

            // 2. Prior Period (untuk Laba Ditahan dari Laba Rugi masa lalu)
            if (in_array($account->type, ['revenue', 'expense']) && $this->start_date) {
                $linesPrior = JournalEntryLine::where('account_id', $account->id)
                    ->whereHas('journalEntry', function ($query) {
                        $query->where('status', 'posted');
                        $query->whereDate('entry_date', '<', $this->start_date);
                        if ($this->branch_id) {
                            $query->where('branch_id', $this->branch_id);
                        }
                    })
                    ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                    ->first();
                $debitPrior = $linesPrior->total_debit ?? 0;
                $creditPrior = $linesPrior->total_credit ?? 0;

                if ($account->type === 'revenue') {
                    $retainedEarnings += ($creditPrior - $debitPrior);
                } else { // expense
                    $retainedEarnings -= ($debitPrior - $creditPrior);
                }
            }

            // 3. Total Balance (untuk Neraca)
            $linesTotal = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($query) {
                    $query->where('status', 'posted');
                    if ($this->end_date) {
                        $query->whereDate('entry_date', '<=', $this->end_date);
                    }
                    if ($this->branch_id) {
                        $query->where('branch_id', $this->branch_id);
                    }
                })
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();

            $debitTotal = $linesTotal->total_debit ?? 0;
            $creditTotal = $linesTotal->total_credit ?? 0;

            $balance = 0;
            // Saldo Normal: Asset & Expense -> Debit, Liability, Equity, Revenue -> Credit
            if (in_array($account->type, ['asset', 'expense'])) {
                if ($account->type === 'asset') {
                    $balance = $debitTotal - $creditTotal; // Neraca mengambil saldo akumulasi total
                } else {
                    $balance = $debitCurrent - $creditCurrent; // Laba Rugi mengambil saldo periode ini
                }
            } else {
                if (in_array($account->type, ['liability', 'equity'])) {
                    $balance = $creditTotal - $debitTotal; // Neraca mengambil saldo akumulasi total
                } else {
                    $balance = $creditCurrent - $debitCurrent; // Laba Rugi mengambil saldo periode ini
                }
            }

            if ($account->type === 'revenue') {
                $netProfit += $balance;
            } elseif ($account->type === 'expense') {
                $netProfit -= $balance;
            }

            $accountBalances[$account->id] = [
                'account' => $account,
                'debit' => $debitCurrent, // Info debit periode ini
                'credit' => $creditCurrent, // Info credit periode ini
                'balance' => $balance
            ];
        }

        return [
            'accountBalances' => $accountBalances,
            'netProfit' => $netProfit,
            'retainedEarnings' => $retainedEarnings,
            'startDate' => $this->start_date,
            'endDate' => $this->end_date,
            'branchId' => $this->branch_id,
        ];
    }
}
