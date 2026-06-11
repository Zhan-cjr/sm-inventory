<?php

namespace App\Filament\Resources\CPBranches;

use App\Filament\Resources\CPBranches\Pages\CreateCPBranch;
use App\Filament\Resources\CPBranches\Pages\EditCPBranch;
use App\Filament\Resources\CPBranches\Pages\ListCPBranches;
use App\Filament\Resources\CPBranches\Schemas\CPBranchForm;
use App\Filament\Resources\CPBranches\Tables\CPBranchesTable;
use App\Models\CPBranch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CPBranchResource extends Resource
{
    protected static ?string $model = CPBranch::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';
    protected static \UnitEnum|string|null $navigationGroup = 'COMPANY PROFILE';
    protected static ?int $navigationSort = 99;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CPBranchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CPBranchesTable::configure($table);
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
            'index' => ListCPBranches::route('/'),
            'create' => CreateCPBranch::route('/create'),
            'edit' => EditCPBranch::route('/{record}/edit'),
        ];
    }
}
