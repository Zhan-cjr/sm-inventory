<?php

namespace App\Filament\Resources\CPPartnerships;

use App\Filament\Resources\CPPartnerships\Pages\CreateCPPartnership;
use App\Filament\Resources\CPPartnerships\Pages\EditCPPartnership;
use App\Filament\Resources\CPPartnerships\Pages\ListCPPartnerships;
use App\Filament\Resources\CPPartnerships\Schemas\CPPartnershipForm;
use App\Filament\Resources\CPPartnerships\Tables\CPPartnershipsTable;
use App\Models\CPPartnership;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CPPartnershipResource extends Resource
{
    protected static ?string $model = CPPartnership::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'COMPANY PROFILE';

    protected static ?string $recordTitleAttribute = 'business_name';

    public static function form(Schema $schema): Schema
    {
        return CPPartnershipForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CPPartnershipsTable::configure($table);
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
            'index' => ListCPPartnerships::route('/'),
            'create' => CreateCPPartnership::route('/create'),
            'edit' => EditCPPartnership::route('/{record}/edit'),
        ];
    }
}
