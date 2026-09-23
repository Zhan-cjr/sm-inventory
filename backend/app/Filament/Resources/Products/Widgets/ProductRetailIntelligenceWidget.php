<?php

namespace App\Filament\Resources\Products\Widgets;

use App\Models\Product;
use App\Services\RetailIntelligenceService;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;

class ProductRetailIntelligenceWidget extends Widget
{
    protected string $view = "filament.products.widgets.product-retail-intelligence";

    public ?Model $record = null;

    #[Url(as: 'branch_id')]
    public ?string $branchId = null;

    protected int | string | array $columnSpan = "full";

    public function getViewData(): array
    {
        /** @var Product|null $product */
        $product = $this->record;

        if (!$product) {
            return ["hasData" => false];
        }

        // User cabang MUTLAK terkunci ke cabangnya; Super admin membaca eksplisit dari URL query
        $userBranchId = auth()->user()?->branch_id;
        $branchId = !empty($userBranchId) ? $userBranchId : ($this->branchId ?: request()->query('branch_id'));

        return RetailIntelligenceService::getIntelligenceData($product, $branchId);
    }
}