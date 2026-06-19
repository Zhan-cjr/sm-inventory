<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PosCatalogController extends Controller
{
    /**
     * Get Products for POS Terminal (Filtered by Branch & Branch-specific pricing)
     * Heavily optimized with pre-serialized JSON caching.
     */
    public function getProducts(Request $request)
    {
        $user = $request->user();
        $branchId = $user->branch_id;
        $isAdmin = in_array(strtoupper($user->role), ['ADMIN', 'SUPER_ADMIN']);
        
        if (!$branchId || $isAdmin) {
            $branchId = $request->query('branch_id') ?: $branchId;
        }
        
        if (!$branchId) {
            return response()->json([]);
        }

        // Cache key specific to this branch (now using _gz to indicate gzipped cache)
        $cacheKey = 'pos_products_json_gz_branch_' . $branchId;

        // Try to get pre-serialized AND gzipped JSON from cache
        $cachedJsonGz = Cache::get($cacheKey);

        if ($cachedJsonGz) {
            // Return raw GZIPPED string immediately
            return response($cachedJsonGz)
                ->header('Content-Type', 'application/json')
                ->header('Content-Encoding', 'gzip');
        }

        // --- MENCEGAH CACHE STAMPEDE ---
        // Jika ratusan kasir melakukan request persis di milidetik yang sama saat cache kosong,
        // kita mengunci (lock) prosesnya agar hanya 1 kasir yang me-load ke database.
        $lock = Cache::lock('lock_' . $cacheKey, 30);

        if ($lock->get()) {
            try {
                // Cache Miss & Got Lock: Query Database
                $products = \App\Models\Product::query()
                    ->join('stocks', 'products.id', '=', 'stocks.product_id')
                    ->where('stocks.branch_id', $branchId)
                    ->where('products.is_active', true)
                    ->select([
                        'products.*',
                        'stocks.cost_price as branch_cost_price',
                        'stocks.cost_price_tax as branch_cost_price_tax',
                        'stocks.selling_price as branch_selling_price',
                        'stocks.harga_jual_1 as branch_harga_jual_1',
                        'stocks.qty_min_gol_1 as branch_qty_min_gol_1',
                        'stocks.harga_jual_2 as branch_harga_jual_2',
                        'stocks.qty_min_gol_2 as branch_qty_min_gol_2',
                        'stocks.harga_jual_3 as branch_harga_jual_3',
                        'stocks.qty_min_gol_3 as branch_qty_min_gol_3',
                        'stocks.quantity_on_hand'
                    ])
                    ->get()
                    ->map(function ($product) {
                        if ($product->branch_selling_price !== null && $product->branch_selling_price > 0) {
                            $product->selling_price = $product->branch_selling_price;
                        }
                        if ($product->branch_cost_price !== null && $product->branch_cost_price > 0) {
                            $product->cost_price = $product->branch_cost_price;
                        }
                        if ($product->branch_harga_jual_1 !== null && $product->branch_harga_jual_1 > 0) {
                            $product->harga_jual_1 = $product->branch_harga_jual_1;
                            $product->qty_min_gol_1 = $product->branch_qty_min_gol_1;
                        }
                        if ($product->branch_harga_jual_2 !== null && $product->branch_harga_jual_2 > 0) {
                            $product->harga_jual_2 = $product->branch_harga_jual_2;
                            $product->qty_min_gol_2 = $product->branch_qty_min_gol_2;
                        }
                        if ($product->branch_harga_jual_3 !== null && $product->branch_harga_jual_3 > 0) {
                            $product->harga_jual_3 = $product->branch_harga_jual_3;
                            $product->qty_min_gol_3 = $product->branch_qty_min_gol_3;
                        }
                        return $product;
                    });

                // Serialize to JSON string
                $jsonString = $products->toJson();
                
                // Compress with GZIP (Level 9 - Max Compression)
                $gzipped = gzencode($jsonString, 9);

                // Store GZIPPED string in cache for 60 seconds (1 minute)
                Cache::put($cacheKey, $gzipped, 60);

                return response($gzipped)
                    ->header('Content-Type', 'application/json')
                    ->header('Content-Encoding', 'gzip');
            } finally {
                $lock->release();
            }
        } else {
            // Kasir lain yang tidak kebagian Lock (mengantre)
            // Tunggu maksimal 15 detik sampai kasir pertama selesai membuat Cache
            $waited = 0;
            while (!Cache::has($cacheKey) && $waited < 15) {
                sleep(1);
                $waited++;
            }
            
            $cachedJsonGz = Cache::get($cacheKey);
            if ($cachedJsonGz) {
                return response($cachedJsonGz)
                    ->header('Content-Type', 'application/json')
                    ->header('Content-Encoding', 'gzip');
            }
            
            return response()->json([]); // Fallback jika gagal ekstrem
        }
    }

    /**
     * Get Active Promotions
     * Optimized with pre-serialized JSON caching.
     */
    public function getPromotions(Request $request)
    {
        $now = now();
        $user = $request->user();
        $branchId = $user->branch_id;
        
        $cacheKey = 'pos_promos_json_gz_branch_' . ($branchId ?: 'all');
        $cachedJsonGz = Cache::get($cacheKey);

        if ($cachedJsonGz) {
            return response($cachedJsonGz)
                ->header('Content-Type', 'application/json')
                ->header('Content-Encoding', 'gzip');
        }

        $lock = Cache::lock('lock_' . $cacheKey, 30);
        if ($lock->get()) {
            try {
                $promotions = \App\Models\Promotion::where('is_active', true)
                    ->where('valid_from', '<=', $now)
                    ->where('valid_until', '>=', $now)
                    ->where(function ($query) use ($branchId) {
                        $query->whereDoesntHave('branches');
                        if ($branchId) {
                            $query->orWhereHas('branches', function ($q) use ($branchId) {
                                $q->where('branches.id', $branchId);
                            });
                        }
                    })
                    ->get();

                $jsonString = $promotions->toJson();
                $gzipped = gzencode($jsonString, 9);
                Cache::put($cacheKey, $gzipped, 60);

                return response($gzipped)
                    ->header('Content-Type', 'application/json')
                    ->header('Content-Encoding', 'gzip');
            } finally {
                $lock->release();
            }
        } else {
            $waited = 0;
            while (!Cache::has($cacheKey) && $waited < 15) {
                sleep(1);
                $waited++;
            }
            $cachedJsonGz = Cache::get($cacheKey);
            if ($cachedJsonGz) {
                return response($cachedJsonGz)
                    ->header('Content-Type', 'application/json')
                    ->header('Content-Encoding', 'gzip');
            }
            return response()->json([]);
        }
    }
}
