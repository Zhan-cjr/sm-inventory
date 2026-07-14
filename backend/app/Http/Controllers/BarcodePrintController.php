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

        return view('print.barcodes.pricecard', [
            'products' => $products, 
            'from_session' => false,
            'branch_id' => $branchId,
            'date_type' => $dateType,
            'custom_date' => $customDate
        ]);
    }
}
