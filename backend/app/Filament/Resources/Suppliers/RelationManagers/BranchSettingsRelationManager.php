<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Models\SupplierBranchSetting;
use App\Models\SupplierDivision;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class BranchSettingsRelationManager extends RelationManager
{
    protected static string $relationship = 'branchSettings';

    protected static ?string $title = 'Siklus & Lead Time Per Cabang';

    protected static ?string $recordTitleAttribute = 'branch.name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('supplier_division_id')
                    ->label('Sub-Divisi Supplier (Opsional)')
                    ->placeholder('Semua Sub-Divisi (Berlaku Umum di Cabang Ini)')
                    ->options(function (RelationManager $livewire) {
                        $supplier = $livewire->getOwnerRecord();
                        if (!$supplier) {
                            return [];
                        }
                        return SupplierDivision::where('supplier_id', $supplier->id)
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->helperText('Kosongkan jika pengaturan cabang ini berlaku untuk semua divisi dari supplier ini.'),
                Forms\Components\TextInput::make('inventory_days')
                    ->label('Target Inventori / Siklus Kunjungan (Hari)')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(180)
                    ->default(fn (RelationManager $livewire) => $livewire->getOwnerRecord()?->default_inventory_days ?? 14)
                    ->suffix('Hari')
                    ->helperText('Panduan: 7 = Mingguan, 14 = 2 Mingguan, 21 = 3 Mingguan, 30 = Bulanan.'),
                Forms\Components\TextInput::make('lead_time_days')
                    ->label('Lead Time Pengiriman (Hari)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(90)
                    ->placeholder(fn (RelationManager $livewire) => 'Ikut Default (' . ($livewire->getOwnerRecord()?->default_lead_time_days ?? 3) . ' Hari)')
                    ->suffix('Hari')
                    ->helperText('Kosongkan jika waktu pengiriman sama dengan default supplier/divisi.'),
                Forms\Components\TextInput::make('notes')
                    ->label('Catatan Jadwal Kunjungan Sales')
                    ->placeholder('Contoh: Sales datang 2 minggu sekali setiap hari Selasa')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description('Gunakan pengaturan ini jika jadwal kedatangan sales / target cover stok di cabang tertentu berbeda dari pengaturan default supplier.')
            ->columns([
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('division.name')
                    ->label('Sub-Divisi')
                    ->placeholder('Semua Sub-Divisi')
                    ->badge()
                    ->color(fn ($record) => $record->supplier_division_id ? 'info' : 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('inventory_days')
                    ->label('Target Inventori (Siklus)')
                    ->formatStateUsing(function ($state) {
                        $label = match ((int) $state) {
                            7 => '7 Hari (1 Minggu)',
                            14 => '14 Hari (2 Minggu)',
                            21 => '21 Hari (3 Minggu)',
                            30 => '30 Hari (1 Bulan)',
                            default => "{$state} Hari",
                        };
                        return $label;
                    })
                    ->badge()
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lead_time_days')
                    ->label('Lead Time')
                    ->formatStateUsing(fn ($state, $record) => $state ? "{$state} Hari" : 'Ikut Default (' . ($record->division?->lead_time_days ?? $record->supplier?->default_lead_time_days ?? 3) . ' Hr)')
                    ->badge()
                    ->color(fn ($state) => $state ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan Jadwal')
                    ->placeholder('-')
                    ->limit(50),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Pengaturan Cabang')
                    ->modalHeading('Tambah Siklus & Lead Time Khusus Cabang')
                    ->visible(fn () => (bool) auth()->user()?->hasCustomAuthorization('EDIT_SUPPLIER_CYCLE'))
                    ->using(function (array $data, RelationManager $livewire): SupplierBranchSetting {
                        $supplier = $livewire->getOwnerRecord();
                        $existing = SupplierBranchSetting::where('supplier_id', $supplier->id)
                            ->where('branch_id', $data['branch_id'])
                            ->where(function ($q) use ($data) {
                                if (!empty($data['supplier_division_id'])) {
                                    $q->where('supplier_division_id', $data['supplier_division_id']);
                                } else {
                                    $q->whereNull('supplier_division_id');
                                }
                            })
                            ->first();

                        if ($existing) {
                            $existing->update($data);
                            Notification::make()
                                ->title('Pengaturan cabang yang sudah ada telah diperbarui.')
                                ->info()
                                ->send();
                            return $existing;
                        }

                        return $supplier->branchSettings()->create($data);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => (bool) auth()->user()?->hasCustomAuthorization('EDIT_SUPPLIER_CYCLE')),
                DeleteAction::make()
                    ->visible(fn () => (bool) auth()->user()?->hasCustomAuthorization('EDIT_SUPPLIER_CYCLE')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => (bool) auth()->user()?->hasCustomAuthorization('EDIT_SUPPLIER_CYCLE')),
                ]),
            ]);
    }
}

