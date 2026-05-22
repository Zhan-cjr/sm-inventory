<?php

namespace App\Filament\Resources\Shifts;

use App\Filament\Resources\Shifts\Pages\CreateShift;
use App\Filament\Resources\Shifts\Pages\EditShift;
use App\Filament\Resources\Shifts\Pages\ListShifts;
use App\Filament\Resources\Shifts\Pages\ViewShift;
use App\Filament\Resources\Shifts\Schemas\ShiftForm;
use App\Filament\Resources\Shifts\Schemas\ShiftInfolist;
use App\Filament\Resources\Shifts\Tables\ShiftsTable;
use App\Models\Shift;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use App\Traits\HasBranchScope;
use Filament\Tables\Table;

class ShiftResource extends Resource
{
    use HasBranchScope;

    protected static ?string $model = Shift::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Manajemen Shift';
    protected static ?string $modelLabel = 'Shift Kasir';
    protected static ?string $pluralModelLabel = 'Shift Kasir';
    protected static \UnitEnum|string|null $navigationGroup = 'PENGATURAN';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return ShiftForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ShiftInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShiftsTable::configure($table);
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
            'index' => ListShifts::route('/'),
            'create' => CreateShift::route('/create'),
            'view' => ViewShift::route('/{record}'),
            'edit' => EditShift::route('/{record}/edit'),
        ];
    }
}
