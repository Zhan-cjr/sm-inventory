<?php

namespace App\Filament\Resources\CPArticles\Pages;

use App\Filament\Resources\CPArticles\CPArticleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCPArticle extends EditRecord
{
    protected static string $resource = CPArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
