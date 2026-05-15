<?php

namespace App\Filament\Resources\StockAdjustments\Pages;

use App\Filament\Resources\StockAdjustments\StockAdjustmentResource;
use App\Models\AdjustmentReason;
use App\Models\Product;
use App\Models\Stock;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\On;

class CreateStockAdjustment extends CreateRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_by'] = auth()->id() ?? '00000000-0000-0000-0000-000000000001';
        unset($data['search_product']); // remove virtual field
        return $data;
    }

    /**
     * Called from JS when user presses Enter in the barcode/search TextInput.
     * Looks up the product by barcode, SKU, or name and adds it to the cart.
     */
    #[On('add-product-by-search')]
    public function addProductBySearch(string $query): void
    {
        if (blank($query)) return;

        $product = Product::where('barcode', $query)
            ->orWhere('sku', $query)
            ->orWhere('name', 'like', "%{$query}%")
            ->first();

        if (! $product) {
        $this->dispatch('product-not-found');
            return;
        }

        $items    = $this->data['items'] ?? [];
        $reasonId = $this->data['adjustment_reason_id'] ?? null;
        $branchId = $this->data['branch_id'] ?? null;
        $reason   = AdjustmentReason::find($reasonId);
        $multiplier = ($reason && $reason->type === 'MINUS') ? -1 : 1;

        // Skip if already in cart
        foreach ($items as $item) {
            if (($item['product_id'] ?? null) === $product->id) {
                $this->dispatch('product-already-in-cart');
                return;
            }
        }

        $stock = Stock::where('product_id', $product->id)
            ->where('branch_id', $branchId)
            ->value('quantity_on_hand') ?? 0;

        $items[] = [
            'product_id'          => $product->id,
            'sku'                 => $product->sku,
            'barcode'             => $product->barcode,
            'name'                => $product->name,
            'previous_quantity'   => $stock,
            'adjustment_quantity' => 1,
            'new_quantity'        => $stock + (1 * $multiplier),
        ];

        $this->data['items'] = $items;
        $this->data['search_product'] = null;
        
        $this->form->fill($this->data);


        $this->dispatch('product-added');
    }

    protected function afterCreate(): void
    {
        $record     = $this->record;
        $reason     = $record->adjustmentReason;
        $multiplier = ($reason && $reason->type === 'MINUS') ? -1 : 1;

        foreach ($record->items as $item) {
            $stock = Stock::firstOrCreate([
                'branch_id'  => $record->branch_id,
                'product_id' => $item->product_id,
            ], ['quantity_on_hand' => 0]);

            $currentStock    = (int) $stock->quantity_on_hand;
            $adjustmentQty   = (int) $item->adjustment_quantity;
            $finalAdjustment = $adjustmentQty * $multiplier;
            $newQuantity     = $currentStock + $finalAdjustment;

            $item->update([
                'previous_quantity' => $currentStock,
                'new_quantity'      => $newQuantity,
            ]);

            $stock->log_type           = 'ADJUSTMENT';
            $stock->reason_code        = $reason->name ?? 'MANUAL_UPDATE';
            $stock->notes              = $record->notes;
            $stock->reference_doc_type = 'STOCK_ADJUSTMENT';
            $stock->reference_doc_id   = $record->adjustment_number;

            $stock->update(['quantity_on_hand' => $newQuantity]);
        }
    }
}
