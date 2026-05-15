<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Imports\ProductImporter;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make('import_products')
                ->label('Import Produk')
                ->importer(ProductImporter::class)
                ->icon('heroicon-o-shopping-bag'),
            ImportAction::make('import_stocks')
                ->label('Import Stok Cabang')
                ->importer(\App\Filament\Imports\StockImporter::class)
                ->icon('heroicon-o-building-storefront')
                ->color('info'),
            CreateAction::make(),
        ];
    }
}
