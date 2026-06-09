<?php

namespace App\Filament\Resources\ProductAssemblies;

use App\Filament\Resources\ProductAssemblies\Pages\CreateProductAssembly;
use App\Filament\Resources\ProductAssemblies\Pages\EditProductAssembly;
use App\Filament\Resources\ProductAssemblies\Pages\ListProductAssemblies;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductAssemblyResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube-transparent';

    protected static \UnitEnum|string|null $navigationGroup = 'PERSEDIAAN';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Paket & Bundling';

    protected static ?string $modelLabel = 'Paket Produk';

    protected static ?string $pluralModelLabel = 'Paket & Bundling';

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Hanya tampilkan produk yang memiliki assembly components
     * ATAU produk yang bertipe fisik (kandidat untuk dijadikan paket)
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('product_type', 'physical')
            ->whereHas('assemblies');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(
            \App\Filament\Resources\ProductAssemblies\Schemas\ProductAssemblyForm::schema()
        );
    }

    public static function table(Table $table): Table
    {
        return \App\Filament\Resources\ProductAssemblies\Tables\ProductAssemblyTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListProductAssemblies::route('/'),
            'create' => CreateProductAssembly::route('/create'),
            'edit'   => EditProductAssembly::route('/{record}/edit'),
        ];
    }
}
