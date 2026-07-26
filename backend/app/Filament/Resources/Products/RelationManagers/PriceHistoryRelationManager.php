<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PriceHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';
    protected static ?string $title = 'Riwayat Perubahan Harga Cabang';
    protected static string|\BackedEnum|null $icon = 'heroicon-o-currency-dollar';

    public function table(Table $table): Table
    {
        $product = $this->getOwnerRecord();
        $userBranchId = auth()->user()->branch_id ?? null;

        $stockIds = \App\Models\Stock::where('product_id', $product->id)
            ->when($userBranchId, fn($q) => $q->where('branch_id', $userBranchId))
            ->pluck('id');

        $query = \App\Models\Activity::query()
            ->where(function ($q) use ($product, $stockIds) {
                $q->where(function ($sub) use ($stockIds) {
                    $sub->where('subject_type', 'App\Models\Stock')
                        ->whereIn('subject_id', $stockIds);
                })->orWhere(function ($sub) use ($product) {
                    $sub->where('subject_type', 'App\Models\Product')
                        ->where('subject_id', $product->id);
                });
            })
            ->where(function ($q) {
                $q->where('properties', 'like', '%selling_price%')
                    ->orWhere('properties', 'like', '%cost_price%')
                    ->orWhere('properties', 'like', '%price%')
                    ->orWhere('properties', 'like', '%harga_jual_1%')
                    ->orWhere('properties', 'like', '%harga_jual_2%')
                    ->orWhere('properties', 'like', '%harga_jual_3%');
            })
            ->latest('created_at');

        return $table
            ->query($query)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu Perubahan')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),

                TextColumn::make('branch_name')
                    ->label('Cabang')
                    ->state(function ($record) {
                        if ($record->subject_type === 'App\Models\Stock') {
                            $stock = \App\Models\Stock::with('branch')->find($record->subject_id);
                            return $stock?->branch?->name ?? 'Cabang';
                        }
                        return 'Semua Cabang (Master)';
                    })
                    ->badge()
                    ->color('info'),

                TextColumn::make('causer.name')
                    ->label('Operator')
                    ->default('Sistem / Otomatis'),

                TextColumn::make('price_diff')
                    ->label('Perubahan Harga (Lama -> Baru)')
                    ->html()
                    ->state(function ($record) {
                        $props = is_array($record->properties) ? $record->properties : (json_decode(json_encode($record->properties ?? []), true) ?? []);
                        $old = $props['old'] ?? [];
                        $new = $props['attributes'] ?? [];

                        $priceFields = [
                            'selling_price' => 'Harga Jual Eceran',
                            'cost_price' => 'HPP / Modal',
                            'harga_jual_1' => 'Harga Grosir 1',
                            'harga_jual_2' => 'Harga Grosir 2',
                            'harga_jual_3' => 'Harga Grosir 3',
                            'price' => 'Harga Jual Master',
                        ];

                        $diffs = [];
                        foreach ($priceFields as $key => $label) {
                            if (array_key_exists($key, $old) || array_key_exists($key, $new)) {
                                $oldVal = isset($old[$key]) ? 'Rp ' . number_format((float)$old[$key], 0, ',', '.') : '-';
                                $newVal = isset($new[$key]) ? 'Rp ' . number_format((float)$new[$key], 0, ',', '.') : '-';
                                $diffs[] = "<div style='margin-bottom: 2px;'><strong>{$label}:</strong> <span style='color: #e11d48;'>{$oldVal}</span> &rarr; <span style='color: #059669; font-weight: 700;'>{$newVal}</span></div>";
                            }
                        }

                        return !empty($diffs) ? implode('', $diffs) : '<span style="color: #64748b;">Detail Diubah</span>';
                    }),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
