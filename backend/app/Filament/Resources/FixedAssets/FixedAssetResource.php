<?php

namespace App\Filament\Resources\FixedAssets;

use App\Filament\Resources\FixedAssets\Pages\ManageFixedAssets;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciation;
use App\Models\Account;
use App\Models\Organization;
use App\Models\Branch;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FixedAssetResource extends Resource
{
    protected static ?string $model = FixedAsset::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';
    protected static \UnitEnum|string|null $navigationGroup = 'AKUNTANSI';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Aset Tetap (Fixed Assets)';
    protected static ?string $pluralModelLabel = 'Aset Tetap';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Grid::make(3)->schema([
                    \Filament\Schemas\Components\Group::make()->schema([
                        Section::make('Informasi Dasar')->schema([
                            TextInput::make('asset_code')
                                ->label('Kode Aset')
                                ->default(function () {
                                    $count = FixedAsset::count() + 1;
                                    return 'AST-' . str_pad($count, 4, '0', STR_PAD_LEFT);
                                })
                                ->required()
                                ->unique(ignoreRecord: true),
                                
                            TextInput::make('name')
                                ->label('Nama Aset')
                                ->required()
                                ->maxLength(255),
                                
                            DatePicker::make('purchase_date')
                                ->label('Tanggal Pembelian')
                                ->default(now())
                                ->required(),
                                
                            Textarea::make('description')
                                ->label('Keterangan')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])->columns(2),
                        
                        Section::make('Pemetaan Akun (Accounting)')->schema([
                            Select::make('asset_account_id')
                                ->label('Akun Aset Tetap')
                                ->options(fn () => Account::where('type', 'asset')->pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                                
                            Select::make('accumulated_depreciation_account_id')
                                ->label('Akun Akumulasi Penyusutan')
                                ->options(fn () => Account::whereIn('type', ['asset', 'liability', 'equity'])->pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                                
                            Select::make('depreciation_expense_account_id')
                                ->label('Akun Beban Penyusutan')
                                ->options(fn () => Account::where('type', 'expense')->pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                                
                            Select::make('payment_account_id')
                                ->label('Dibayar Melalui (Opsional)')
                                ->helperText('Jika diisi, sistem akan otomatis membuat Jurnal Pembelian.')
                                ->options(fn () => Account::whereIn('type', ['asset', 'liability'])->pluck('name', 'id'))
                                ->searchable(),
                        ])->columns(2),
                    ])->columnSpan(2),

                    \Filament\Schemas\Components\Group::make()->schema([
                        Section::make('Nilai & Penyusutan')->schema([
                            TextInput::make('purchase_price')
                                ->label('Harga Perolehan')
                                ->prefix('Rp')
                                ->numeric()
                                ->mask(\Filament\Support\RawJs::make('$money($input, \',\', \'.\', 0)'))
                                ->stripCharacters('.')
                                ->required(),
                                
                            TextInput::make('salvage_value')
                                ->label('Nilai Sisa (Residu)')
                                ->prefix('Rp')
                                ->numeric()
                                ->default(0)
                                ->mask(\Filament\Support\RawJs::make('$money($input, \',\', \'.\', 0)'))
                                ->stripCharacters('.')
                                ->required(),
                                
                            TextInput::make('useful_life_years')
                                ->label('Umur Ekonomis (Tahun)')
                                ->numeric()
                                ->default(4)
                                ->required(),
                        ])->columns(1),
                    ])->columnSpan(1),
                ]),
                
                Hidden::make('organization_id')->default(fn () => auth()->user()?->organization_id ?? Organization::first()?->id),
                Hidden::make('branch_id')->default(fn () => auth()->user()?->branch_id),
                Hidden::make('created_by')->default(fn () => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('asset_code')->label('Kode')->searchable(),
                TextColumn::make('name')->label('Nama Aset')->searchable(),
                TextColumn::make('purchase_date')->label('Tgl Beli')->date('d M Y'),
                TextColumn::make('purchase_price')->label('Harga Beli')->money('IDR', true),
                TextColumn::make('useful_life_years')->label('Umur (Thn)'),
                TextColumn::make('book_value')
                    ->label('Nilai Buku')
                    ->money('IDR', true)
                    ->state(function (FixedAsset $record) {
                        $accumulated = $record->depreciations()->sum('depreciation_amount');
                        return $record->purchase_price - $accumulated;
                    }),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('runDepreciation')
                    ->label('Jalankan Penyusutan Bulan Ini')
                    ->icon('heroicon-o-calculator')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Hitung Penyusutan Aset')
                    ->modalDescription('Tindakan ini akan menghitung beban penyusutan untuk semua Aset Tetap aktif pada bulan ini, dan secara otomatis membuat Jurnal Akuntansinya. Lanjutkan?')
                    ->action(function () {
                        $assets = FixedAsset::where('is_active', true)->get();
                        $period = date('Y-m');
                        $count = 0;
                        
                        DB::beginTransaction();
                        try {
                            $accountingService = app(AccountingService::class);
                            
                            foreach ($assets as $asset) {
                                // Check if already depreciated this month
                                $exists = FixedAssetDepreciation::where('fixed_asset_id', $asset->id)
                                    ->where('period', $period)->exists();
                                    
                                if ($exists) continue;
                                
                                // Calculate straight-line depreciation
                                $depreciationAmount = $asset->monthly_depreciation_amount;
                                if ($depreciationAmount <= 0) continue;
                                
                                // Stop depreciating if book value reaches salvage value
                                $currentAccumulated = $asset->depreciations()->sum('depreciation_amount');
                                $currentBookValue = $asset->purchase_price - $currentAccumulated;
                                
                                if ($currentBookValue <= $asset->salvage_value) continue;
                                
                                // Adjust final month
                                if (($currentBookValue - $depreciationAmount) < $asset->salvage_value) {
                                    $depreciationAmount = $currentBookValue - $asset->salvage_value;
                                }
                                
                                // Create Journal
                                $journal = \App\Models\JournalEntry::create([
                                    'organization_id' => $asset->organization_id,
                                    'branch_id' => $asset->branch_id,
                                    'reference_number' => 'DEP-' . $period . '-' . strtoupper(Str::random(6)),
                                    'entry_date' => now(),
                                    'description' => 'Penyusutan Aset: ' . $asset->name . ' (' . $period . ')',
                                    'status' => 'posted',
                                    'created_by' => auth()->id(),
                                ]);
                                
                                \App\Models\JournalEntryLine::create([
                                    'journal_entry_id' => $journal->id,
                                    'account_id' => $asset->depreciation_expense_account_id,
                                    'description' => 'Beban Penyusutan',
                                    'debit' => $depreciationAmount,
                                    'credit' => 0,
                                ]);
                                
                                \App\Models\JournalEntryLine::create([
                                    'journal_entry_id' => $journal->id,
                                    'account_id' => $asset->accumulated_depreciation_account_id,
                                    'description' => 'Akumulasi Penyusutan',
                                    'debit' => 0,
                                    'credit' => $depreciationAmount,
                                ]);
                                
                                FixedAssetDepreciation::create([
                                    'fixed_asset_id' => $asset->id,
                                    'period' => $period,
                                    'depreciation_amount' => $depreciationAmount,
                                    'accumulated_amount' => $currentAccumulated + $depreciationAmount,
                                    'book_value' => $asset->purchase_price - ($currentAccumulated + $depreciationAmount),
                                    'journal_entry_id' => $journal->id,
                                    'created_by' => auth()->id(),
                                ]);
                                
                                $count++;
                            }
                            
                            DB::commit();
                            Notification::make()->title("Berhasil menyusutkan $count aset!")->success()->send();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                        }
                    })
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data): array {
                        $data['purchase_price'] = number_format($data['purchase_price'], 0, '', '');
                        $data['salvage_value'] = number_format($data['salvage_value'], 0, '', '');
                        return $data;
                    })
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['purchase_price'] = (float) str_replace('.', '', (string) $data['purchase_price']);
                        $data['salvage_value'] = (float) str_replace('.', '', (string) $data['salvage_value']);
                        return $data;
                    }),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFixedAssets::route('/'),
        ];
    }
}
