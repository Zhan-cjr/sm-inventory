<?php

namespace App\Filament\Resources\Products\Widgets;

use App\Models\Branch;
use App\Models\Product;
use App\Services\RetailIntelligenceService;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class ProductRetailIntelligenceWidget extends Widget
{
    protected string $view = "filament.products.widgets.product-retail-intelligence";

    public ?Model $record = null;

    public ?string $selectedBranchId = null;

    protected int | string | array $columnSpan = "full";

    public function mount(): void
    {
        $this->selectedBranchId = request()->query('branch_id') 
            ?: session('active_selected_branch_id') 
            ?: auth()->user()?->branch_id;
    }

    public function selectBranch(?string $branchId = null): void
    {
        $this->selectedBranchId = ($branchId === 'all' || empty($branchId)) ? null : $branchId;
        
        if ($this->selectedBranchId) {
            session(['active_selected_branch_id' => $this->selectedBranchId]);
        } else {
            session(['active_selected_branch_id' => 'all']);
        }

        $this->dispatch('branch-context-changed', branchId: $this->selectedBranchId);
    }

    public function getViewData(): array
    {
        /** @var Product|null $product */
        $product = $this->record;

        if (!$product) {
            return ["hasData" => false];
        }

        $branchId = $this->selectedBranchId;
        if ($branchId === null && session('active_selected_branch_id') !== 'all') {
            $branchId = request()->query('branch_id') 
                ?: session('active_selected_branch_id') 
                ?: auth()->user()?->branch_id;
        }

        $data = RetailIntelligenceService::getIntelligenceData($product, $branchId);
        $data['allBranchesList'] = Branch::where('is_active', true)->orderBy('sort_order')->get();
        $data['activeSelectedBranchId'] = $branchId;
        $data['isUserBranchLocked'] = auth()->user()?->branch_id !== null;

        return $data;
    }
}