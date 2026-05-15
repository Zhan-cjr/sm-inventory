<?php

namespace App\Livewire;

use App\Models\InventoryLog;
use Livewire\Component;
use Livewire\WithPagination;

class StockCard extends Component
{
    use WithPagination;

    public $productId;

    public function mount($productId)
    {
        $this->productId = $productId;
    }

    public function render()
    {
        $query = InventoryLog::where('product_id', $this->productId);
        
        $user = auth()->user();
        if ($user && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        $logs = $query->latest()->paginate(10);

        return view('livewire.stock-card', [
            'logs' => $logs,
        ]);
    }
}
