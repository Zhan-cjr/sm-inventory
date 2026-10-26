<?php

namespace App\Filament\Exports;

use App\Models\Stock;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class StockExporter extends Exporter
{
    protected static ?string $model = Stock::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->whereHas('product', fn ($q) => $q->where(fn ($sub) => $sub->where('product_type', '!=', 'digital')->orWhereNull('product_type')));
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('branch.name')->label('Cabang'),
            ExportColumn::make('product.sku')->label('SKU'),
            ExportColumn::make('product.barcode')->label('Barcode'),
            ExportColumn::make('product.name')->label('Nama Barang'),
            ExportColumn::make('product.category.name')->label('Kategori'),
            ExportColumn::make('salable_quantity')
                ->label('Stok Layak Jual')
                ->state(fn (Stock $record) => max(0, (float)$record->quantity_on_hand - (float)($record->quantity_damaged ?? 0))),
            ExportColumn::make('quantity_damaged')
                ->label('Stok BS (Karantina)')
                ->state(fn (Stock $record) => (float)($record->quantity_damaged ?? 0)),
            ExportColumn::make('quantity_on_hand')
                ->label('Total Stok Fisik')
                ->state(fn (Stock $record) => (float)$record->quantity_on_hand),
            ExportColumn::make('product.cost_price_tax')
                ->label('Harga Pokok')
                ->state(function (Stock $record) {
                    if ($record->quantity_on_hand > 0 && isset($record->batch_valuation) && $record->batch_valuation > 0) {
                        return $record->batch_valuation / $record->quantity_on_hand;
                    }
                    return $record->cost_price_tax > 0 ? $record->cost_price_tax : ($record->product->cost_price_tax ?? $record->product->cost_price ?? 0);
                }),
            ExportColumn::make('product.selling_price')->label('Harga Jual'),
            ExportColumn::make('salable_valuation')->label('Valuasi Layak Jual')->state(function (Stock $record) {
                $unitCost = ($record->quantity_on_hand > 0 && isset($record->batch_valuation) && $record->batch_valuation > 0)
                    ? ($record->batch_valuation / $record->quantity_on_hand)
                    : ($record->cost_price_tax > 0 ? $record->cost_price_tax : ($record->product->cost_price_tax ?? $record->product->cost_price ?? 0));
                $salable = max(0, (float)$record->quantity_on_hand - (float)($record->quantity_damaged ?? 0));
                return $salable * (float)$unitCost;
            }),
            ExportColumn::make('damaged_valuation')->label('Valuasi BS')->state(function (Stock $record) {
                $unitCost = ($record->quantity_on_hand > 0 && isset($record->batch_valuation) && $record->batch_valuation > 0)
                    ? ($record->batch_valuation / $record->quantity_on_hand)
                    : ($record->cost_price_tax > 0 ? $record->cost_price_tax : ($record->product->cost_price_tax ?? $record->product->cost_price ?? 0));
                return (float)($record->quantity_damaged ?? 0) * (float)$unitCost;
            }),
            ExportColumn::make('valuation')->label('Total Valuasi Fisik')->state(function (Stock $record) {
                if ($record->quantity_on_hand > 0 && isset($record->batch_valuation) && $record->batch_valuation > 0) {
                    return (float) $record->batch_valuation;
                }
                return (float) $record->quantity_on_hand * ($record->cost_price_tax > 0 ? $record->cost_price_tax : ($record->product->cost_price_tax ?? $record->product->cost_price ?? 0));
            }),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Laporan Persediaan selesai. ' . \Illuminate\Support\Number::format($export->successful_rows) . ' baris berhasil diexport.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . \Illuminate\Support\Number::format($failedRowsCount) . ' baris gagal diexport.';
        }

        return $body;
    }
}
