<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\SupplierDivision;
use Illuminate\Support\Facades\DB;

class DivisionProductsModal extends Component
{
    public $divisionId = null;
    public $supplierId = null;
    public $isUnassigned = false;
    public $search = '';
    public $selectedProducts = [];
    public $selectAll = false;

    // Tambah Barang Modal
    public $showAddModal = false;
    public $addSearch = '';
    public $addSelectedProducts = [];

    // Pindah Supplier / Sub Divisi Modal
    public $showMoveModal = false;
    public $targetSupplierId = '';
    public $targetDivisionId = '';

    public function mount($divisionId = null, $supplierId = null, $isUnassigned = false)
    {
        $this->divisionId = $divisionId;
        $this->supplierId = $supplierId;
        $this->isUnassigned = (bool) $isUnassigned || ($divisionId === 'unassigned') || (!$divisionId && $supplierId);

        if (!$this->isUnassigned && $divisionId) {
            $division = SupplierDivision::find($divisionId);
            if ($division) {
                $this->supplierId = $division->supplier_id;
            }
        }
    }

    public function updatedSelectAll($val)
    {
        if ($val) {
            if ($this->isUnassigned) {
                $query = Product::where('supplier_id', $this->supplierId)->whereNull('supplier_division_id');
            } else {
                $query = Product::where('supplier_division_id', $this->divisionId);
            }

            if (!empty($this->search)) {
                $s = '%' . trim($this->search) . '%';
                $query->where(function ($q) use ($s) {
                    $q->where('name', 'like', $s)
                      ->orWhere('barcode', 'like', $s)
                      ->orWhere('sku', 'like', $s);
                });
            }
            $this->selectedProducts = $query->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedProducts = [];
        }
    }

    public function openAddModal()
    {
        $this->addSelectedProducts = [];
        $this->addSearch = '';
        $this->showAddModal = true;
    }

    public function closeAddModal()
    {
        $this->showAddModal = false;
    }

    public function addProductsToDivision()
    {
        if (empty($this->addSelectedProducts)) {
            session()->flash('error', 'Pilih minimal satu barang untuk ditambahkan.');
            return;
        }

        DB::beginTransaction();
        try {
            $targetDivId = $this->isUnassigned ? null : $this->divisionId;

            Product::whereIn('id', $this->addSelectedProducts)
                ->update([
                    'supplier_id' => $this->supplierId,
                    'supplier_division_id' => $targetDivId,
                ]);

            Stock::whereIn('product_id', $this->addSelectedProducts)
                ->update([
                    'supplier_id' => $this->supplierId,
                    'supplier_division_id' => $targetDivId,
                ]);

            DB::commit();

            $count = count($this->addSelectedProducts);
            $this->addSelectedProducts = [];
            $this->showAddModal = false;
            $msg = $this->isUnassigned
                ? "{$count} barang berhasil dipindahkan ke status Tanpa Sub-Divisi."
                : "{$count} barang berhasil ditambahkan ke sub-divisi ini.";
            session()->flash('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menambahkan barang: ' . $e->getMessage());
        }
    }

    public function openMoveModal($singleId = null)
    {
        if ($singleId) {
            $this->selectedProducts = [(string) $singleId];
        }

        if (empty($this->selectedProducts)) {
            session()->flash('error', 'Pilih minimal satu barang untuk dipindahkan.');
            return;
        }

        if ($this->isUnassigned) {
            $this->targetSupplierId = (string) $this->supplierId;
        } else {
            $division = SupplierDivision::find($this->divisionId);
            $this->targetSupplierId = $division ? (string) $division->supplier_id : '';
        }

        $this->targetDivisionId = '';
        $this->showMoveModal = true;
    }

    public function closeMoveModal()
    {
        $this->showMoveModal = false;
    }

    public function executeMove()
    {
        if (empty($this->selectedProducts)) {
            session()->flash('error', 'Tidak ada barang yang dipilih.');
            return;
        }

        if (empty($this->targetSupplierId)) {
            session()->flash('error', 'Silakan pilih Supplier tujuan.');
            return;
        }

        DB::beginTransaction();
        try {
            $updates = [
                'supplier_id' => $this->targetSupplierId,
                'supplier_division_id' => !empty($this->targetDivisionId) ? $this->targetDivisionId : null,
            ];

            Product::whereIn('id', $this->selectedProducts)->update($updates);

            Stock::whereIn('product_id', $this->selectedProducts)->update([
                'supplier_id' => $this->targetSupplierId,
                'supplier_division_id' => !empty($this->targetDivisionId) ? $this->targetDivisionId : null,
            ]);

            DB::commit();

            $count = count($this->selectedProducts);
            $this->selectedProducts = [];
            $this->showMoveModal = false;
            $this->selectAll = false;
            session()->flash('success', "{$count} barang berhasil dipindahkan ke supplier/sub-divisi tujuan.");
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal memindahkan barang: ' . $e->getMessage());
        }
    }

    public function detachProduct($productId)
    {
        DB::beginTransaction();
        try {
            Product::where('id', $productId)->update(['supplier_division_id' => null]);
            Stock::where('product_id', $productId)->update(['supplier_division_id' => null]);

            DB::commit();
            session()->flash('success', 'Barang berhasil dilepaskan dari sub-divisi ini.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal melepaskan barang: ' . $e->getMessage());
        }
    }

    public function render()
    {
        if ($this->isUnassigned) {
            $supplier = Supplier::findOrFail($this->supplierId);
            $division = null;

            $query = Product::where('supplier_id', $this->supplierId)
                ->whereNull('supplier_division_id')
                ->with(['category', 'stocks']);
        } else {
            $division = SupplierDivision::with(['supplier', 'branch', 'creator'])->findOrFail($this->divisionId);
            $supplier = $division->supplier;

            $query = Product::where('supplier_division_id', $this->divisionId)
                ->with(['category', 'stocks']);
        }

        if (!empty($this->search)) {
            $s = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)
                  ->orWhere('barcode', 'like', $s)
                  ->orWhere('sku', 'like', $s);
            });
        }

        $products = $query->orderBy('name')->get();

        // Candidates for Add Modal
        $addCandidates = collect();
        if ($this->showAddModal && $supplier) {
            $addQuery = Product::where('supplier_id', $supplier->id)
                ->with(['category', 'supplierDivision']);

            if ($this->isUnassigned) {
                $addQuery->whereNotNull('supplier_division_id');
            } else {
                $addQuery->where(function ($q) {
                    $q->whereNull('supplier_division_id')
                      ->orWhere('supplier_division_id', '!=', $this->divisionId);
                });
            }

            if (!empty($this->addSearch)) {
                $s = '%' . trim($this->addSearch) . '%';
                $addQuery->where(function ($q) use ($s) {
                    $q->where('name', 'like', $s)
                      ->orWhere('barcode', 'like', $s)
                      ->orWhere('sku', 'like', $s);
                });
            }

            $addCandidates = $addQuery->orderBy('name')->limit(40)->get();
        }

        // Target divisions for Move Modal
        $targetDivisions = collect();
        if ($this->showMoveModal && $this->targetSupplierId) {
            $targetDivisions = SupplierDivision::where('supplier_id', $this->targetSupplierId)
                ->orderBy('name')
                ->get();
        }

        $allSuppliers = Supplier::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);

        return view('livewire.division-products-modal', [
            'division' => $division,
            'supplier' => $supplier,
            'products' => $products,
            'addCandidates' => $addCandidates,
            'targetDivisions' => $targetDivisions,
            'allSuppliers' => $allSuppliers,
            'isUnassigned' => $this->isUnassigned,
        ]);
    }
}
