<?php

namespace App\Filament\Resources\EcommerceSettings;

use App\Filament\Resources\EcommerceSettings\Pages\EditEcommerceSetting;
use App\Filament\Resources\EcommerceSettings\Pages\ListEcommerceSettings;
use App\Filament\Resources\EcommerceSettings\Schemas\EcommerceSettingForm;
use App\Filament\Resources\EcommerceSettings\Tables\EcommerceSettingsTable;
use App\Models\Organization;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EcommerceSettingResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static \UnitEnum|string|null $navigationGroup = 'E-COMMERCE';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Pengaturan E-Commerce';

    protected static ?string $pluralModelLabel = 'Pengaturan E-Commerce';

    protected static ?string $slug = 'ecommerce-settings';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return EcommerceSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EcommerceSettingsTable::configure($table);
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
            'index' => ListEcommerceSettings::route('/'),
            'edit' => EditEcommerceSetting::route('/{record}/edit'),
        ];
    }
}
