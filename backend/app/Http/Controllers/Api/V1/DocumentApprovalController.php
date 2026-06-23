<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Approval;

class DocumentApprovalController extends Controller
{
    public function pending(Request $request)
    {
        $user = auth()->user();
        $branchId = $request->query('branch_id');

        $allowedTypes = [];
        if ($user->hasCustomAuthorization('APPROVE_PO')) {
            $allowedTypes[] = \App\Models\PurchaseOrder::class;
        }
        if ($user->hasCustomAuthorization('APPROVE_STOCK_ADJUSTMENT')) {
            $allowedTypes[] = \App\Models\StockAdjustment::class;
        }
        if ($user->hasCustomAuthorization('APPROVE_GR_OVERQUANTITY')) {
            $allowedTypes[] = \App\Models\WarehouseCheck::class;
        }

        if (empty($allowedTypes)) {
            return response()->json(['data' => []]);
        }

        $query = Approval::with(['approvable', 'approvable.branch'])
            ->where('status', 'pending')
            ->whereIn('approvable_type', $allowedTypes);

        $approvals = $query->latest()->get();
        $approvals->load([
            'approvable' => function ($morphTo) {
                $morphTo->morphWith([
                    \App\Models\PurchaseOrder::class => ['creator', 'supplier'],
                    \App\Models\StockAdjustment::class => ['recorder'],
                    \App\Models\WarehouseCheck::class => ['checker', 'purchaseOrder.supplier']
                ]);
            }
        ]);

        $data = $approvals->map(function ($approval) {
            $model = $approval->approvable;
            if (!$model) return null;

            $type = 'Otorisasi Koreksi Stok';
            $number = '-';
            $total = 0;
            $userName = 'System';
            $supplierName = null;
            $branchName = $model->branch?->name;

            if (get_class($model) === \App\Models\PurchaseOrder::class) {
                $type = 'Otorisasi PO';
                $number = $model->po_number;
                $total = $model->total_amount;
                $userName = $model->creator?->name ?? 'System';
                $supplierName = $model->supplier?->name;
            } elseif (get_class($model) === \App\Models\StockAdjustment::class) {
                $type = 'Otorisasi Koreksi Stok';
                $number = $model->adjustment_number;
                $total = $model->total_value;
                $userName = $model->recorder?->name ?? 'System';
            } elseif (get_class($model) === \App\Models\WarehouseCheck::class) {
                $type = 'Otorisasi Penerimaan Qty Gudang';
                $number = 'Cek PO: ' . ($model->purchaseOrder?->po_number ?? '-');
                // Target the items from the check to compute total quantity
                $total = $model->items->sum('qty_scanned');
                $userName = $model->checker?->name ?? 'System';
                $supplierName = $model->purchaseOrder?->supplier?->name;
            }

            return [
                'id' => $approval->id,
                'type' => $type,
                'number' => $number,
                'total' => $total,
                'created_by' => $userName,
                'supplier_name' => $supplierName,
                'notes' => $approval->notes,
                'created_at' => $approval->created_at,
                'branch_name' => $branchName,
            ];
        })->filter()->values();

        return response()->json(['data' => $data]);
    }

    public function action(Request $request, $id, $action)
    {
        $approval = Approval::findOrFail($id);
        $user = auth()->user();

        // Check permission based on type
        $type = get_class($approval->approvable);
        if ($type === \App\Models\PurchaseOrder::class && !$user->hasCustomAuthorization('APPROVE_PO')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($type === \App\Models\StockAdjustment::class && !$user->hasCustomAuthorization('APPROVE_STOCK_ADJUSTMENT')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($type === \App\Models\WarehouseCheck::class && !$user->hasCustomAuthorization('APPROVE_GR_OVERQUANTITY')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notes = $request->input('notes');

        if ($action === 'approve') {
            $approval->approvable->approve($user->id, $notes ?: 'Disetujui dari Mobile App');
        } elseif ($action === 'reject') {
            $approval->approvable->reject($user->id, $notes ?: 'Ditolak dari Mobile App');
        } else {
            return response()->json(['message' => 'Invalid action'], 400);
        }

        return response()->json(['message' => 'Success']);
    }

    public function details($id)
    {
        $approval = Approval::with(['approvable'])->findOrFail($id);
        
        $model = $approval->approvable;
        if (!$model) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        $modelClass = get_class($model);

        if ($modelClass === \App\Models\PurchaseOrder::class) {
            $model->load(['items.product:id,name,sku', 'supplier:id,name']);
            return response()->json([
                'type' => 'Otorisasi PO',
                'number' => $model->po_number,
                'supplier' => $model->supplier?->name,
                'date' => $model->po_date?->format('d-m-Y'),
                'total' => $model->total_amount,
                'notes' => $model->notes,
                'items' => $model->items->map(function ($item) use ($model) {
                    $thirtyDaysAgo = \Carbon\Carbon::now()->subDays(30);
                    $soldLast30Days = \App\Models\InventoryLog::where('product_id', $item->product_id)
                        ->where('created_at', '>=', $thirtyDaysAgo)
                        ->where('log_type', 'SALE')
                        ->when($model->branch_id, function($q) use ($model) {
                            return $q->where('branch_id', $model->branch_id);
                        })
                        ->sum('quantity_change');
                    $soldLast30Days = abs($soldLast30Days);

                    $stockQuery = \App\Models\Stock::where('product_id', $item->product_id);
                    if ($model->branch_id) {
                        $stockQuery->where('branch_id', $model->branch_id);
                    }
                    $currentStock = $stockQuery->sum('quantity_on_hand');

                    return [
                        'product_name' => $item->product?->name,
                        'product_sku' => $item->product?->sku,
                        'qty' => $item->quantity_ordered,
                        'price' => $item->unit_cost,
                        'subtotal' => $item->subtotal,
                        'avg_sales_per_month' => (float) $soldLast30Days,
                        'current_stock' => (float) $currentStock,
                    ];
                })
            ]);
        } elseif ($modelClass === \App\Models\StockAdjustment::class) {
            $model->load(['items.product:id,name,sku', 'adjustmentReason:id,name']);
            return response()->json([
                'type' => 'Otorisasi Koreksi Stok',
                'number' => $model->adjustment_number,
                'reason' => $model->adjustmentReason?->name,
                'date' => $model->adjustment_date ? \Carbon\Carbon::parse($model->adjustment_date)->format('d-m-Y') : null,
                'total' => $model->total_value,
                'notes' => $model->notes,
                'items' => $model->items->map(function ($item) {
                    return [
                        'product_name' => $item->product?->name,
                        'product_sku' => $item->product?->sku,
                        'old_qty' => $item->previous_quantity,
                        'new_qty' => $item->new_quantity,
                        'diff' => floatval($item->new_quantity) - floatval($item->previous_quantity),
                    ];
                })
            ]);
        } elseif ($modelClass === \App\Models\WarehouseCheck::class) {
            $model->load(['items.product:id,name,sku', 'purchaseOrder.supplier:id,name']);
            return response()->json([
                'type' => 'Otorisasi Penerimaan Qty Gudang',
                'number' => 'Cek PO: ' . ($model->purchaseOrder?->po_number ?? '-'),
                'supplier' => $model->purchaseOrder?->supplier?->name,
                'date' => $model->created_at?->format('d-m-Y'),
                'total' => $model->items->sum('qty_scanned'),
                'notes' => $model->notes,
                'items' => $model->items->map(function ($item) {
                    return [
                        'product_name' => $item->product?->name,
                        'product_sku' => $item->product?->sku,
                        'old_qty' => $item->qty_po,
                        'new_qty' => $item->qty_scanned,
                        'diff' => floatval($item->qty_scanned) - floatval($item->qty_po),
                    ];
                })
            ]);
        }
        
        return response()->json(['message' => 'Type not supported'], 400);
    }
}
