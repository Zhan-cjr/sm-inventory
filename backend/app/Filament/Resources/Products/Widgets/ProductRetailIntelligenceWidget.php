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

        // User cabang MUTLAK terkunci ke cabangnya sendiri
        $userBranchId = auth()->user()?->branch_id;
        $branchId = !empty($userBranchId) 
            ? $userBranchId 
            : (request()->query('branch_id') ?: session('active_selected_branch_id'));

        return RetailIntelligenceService::getIntelligenceData($product, $branchId);
    }
}