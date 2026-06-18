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

        if (empty($allowedTypes)) {
            return response()->json(['data' => []]);
        }

        $query = Approval::with(['approvable', 'approvable.branch'])
            ->where('status', 'pending')
            ->whereIn('approvable_type', $allowedTypes);

        if ($branchId) {
            $query->whereHasMorph('approvable', $allowedTypes, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $approvals = $query->latest()->get();
        // Eager load creator/recorder relationships to avoid N+1 and get the actual names
        $approvals->load([
            'approvable' => function ($morphTo) {
                $morphTo->morphWith([
                    \App\Models\PurchaseOrder::class => ['creator', 'supplier'],
                    \App\Models\StockAdjustment::class => ['recorder']
                ]);
            }
        ]);

        $data = $approvals->map(function ($approval) {
            $model = $approval->approvable;
            if (!$model) return null;

            $type = get_class($model) === \App\Models\PurchaseOrder::class ? 'Purchase Order' : 'Koreksi Stok';
            $number = $model->po_number ?? $model->adjustment_number ?? '-';
            $total = $model->total_amount ?? $model->total_value ?? 0;
            
            $user = 'System';
            if ($type === 'Purchase Order') {
                $user = $model->creator?->name ?? 'System';
            } else {
                $user = $model->recorder?->name ?? 'System';
            }

            return [
                'id' => $approval->id,
                'type' => $type,
                'number' => $number,
                'total' => $total,
                'created_by' => $user,
                'supplier_name' => $model->supplier?->name,
                'notes' => $approval->notes,
                'created_at' => $approval->created_at,
                'branch_name' => $model->branch?->name,
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

        if ($user->branch_id !== null && $approval->approvable->branch_id !== $user->branch_id) {
            return response()->json(['message' => 'Akses ditolak: Anda tidak dapat memproses dokumen di luar cabang Anda'], 403);
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
        $user = auth()->user();
        
        $model = $approval->approvable;
        if (!$model) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        if ($user->branch_id !== null && $model->branch_id !== $user->branch_id) {
            return response()->json(['message' => 'Unauthorized branch'], 403);
        }

        $type = get_class($model) === \App\Models\PurchaseOrder::class ? 'PO' : 'SO';

        if ($type === 'PO') {
            $model->load(['items.product:id,name,sku', 'supplier:id,name']);
            return response()->json([
                'type' => 'Purchase Order',
                'number' => $model->po_number,
                'supplier' => $model->supplier?->name,
                'date' => $model->po_date?->format('d-m-Y'),
                'total' => $model->total_amount,
                'notes' => $model->notes,
                'items' => $model->items->map(function ($item) use ($model) {
                    $thirtyDaysAgo = \Carbon\Carbon::now()->subDays(30);
                    
                    // 1. Total penjualan 30 hari terakhir (Mengambil langsung dari Kartu Stok / Inventory Log)
                    $soldLast30Days = \App\Models\InventoryLog::where('product_id', $item->product_id)
                        ->where('created_at', '>=', $thirtyDaysAgo)
                        ->where('log_type', 'SALE')
                        ->when($model->branch_id, function($q) use ($model) {
                            return $q->where('branch_id', $model->branch_id);
                        })
                        ->sum('quantity_change');
                        
                    // Karena penjualan mengurangi stok (minus), kita absolutkan nilainya
                    $soldLast30Days = abs($soldLast30Days);

                    // 2. Ambil Stok Fisik Saat Ini
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
        } else {
            $model->load(['items.product:id,name,sku', 'adjustmentReason:id,name']);
            return response()->json([
                'type' => 'Stock Adjustment',
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
        }
    }
}
