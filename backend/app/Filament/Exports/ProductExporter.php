<?php

namespace App\Filament\Exports;

use App\Models\Product;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class ProductExporter extends Exporter
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('organization.name'),
            ExportColumn::make('sku')
                ->label('SKU'),
            ExportColumn::make('barcode'),
            ExportColumn::make('name'),
            ExportColumn::make('image_path'),
            ExportColumn::make('category.name'),
            ExportColumn::make('sub_category'),
            ExportColumn::make('supplier.name'),
            ExportColumn::make('cost_price'),
            ExportColumn::make('cost_price_tax'),
            ExportColumn::make('selling_price'),
            ExportColumn::make('margin_gol_1'),
            ExportColumn::make('harga_jual_1'),
            ExportColumn::make('qty_min_gol_1'),
            ExportColumn::make('margin_gol_2'),
            ExportColumn::make('harga_jual_2'),
            ExportColumn::make('qty_min_gol_2'),
            ExportColumn::make('margin_gol_3'),
            ExportColumn::make('harga_jual_3'),
            ExportColumn::make('qty_min_gol_3'),
            ExportColumn::make('is_taxable'),
            ExportColumn::make('unit_of_measure'),
            ExportColumn::make('reorder_point'),
            ExportColumn::make('reorder_qty'),
            ExportColumn::make('lead_time_days'),
            ExportColumn::make('is_active'),
            ExportColumn::make('is_ecommerce_active'),
            ExportColumn::make('ecommerce_category'),
            ExportColumn::make('metadata'),
            ExportColumn::make('product_type'),
            ExportColumn::make('ppob_sku'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
            ExportColumn::make('weight_in_grams'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your product export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
