<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Widgets\ProductPerformanceWidget;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\Url;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    #[Url(as: 'branch_id')]
    public ?string $branchId = null;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        if (empty($this->branchId)) {
            $this->branchId = request()->query('branch_id');
        }
    }

    public function getWidgetData(): array
    {
        $userBranchId = auth()->user()?->branch_id;
        $evalBranchId = !empty($userBranchId) 
            ? $userBranchId 
            : ($this->branchId ?: request()->query('branch_id'));

        return [
            'record' => $this->getRecord(),
            'branchId' => $evalBranchId,
        ];
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
                ->visible(fn () => auth()->user()?->hasRole(['superadmin', 'super_admin', 'super-admin']) 
                    || (auth()->user()?->branch_id === null && auth()->user()?->hasCustomAuthorization('EDIT_PRODUCT_MASTER'))),
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
        $canEditMaster = auth()->user()?->hasRole(['superadmin', 'super_admin', 'super-admin']) 
            || auth()->user()?->hasCustomAuthorization('EDIT_PRODUCT_MASTER');

        if (! $canEditMaster) {
            return [];
        }

        return parent::getFormActions();
    }
}