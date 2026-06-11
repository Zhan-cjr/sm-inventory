<?php

namespace App\Filament\Resources\CPSettings;

use App\Filament\Resources\CPSettings\Pages\CreateCPSetting;
use App\Filament\Resources\CPSettings\Pages\EditCPSetting;
use App\Filament\Resources\CPSettings\Pages\ListCPSettings;
use App\Filament\Resources\CPSettings\Schemas\CPSettingForm;
use App\Filament\Resources\CPSettings\Tables\CPSettingsTable;
use App\Models\CPSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CPSettingResource extends Resource
{
    protected static ?string $model = CPSetting::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog';
    protected static \UnitEnum|string|null $navigationGroup = 'COMPANY PROFILE';
    protected static ?int $navigationSort = 99;

    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Schema $schema): Schema
    {
        return CPSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CPSettingsTable::configure($table);
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
            'index' => ListCPSettings::route('/'),
            'create' => CreateCPSetting::route('/create'),
            'edit' => EditCPSetting::route('/{record}/edit'),
        ];
    }
}
