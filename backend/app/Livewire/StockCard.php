<?php

namespace App\Livewire;

use App\Models\InventoryLog;
use Livewire\Component;
use Livewire\WithPagination;

class StockCard extends Component
{
    use WithPagination;

    public $productId;
    public $branchId;

    public function mount($productId, $branchId = null)
    {
        $this->productId = $productId;
        $this->branchId = $branchId;
    }

    public function render()
    {
        $user = auth()->user();
        $effectiveBranchId = $user->branch_id ?? $this->branchId;

        if (!$effectiveBranchId) {
            return view('livewire.stock-card', [
                'logs' => null,
                'noBranchSelected' => true,
            ]);
        }

        $query = InventoryLog::where('product_id', $this->productId)
            ->where('branch_id', $effectiveBranchId);

        $logs = $query->latest('id')->paginate(10);

        return view('livewire.stock-card', [
            'logs' => $logs,
            'noBranchSelected' => false,
        ]);
    }
}
