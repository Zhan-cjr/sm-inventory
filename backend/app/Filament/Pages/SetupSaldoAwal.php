<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Filament\Notifications\Notification;
use App\Models\Organization;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Account;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Get;

class SetupSaldoAwal extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-scale';
    protected static string|\UnitEnum|null $navigationGroup = 'AKUNTANSI';
    protected static ?string $navigationLabel = 'Setup Saldo Awal';
    protected static ?string $title = 'Setup Saldo Awal (Opening Balance)';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.setup-saldo-awal';

    public ?array $data = [];
    public float $total_debit = 0;
    public float $total_credit = 0;

    public function mount()
    {
        $user = auth()->user();
        $organization_id = $user->organization_id ?? Organization::first()?->id;
        $branch_id = null;
        $entry_date = date('Y') . '-01-01'; // Default to start of current year
        $lines = [];
        
        // Load existing Saldo Awal if any
        $existing = JournalEntry::where('organization_id', $organization_id)
            ->where('reference_number', 'LIKE', 'SALDO-AWAL%')
            ->with('lines')
            ->first();

        if ($existing) {
            $entry_date = $existing->entry_date->format('Y-m-d');
            $branch_id = $existing->branch_id;
            
            foreach ($existing->lines as $line) {
                $lines[] = [
                    'account_id' => $line->account_id,
                    'debit' => $line->debit,
                    'credit' => $line->credit,
                ];
            }
        } else {
            // Default lines (Kas, Bank, Modal)
            $accounts = Account::where('organization_id', $organization_id)
                ->whereIn('type', ['asset', 'liability', 'equity'])
                ->get();
                
            foreach ($accounts as $acc) {
                $lines[] = [
                    'account_id' => $acc->id,
                    'debit' => 0,
                    'credit' => 0,
                ];
            }
        }
        
        $this->form->fill([
            'organization_id' => $organization_id,
            'branch_id' => $branch_id,
            'entry_date' => $entry_date,
            'lines' => $lines,
        ]);
        
        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        $this->total_debit = 0;
        $this->total_credit = 0;
        
        $lines = $this->data['lines'] ?? [];
        foreach ($lines as $line) {
            $debitStr = str_replace('.', '', (string) ($line['debit'] ?? 0));
            $creditStr = str_replace('.', '', (string) ($line['credit'] ?? 0));
            $this->total_debit += (float) $debitStr;
            $this->total_credit += (float) $creditStr;
        }
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Informasi Dasar')->schema([
                Select::make('organization_id')
                    ->label('Perusahaan')
                    ->options(Organization::pluck('name', 'id'))
                    ->disabled(!(auth()->user()?->hasRole('super_admin')))
                    ->required(),
                    
                Select::make('branch_id')
                    ->label('Cabang (Opsional)')
                    ->options(Branch::pluck('name', 'id'))
                    ->nullable(),
                    
                DatePicker::make('entry_date')
                    ->label('Tanggal Saldo Awal')
                    ->required(),
            ])->columns(3),
            
            Section::make('Rincian Saldo Akun (Harta, Hutang, Modal)')->schema([
                Repeater::make('lines')
                    ->hiddenLabel()
                    ->schema([
                        Select::make('account_id')
                            ->label('Akun')
                            ->options(function ($get) {
                                return Account::where('organization_id', $get('../../organization_id'))
                                    ->whereIn('type', ['asset', 'liability', 'equity'])
                                    ->get()
                                    ->mapWithKeys(function ($account) {
                                        return [$account->id => $account->account_code . ' - ' . $account->name . ' (' . ucfirst($account->type) . ')'];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                            
                        TextInput::make('debit')
                            ->label('Debit (Harta bertambah)')
                            ->default(0)
                            ->prefix('Rp')
                            ->mask(\Filament\Support\RawJs::make("String(\$input).replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"))
                            ->stripCharacters('.')
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ((float) $state > 0) $set('credit', 0);
                            }),
                            
                        TextInput::make('credit')
                            ->label('Kredit (Hutang/Modal bertambah)')
                            ->default(0)
                            ->prefix('Rp')
                            ->mask(\Filament\Support\RawJs::make("String(\$input).replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"))
                            ->stripCharacters('.')
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ((float) $state > 0) $set('debit', 0);
                            }),
                    ])
                    ->columns(3)
                    ->addActionLabel('Tambah Baris Akun')
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->calculateTotals();
                    }),
            ])
        ])->statePath('data');
    }

    public function save()
    {
        $data = $this->form->getState();
        $this->calculateTotals();
        
        // Validation
        if (round($this->total_debit, 2) !== round($this->total_credit, 2)) {
            Notification::make()
                ->title('Gagal Disimpan')
                ->body('Total Debit (Rp ' . number_format($this->total_debit, 0, ',', '.') . ') tidak sama dengan Total Kredit (Rp ' . number_format($this->total_credit, 0, ',', '.') . '). Selisih: Rp ' . number_format(abs($this->total_debit - $this->total_credit), 0, ',', '.'))
                ->danger()
                ->send();
            return;
        }

        DB::beginTransaction();
        try {
            $orgId = $data['organization_id'];
            $branchId = $data['branch_id'] ?? null;
            $entryDate = $data['entry_date'];
            
            // Branch suffix for ref number
            $branchCode = $branchId ? ('-' . Branch::find($branchId)->code) : '-PUSAT';
            $refNumber = "SALDO-AWAL" . $branchCode;

            // Delete existing saldo awal for this branch
            $existing = JournalEntry::where('organization_id', $orgId)
                ->where('reference_number', $refNumber)
                ->first();
                
            if ($existing) {
                $existing->lines()->delete();
                $existing->delete();
            }

            // Create new journal
            $journal = JournalEntry::create([
                'organization_id' => $orgId,
                'branch_id' => $branchId,
                'reference_number' => $refNumber,
                'entry_date' => $entryDate,
                'description' => 'Pencatatan Saldo Awal',
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            $lines = $data['lines'] ?? [];
            foreach ($lines as $line) {
                $debitVal = (float) str_replace('.', '', (string) ($line['debit'] ?? 0));
                $creditVal = (float) str_replace('.', '', (string) ($line['credit'] ?? 0));
                
                if ($debitVal == 0 && $creditVal == 0) continue;
                
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $line['account_id'],
                    'description' => 'Saldo Awal',
                    'debit' => $debitVal,
                    'credit' => $creditVal,
                ]);
            }
            
            DB::commit();
            
            Notification::make()
                ->title('Berhasil Disimpan')
                ->body('Jurnal Saldo Awal berhasil disimpan dan Neraca awal telah diseimbangkan.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
        }
    }
}
