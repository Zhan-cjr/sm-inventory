<?php

namespace App\Filament\Resources\Promotions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PromotionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('organization.name')
                    ->label('Organisasi')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->label('Nama Promosi')
                    ->searchable(),
                TextColumn::make('promo_type')
                    ->label('Jenis Promosi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PERCENTAGE' => 'success',
                        'FIXED' => 'info',
                        'PERCENTAGE_PER_ITEM' => 'success',
                        'NOMINAL_PER_ITEM' => 'info',
                        'BUNDLING' => 'warning',
                        'TIERED' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'PERCENTAGE' => 'Persentase Fixed',
                        'FIXED' => 'Nominal Fixed',
                        'PERCENTAGE_PER_ITEM' => 'Persentase Per Item',
                        'NOMINAL_PER_ITEM' => 'Nominal Per Item',
                        'BUNDLING' => 'Bundling (Beli X Gratis Y)',
                        'TIERED' => 'Diskon Bertingkat',
                        default => $state,
                    })
                    ->searchable(),
                TextColumn::make('discount_value')
                    ->label('Nilai Diskon')
                    ->formatStateUsing(function ($state, $record) {
                        if (in_array($record->promo_type, ['BUNDLING', 'TIERED'])) {
                            return '-';
                        }
                        if (in_array($record->promo_type, ['FIXED', 'NOMINAL_PER_ITEM'])) {
                            return 'Rp ' . number_format($state, 0, ',', '.');
                        }
                        return number_format($state, 0) . '%';
                    })
                    ->sortable(),
                TextColumn::make('min_purchase_amount')
                    ->label('Minimal Pembelian')
                    ->money('IDR')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('applicable_to')
                    ->label('Berlaku Untuk')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ALL' => 'Semua Produk',
                        'PRODUCT' => 'Produk Tertentu',
                        'CATEGORY' => 'Kategori Tertentu',
                        default => $state,
                    })
                    ->searchable(),
                TextColumn::make('promo_config')
                    ->label('Kriteria Tambahan')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        
                        $lines = [];
                        if (!empty($state['member_tiers'])) {
                            $lines[] = 'Member: ' . implode(', ', (array) $state['member_tiers']);
                        }
                        if (!empty($state['applicable_days'])) {
                            $daysMap = [
                                'MONDAY' => 'Senin',
                                'TUESDAY' => 'Selasa',
                                'WEDNESDAY' => 'Rabu',
                                'THURSDAY' => 'Kamis',
                                'FRIDAY' => 'Jumat',
                                'SATURDAY' => 'Sabtu',
                                'SUNDAY' => 'Minggu',
                            ];
                            $translatedDays = array_map(fn ($d) => $daysMap[$d] ?? $d, (array) $state['applicable_days']);
                            $lines[] = 'Hari: ' . implode(', ', $translatedDays);
                        }
                        
                        
                        return count($lines) > 0 ? implode(' | ', $lines) : '-';
                    })
                    ->wrap()
                    ->placeholder('-'),
                TextColumn::make('valid_from')
                    ->label('Berlaku Dari')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('valid_until')
                    ->label('Berlaku Sampai')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('branches.name')
                    ->label('Berlaku di Cabang')
                    ->badge()
                    ->separator(',')
                    ->searchable()
                    ->placeholder('Semua Cabang / Tidak Ditentukan'),
                TextColumn::make('max_discount_per_transaction')
                    ->label('Maksimal Diskon')
                    ->money('IDR')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Status Aktif')
                    ->boolean(),
                TextColumn::make('supplier.name')
                    ->label('Tanggungan Supplier')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                IconColumn::make('is_settled')
                    ->label('Settled')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
