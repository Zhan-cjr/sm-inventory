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
        $user = auth()->user();
        $isSuperAdmin = $user?->hasRole(['superadmin', 'super_admin', 'super-admin']);
        $canCreateProduct = $isSuperAdmin || $user?->hasCustomAuthorization('EDIT_PRODUCT_MASTER');
        $canEditStock = $isSuperAdmin || $user?->hasCustomAuthorization('EDIT_BRANCH_STOCK');

        return [
            ImportAction::make('import_products')
                ->label('Import Produk')
                ->importer(ProductImporter::class)
                ->icon('heroicon-o-shopping-bag')
                ->visible(fn () => $canCreateProduct && auth()->user()?->branch_id === null),
            ImportAction::make('import_stocks')
                ->label('Import Stok Cabang')
                ->importer(\App\Filament\Imports\StockImporter::class)
                ->icon('heroicon-o-building-storefront')
                ->color('info')
                ->visible(fn () => $canEditStock),
            \App\Filament\Actions\QuickCreateProductAction::make(),
            CreateAction::make()
                ->visible(fn () => $canCreateProduct && auth()->user()?->branch_id === null),
        ];
    }

    protected function applySearchToTableQuery(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        // Global search filtering is centrally handled in ProductsTable::configure() via modifyQueryUsing()
        return $this->applyColumnSearchesToTableQuery($query);
    }
}
