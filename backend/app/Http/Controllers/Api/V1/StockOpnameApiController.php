<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StockOpnameSession;
use App\Models\StockOpnameRackSession;
use App\Models\StockOpnameItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class StockOpnameApiController extends Controller
{
    public function getActiveSessions(Request $request)
    {
        $user = $request->user();
        
        $sessions = StockOpnameSession::with(['rackSessions.rack'])
            ->where('branch_id', $user->branch_id)
            ->whereIn('status', ['DRAFT', 'IN_PROGRESS', 'COUNT1_DONE', 'COUNT2_DONE', 'DISCREPANCY'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $sessions]);
    }

    public function scanProduct(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|uuid',
            'rack_session_id' => 'required|uuid',
            'barcode' => 'required|string',
            'quantity' => 'required|numeric',
            'role' => 'required|in:PENGHITUNG_1,PENGECEK_2'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Find the product by barcode or sku
        $product = Product::where('sku', $request->barcode)->first();
        if (!$product) {
            return response()->json(['error' => 'Produk tidak ditemukan'], 404);
        }

        $session = StockOpnameSession::find($request->session_id);
        if (!$session) {
            return response()->json(['error' => 'Sesi tidak ditemukan'], 404);
        }

        // Cari item di session dan rack ini
        $item = StockOpnameItem::firstOrNew([
            'session_id' => $request->session_id,
            'rack_session_id' => $request->rack_session_id,
            'product_id' => $product->id,
        ]);

        if ($request->role === 'PENGHITUNG_1') {
            // Add quantity instead of replacing? Usually scan adds +1 or sets it.
            // Assuming the frontend sends the total counted so far for this session.
            $item->count1_quantity = $request->quantity;
            $item->count1_at = Carbon::now();
            if ($item->status !== 'DISCREPANCY' && $item->status !== 'COUNT2_DONE') {
                $item->status = 'COUNT1_DONE';
            }
        } else {
            $item->count2_quantity = $request->quantity;
            $item->count2_at = Carbon::now();
        }

        $item->save();

        if ($request->role === 'PENGECEK_2') {
            $item->detectDiscrepancy();
        }

        return response()->json([
            'message' => 'Berhasil scan',
            'data' => [
                'product_name' => $product->name,
                'count1_quantity' => $item->count1_quantity,
                'count2_quantity' => $item->count2_quantity,
                'status' => $item->status,
            ]
        ]);
    }

    public function lockRack(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|uuid',
            'rack_session_id' => 'required|uuid',
            'role' => 'required|in:PENGHITUNG_1,PENGECEK_2'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $rackSession = StockOpnameRackSession::find($request->rack_session_id);
        if (!$rackSession) {
            return response()->json(['error' => 'Rak tidak ditemukan'], 404);
        }

        if ($request->role === 'PENGHITUNG_1') {
            $rackSession->count1_status = 'DONE';
            $rackSession->count1_by_name = $user->name;
        } else {
            $rackSession->count2_status = 'DONE';
            $rackSession->count2_by_name = $user->name;
        }
        $rackSession->save();

        return response()->json(['message' => 'Sesi hitung untuk rak ini berhasil dikunci (Selesai).']);
    }
}
