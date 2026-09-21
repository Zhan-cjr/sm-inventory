<?php

namespace App\Filament\Resources\Products\Widgets;

use App\Models\Product;
use App\Services\RetailIntelligenceService;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class ProductRetailIntelligenceWidget extends Widget
{
    protected string $view = "filament.products.widgets.product-retail-intelligence";

    public ?Model $record = null;

    protected int | string | array $columnSpan = "full";

    public function getViewData(): array
    {
        /** @var Product|null $product */
        $product = $this->record;

        if (!$product) {
            return ["hasData" => false];
        }

        return RetailIntelligenceService::getIntelligenceData($product);
    }
}
