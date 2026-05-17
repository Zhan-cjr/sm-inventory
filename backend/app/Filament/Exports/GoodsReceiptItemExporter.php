<?php

namespace App\Filament\Exports;

use App\Models\GoodsReceiptItem;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class GoodsReceiptItemExporter extends Exporter
{
    protected static ?string $model = GoodsReceiptItem::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('goodsReceipt.receipt_date')->label('Tanggal'),
            ExportColumn::make('goodsReceipt.receipt_number')->label('No Penerimaan'),
            ExportColumn::make('goodsReceipt.supplier.name')->label('Supplier'),
            ExportColumn::make('product.sku')->label('SKU'),
            ExportColumn::make('product.name')->label('Barang'),
            ExportColumn::make('quantity_received')->label('Qty'),
            ExportColumn::make('unit_price')->label('Harga'),
            ExportColumn::make('subtotal')->label('Subtotal'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Export completed.';
    }
}
