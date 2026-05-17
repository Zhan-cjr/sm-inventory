<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function createFromSuggestion(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'suggested_qty' => 'required|numeric|min:0.01',
        ]);

        return $this->processBulk($request->user(), [
            [
                'product_id' => $request->product_id,
                'suggested_qty' => $request->suggested_qty
            ]
        ]);
    }

    public function createBulkFromSuggestions(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.suggested_qty' => 'required|numeric|min:0.01',
        ]);

        return $this->processBulk($request->user(), $request->items);
    }

    private function processBulk($user, $items)
    {
        return DB::transaction(function () use ($user, $items) {
            $firstProduct = Product::find($items[0]['product_id']);
            
            $po = PurchaseOrder::create([
                'organization_id' => $firstProduct->organization_id,
                'branch_id' => $user->branch_id,
                'supplier_id' => $firstProduct->supplier_id,
                'po_number' => 'PO-' . date('YmdHis'),
                'po_date' => now(),
                'status' => 'DRAFT',
                'total_amount' => 0,
                'created_by' => $user->id,
            ]);

            $totalAmount = 0;
            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                $qty = $item['suggested_qty'];
                $subtotal = $qty * $product->cost_price;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $product->id,
                    'quantity_ordered' => $qty,
                    'unit_cost' => $product->cost_price,
                    'subtotal' => $subtotal,
                ]);

                $totalAmount += $subtotal;
            }

            $po->update(['total_amount' => $totalAmount]);

            return response()->json([
                'message' => count($items) > 1 
                    ? 'Draft Pesanan Pembelian Massal berhasil dibuat' 
                    : 'Draft Pesanan Pembelian berhasil dibuat',
                'po' => $po,
                'items_count' => count($items)
            ]);
        });
    }
}
