<?php

namespace App\Filament\Resources\StockTransfers;

use App\Filament\Resources\StockTransfers\Pages\CreateStockTransfer;
use App\Filament\Resources\StockTransfers\Pages\EditStockTransfer;
use App\Filament\Resources\StockTransfers\Pages\ListStockTransfers;
use App\Filament\Resources\StockTransfers\Schemas\StockTransferForm;
use App\Filament\Resources\StockTransfers\Tables\StockTransfersTable;
use App\Models\StockTransfer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Filament\Resources\StockTransfers\RelationManagers;

class StockTransferResource extends Resource
{
    protected static ?string $model = StockTransfer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static \UnitEnum|string|null $navigationGroup = 'TRANSAKSI';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Stok Transfer';

    protected static ?string $pluralModelLabel = 'Stok Transfer';

    public static function form(Schema $schema): Schema
    {
        return StockTransferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockTransfersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockTransfers::route('/'),
            'create' => CreateStockTransfer::route('/create'),
            'edit' => EditStockTransfer::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = \Illuminate\Support\Facades\Auth::user();

        if ($user && $user->branch_id) {
            $query->where(function ($q) use ($user) {
                $q->where('from_branch_id', $user->branch_id)
                  ->orWhere('to_branch_id', $user->branch_id);
            });
        }

        return $query;
    }
}
