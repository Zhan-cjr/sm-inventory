<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Widgets\ProductPerformanceWidget;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ProductPerformanceWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => auth()->user()->branch_id === null),
        ];
    }

    protected function getFormActions(): array
    {
        if (auth()->user()->branch_id !== null) {
            return [];
        }

        return parent::getFormActions();
    }
}
