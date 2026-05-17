<?php

namespace App\Filament\Exports;

use App\Models\Stock;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class StockExporter extends Exporter
{
    protected static ?string $model = Stock::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('branch.name')->label('Cabang'),
            ExportColumn::make('product.sku')->label('SKU'),
            ExportColumn::make('product.name')->label('Nama Barang'),
            ExportColumn::make('product.category.name')->label('Kategori'),
            ExportColumn::make('quantity_on_hand')->label('Sisa Stok'),
            ExportColumn::make('product.cost_price')->label('Harga Pokok'),
            ExportColumn::make('valuation')->label('Valuasi Stok')->state(fn (Stock $record): float => $record->quantity_on_hand * ($record->product->cost_price ?? 0)),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Export completed.';
    }
}
