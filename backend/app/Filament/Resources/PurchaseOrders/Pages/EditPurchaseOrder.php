<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class EditPurchaseOrder extends Page
{
    protected static string $resource = PurchaseOrderResource::class;

    protected string $view = 'filament.pages.edit-purchase-order';

    public $record;

    public function mount($record): void
    {
        $this->record = PurchaseOrderResource::getModel()::findOrFail($record);
    }

    public function getTitle(): string | Htmlable
    {
        return 'Edit Pesanan Pembelian';
    }
}
