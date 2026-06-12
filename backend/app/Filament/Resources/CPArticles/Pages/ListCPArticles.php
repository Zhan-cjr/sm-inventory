<?php

namespace App\Filament\Resources\CPArticles\Pages;

use App\Filament\Resources\CPArticles\CPArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCPArticles extends ListRecords
{
    protected static string $resource = CPArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
