<?php

namespace App\Http\Controllers;

use App\Models\StockOpnameItem;
use App\Models\StockOpnameRackSession;
use App\Models\StockOpnameSession;
use Illuminate\Http\Request;

class OpnamePublicController extends Controller
{
    // ============================================================
    // PORTAL UTAMA: Scan QR Sesi → Pilih Peran
    // URL: /opname/{sessionToken}
    // Ini adalah halaman yang pertama dibuka oleh siapapun
    // yang scan QR sesi (baik penghitung maupun pengecek)
    // ============================================================

    public function showPortal(string $sessionToken)
    {
        $session = StockOpnameSession::with(['branch', 'rackSessions'])
            ->where('session_token', $sessionToken)
            ->firstOrFail();

        $total      = $session->rackSessions->count();
        $c1Done     = $session->rackSessions->where('count1_status', 'DONE')->count();
        $c2Done     = $session->rackSessions->where('count2_status', 'DONE')->count();
        $discrepancy = $session->items()->where('status', 'DISCREPANCY')->count();

        return view('opname.portal', compact(
            'session', 'sessionToken', 'total', 'c1Done', 'c2Done', 'discrepancy'
        ));
    }

    // ============================================================
    // Cetak QR semua rak dalam satu sesi (admin login → akses via ID sesi)
    // ============================================================

    public function printQr(string $sessionId)
    {
        $session = StockOpnameSession::with(['branch', 'rackSessions.rack'])
            ->findOrFail($sessionId);

        $rackSessions = $session->rackSessions()->with('rack')->get();

        return view('opname.print-qr', compact('session', 'rackSessions'));
    }

    // ============================================================
    // Cetak QR satu rak saja
    // ============================================================

    public function printQrSingle(string $rackSessionId)
    {
        $rackSession = StockOpnameRackSession::with(['rack', 'session.branch'])
            ->findOrFail($rackSessionId);

        return view('opname.print-qr-single', compact('rackSession'));
    }

    // ============================================================
    // Penghitung 1: Scan QR rak → /opname/hitung/{rackToken}
    // ============================================================

    public function showCount1(string $rackToken)
    {
        $rackSession = StockOpnameRackSession::with([
            'session.branch',
            'rack',
            'items.product',
        ])->where('rack_token', $rackToken)->firstOrFail();

        // Track live counting activity
        if ($rackSession->count1_status !== 'DONE') {
            $rackSession->update(['active_count_at' => now()]);
        }

        // Validasi sesi masih COUNTING
        if (!in_array($rackSession->session->status, ['COUNTING', 'CHECKING'])) {
            return view('opname.error', [
                'message' => 'Sesi opname ini sudah tidak menerima input penghitung.',
                'session' => $rackSession->session,
            ]);
        }

        // Sudah dikunci oleh penghitung 1
        if ($rackSession->count1_status === 'DONE') {
            return view('opname.locked', [
                'message'     => 'Rak ini sudah dihitung oleh penghitung pertama dan terkunci.',
                'locker_name' => $rackSession->count1_by_name,
                'locked_at'   => $rackSession->count1_at,
                'rack'        => $rackSession->rack,
                'role'        => 'count1',
            ]);
        }

        return view('opname.hitung', [
            'rackSession' => $rackSession,
            'session'     => $rackSession->session,
            'rack'        => $rackSession->rack,
            'items'       => $rackSession->items()
                ->with('product.category')
                ->get()
                ->sortBy('product.name'),
        ]);
    }

    public function submitCount1(Request $request, string $rackToken)
    {
        $request->validate([
            'counter_name' => 'required|string|max:100',
            'quantities'   => 'required|array',
            'quantities.*' => 'nullable|numeric|min:0',
        ]);

        $rackSession = StockOpnameRackSession::with('session')
            ->where('rack_token', $rackToken)
            ->firstOrFail();

        // Double-check lock
        if ($rackSession->count1_status === 'DONE') {
            return redirect()->back()->with('error', 'Rak ini sudah dikunci!');
        }

        if (!in_array($rackSession->session->status, ['COUNTING', 'CHECKING'])) {
            return redirect()->back()->with('error', 'Sesi tidak aktif.');
        }

        \DB::transaction(function () use ($request, $rackSession) {
            $submittedIds = [];
            foreach ($request->quantities as $itemId => $qty) {
                $item = StockOpnameItem::where('id', $itemId)
                    ->where('rack_session_id', $rackSession->id)
                    ->first();

                if ($item && $item->status === 'PENDING') {
                    $actualQty = ($qty === null || $qty === '') ? 0.0 : (float) $qty;
                    $item->update([
                        'count1_quantity' => $actualQty,
                        'count1_at'       => now(),
                        'status'          => 'COUNT1_DONE',
                    ]);
                    $submittedIds[] = $itemId;
                }
            }

            // Handle items removed from rack
            if ($request->has('remove_from_rack') && is_array($request->remove_from_rack)) {
                foreach ($request->remove_from_rack as $itemId) {
                    $item = StockOpnameItem::where('id', $itemId)
                        ->where('rack_session_id', $rackSession->id)
                        ->first();
                        
                    if ($item) {
                        // Detach from Stock
                        $stock = \App\Models\Stock::where('branch_id', $rackSession->session->branch_id)
                            ->where('product_id', $item->product_id)
                            ->first();
                            
                        if ($stock && $rackSession->rack_id) {
                            $stock->racks()->detach($rackSession->rack_id);
                        }
                        
                        $item->delete();
                        
                        // Ensure it's not marked as submitted for the 0.0 default fallback
                        $submittedIds[] = $itemId; 
                    }
                }
            }

            // Handle dynamically added products (new_quantities)
            if ($request->has('new_quantities') && is_array($request->new_quantities)) {
                foreach ($request->new_quantities as $productId => $qty) {
                    $actualQty = ($qty === null || $qty === '') ? 0.0 : (float) $qty;
                    $stock = \App\Models\Stock::where('branch_id', $rackSession->session->branch_id)
                        ->where('product_id', $productId)
                        ->first();
                        
                    \App\Models\StockOpnameItem::create([
                        'session_id' => $rackSession->session_id,
                        'rack_session_id' => $rackSession->id,
                        'product_id' => $productId,
                        'system_quantity' => $stock ? $stock->quantity_on_hand : 0,
                        'count1_quantity' => $actualQty,
                        'count1_at' => now(),
                        'status' => 'COUNT1_DONE',
                    ]);
                }
            }

            // Default any remaining PENDING items in this rack session to 0.0
            $remainingPendingItems = StockOpnameItem::where('rack_session_id', $rackSession->id)
                ->where('status', 'PENDING')
                ->whereNotIn('id', $submittedIds)
                ->get();

            foreach ($remainingPendingItems as $item) {
                $item->update([
                    'count1_quantity' => 0.0,
                    'count1_at'       => now(),
                    'status'          => 'COUNT1_DONE',
                ]);
            }

            $rackSession->update([
                'count1_status'  => 'DONE',
                'count1_by_name' => $request->counter_name,
                'count1_at'      => now(),
            ]);
        });

        return redirect()->route('opname.done', [
            'role'      => 'penghitung',
            'rack_code' => $rackSession->rack?->rack_code,
        ]);
    }

    // ============================================================
    // Pengecek 2: Scan QR sesi → /opname/cek/{sessionToken}
    // ============================================================

    public function showCheck2List(string $sessionToken)
    {
        $session = StockOpnameSession::with(['branch', 'rackSessions.rack'])
            ->where('session_token', $sessionToken)
            ->firstOrFail();

        if (!in_array($session->status, ['COUNTING', 'CHECKING'])) {
            return view('opname.error', [
                'message' => 'Sesi opname ini sudah tidak menerima pengecekan.',
                'session' => $session,
            ]);
        }

        // Rak yang sudah COUNT1_DONE dan belum COUNT2_DONE
        $racksReady = $session->rackSessions()
            ->with('rack')
            ->where('count1_status', 'DONE')
            ->get();

        $racksDone = $session->rackSessions()
            ->with('rack')
            ->where('count2_status', 'DONE')
            ->get();

        return view('opname.cek', [
            'session'     => $session,
            'racksReady'  => $racksReady,
            'racksDone'   => $racksDone,
            'sessionToken' => $sessionToken,
        ]);
    }

    public function showCheck2Form(string $sessionToken, string $rackId)
    {
        $session = StockOpnameSession::where('session_token', $sessionToken)->firstOrFail();

        $rackSession = StockOpnameRackSession::with(['rack', 'items.product.category'])
            ->where('session_id', $session->id)
            ->where('id', $rackId)
            ->firstOrFail();

        // Track live checking activity
        if ($rackSession->count2_status !== 'DONE') {
            $rackSession->update(['active_check_at' => now()]);
        }

        // Harus sudah COUNT1_DONE
        if ($rackSession->count1_status !== 'DONE') {
            return redirect()->route('opname.cek', $sessionToken)
                ->with('error', 'Rak ini belum selesai dihitung penghitung pertama.');
        }

        // Sudah COUNT2_DONE → terkunci
        if ($rackSession->count2_status === 'DONE') {
            return view('opname.locked', [
                'message'     => 'Rak ini sudah dicek oleh pengecek ke-2 dan terkunci.',
                'locker_name' => $rackSession->count2_by_name,
                'locked_at'   => $rackSession->count2_at,
                'rack'        => $rackSession->rack,
                'role'        => 'count2',
            ]);
        }

        return view('opname.cek-form', [
            'session'      => $session,
            'rackSession'  => $rackSession,
            'rack'         => $rackSession->rack,
            'sessionToken' => $sessionToken,
            // count1_quantity DISEMBUNYIKAN dari view (tidak dikirim ke template)
            'items' => $rackSession->items()
                ->with('product.category')
                ->get()
                ->sortBy('product.name')
                ->map(fn ($item) => [
                    'id'           => $item->id,
                    'product_name' => $item->product?->name,
                    'product_sku'  => $item->product?->sku,
                    'barcode'      => $item->product?->barcode,
                    'additional_barcodes' => isset($item->product?->metadata['additional_barcodes']) && is_array($item->product?->metadata['additional_barcodes']) ? implode(',', $item->product?->metadata['additional_barcodes']) : '',
                    // TIDAK mengirim count1_quantity → pengecek 2 objektif
                ]),
        ]);
    }

    public function submitCount2(Request $request, string $sessionToken, string $rackId)
    {
        $request->validate([
            'checker_name' => 'required|string|max:100',
            'quantities'   => 'nullable|array',
            'quantities.*' => 'nullable|numeric|min:0',
        ]);

        $session = StockOpnameSession::where('session_token', $sessionToken)->firstOrFail();

        $rackSession = StockOpnameRackSession::where('session_id', $session->id)
            ->where('id', $rackId)
            ->firstOrFail();

        if ($rackSession->count2_status === 'DONE') {
            return redirect()->back()->with('error', 'Rak ini sudah terkunci untuk pengecek 2!');
        }

        \DB::transaction(function () use ($request, $rackSession) {
            $submittedIds = [];
            $quantities = $request->input('quantities', []);

            if (is_array($quantities)) {
                foreach ($quantities as $itemId => $qty) {
                    $item = StockOpnameItem::where('id', $itemId)
                        ->where('rack_session_id', $rackSession->id)
                        ->where('status', 'COUNT1_DONE')
                        ->first();

                    if ($item) {
                        $actualQty = ($qty === null || $qty === '') ? 0.0 : (float) $qty;
                        $item->count2_quantity = $actualQty;
                        $item->count2_at       = now();
                        $item->save();

                        // Deteksi selisih (juga evaluasi cross-rack)
                        $item->detectDiscrepancy();
                        $submittedIds[] = $itemId;
                    }
                }
            }

            // Handle items removed from rack
            if ($request->has('remove_from_rack') && is_array($request->remove_from_rack)) {
                foreach ($request->remove_from_rack as $itemId) {
                    $item = StockOpnameItem::where('id', $itemId)
                        ->where('rack_session_id', $rackSession->id)
                        ->first();
                        
                    if ($item) {
                        // Detach from Stock
                        $stock = \App\Models\Stock::where('branch_id', $rackSession->session->branch_id)
                            ->where('product_id', $item->product_id)
                            ->first();
                            
                        if ($stock && $rackSession->rack_id) {
                            $stock->racks()->detach($rackSession->rack_id);
                        }
                        
                        $item->delete();
                        
                        // Ensure it's not marked as submitted for the 0.0 default fallback
                        $submittedIds[] = $itemId; 
                    }
                }
            }

            // Handle dynamically added products (new_quantities)
            if ($request->has('new_quantities') && is_array($request->new_quantities)) {
                foreach ($request->new_quantities as $productId => $qty) {
                    $actualQty = ($qty === null || $qty === '') ? 0.0 : (float) $qty;
                    $stock = \App\Models\Stock::where('branch_id', $rackSession->session->branch_id)
                        ->where('product_id', $productId)
                        ->first();
                        
                    $newItem = \App\Models\StockOpnameItem::create([
                        'session_id' => $rackSession->session_id,
                        'rack_session_id' => $rackSession->id,
                        'product_id' => $productId,
                        'system_quantity' => $stock ? $stock->quantity_on_hand : 0,
                        'count1_quantity' => 0.0, // Pengecek 2 menambahkan produk baru yg terlewat, brarti count1=0
                        'count1_at' => now(),
                        'count2_quantity' => $actualQty,
                        'count2_at' => now(),
                        'status' => 'COUNT1_DONE',
                    ]);
                    
                    $newItem->detectDiscrepancy();
                }
            }

            // Default any remaining COUNT1_DONE items in this rack session to 0.0
            $remainingItems = StockOpnameItem::where('rack_session_id', $rackSession->id)
                ->where('status', 'COUNT1_DONE')
                ->whereNotIn('id', $submittedIds)
                ->get();

            foreach ($remainingItems as $item) {
                $item->count2_quantity = 0.0;
                $item->count2_at       = now();
                $item->save();

                // Deteksi selisih (juga evaluasi cross-rack)
                $item->detectDiscrepancy();
            }

            $rackSession->update([
                'count2_status'  => 'DONE',
                'count2_by_name' => $request->checker_name,
                'count2_at'      => now(),
            ]);
        });

        return redirect()->route('opname.done', [
            'role'      => 'pengecek',
            'rack_code' => $rackSession->rack?->rack_code,
            'next_url'  => route('opname.cek', $sessionToken),
        ]);
    }

    public function done(Request $request)
    {
        return view('opname.done', [
            'role'      => $request->role,
            'rack_code' => $request->rack_code,
            'next_url'  => $request->next_url,
        ]);
    }

    public function searchProduct(Request $request)
    {
        $code = trim($request->query('code'));
        if (!$code) {
            return response()->json(['error' => 'Code required'], 400);
        }

        $product = \App\Models\Product::where('sku', $code)
            ->orWhere('barcode', $code)
            ->orWhereJsonContains('metadata->additional_barcodes', $code)
            ->first();

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'additional_barcodes' => isset($product->metadata['additional_barcodes']) && is_array($product->metadata['additional_barcodes']) ? $product->metadata['additional_barcodes'] : [],
            'category_name' => $product->category?->name,
        ]);
    }
}
