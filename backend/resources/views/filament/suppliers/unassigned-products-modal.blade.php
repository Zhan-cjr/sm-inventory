<div>
    @livewire(\App\Livewire\DivisionProductsModal::class, [
        'supplierId' => $supplier->id,
        'isUnassigned' => true,
    ], key('unassigned-' . $supplier->id))
</div>
