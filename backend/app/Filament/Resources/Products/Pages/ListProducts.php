<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Imports\ProductImporter;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        $isBranchUser = auth()->user()->branch_id !== null;

        return [
            ImportAction::make('import_products')
                ->label('Import Produk')
                ->importer(ProductImporter::class)
                ->icon('heroicon-o-shopping-bag')
                ->visible(!$isBranchUser),
            ImportAction::make('import_stocks')
                ->label('Import Stok Cabang')
                ->importer(\App\Filament\Imports\StockImporter::class)
                ->icon('heroicon-o-building-storefront')
                ->color('info')
                ->visible(!$isBranchUser),
            CreateAction::make()
                ->visible(!$isBranchUser),
        ];
    }

    protected function applySearchToTableQuery(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        if (filled($search = $this->getTableSearch())) {
            $query->whereIn('id', \App\Models\Product::search($search)->take(1000)->keys());
        }

        return $query;
    }
}
