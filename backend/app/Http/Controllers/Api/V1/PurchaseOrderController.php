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
            // Group items by supplier_id and supplier_division_id
            $itemsByGroup = [];
            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    $stock = Stock::where('branch_id', $branchId)->where('product_id', $product->id)->first();
                    $targetSupplierId = $stock?->supplier_id ?: $product->supplier_id;
                    $targetDivisionId = $stock?->supplier_id ? $stock->supplier_division_id : $product->supplier_division_id;

                    if ($targetSupplierId) {
                        $groupKey = $targetSupplierId . '_' . ($targetDivisionId ?: 'none');
                        $itemsByGroup[$groupKey][] = [
                            'product' => $product,
                            'supplier_id' => $targetSupplierId,
                            'supplier_division_id' => $targetDivisionId,
                            'qty' => $item['suggested_qty'],
                            'original_qty' => $item['original_qty'] ?? $item['suggested_qty']
                        ];
                    }
                }
            }

            if (empty($itemsByGroup)) {
                return response()->json(['error' => 'Tidak ada produk valid dengan Supplier yang ditemukan'], 400);
            }

            $createdPOs = [];

            foreach ($itemsByGroup as $groupKey => $supplierItems) {
                $firstProduct = $supplierItems[0]['product'];
                $firstItem = $supplierItems[0];
                $supplierId = $firstItem['supplier_id'];
                $divisionId = $firstItem['supplier_division_id'];
                
                $needsApproval = false;
                $approvalReasons = [];
                foreach ($supplierItems as $sItem) {
                    $originalQty = $sItem['original_qty'] ?? $sItem['qty'];
                    if ((float)$sItem['qty'] > (float)$originalQty) {
                        $needsApproval = true;
                        $approvalReasons[] = "Kuantitas {$sItem['product']->name} ({$sItem['qty']}) melebihi saran order sistem ({$originalQty}).";
                    }
                }

                $supplier = \App\Models\Supplier::find($supplierId);
                $expiredDate = null;
                if ($supplier && $supplier->default_po_expired_days > 0) {
                    $expiredDate = now()->addDays($supplier->default_po_expired_days)->format('Y-m-d');
                }

                $po = PurchaseOrder::create([
                    'organization_id' => $firstProduct->organization_id,
                    'branch_id' => $branchId,
                    'supplier_id' => $supplierId,
                    'supplier_division_id' => $divisionId,
                    'po_number' => 'PO-' . date('YmdHis') . '-' . rand(100, 999),
                    'po_date' => now(),
                    'expired_date' => $expiredDate,
                    'status' => $needsApproval ? 'pending_approval' : 'approved',
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
                        'quantity_suggested' => $sItem['original_qty'] ?? $qty,
                        'quantity_ordered' => $qty,
                        'unit_cost' => $product->cost_price,
                        'subtotal' => $subtotal,
                    ]);

                    $totalAmount += $subtotal;
                }

                $po->update(['total_amount' => $totalAmount]);

                if ($needsApproval) {
                    $po->requestApproval('Otomatis: ' . implode(', ', $approvalReasons));
                }

                $createdPOs[] = $po->po_number;
            }

            return response()->json([
                'message' => count($createdPOs) > 1 
                    ? count($createdPOs) . ' Draft PO berhasil dibuat (terpisah berdasarkan Pemasok & Sub Divisi)' 
                    : '1 Draft Pesanan Pembelian berhasil dibuat',
                'po_numbers' => $createdPOs,
                'items_count' => count($items)
            ]);
        });
    }
}
