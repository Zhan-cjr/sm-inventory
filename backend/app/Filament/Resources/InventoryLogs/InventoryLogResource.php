<?php

namespace App\Filament\Resources\InventoryLogs;

use App\Filament\Resources\InventoryLogs\Pages\CreateInventoryLog;
use App\Filament\Resources\InventoryLogs\Pages\EditInventoryLog;
use App\Filament\Resources\InventoryLogs\Pages\ListInventoryLogs;
use App\Filament\Resources\InventoryLogs\Schemas\InventoryLogForm;
use App\Filament\Resources\InventoryLogs\Tables\InventoryLogsTable;
use App\Models\InventoryLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InventoryLogResource extends Resource
{
    protected static ?string $model = InventoryLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $shouldRegisterNavigation = false;
    protected static \UnitEnum|string|null $navigationGroup = 'PERSEDIAAN';

    protected static ?string $modelLabel = 'Kartu Stok';

    protected static ?string $pluralModelLabel = 'Kartu Stok';

    public static function form(Schema $schema): Schema
    {
        return InventoryLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InventoryLogsTable::configure($table);
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
            'index' => ListInventoryLogs::route('/'),
            'create' => CreateInventoryLog::route('/create'),
            'edit' => EditInventoryLog::route('/{record}/edit'),
        ];
    }
}
