<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Widgets\ProductPerformanceWidget;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        $branchId = request()->query('branch_id');
        if ($branchId) {
            session(['active_selected_branch_id' => $branchId]);
        }
    }

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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('cancel')
            ->label(__('filament-panels::resources/pages/edit-record.form.actions.cancel.label'))
            ->url($this->getResource()::getUrl('index'))
            ->color('gray');
    }

    protected function getFormActions(): array
    {
        if (auth()->user()->branch_id !== null) {
            return [];
        }

        return parent::getFormActions();
    }
}