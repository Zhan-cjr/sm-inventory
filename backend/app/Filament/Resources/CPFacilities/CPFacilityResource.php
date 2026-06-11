<?php

namespace App\Filament\Resources\CPFacilities;

use App\Filament\Resources\CPFacilities\Pages\CreateCPFacility;
use App\Filament\Resources\CPFacilities\Pages\EditCPFacility;
use App\Filament\Resources\CPFacilities\Pages\ListCPFacilities;
use App\Filament\Resources\CPFacilities\Schemas\CPFacilityForm;
use App\Filament\Resources\CPFacilities\Tables\CPFacilitiesTable;
use App\Models\CPFacility;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CPFacilityResource extends Resource
{
    protected static ?string $model = CPFacility::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-star';
    protected static \UnitEnum|string|null $navigationGroup = 'COMPANY PROFILE';
    protected static ?int $navigationSort = 99;

    protected static ?string $recordTitleAttribute = 'no';

    public static function form(Schema $schema): Schema
    {
        return CPFacilityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CPFacilitiesTable::configure($table);
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
            'index' => ListCPFacilities::route('/'),
            'create' => CreateCPFacility::route('/create'),
            'edit' => EditCPFacility::route('/{record}/edit'),
        ];
    }
}
