<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class BarcodePrintController extends Controller
{
    /**
     * Cetak label barcode tempel (32x18mm, 3 kolom)
     */
    public function printLabel(Request $request)
    {
        $sessionKey = $request->input('session_key');
        $branchId = $request->input('branch_id');
        $dateType = $request->input('date_type', 'cetak');
        $customDate = $request->input('custom_date');

        if ($sessionKey) {
            $printQueue = \Illuminate\Support\Facades\Cache::get('print_queue_' . $sessionKey);
            if (!$printQueue) {
                return abort(404, 'Sesi cetak telah kadaluarsa atau tidak valid.');
            }
            
            // Format of $printQueue: array of ['product_id' => ..., 'copies' => ...]
            $products = collect();
            foreach ($printQueue as $item) {
                $product = Product::find($item['product_id'] ?? null);
                if ($product) {
                    $product->copies = (int) ($item['copies'] ?? 1);
                    if ($branchId) {
                        $stock = \App\Models\Stock::where('product_id', $product->id)->where('branch_id', $branchId)->first();
                        $product->display_price = ($stock && $stock->selling_price > 0) ? $stock->selling_price : $product->selling_price;
                    } else {
                        $product->display_price = $product->selling_price;
                    }
                    $products->push($product);
                }
            }
            
            return view('print.barcodes.label', [
                'products' => $products, 
                'from_session' => true,
                'branch_id' => $branchId,
                'date_type' => $dateType,
                'custom_date' => $customDate
            ]);
        }

        // Fallback for simple single/bulk action
        $productIds = $request->input('product_ids', []);
        $copies = $request->input('copies', 1);

        if (empty($productIds)) {
            return back()->with('error', 'Tidak ada produk yang dipilih.');
        }

        $products = Product::whereIn('id', $productIds)->get();
        // Give them all the same copies
        foreach ($products as $p) {
            $p->copies = $copies;
            if ($branchId) {
                $stock = \App\Models\Stock::where('product_id', $p->id)->where('branch_id', $branchId)->first();
                $p->display_price = ($stock && $stock->selling_price > 0) ? $stock->selling_price : $p->selling_price;
            } else {
                $p->display_price = $p->selling_price;
            }
        }

        return view('print.barcodes.label', [
            'products' => $products, 
            'from_session' => false,
            'branch_id' => $branchId,
            'date_type' => $dateType,
            'custom_date' => $customDate
        ]);
    }

    /**
     * Cetak barcode pricecard rak
     */
    public function printPricecard(Request $request)
    {
        $sessionKey = $request->input('session_key');
        $branchId = $request->input('branch_id');
        $dateType = $request->input('date_type', 'cetak');
        $customDate = $request->input('custom_date');

        if ($sessionKey) {
            $printQueue = \Illuminate\Support\Facades\Cache::get('print_queue_' . $sessionKey);
            if (!$printQueue) {
                return abort(404, 'Sesi cetak telah kadaluarsa atau tidak valid.');
            }
            
            // Format of $printQueue: array of ['product_id' => ..., 'copies' => ...]
            $products = collect();
            foreach ($printQueue as $item) {
                $product = Product::find($item['product_id'] ?? null);
                if ($product) {
                    $product->copies = (int) ($item['copies'] ?? 1);
                    if ($branchId) {
                        $stock = \App\Models\Stock::where('product_id', $product->id)->where('branch_id', $branchId)->first();
                        $product->display_price = ($stock && $stock->selling_price > 0) ? $stock->selling_price : $product->selling_price;
                    } else {
                        $product->display_price = $product->selling_price;
                    }
                    $products->push($product);
                }
            }
            
            $this->attachVelocityCategories($products, $branchId);

            return view('print.barcodes.pricecard', [
                'products' => $products, 
                'from_session' => true,
                'branch_id' => $branchId,
                'date_type' => $dateType,
                'custom_date' => $customDate
            ]);
        }

        $productIds = $request->input('product_ids', []);
        $copies = $request->input('copies', 1);

        if (empty($productIds)) {
            return back()->with('error', 'Tidak ada produk yang dipilih.');
        }

        $products = Product::whereIn('id', $productIds)->get();
        // Give them all the same copies
        foreach ($products as $p) {
            $p->copies = $copies;
            if ($branchId) {
                $stock = \App\Models\Stock::where('product_id', $p->id)->where('branch_id', $branchId)->first();
                $p->display_price = ($stock && $stock->selling_price > 0) ? $stock->selling_price : $p->selling_price;
            } else {
                $p->display_price = $p->selling_price;
            }
        }

        $this->attachVelocityCategories($products, $branchId);

        return view('print.barcodes.pricecard', [
            'products' => $products, 
            'from_session' => false,
            'branch_id' => $branchId,
            'date_type' => $dateType,
            'custom_date' => $customDate
        ]);
    }

    /**
     * Hitung kategori pergerakan produk (Fast, Medium, Slow, Dead) di cabang tersebut
     * berdasarkan riwayat transaksi 90 hari terakhir (standar ritel modern).
     */
    protected function attachVelocityCategories($products, ?string $branchId): void
    {
        if ($products->isEmpty()) return;

        $productIds = $products->pluck('id')->filter()->toArray();
        $ninetyDaysAgo = \Carbon\Carbon::now()->subDays(90);

        $salesMap = \Illuminate\Support\Facades\DB::table('transaction_items as ti')
            ->join('transactions as t', 't.id', '=', 'ti.transaction_id')
            ->whereIn('ti.product_id', $productIds)
            ->when($branchId, fn($q) => $q->where('t.branch_id', $branchId))
            ->where('t.transaction_date', '>=', $ninetyDaysAgo)
            ->where(fn($q) => $q->where('t.is_voided', false)->orWhereNull('t.is_voided'))
            ->groupBy('ti.product_id')
            ->select('ti.product_id', \Illuminate\Support\Facades\DB::raw('SUM(ti.quantity) as total_qty'))
            ->pluck('total_qty', 'ti.product_id')
            ->toArray();

        $stockDates = $branchId
            ? \App\Models\Stock::whereIn('product_id', $productIds)
                ->where('branch_id', $branchId)
                ->pluck('created_at', 'product_id')
                ->toArray()
            : [];

        $now = now();
        foreach ($products as $p) {
            $qty = (float) ($salesMap[$p->id] ?? 0);
            $stockCreated = $stockDates[$p->id] ?? null;
            $daysActive = $stockCreated ? max(1, min(90, (int) \Carbon\Carbon::parse($stockCreated)->diffInDays($now) + 1)) : 90;
            $normalized90d = $daysActive < 90 ? ($qty / $daysActive) * 90 : $qty;

            if ($normalized90d >= 90) {
                $p->velocity_type = 'FAST';
                $p->velocity_color = '#10b981'; // Emerald Green (Fast Moving)
            } elseif ($normalized90d >= 15) {
                $p->velocity_type = 'MEDIUM';
                $p->velocity_color = '#0284c7'; // Sky Blue (Medium / Reguler)
            } elseif ($normalized90d > 0) {
                $p->velocity_type = 'SLOW';
                $p->velocity_color = '#f59e0b'; // Amber / Orange (Slow Moving)
            } else {
                $p->velocity_type = 'DEAD';
                $p->velocity_color = '#64748b'; // Slate Gray (Dead Stock / Pasif)
            }
        }
    }
}
