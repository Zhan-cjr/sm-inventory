<?php

namespace App\Filament\Resources\ListingProduks;

use App\Filament\Resources\ListingProduks\Pages\CreateListingProduk;
use App\Filament\Resources\ListingProduks\Pages\EditListingProduk;
use App\Filament\Resources\ListingProduks\Pages\ListListingProduks;
use App\Filament\Resources\ListingProduks\Schemas\ListingProdukForm;
use App\Filament\Resources\ListingProduks\Tables\ListingProduksTable;
use App\Models\ProductListing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListingProdukResource extends Resource
{
    protected static ?string $model = ProductListing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static \UnitEnum|string|null $navigationGroup = 'PERSEDIAAN';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Listing Produk';

    protected static ?string $pluralModelLabel = 'Listing Produk';

    protected static ?string $recordTitleAttribute = 'listing_number';

    protected static ?string $slug = 'listing-produk';

    public static function form(Schema $schema): Schema
    {
        return ListingProdukForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ListingProduksTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();
        if ($user && $user->branch_id) {
            $query->whereJsonContains('allowed_branch_ids', $user->branch_id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListListingProduks::route('/'),
            'create' => CreateListingProduk::route('/create'),
            'edit' => EditListingProduk::route('/{record}/edit'),
        ];
    }
}
