<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\WarehouseCheck;
use App\Models\WarehouseCheckItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseCheckController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $poQuery = \App\Models\PurchaseOrder::where('status', 'approved');

        $branches = [];
        if ($user && $user->branch_id) {
            $poQuery->where('branch_id', $user->branch_id);
        } else {
            $branches = \App\Models\Branch::all();
            if ($request->has('branch_id') && $request->branch_id) {
                $poQuery->where('branch_id', $request->branch_id);
            }
        }

        $allPos = $poQuery->orderBy('created_at', 'desc')->get();
        $purchaseOrders = $allPos->filter(function ($po) {
            return $po->remainingQuantity() > 0;
        })->values();

        // Get suppliers from the available POs
        $supplierIds = $purchaseOrders->pluck('supplier_id')->unique();
        $suppliers = \App\Models\Supplier::whereIn('id', $supplierIds)->get();

        return view('warehouse.receive.index', compact('suppliers', 'purchaseOrders', 'branches'));
    }

    public function search(Request $request)
    {
        $request->validate([
            'po_number' => 'required|string'
        ]);

        $user = Auth::user();
        $query = PurchaseOrder::where('po_number', $request->po_number)
            ->where('status', 'approved');

        if ($user && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        $po = $query->first();

        if (!$po) {
            return back()->with('error', 'Purchase Order tidak ditemukan, belum di-approve, atau bukan milik cabang Anda.');
        }

        if ($po->remainingQuantity() <= 0) {
            return back()->with('error', 'Semua barang di PO ini sudah diproses pengecekan gudang.');
        }

        return redirect()->route('warehouse.receive.scan', ['po_id' => $po->id]);
    }

    public function scan($po_id)
    {
        $po = PurchaseOrder::with(['items.product'])->findOrFail($po_id);
        
        $rejectedCheck = WarehouseCheck::with('items')->where('purchase_order_id', $po->id)->where('status', 'rejected')->first();
        $previousScans = [];
        if ($rejectedCheck) {
            foreach ($rejectedCheck->items as $item) {
                $previousScans[$item->product_id] = floatval($item->qty_scanned);
            }
        }

        $previousChecks = WarehouseCheck::with('items')->where('purchase_order_id', $po->id)
            ->whereIn('status', ['pending', 'pending_approval', 'approved', 'processed'])
            ->get();
            
        $alreadyScanned = [];
        foreach ($previousChecks as $c) {
            foreach ($c->items as $item) {
                $alreadyScanned[$item->product_id] = ($alreadyScanned[$item->product_id] ?? 0) + $item->qty_scanned;
            }
        }

        $items = $po->items->map(function($item) use ($previousScans, $alreadyScanned) {
            $remainingQty = max(0, floatval($item->quantity_ordered) - ($alreadyScanned[$item->product_id] ?? 0));
            return [
                'product_id' => $item->product_id,
                'barcode' => $item->product->barcode,
                'name' => $item->product->name,
                'qty_po' => $remainingQty,
                'qty_scanned' => isset($previousScans[$item->product_id]) ? $previousScans[$item->product_id] : 0,
            ];
        });

        return view('warehouse.receive.scan', compact('po', 'items', 'rejectedCheck'));
    }

    public function submit(Request $request, $po_id)
    {
        $po = PurchaseOrder::findOrFail($po_id);
        
        $scannedItems = json_decode($request->scanned_items, true);
        
        if (empty($scannedItems)) {
            return back()->with('error', 'Tidak ada barang yang di-scan.');
        }

        $check = WarehouseCheck::where('purchase_order_id', $po->id)->where('status', 'rejected')->first();
        
        if ($check) {
            // Update existing check
            $check->update([
                'checked_by' => Auth::id(),
                'status' => 'pending',
                'notes' => $request->notes,
            ]);
            $check->items()->delete(); // Clear old items to recreate
        } else {
            // Create new check
            $check = WarehouseCheck::create([
                'purchase_order_id' => $po->id,
                'branch_id' => $po->branch_id, // assume same branch
                'checked_by' => Auth::id(),
                'status' => 'pending',
                'notes' => $request->notes,
            ]);
        }

        $hasOverQty = false;

        $previousChecks = WarehouseCheck::with('items')->where('purchase_order_id', $po->id)
            ->whereIn('status', ['pending', 'pending_approval', 'approved', 'processed'])
            ->get();
            
        $alreadyScanned = [];
        foreach ($previousChecks as $c) {
            foreach ($c->items as $item) {
                $alreadyScanned[$item->product_id] = ($alreadyScanned[$item->product_id] ?? 0) + $item->qty_scanned;
            }
        }

        foreach ($scannedItems as $productId => $qtyScanned) {
            $poItem = $po->items()->where('product_id', $productId)->first();
            $qtyOrdered = $poItem ? $poItem->quantity_ordered : 0;
            $qtyAlreadyScanned = $alreadyScanned[$productId] ?? 0;
            $remainingQty = max(0, $qtyOrdered - $qtyAlreadyScanned);
            
            WarehouseCheckItem::create([
                'warehouse_check_id' => $check->id,
                'product_id' => $productId,
                'qty_po' => $remainingQty,
                'qty_scanned' => $qtyScanned,
            ]);

            if ($qtyScanned > $remainingQty) {
                $hasOverQty = true;
            }
        }

        if ($hasOverQty) {
            $check->requestApproval('Terdapat kuantitas barang yang melebihi sisa PO.', 1);
            return redirect()->route('warehouse.receive.index')->with('warning', 'Pengecekan berhasil disimpan, namun karena ada barang yang melebihi Sisa PO, membutuhkan Otorisasi Supervisor.');
        } else {
            $check->update(['status' => 'approved']);
            return redirect()->route('warehouse.receive.index')->with('success', 'Pengecekan berhasil disimpan dan Disetujui (Sesuai Sisa PO). Silakan infokan EDP untuk memproses GR.');
        }
    }
}
