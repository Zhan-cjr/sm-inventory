<?php

namespace App\Filament\Resources\CPArticles;

use App\Filament\Resources\CPArticles\Pages\CreateCPArticle;
use App\Filament\Resources\CPArticles\Pages\EditCPArticle;
use App\Filament\Resources\CPArticles\Pages\ListCPArticles;
use App\Filament\Resources\CPArticles\Schemas\CPArticleForm;
use App\Filament\Resources\CPArticles\Tables\CPArticlesTable;
use App\Models\CPArticle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CPArticleResource extends Resource
{
    protected static ?string $model = CPArticle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'COMPANY PROFILE';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return CPArticleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CPArticlesTable::configure($table);
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
            'index' => ListCPArticles::route('/'),
            'create' => CreateCPArticle::route('/create'),
            'edit' => EditCPArticle::route('/{record}/edit'),
        ];
    }
}
