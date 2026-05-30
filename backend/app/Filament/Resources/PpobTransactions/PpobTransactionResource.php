<?php

namespace App\Filament\Resources\PpobTransactions;

use App\Filament\Resources\PpobTransactions\Pages\CreatePpobTransaction;
use App\Filament\Resources\PpobTransactions\Pages\EditPpobTransaction;
use App\Filament\Resources\PpobTransactions\Pages\ListPpobTransactions;
use App\Filament\Resources\PpobTransactions\Pages\ViewPpobTransaction;
use App\Filament\Resources\PpobTransactions\Schemas\PpobTransactionForm;
use App\Filament\Resources\PpobTransactions\Schemas\PpobTransactionInfolist;
use App\Filament\Resources\PpobTransactions\Tables\PpobTransactionsTable;
use App\Models\PpobTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PpobTransactionResource extends Resource
{
    protected static ?string $model = PpobTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;
    
    protected static ?string $navigationLabel = 'Laporan PPOB';
    
    protected static ?string $modelLabel = 'Laporan PPOB';
    
    protected static \UnitEnum|string|null $navigationGroup = 'LAPORAN/ARSIP';

    protected static ?string $recordTitleAttribute = 'ref_id';

    public static function form(Schema $schema): Schema
    {
        return PpobTransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PpobTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PpobTransactionsTable::configure($table);
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
            'index' => ListPpobTransactions::route('/'),
            'create' => CreatePpobTransaction::route('/create'),
            'view' => ViewPpobTransaction::route('/{record}'),
            'edit' => EditPpobTransaction::route('/{record}/edit'),
        ];
    }
}
