<?php

namespace App\Filament\Resources\GoodsReceipts;

use App\Filament\Resources\GoodsReceipts\Pages\CreateGoodsReceipt;
use App\Filament\Resources\GoodsReceipts\Pages\EditGoodsReceipt;
use App\Filament\Resources\GoodsReceipts\Pages\ListGoodsReceipts;
use App\Filament\Resources\GoodsReceipts\Schemas\GoodsReceiptForm;
use App\Filament\Resources\GoodsReceipts\Tables\GoodsReceiptsTable;
use App\Models\GoodsReceipt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use App\Traits\HasBranchScope;
use App\Filament\Resources\GoodsReceipts\RelationManagers;
use Filament\Tables\Table;

class GoodsReceiptResource extends Resource
{
    use HasBranchScope;

    protected static ?string $model = GoodsReceipt::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationLabel = 'Penerimaan Barang (GR)';
    protected static ?string $modelLabel = 'Penerimaan Barang';
    protected static ?string $pluralModelLabel = 'Penerimaan Barang';
    protected static \UnitEnum|string|null $navigationGroup = 'Transaksi';

    protected static ?string $recordTitleAttribute = 'receipt_number';

    public static function form(Schema $schema): Schema
    {
        return GoodsReceiptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GoodsReceiptsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGoodsReceipts::route('/'),
            'create' => CreateGoodsReceipt::route('/create'),
            'edit' => EditGoodsReceipt::route('/{record}/edit'),
        ];
    }
}
