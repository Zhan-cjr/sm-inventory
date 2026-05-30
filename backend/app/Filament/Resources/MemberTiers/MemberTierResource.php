<?php

namespace App\Filament\Resources\MemberTiers;

use App\Filament\Resources\MemberTiers\Pages\CreateMemberTier;
use App\Filament\Resources\MemberTiers\Pages\EditMemberTier;
use App\Filament\Resources\MemberTiers\Pages\ListMemberTiers;
use App\Filament\Resources\MemberTiers\Pages\ViewMemberTier;
use App\Filament\Resources\MemberTiers\Schemas\MemberTierForm;
use App\Filament\Resources\MemberTiers\Schemas\MemberTierInfolist;
use App\Filament\Resources\MemberTiers\Tables\MemberTiersTable;
use App\Models\MemberTier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MemberTierResource extends Resource
{
    protected static ?string $model = MemberTier::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-star';
    
    protected static \UnitEnum|string|null $navigationGroup = 'DATA MASTER';
    
    protected static ?string $modelLabel = 'Level Member';
    
    protected static ?string $pluralModelLabel = 'Level Member';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return MemberTierForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MemberTierInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MemberTiersTable::configure($table);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        
        if (auth()->check() && auth()->user()->organization_id) {
            $query->where('organization_id', auth()->user()->organization_id);
        }

        return $query;
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
            'index' => ListMemberTiers::route('/'),
            'create' => CreateMemberTier::route('/create'),
            'view' => ViewMemberTier::route('/{record}'),
            'edit' => EditMemberTier::route('/{record}/edit'),
        ];
    }
}
