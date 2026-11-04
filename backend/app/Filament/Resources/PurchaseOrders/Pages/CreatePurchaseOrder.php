<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class CreatePurchaseOrder extends Page
{
    protected static string $resource = PurchaseOrderResource::class;

    protected string $view = 'filament.pages.create-purchase-order';

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    public function getTitle(): string | Htmlable
    {
        return 'Buat Pesanan Pembelian';
    }

    // Hide the default save button from standard Filament Form
    public function hasSaveButton(): bool
    {
        return false;
    }
}
