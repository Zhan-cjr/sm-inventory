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

        $branchId = $request->input('branch_id') ?: $request->user()->branch_id;
        if (!$branchId) {
            $firstBranch = \App\Models\Branch::where('organization_id', $request->user()->organization_id)->first();
            if ($firstBranch) {
                $branchId = $firstBranch->id;
            } else {
                return response()->json(['error' => 'No branch available for this organization'], 400);
            }
        }

        return $this->processBulk($request->user(), [
            [
                'product_id' => $request->product_id,
                'suggested_qty' => $request->suggested_qty
            ]
        ], $branchId);
    }

    public function createBulkFromSuggestions(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.suggested_qty' => 'required|numeric|min:0.01',
        ]);

        $branchId = $request->input('branch_id') ?: $request->user()->branch_id;
        if (!$branchId) {
            $firstBranch = \App\Models\Branch::where('organization_id', $request->user()->organization_id)->first();
            if ($firstBranch) {
                $branchId = $firstBranch->id;
            } else {
                return response()->json(['error' => 'No branch available for this organization'], 400);
            }
        }

        return $this->processBulk($request->user(), $request->items, $branchId);
    }

    private function processBulk($user, $items, $branchId)
    {
        return DB::transaction(function () use ($user, $items, $branchId) {
            // Group items by supplier_id
            $itemsBySupplier = [];
            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                if ($product && $product->supplier_id) {
                    $itemsBySupplier[$product->supplier_id][] = [
                        'product' => $product,
                        'qty' => $item['suggested_qty']
                    ];
                }
            }

            if (empty($itemsBySupplier)) {
                return response()->json(['error' => 'Tidak ada produk valid dengan Supplier yang ditemukan'], 400);
            }

            $createdPOs = [];

            foreach ($itemsBySupplier as $supplierId => $supplierItems) {
                $firstProduct = $supplierItems[0]['product'];
                
                $po = PurchaseOrder::create([
                    'organization_id' => $firstProduct->organization_id,
                    'branch_id' => $branchId,
                    'supplier_id' => $supplierId,
                    'po_number' => 'PO-' . date('YmdHis') . '-' . rand(100, 999),
                    'po_date' => now(),
                    'status' => 'DRAFT',
                    'total_amount' => 0,
                    'created_by' => $user->id,
                ]);

                $totalAmount = 0;
                foreach ($supplierItems as $sItem) {
                    $product = $sItem['product'];
                    $qty = $sItem['qty'];
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
                $createdPOs[] = $po->po_number;
            }

            return response()->json([
                'message' => count($createdPOs) > 1 
                    ? count($createdPOs) . ' Draft PO berhasil dibuat (terpisah berdasarkan Supplier)' 
                    : '1 Draft Pesanan Pembelian berhasil dibuat',
                'po_numbers' => $createdPOs,
                'items_count' => count($items)
            ]);
        });
    }
}
