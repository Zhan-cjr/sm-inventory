<?php

namespace App\Filament\Resources\PosSettings;

use App\Filament\Resources\PosSettings\Pages\CreatePosSetting;
use App\Filament\Resources\PosSettings\Pages\EditPosSetting;
use App\Filament\Resources\PosSettings\Pages\ListPosSettings;
use App\Filament\Resources\PosSettings\Schemas\PosSettingForm;
use App\Filament\Resources\PosSettings\Tables\PosSettingsTable;
use App\Models\PosSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PosSettingResource extends Resource
{
    protected static ?string $model = PosSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static \UnitEnum|string|null $navigationGroup = 'Sistem & Pengaturan';
    protected static ?string $modelLabel = 'Pengaturan POS';
    protected static ?string $pluralModelLabel = 'Pengaturan POS';

    public static function form(Schema $schema): Schema
    {
        return PosSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PosSettingsTable::configure($table);
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
            'index' => ListPosSettings::route('/'),
            'create' => CreatePosSetting::route('/create'),
            'edit' => EditPosSetting::route('/{record}/edit'),
        ];
    }
}
