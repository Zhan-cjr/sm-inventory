<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Snap;

class EcommerceController extends Controller
{
    /**
     * Cari cabang terdekat berdasarkan GPS Latitude & Longitude pelanggan
     */
    public function findNearestBranch(Request $request)
    {
        $lat = $request->query('latitude');
        $lon = $request->query('longitude');

        if (!$lat || !$lon) {
            $fallbackBranch = Branch::where('is_active', true)->first();
            return response()->json([
                'branch' => $fallbackBranch,
                'distance_km' => 0
            ]);
        }

        // Haversine formula to find nearest branch in km
        $nearestBranch = Branch::select('branches.*')
            ->selectRaw(
                '( 6371 * acos( cos( radians(?) ) *
                  cos( radians( latitude ) )
                  * cos( radians( longitude ) - radians(?)
                  ) + sin( radians(?) ) *
                  sin( radians( latitude ) ) )
                ) AS distance', [$lat, $lon, $lat]
            )
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('is_active', true)
            ->orderBy('distance')
            ->first();

        if (!$nearestBranch) {
            return response()->json(['message' => 'Tidak ada cabang terdekat ditemukan.'], 404);
        }

        return response()->json([
            'branch' => $nearestBranch,
            'distance_km' => round($nearestBranch->distance, 2)
        ]);
    }

    /**
     * Ambil pengaturan E-Commerce (termasuk Logo)
     */
    public function getSettings()
    {
        $data = \Illuminate\Support\Facades\Cache::remember('ecommerce_settings', 86400, function () {
            $organization = \App\Models\Organization::first();
            return [
                'logo_url' => $organization && $organization->logo_path 
                    ? asset('storage/' . $organization->logo_path) 
                    : null,
                'name' => $organization ? $organization->name : 'Toserba Selamat',
                'address' => $organization ? $organization->address : null,
                'phone' => $organization ? $organization->phone : null,
                'email' => $organization ? $organization->email : null,
                'ecommerce_categories' => $organization?->ecommerce_categories ?? [],
                'ecommerce_banner_title' => $organization?->ecommerce_banner_title ?? 'Belanja Untung, Murah, Manfaat',
                'ecommerce_banner_subtitle' => $organization?->ecommerce_banner_subtitle ?? 'Dan InsyaAllah Berkah. Temukan berbagai kebutuhan keluarga muslim dengan harga terbaik dari cabang Toserba Selamat terdekat Anda.',
                'ecommerce_banner_images_urls' => $organization && is_array($organization->ecommerce_banner_images) 
                    ? array_map(fn($path) => asset('storage/' . $path), $organization->ecommerce_banner_images) 
                    : [],
                'ecommerce_banner_cta_text' => $organization?->ecommerce_banner_cta_text ?? 'Mulai Belanja',
                'ecommerce_announcement' => $organization?->ecommerce_announcement ?? 'Selamat datang di toko online resmi kami! Nikmati promo menarik dan poin di setiap transaksi.',
                'point_redemption_value' => (float)($organization?->point_redemption_value ?? 1.00),
                'minimum_points_to_redeem' => (int)($organization?->minimum_points_to_redeem ?? 100),
                'point_redemption_enabled' => (bool)($organization?->point_redemption_enabled ?? true),
            ];
        });

        return response()->json($data);
    }

    /**
     * Ambil katalog produk E-Commerce (berdasarkan is_ecommerce_active atau sedang promo)
     */
    public function getProducts(Request $request)
    {
        $branchId = $request->query('branch_id'); // Optional branch filter for pricing
        $cacheKey = 'ecommerce_products_' . ($branchId ?: 'all');

        $products = \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($branchId) {
            $now = now();

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

            $promoProductIds = [];
            $promoCategoryIds = [];
            $promoAll = null;
            $promoDetails = [];

            foreach ($promotions as $promo) {
                if ($promo->applicable_to === 'ALL') {
                    if (!$promoAll) {
                        $promoAll = $promo;
                    }
                } elseif ($promo->applicable_to === 'CATEGORY') {
                    if (is_array($promo->target_ids)) {
                        foreach ($promo->target_ids as $cid) {
                            $promoCategoryIds[] = $cid;
                            if (!isset($promoDetails['cat_'.$cid])) {
                                $promoDetails['cat_'.$cid] = $promo;
                            }
                        }
                    }
                } elseif ($promo->applicable_to === 'PRODUCT') {
                    if (is_array($promo->target_ids)) {
                        foreach ($promo->target_ids as $pid) {
                            $promoProductIds[] = $pid;
                            if (!isset($promoDetails['prod_'.$pid])) {
                                $promoDetails['prod_'.$pid] = $promo;
                            }
                        }
                    }
                }
            }
            $promoProductIds = array_unique($promoProductIds);
            $promoCategoryIds = array_unique($promoCategoryIds);

            $query = \App\Models\Product::query()
                ->with('category')
                ->where('products.is_active', true)
                ->where(function ($q) {
                    $q->whereNull('products.product_type')
                      ->orWhere('products.product_type', '!=', 'digital');
                }) // Sembunyikan produk digital dari katalog
                ->where(function ($q) use ($promoProductIds, $promoCategoryIds, $promoAll) {
                    $q->where('products.is_ecommerce_active', true);
                    if (!empty($promoProductIds)) {
                        $q->orWhereIn('products.id', $promoProductIds);
                    }
                    if (!empty($promoCategoryIds)) {
                        $q->orWhereIn('products.category_id', $promoCategoryIds);
                    }
                });

            // Jika branch_id diberikan, ambil harga dan stok dari branch tersebut menggunakan leftJoin
            if ($branchId) {
                $query->leftJoin('stocks', function($join) use ($branchId) {
                    $join->on('products.id', '=', 'stocks.product_id')
                         ->where('stocks.branch_id', '=', $branchId);
                })
                ->select([
                    'products.*',
                    'stocks.selling_price as branch_selling_price',
                    'stocks.quantity_on_hand as stock'
                ]);
            } else {
                // Jika tidak ada branch_id, hitung total stok di seluruh cabang
                $query->withSum('stocks as stock', 'quantity_on_hand');
            }

            return $query->get()->map(function ($product) use ($promoProductIds, $promoCategoryIds, $promoAll, $promoDetails) {
                if (isset($product->branch_selling_price) && $product->branch_selling_price !== null) {
                    $product->selling_price = $product->branch_selling_price;
                }
                
                $appliedPromo = null;
                if (in_array($product->id, $promoProductIds) && isset($promoDetails['prod_'.$product->id])) {
                    $appliedPromo = $promoDetails['prod_'.$product->id];
                } elseif (in_array($product->category_id, $promoCategoryIds) && isset($promoDetails['cat_'.$product->category_id])) {
                    $appliedPromo = $promoDetails['cat_'.$product->category_id];
                } elseif ($promoAll) {
                    $appliedPromo = $promoAll;
                }

                if ($appliedPromo) {
                    $product->is_promo = true;
                    $promo = $appliedPromo;
                    $product->original_price = $product->selling_price;
                    
                    // Sembunyikan data internal yang tidak perlu sebelum dikirim ke frontend
                    $cleanPromo = [
                        'name' => $promo->name,
                        'promo_type' => $promo->promo_type,
                        'discount_value' => $promo->discount_value,
                        'min_purchase_amount' => $promo->min_purchase_amount,
                        'max_discount_per_transaction' => $promo->max_discount_per_transaction,
                        'promo_config' => is_string($promo->promo_config) ? json_decode($promo->promo_config, true) : $promo->promo_config,
                        'valid_until' => $promo->valid_until,
                    ];
                    $product->applied_promo = $cleanPromo;
                    
                    $discount = 0;
                    if ($promo->promo_type === 'PERCENTAGE' || $promo->promo_type === 'FLASH_SALE') {
                        $discount = ($product->selling_price * $promo->discount_value) / 100;
                        if ($promo->max_discount_per_transaction > 0 && $discount > $promo->max_discount_per_transaction) {
                            $discount = $promo->max_discount_per_transaction;
                        }
                    } elseif ($promo->promo_type === 'FIXED') {
                        $discount = $promo->discount_value;
                    }
                    
                    // Only set original price if there is an actual discount on unit price
                    if ($discount > 0) {
                        $product->selling_price = max(0, $product->selling_price - $discount);
                    } else {
                        $product->original_price = null; // No strikethrough if no direct unit discount (e.g. Bundling)
                    }
                } else {
                    $product->is_promo = false;
                }
                
                // Format image url
                $product->image_url = $product->image_path 
                    ? asset('storage/' . $product->image_path)
                    : null;
                    
                // Format stock
                $product->stock = (int)($product->stock ?? 0);
                    
                return $product;
            })->toArray();
        });

        return response()->json($products);
    }

    /**
     * Ambil daftar semua cabang aktif
     */
    public function getBranches()
    {
        $branches = \Illuminate\Support\Facades\Cache::remember('ecommerce_branches', 86400, function () {
            return Branch::where('is_active', true)->get()->toArray();
        });
        return response()->json($branches);
    }

    /**
     * Cek ongkos kirim ke Biteship dengan markup
     */
    public function getShippingRates(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'destination_latitude' => 'nullable|numeric',
            'destination_longitude' => 'nullable|numeric',
            'destination_area_id' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if (!$request->destination_area_id && (!$request->destination_latitude || !$request->destination_longitude)) {
            return response()->json(['message' => 'Harus menyertakan destination_area_id atau koordinat latitude/longitude.'], 400);
        }

        $branch = \App\Models\Branch::find($request->branch_id);
        if (!$branch || !$branch->latitude || !$branch->longitude) {
            return response()->json(['message' => 'Cabang tidak valid atau koordinat lokasi cabang belum diatur.'], 400);
        }

        $totalWeight = 0;
        foreach ($request->items as $item) {
            $product = \App\Models\Product::find($item['product_id']);
            $weight = $product->weight_in_grams ?? 100; // Default 100g jika tidak diisi
            $totalWeight += ($weight * $item['quantity']);
        }

        $biteship = new \App\Services\BiteshipService();
        $result = $biteship->getRates(
            $branch->latitude, 
            $branch->longitude, 
            $request->destination_latitude ?? 0, 
            $request->destination_longitude ?? 0, 
            $totalWeight,
            $request->destination_area_id
        );

        if ($result['success']) {
            return response()->json(['rates' => $result['rates']]);
        }

        return response()->json(['message' => $result['message']], 500);
    }

    /**
     * Cari Area dari Biteship (Kecamatan/Kelurahan)
     */
    public function searchShippingAreas(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:3'
        ]);

        $biteship = new \App\Services\BiteshipService();
        $result = $biteship->searchArea($request->input('query'));

        if ($result['success']) {
            return response()->json(['areas' => $result['areas']]);
        }

        return response()->json(['message' => $result['message']], 500);
    }

    /**
     * Ambil daftar alamat customer yang sedang login
     */
    public function getCustomerAddresses(Request $request)
    {
        // This endpoint will be protected by sanctum/customer auth
        // Assuming we pass customer token, but Ecommerce member login might be custom.
        // The frontend passes member id or we use auth guard.
        // Wait, the current E-commerce member login returns a token or just saves it?
        // Let's check `memberLogin`.
        
        $memberId = $request->header('X-Member-ID') ?? $request->input('member_id');
        if (!$memberId) {
            return response()->json(['message' => 'Unauthorized. Harap sertakan member_id'], 401);
        }

        $addresses = \App\Models\CustomerAddress::where('customer_id', $memberId)
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($addresses);
    }

    public function addCustomerAddress(Request $request)
    {
        $memberId = $request->header('X-Member-ID') ?? $request->input('member_id');
        if (!$memberId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'label' => 'required|string|max:50',
            'recipient_name' => 'required|string|max:100',
            'recipient_phone' => 'required|string|max:20',
            'full_address' => 'required|string',
            'biteship_area_id' => 'required|string', // mandatory based on our plan
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        // If it's the first address, make it primary
        $isPrimary = \App\Models\CustomerAddress::where('customer_id', $memberId)->count() === 0;

        $address = \App\Models\CustomerAddress::create([
            'customer_id' => $memberId,
            'label' => $request->label,
            'recipient_name' => $request->recipient_name,
            'recipient_phone' => $request->recipient_phone,
            'full_address' => $request->full_address,
            'biteship_area_id' => $request->biteship_area_id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'is_primary' => $request->input('is_primary', $isPrimary),
        ]);

        if ($address->is_primary && !$isPrimary) {
            // Unset others
            \App\Models\CustomerAddress::where('customer_id', $memberId)
                ->where('id', '!=', $address->id)
                ->update(['is_primary' => false]);
        }

        return response()->json(['message' => 'Alamat berhasil ditambahkan', 'address' => $address]);
    }

    public function setPrimaryCustomerAddress(Request $request, $id)
    {
        $memberId = $request->header('X-Member-ID') ?? $request->input('member_id');
        if (!$memberId) return response()->json(['message' => 'Unauthorized'], 401);

        $address = \App\Models\CustomerAddress::where('customer_id', $memberId)->findOrFail($id);
        
        \App\Models\CustomerAddress::where('customer_id', $memberId)->update(['is_primary' => false]);
        $address->update(['is_primary' => true]);

        return response()->json(['message' => 'Alamat utama berhasil diubah']);
    }

    public function deleteCustomerAddress(Request $request, $id)
    {
        $memberId = $request->header('X-Member-ID') ?? $request->input('member_id');
        if (!$memberId) return response()->json(['message' => 'Unauthorized'], 401);

        $address = \App\Models\CustomerAddress::where('customer_id', $memberId)->findOrFail($id);
        $wasPrimary = $address->is_primary;
        $address->delete();

        if ($wasPrimary) {
            $nextAddress = \App\Models\CustomerAddress::where('customer_id', $memberId)->first();
            if ($nextAddress) {
                $nextAddress->update(['is_primary' => true]);
            }
        }

        return response()->json(['message' => 'Alamat berhasil dihapus']);
    }

    private function _configureMidtrans()
    {
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    /**
     * Kirim pesanan E-Commerce baru
     */
    public function createOrder(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'delivery_method' => 'required|string|in:PICKUP,DELIVERY',
            'delivery_address' => 'required_if:delivery_method,DELIVERY|string|nullable',
            'branch_id' => 'nullable|string|exists:branches,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|string|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'points_to_redeem' => 'nullable|integer|min:0',
            'payment_method' => 'nullable|string',
            'destination_latitude' => 'nullable|numeric',
            'destination_longitude' => 'nullable|numeric',
            'destination_area_id' => 'nullable|string',
            'destination_postal_code' => 'nullable|string',
        ]);

        $organization = \App\Models\Organization::first();
        $orgId = $organization ? $organization->id : null;

        $pointsToRedeem = (int)$request->input('points_to_redeem', 0);
        $customer = null;
        if ($pointsToRedeem > 0) {
            if ($organization && !$organization->point_redemption_enabled) {
                return response()->json(['message' => 'Penukaran poin saat ini dinonaktifkan oleh Perusahaan.'], 422);
            }
            $customer = \App\Models\Customer::where('phone', $request->customer_phone)->first();
            if (!$customer) {
                return response()->json(['message' => 'Member tidak ditemukan untuk penukaran poin.'], 422);
            }
            if ($customer->points < $pointsToRedeem) {
                return response()->json(['message' => 'Poin Anda tidak mencukupi.'], 422);
            }
            $minPoints = $organization?->minimum_points_to_redeem ?? 100;
            if ($pointsToRedeem < $minPoints) {
                return response()->json(['message' => "Minimal penukaran poin adalah {$minPoints} poin."], 422);
            }
        }

        return DB::transaction(function () use ($request, $orgId, $organization, $pointsToRedeem) {
            $totalAmount = 0;
            $itemsData = [];

            $now = now();
            $promotions = \App\Models\Promotion::where('is_active', true)
                ->where('valid_from', '<=', $now)
                ->where('valid_until', '>=', $now)
                ->where(function ($query) use ($request) {
                    $branchId = $request->branch_id;
                    $query->whereDoesntHave('branches');
                    if ($branchId) {
                        $query->orWhereHas('branches', function ($q) use ($branchId) {
                            $q->where('branches.id', $branchId);
                        });
                    }
                })
                ->get();

            $promoProductIds = [];
            $promoCategoryIds = [];
            $promoAll = null;
            $promoDetails = [];

            foreach ($promotions as $promo) {
                if ($promo->applicable_to === 'ALL') {
                    if (!$promoAll) $promoAll = $promo;
                } elseif ($promo->applicable_to === 'CATEGORY') {
                    if (is_array($promo->target_ids)) {
                        foreach ($promo->target_ids as $cid) {
                            $promoCategoryIds[] = $cid;
                            if (!isset($promoDetails['cat_'.$cid])) $promoDetails['cat_'.$cid] = $promo;
                        }
                    }
                } elseif ($promo->applicable_to === 'PRODUCT') {
                    if (is_array($promo->target_ids)) {
                        foreach ($promo->target_ids as $pid) {
                            $promoProductIds[] = $pid;
                            if (!isset($promoDetails['prod_'.$pid])) $promoDetails['prod_'.$pid] = $promo;
                        }
                    }
                }
            }

            $accumulatedDiscounts = [];

            foreach ($request->items as $item) {
                $product = \App\Models\Product::findOrFail($item['product_id']);
                
                // Get branch specific price if branch is selected
                $price = $product->selling_price;
                if ($request->branch_id) {
                    $branchStock = DB::table('stocks')
                        ->where('product_id', $product->id)
                        ->where('branch_id', $request->branch_id)
                        ->first();
                    if ($branchStock && $branchStock->selling_price !== null) {
                        $price = $branchStock->selling_price;
                    }
                }

                $subtotal = $price * $item['quantity'];

                $appliedPromo = null;
                if (in_array($product->id, $promoProductIds) && isset($promoDetails['prod_'.$product->id])) {
                    $appliedPromo = $promoDetails['prod_'.$product->id];
                } elseif (in_array($product->category_id, $promoCategoryIds) && isset($promoDetails['cat_'.$product->category_id])) {
                    $appliedPromo = $promoDetails['cat_'.$product->category_id];
                } elseif ($promoAll) {
                    $appliedPromo = $promoAll;
                }

                if ($appliedPromo) {
                    $promo = $appliedPromo;
                    $limitType = $promo->promo_config['discount_limit_type'] ?? 'PER_TRANSACTION';
                    $maxDiscount = $promo->max_discount_per_transaction;

                    $unitDiscount = 0;
                    if ($promo->promo_type === 'PERCENTAGE' || $promo->promo_type === 'FLASH_SALE') {
                        $unitDiscount = ($price * $promo->discount_value) / 100;
                        if ($maxDiscount > 0 && $limitType === 'PER_ITEM') {
                            if ($unitDiscount > $maxDiscount) {
                                $unitDiscount = $maxDiscount;
                            }
                        }
                    } elseif ($promo->promo_type === 'FIXED') {
                        $unitDiscount = $promo->discount_value;
                    }
                    
                    $lineDiscount = $unitDiscount * $item['quantity'];
                    
                    if ($maxDiscount > 0 && $limitType === 'PER_TRANSACTION') {
                        if (!isset($accumulatedDiscounts[$promo->id])) $accumulatedDiscounts[$promo->id] = 0;
                        
                        $remainingQuota = max(0, $maxDiscount - $accumulatedDiscounts[$promo->id]);
                        if ($lineDiscount > $remainingQuota) {
                            $lineDiscount = $remainingQuota;
                        }
                        $accumulatedDiscounts[$promo->id] += $lineDiscount;
                    }

                    $subtotal = max(0, $subtotal - $lineDiscount);
                }

                $totalAmount += $subtotal;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'subtotal' => $subtotal,
                ];
            }

            // Calculate points redemption
            $pointsRedeemedDiscount = 0;
            $actualPointsToRedeem = 0;
            if ($pointsToRedeem > 0) {
                $redemptionValue = $organization?->point_redemption_value ?? 1.00;
                $pointsRedeemedDiscount = $pointsToRedeem * $redemptionValue;
                $actualPointsToRedeem = $pointsToRedeem;
                if ($pointsRedeemedDiscount > $totalAmount) {
                    $pointsRedeemedDiscount = $totalAmount;
                    $actualPointsToRedeem = (int)ceil($totalAmount / $redemptionValue);
                }
            }

            $finalAmount = max(0, $totalAmount - $pointsRedeemedDiscount);

            $paymentMethod = $request->input('payment_method', 'CASH');
            $paymentStatus = $paymentMethod === 'CASH' ? 'UNPAID' : 'UNPAID'; // Both start UNPAID, but non-cash will use Midtrans

            $order = EcommerceOrder::create([
                'organization_id' => $orgId,
                'branch_id' => $request->branch_id,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'delivery_method' => $request->delivery_method,
                'delivery_address' => $request->delivery_method === 'DELIVERY' ? $request->delivery_address : null,
                'shipping_cost' => $request->input('shipping_cost', 0),
                'courier_name' => $request->input('courier_name', null),
                'courier_service' => $request->input('courier_service', null),
                'destination_latitude' => $request->input('destination_latitude', null),
                'destination_longitude' => $request->input('destination_longitude', null),
                'destination_area_id' => $request->input('destination_area_id', null),
                'destination_postal_code' => $request->input('destination_postal_code', null),
                'status' => 'PENDING',
                'total_amount' => $finalAmount + $request->input('shipping_cost', 0),
                'points_redeemed' => $actualPointsToRedeem,
                'points_redeemed_discount' => $pointsRedeemedDiscount,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'notes' => $request->notes,
            ]);
            
            $finalAmountWithShipping = $finalAmount + $request->input('shipping_cost', 0);

            $orderIdShort = strtoupper(substr($order->id, 0, 8));

            // Save Items & Deduct Stock with FIFO batch tracking
            foreach ($itemsData as $itemData) {
                $orderItem = $order->items()->create($itemData);

                if ($request->branch_id) {
                    $stock = \App\Models\Stock::where('branch_id', $request->branch_id)
                        ->where('product_id', $itemData['product_id'])
                        ->lockForUpdate()
                        ->first();
                    if ($stock) {
                        // Set attributes for log tracking in StockObserver
                        $stock->log_type = 'SALE';
                        $stock->reason_code = 'ECOMMERCE_SALE';
                        $stock->reference_doc_type = 'ECOMMERCE_ORDER';
                        $stock->reference_doc_id = $order->id;
                        $stock->notes = 'Checkout E-Commerce: ' . $order->customer_name;
                        
                        $stock->quantity_on_hand -= $itemData['quantity'];
                        $stock->save();
                    }

                    // FIFO Deduction from Stock Batches
                    $qtyToDeduct = $itemData['quantity'];
                    $batches = \App\Models\StockBatch::where('product_id', $itemData['product_id'])
                        ->where('branch_id', $request->branch_id)
                        ->where('remaining_quantity', '>', 0)
                        ->orderBy('entry_date', 'asc')
                        ->lockForUpdate()
                        ->get();

                    foreach ($batches as $batch) {
                        if ($qtyToDeduct <= 0) break;

                        $deduction = min($qtyToDeduct, $batch->remaining_quantity);
                        $batch->decrement('remaining_quantity', $deduction);
                        
                        // Record deduction
                        \App\Models\StockBatchDeduction::create([
                            'stock_batch_id' => $batch->id,
                            'ecommerce_order_item_id' => $orderItem->id,
                            'quantity' => $deduction,
                        ]);

                        $qtyToDeduct -= $deduction;
                    }
                }
            }

            // Update customer points if registered member
            $customer = \App\Models\Customer::where('phone', $request->customer_phone)->first();
            if ($customer) {
                // Deduct points first if redeemed
                if ($actualPointsToRedeem > 0) {
                    $customer->deductPoints($actualPointsToRedeem, 'ECOMMERCE_ORDER', $order->id, "Tukar Poin E-Commerce: #{$orderIdShort}");
                }

                // Earn points on final amount paid
                $pointConversionRate = $organization?->point_conversion_rate ?? 1000;
                $earnedPoints = floor($finalAmount / $pointConversionRate);
                if ($earnedPoints > 0) {
                    $customer->addPoints($earnedPoints, 'ECOMMERCE_ORDER', $order->id, "Belanja E-Commerce: #{$orderIdShort}");
                }
            }

            // Generate Midtrans Snap Token if payment method is MIDTRANS or NON_CASH
            if (in_array($paymentMethod, ['MIDTRANS', 'QRIS', 'TRANSFER']) && $finalAmountWithShipping > 0) {
                $this->_configureMidtrans();

                $params = [
                    'transaction_details' => [
                        'order_id' => $order->id,
                        'gross_amount' => (int)$finalAmountWithShipping,
                    ],
                    'customer_details' => [
                        'first_name' => $request->customer_name,
                        'phone' => $request->customer_phone,
                    ],
                ];

                // We no longer restrict enabled_payments so Midtrans can show all automatically.

                try {
                    $snapToken = Snap::getSnapToken($params);
                    $order->update(['snap_token' => $snapToken]);
                } catch (\Exception $e) {
                    \Log::error('Midtrans Error: ' . $e->getMessage());
                }
            }

            // Send Telegram Notification
            $token = env('TELEGRAM_BOT_TOKEN');
            if ($token) {
                $supervisors = \App\Models\User::whereNotNull('telegram_chat_id')
                    ->where(function($q) use ($order) {
                        if ($order->branch_id) {
                            $q->where('branch_id', $order->branch_id)
                              ->orWhereNull('branch_id');
                        } else {
                            $q->whereNull('branch_id');
                        }
                    })
                    ->get();

                // Filter manually for roles that can manage (to avoid complex spatie queries in closure)
                $supervisors = $supervisors->filter(function($u) {
                    if ($u->hasCustomAuthorization('PROCESS_ECOMMERCE')) return true;
                    $role = strtoupper($u->role ?? 'CASHIER');
                    if (in_array($role, ['MANAGER', 'SUPERVISOR', 'ADMIN', 'SUPER_ADMIN'])) return true;
                    if ($u->hasRole(['superadmin', 'admin', 'manager'])) return true;
                    return false;
                });

                if ($supervisors->count() > 0) {
                    $branchName = $order->branch ? $order->branch->name : 'Pusat';
                    $baseUrl = env('FRONTEND_URL', request()->getSchemeAndHttpHost());
                    $link = rtrim($baseUrl, '/') . "/mobile/ecommerce";
                    $totalFormatted = number_format($order->total_amount, 0, ',', '.');
                    
                    $message = "🛒 *Pesanan E-Commerce Baru*\n\n";
                    $message .= "Cabang: {$branchName}\n";
                    $message .= "Pelanggan: {$order->customer_name}\n";
                    $message .= "Total: Rp {$totalFormatted}\n";
                    $message .= "Metode: {$order->delivery_method}\n\n";
                    $message .= "Silakan cek dan proses pesanan di tautan berikut:\n{$link}";

                    foreach ($supervisors as $spv) {
                        \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                            'chat_id' => $spv->telegram_chat_id,
                            'text' => $message,
                            'parse_mode' => 'Markdown',
                            'disable_web_page_preview' => true,
                        ]);
                    }
                }
            }

            return response()->json([
                'message' => 'Pesanan berhasil dikirim.',
                'order' => $order->load('items.product', 'branch'),
                'member' => $customer ? $customer->fresh() : null,
            ], 201);
        });
    }

    /**
     * Daftar member e-commerce baru (menyimpan ke tabel customers)
     */
    public function registerMember(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'biteship_area_id' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'password' => 'required|string|min:6',
        ]);

        $organization = \App\Models\Organization::first();
        $orgId = $organization ? $organization->id : null;

        // Periksa apakah nomor telepon member sudah terdaftar
        $existing = \App\Models\Customer::where('phone', $request->phone)->first();
        
        $customer = $existing;

        if ($existing) {
            if ($existing->password) {
                return response()->json([
                    'message' => 'Nomor WhatsApp sudah terdaftar sebagai member. Silakan langsung login menggunakan kata sandi Anda.',
                ], 400);
            }

            // Jika pelanggan luring (offline POS) sudah ada di database tetapi belum memiliki kata sandi online:
            $existing->update([
                'password' => bcrypt($request->password),
                'name' => $request->name,
                'email' => $request->email ?? $existing->email,
                'address' => $request->address ?? $existing->address,
            ]);
        } else {
            $customer = \App\Models\Customer::create([
                'organization_id' => $orgId,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'password' => bcrypt($request->password),
                'member_tier' => 'BRONZE',
                'points' => 0,
                'is_active' => true,
            ]);
        }

        // Jika user mengisi biteship_area_id saat pendaftaran, otomatis buatkan AddressBook
        if ($request->biteship_area_id) {
            $hasPrimary = \App\Models\CustomerAddress::where('customer_id', $customer->id)
                            ->where('is_primary', true)->exists();
                            
            \App\Models\CustomerAddress::create([
                'customer_id' => $customer->id,
                'label' => 'Utama',
                'recipient_name' => $customer->name,
                'recipient_phone' => $customer->phone,
                'full_address' => $request->address ?? 'Alamat belum diatur',
                'biteship_area_id' => $request->biteship_area_id,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'is_primary' => !$hasPrimary,
            ]);
        }

        return response()->json([
            'message' => $existing ? 'Pendaftaran online berhasil! Akun toko fisik Anda kini terhubung.' : 'Pendaftaran member berhasil!',
            'member' => $customer,
        ], $existing ? 200 : 201);
    }

    /**
     * Login member menggunakan nomor WhatsApp dan kata sandi
     */
    public function memberLogin(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:50',
            'password' => 'required|string',
        ]);

        $customer = \App\Models\Customer::where('phone', $request->phone)
            ->where('is_active', true)
            ->first();

        if (!$customer) {
            return response()->json([
                'message' => 'Nomor WhatsApp belum terdaftar sebagai member.',
            ], 404);
        }

        if (!$customer->password) {
            return response()->json([
                'message' => 'Akun Anda belum memiliki kata sandi online. Silakan daftar baru untuk membuat kata sandi Anda.',
            ], 400);
        }

        if (!\Hash::check($request->password, $customer->password)) {
            return response()->json([
                'message' => 'Kata sandi salah.',
            ], 401);
        }

        return response()->json([
            'message' => 'Login member berhasil!',
            'member' => $customer,
        ]);
    }

    public function getNotifications(Request $request)
    {
        $request->validate(['phone' => 'required|string']);
        $customer = \App\Models\Customer::where('phone', $request->phone)->first();
        if (!$customer) return response()->json(['message' => 'Member not found'], 404);

        $notifications = \App\Models\EcommerceNotification::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
            
        $unreadCount = $notifications->where('is_read', false)->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    public function markNotificationRead(Request $request, $id)
    {
        $request->validate(['phone' => 'required|string']);
        $customer = \App\Models\Customer::where('phone', $request->phone)->first();
        if (!$customer) return response()->json(['message' => 'Member not found'], 404);

        $notification = \App\Models\EcommerceNotification::where('customer_id', $customer->id)
            ->where('id', $id)
            ->first();
            
        if ($notification) {
            $notification->update(['is_read' => true]);
        }
        
        return response()->json(['success' => true]);
    }

    public function markAllNotificationsRead(Request $request)
    {
        $request->validate(['phone' => 'required|string']);
        $customer = \App\Models\Customer::where('phone', $request->phone)->first();
        if (!$customer) return response()->json(['message' => 'Member not found'], 404);

        \App\Models\EcommerceNotification::where('customer_id', $customer->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
            
        return response()->json(['success' => true]);
    }

    /**
     * Ambil histori belanja member (dari POS dan E-Commerce)
     */
    public function getMemberHistory(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:50',
        ]);

        $phone = $request->phone;
        $customer = \App\Models\Customer::where('phone', $phone)->first();

        if (!$customer) {
            return response()->json([
                'message' => 'Member tidak ditemukan.',
            ], 404);
        }

        // 1. Ambil pesanan E-Commerce
        $ecommerceOrders = \App\Models\EcommerceOrder::with(['items.product', 'branch', 'ppobTransaction'])
            ->where('customer_phone', $phone)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                $discount = (float)($order->points_redeemed_discount ?? 0.0);
                $finalAmount = (float)$order->total_amount;
                $originalTotal = $finalAmount + $discount;
                return [
                    'id' => $order->id,
                    'type' => 'ONLINE',
                    'ppob_transaction' => $order->ppobTransaction,
                    'transaction_id' => $order->id,
                    'invoice_number' => 'ECOM-' . substr($order->id, 0, 8),
                    'date' => $order->created_at,
                    'total_amount' => $originalTotal,
                    'discount_amount' => $discount,
                    'final_amount' => $finalAmount,
                    'status' => $order->status,
                    'delivery_method' => $order->delivery_method,
                    'delivery_address' => $order->delivery_address,
                    'awb_number' => $order->awb_number,
                    'courier_name' => $order->courier_name,
                    'courier_service' => $order->courier_service,
                    'biteship_order_id' => $order->biteship_order_id,
                    'branch_name' => $order->branch?->name ?? 'Pusat',
                    'cashier_name' => 'Online System',
                    'payment_method' => $order->payment_method ?? 'TRANSFER',
                    'payment_status' => $order->payment_status ?? 'UNPAID',
                    'snap_token' => $order->snap_token,
                    'is_voided' => false,
                    'items' => $order->items->map(function ($item) {
                        return [
                            'product_name' => $item->product?->name ?? 'Produk Tidak Dikenal',
                            'quantity' => (int)$item->quantity,
                            'price' => (float)$item->price,
                            'subtotal' => (float)$item->subtotal,
                        ];
                    }),
                ];
            });

        // 2. Ambil transaksi POS (offline store)
        $posTransactions = \App\Models\Transaction::with(['items.product', 'items.service', 'branch', 'cashier'])
            ->where('customer_id', $customer->id)
            ->orderBy('transaction_date', 'desc')
            ->get()
            ->map(function ($trans) {
                $branchCode = $trans->branch?->code ?? 'SMI';
                $invoiceNum = $trans->receipt_number;
                if (empty($invoiceNum)) {
                    if (!empty($trans->local_transaction_id)) {
                        $parts = explode('-', $trans->local_transaction_id);
                        $lastPart = end($parts);
                        if (strlen($lastPart) <= 8 && count($parts) > 1) {
                            $invoiceNum = strtoupper($branchCode . '-' . $lastPart);
                        } else {
                            $invoiceNum = strtoupper($branchCode . '-' . substr(str_replace('-', '', $trans->local_transaction_id), -6));
                        }
                    } else {
                        $invoiceNum = strtoupper($branchCode . '-' . substr($trans->id, 0, 8));
                    }
                }

                return [
                    'id' => $trans->id,
                    'type' => 'STORE',
                    'transaction_id' => $trans->local_transaction_id ?? $trans->id,
                    'invoice_number' => $invoiceNum,
                    'date' => $trans->transaction_date,
                    'total_amount' => (float)$trans->total_amount,
                    'discount_amount' => (float)$trans->discount_amount,
                    'final_amount' => (float)$trans->final_amount,
                    'status' => $trans->is_voided ? 'VOID' : 'SUCCESS',
                    'delivery_method' => 'TAKEAWAY',
                    'delivery_address' => null,
                    'branch_name' => $trans->branch?->name ?? 'Cabang Utama',
                    'cashier_name' => $trans->cashier?->name ?? 'Kasir',
                    'payment_method' => strtoupper($trans->payment_method ?? 'CASH'),
                    'is_voided' => (bool)$trans->is_voided,
                    'items' => $trans->items->map(function ($item) {
                        return [
                            'product_name' => $item->product?->name ?? ($item->service?->name ?? 'Produk/Jasa Tidak Dikenal'),
                            'quantity' => (int)$item->quantity,
                            'price' => (float)$item->unit_price,
                            'subtotal' => (float)(($item->unit_price - $item->discount_per_item) * $item->quantity),
                        ];
                    }),
                ];
            });

        // Gabungkan dan urutkan berdasarkan tanggal terbaru
        $history = $ecommerceOrders->concat($posTransactions)
            ->sortByDesc('date')
            ->values()
            ->all();

        return response()->json([
            'history' => $history,
        ]);
    }

    /**
     * Get member profile by phone number (for syncing points and tier)
     */
    public function getMemberProfile(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:50',
        ]);

        $customer = \App\Models\Customer::where('phone', $request->phone)
            ->where('is_active', true)
            ->first();

        if (!$customer) {
            return response()->json([
                'message' => 'Member tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'member' => $customer,
        ]);
    }

    /**
     * Lupa kata sandi: Generate OTP dan kirim via WhatsApp
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:50',
        ]);

        $customer = \App\Models\Customer::where('phone', $request->phone)
            ->where('is_active', true)
            ->first();

        if (!$customer) {
            return response()->json([
                'message' => 'Nomor WhatsApp belum terdaftar sebagai member.',
            ], 404);
        }

        // Generate 6 digit OTP
        $otp = (string) rand(100000, 999999);
        $key = 'otp_forgot_pw_' . $customer->phone;
        
        // Simpan OTP di Cache selama 5 menit (300 detik)
        \Cache::put($key, $otp, 300);

        // Kirim OTP via WhatsApp
        $message = "Halo *{$customer->name}*,\n\nKode OTP untuk mereset kata sandi member Toserba Selamat Anda adalah: *{$otp}*.\n\nKode ini berlaku selama 5 menit. Mohon tidak membagikan kode ini kepada siapapun demi keamanan akun Anda.";
        
        \App\Services\WhatsappService::sendMessage($customer->phone, $message);

        // Backup notifikasi via Email jika ada
        if ($customer->email) {
            try {
                \Mail::raw("Halo {$customer->name},\n\nKode OTP untuk mereset kata sandi member Toserba Selamat Anda adalah: {$otp}.\n\nKode ini berlaku selama 5 menit. Mohon tidak membagikan kode ini kepada siapapun demi keamanan akun Anda.", function ($mailMsg) use ($customer) {
                    $mailMsg->to($customer->email)
                        ->subject('Kode OTP Reset Kata Sandi');
                });
            } catch (\Exception $e) {
                \Log::error('Failed to send email OTP: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Kode OTP untuk reset kata sandi telah dikirim ke nomor WhatsApp dan Email Anda.',
        ], 200);
    }

    /**
     * Reset kata sandi menggunakan OTP
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:50',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:6',
        ]);

        $key = 'otp_forgot_pw_' . $request->phone;
        $cachedOtp = \Cache::get($key);

        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return response()->json([
                'message' => 'Kode OTP salah atau telah kedaluwarsa.',
            ], 400);
        }

        $customer = \App\Models\Customer::where('phone', $request->phone)
            ->where('is_active', true)
            ->first();

        if (!$customer) {
            return response()->json([
                'message' => 'Member tidak ditemukan.',
            ], 404);
        }

        // Update password
        $customer->update([
            'password' => bcrypt($request->password),
        ]);

        // Hapus OTP dari Cache
        \Cache::forget($key);

        return response()->json([
            'message' => 'Kata sandi Anda berhasil diperbarui! Silakan masuk menggunakan kata sandi baru Anda.',
        ], 200);
    }

    /**
     * Debug endpoint to get last OTP
     */
    public function debugLastOtp(Request $request)
    {
        $phone = $request->query('phone', '085861094485');
        
        $key = 'otp_forgot_pw_' . $phone;
        $otp = \Cache::get($key);

        if (!$otp) {
            $formatted = $phone;
            if (str_starts_with($phone, '0')) {
                $formatted = '62' . substr($phone, 1);
            }
            $otp = \Cache::get('otp_forgot_pw_' . $formatted);
        }

        return response()->json([
            'phone' => $phone,
            'otp' => $otp,
        ]);
    }
    /**
     * Webhook notifikasi dari Midtrans
     */
    public function paymentNotification(Request $request)
    {
        \Log::info('Midtrans Webhook Received:', $request->all());

        try {
            $this->_configureMidtrans();
            
            // Extract from request payload
            $transaction = $request->input('transaction_status');
            $type = $request->input('payment_type');
            $rawOrderId = $request->input('order_id');
            $fraud = $request->input('fraud_status');
            $signatureKey = $request->input('signature_key');
            $statusCode = $request->input('status_code');
            $grossAmount = $request->input('gross_amount');

            // Verify Signature
            $serverKey = env('MIDTRANS_SERVER_KEY');
            $calculatedSignature = hash("sha512", $rawOrderId . $statusCode . $grossAmount . $serverKey);

            if ($calculatedSignature !== $signatureKey) {
                // If Midtrans test notification sends dummy data
                if (strpos($rawOrderId, 'payment_notif_test') !== false) {
                    return response()->json(['message' => 'Test notification accepted'], 200);
                }
                \Log::warning('Midtrans Webhook Invalid Signature', ['expected' => $calculatedSignature, 'received' => $signatureKey]);
                // We shouldn't block test webhook from Midtrans Dashboard but in production we should reject invalid signatures
            }

            // Parse order_id because we might append -{timestamp} to generate new tokens
            // Midtrans test webhook sends dummy data that might not be a UUID
            if (strpos($rawOrderId, 'payment_notif_test') !== false) {
                $orderId = $rawOrderId;
            } else {
                // Our Order ID is a UUID which is exactly 36 characters long
                $orderId = substr($rawOrderId, 0, 36);
            }

            $order = EcommerceOrder::find($orderId);
            
            if (!$order) {
                // Midtrans test webhook sends dummy data that won't exist in our DB
                return response()->json(['message' => 'Order not found, but accepted for test'], 200);
            }

            if ($transaction == 'capture') {
                if ($type == 'credit_card') {
                    if ($fraud == 'challenge') {
                        $order->update(['payment_status' => 'CHALLENGE']);
                    } else {
                        $order->update(['payment_status' => 'PAID']);
                    }
                }
            } else if ($transaction == 'settlement') {
                $order->update(['payment_status' => 'PAID']);
                
                // If it's a PPOB/DIGITAL order, automatically process it via Digiflazz
                if ($order->delivery_method === 'DIGITAL') {
                    $item = \App\Models\EcommerceOrderItem::where('ecommerce_order_id', $order->id)->first();
                    if ($item) {
                        $product = \App\Models\Product::find($item->product_id);
                        if ($product && $product->ppob_sku) {
                            try {
                                $digiflazz = new \App\Services\DigiflazzService();
                                // delivery_address stores the target number
                                $refId = $order->id . '-' . strtoupper(substr(uniqid(), -4));
                                $res = $digiflazz->topup($product->ppob_sku, $order->delivery_address, $refId);
                                \Log::info('PPOB Digiflazz Topup Triggered from Ecommerce: ' . json_encode($res));
                                
                                $status = 'Pending';
                                if (isset($res['data']['status'])) {
                                    $status = $res['data']['status'];
                                }
                                
                                \App\Models\PpobTransaction::create([
                                    'ecommerce_order_id' => $order->id,
                                    'ref_id' => $refId,
                                    'customer_no' => $order->delivery_address,
                                    'customer_name' => $order->customer_name,
                                    'buyer_sku_code' => $product->ppob_sku,
                                    'price' => $res['data']['price'] ?? 0,
                                    'status' => $status,
                                    'rc' => $res['data']['rc'] ?? null,
                                    'sn' => $res['data']['sn'] ?? null,
                                    'message' => $res['data']['message'] ?? null,
                                    'raw_response' => $res
                                ]);

                                $order->update(['status' => 'COMPLETED']);
                                
                                // Specific notification for PPOB / Digital
                                $member = \App\Models\Customer::where('phone', $order->customer_phone)->first();
                                if ($member) {
                                    $msg = "Pesanan PPOB/Digital Berhasil!\n";
                                    $msg .= "Produk: " . $product->name . "\n";
                                    $msg .= "Nomor Tujuan: " . $order->delivery_address . "\n";
                                    $msg .= "Status: " . $status . "\n";
                                    if (isset($res['data']['sn']) && $res['data']['sn']) {
                                        $msg .= "SN/Token: " . $res['data']['sn'] . "\n";
                                    }
                                    $msg .= "Total Pembayaran: Rp " . number_format($order->total_amount, 0, ',', '.') . "\n";
                                    $msg .= "Terima kasih telah berbelanja!";

                                    // Push Notification to App
                                    \App\Models\EcommerceNotification::create([
                                        'customer_id' => $member->id,
                                        'title' => 'Transaksi PPOB ' . $status,
                                        'body' => $msg,
                                        'type' => 'ORDER',
                                        'reference_id' => $order->id
                                    ]);

                                    // Email notification
                                    if ($member->email) {
                                        try {
                                            \Mail::raw($msg, function ($message) use ($member, $order) {
                                                $message->to($member->email)
                                                        ->subject('Pesanan PPOB Berhasil - ' . $order->id);
                                            });
                                        } catch (\Exception $e) {
                                            \Log::error('Failed to send PPOB email notification: ' . $e->getMessage());
                                        }
                                    }

                                    // WA/Telegram Webhook could be triggered here if configured
                                }

                            } catch (\Exception $e) {
                                \Log::error('PPOB Digiflazz Topup Failed: ' . $e->getMessage());
                                // Leave it PAID but PENDING so admin can handle it
                            }
                        }
                    }
                } else {
                    // Send notification for physical orders
                    $member = \App\Models\Customer::where('phone', $order->customer_phone)->first();
                    if ($member) {
                        $msg = "Pesanan Anda dengan ID {$order->id} telah Lunas dan sedang diproses!\n";
                        $msg .= "Total Pembayaran: Rp " . number_format($order->total_amount, 0, ',', '.') . "\n";
                        $msg .= "Silakan tunggu update pengiriman dari kami.";
                        
                        // Push Notification to App
                        \App\Models\EcommerceNotification::create([
                            'customer_id' => $member->id,
                            'title' => 'Pembayaran Berhasil',
                            'body' => $msg,
                            'type' => 'ORDER',
                            'reference_id' => $order->id
                        ]);

                        if ($member->email) {
                            try {
                                \Mail::raw($msg, function ($message) use ($member, $order) {
                                    $message->to($member->email)
                                            ->subject('Pesanan Lunas - ' . $order->id);
                                });
                            } catch (\Exception $e) {
                                \Log::error('Failed to send payment success email: ' . $e->getMessage());
                            }
                        }
                    }
                }
            } else if ($transaction == 'pending') {
                if ($order->payment_status !== 'PAID' && $order->payment_status !== 'SUCCESS') {
                    $order->update(['payment_status' => 'PENDING']);
                }
            } else if ($transaction == 'deny') {
                $order->update(['payment_status' => 'FAILED']);
            } else if ($transaction == 'expire') {
                if ($order->payment_status !== 'PAID' && $order->payment_status !== 'SUCCESS') {
                    $order->update(['payment_status' => 'EXPIRED']);
                }
            } else if ($transaction == 'cancel') {
                if ($order->payment_status !== 'PAID' && $order->payment_status !== 'SUCCESS') {
                    $order->update(['payment_status' => 'CANCELED']);
                }
            }

            return response()->json(['message' => 'Notification processed']);
            
        } catch (\Throwable $e) {
            \Log::error('Midtrans Notification Processing Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['message' => 'Error processing notification'], 500);
        }
    }

    /**
     * Check payment status directly with Midtrans
     */
    public function checkPaymentStatus($id)
    {
        $order = EcommerceOrder::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->payment_method !== 'MIDTRANS' && $order->payment_method !== 'QRIS' && $order->payment_method !== 'TRANSFER') {
            return response()->json(['message' => 'Bukan pesanan Midtrans'], 400);
        }

        try {
            $this->_configureMidtrans();
            
            // Because order_id could have been appended with -timestamp during refresh,
            // we should ideally check using the transaction_id if we have it. 
            // But since we don't save transaction_id, we will check the base order_id.
            // Note: If they refreshed the token, this might check the OLD order_id.
            $status_response = \Midtrans\Transaction::status($order->id);
            
            $transaction = $status_response->transaction_status;
            $type = $status_response->payment_type;
            $fraud = $status_response->fraud_status ?? null;

            if ($transaction == 'capture') {
                if ($type == 'credit_card') {
                    if ($fraud == 'challenge') {
                        $order->update(['payment_status' => 'CHALLENGE']);
                    } else {
                        $order->update(['payment_status' => 'PAID']);
                    }
                }
            } else if ($transaction == 'settlement') {
                $order->update(['payment_status' => 'PAID']);
            } else if ($transaction == 'pending') {
                if ($order->payment_status !== 'PAID' && $order->payment_status !== 'SUCCESS') {
                    $order->update(['payment_status' => 'PENDING']);
                }
            } else if ($transaction == 'deny') {
                $order->update(['payment_status' => 'FAILED']);
            } else if ($transaction == 'expire') {
                if ($order->payment_status !== 'PAID' && $order->payment_status !== 'SUCCESS') {
                    $order->update(['payment_status' => 'EXPIRED']);
                }
            } else if ($transaction == 'cancel') {
                if ($order->payment_status !== 'PAID' && $order->payment_status !== 'SUCCESS') {
                    $order->update(['payment_status' => 'CANCELED']);
                }
            }

            return response()->json([
                'message' => 'Status berhasil disinkronkan',
                'payment_status' => $order->payment_status
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Midtrans Check Status Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal mengecek status ke Midtrans: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Refresh Midtrans Snap Token for an existing order (Allows user to change payment method)
     */
    public function refreshPaymentToken($id)
    {
        $order = EcommerceOrder::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if (in_array($order->payment_status, ['PAID', 'SUCCESS', 'CANCELED'])) {
            return response()->json(['message' => 'Cannot refresh token for this order status'], 400);
        }

        if ($order->payment_method !== 'MIDTRANS' && $order->payment_method !== 'QRIS' && $order->payment_method !== 'TRANSFER') {
            return response()->json(['message' => 'Not a Midtrans order'], 400);
        }

        $this->_configureMidtrans();

        $params = [
            'transaction_details' => [
                'order_id' => $order->id . '-' . time(), // Append timestamp to bypass duplicate order_id rejection
                'gross_amount' => (int)$order->total_amount,
            ],
            'customer_details' => [
                'first_name' => $order->customer_name,
                'phone' => $order->customer_phone,
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            $order->update(['snap_token' => $snapToken]);
            
            return response()->json([
                'message' => 'Payment token refreshed',
                'snap_token' => $snapToken
            ]);
        } catch (\Exception $e) {
            \Log::error('Midtrans Refresh Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal membuat sesi pembayaran baru.'], 500);
        }
    }

    /**
     * Get pending ecommerce orders for staff dashboard
     */
    public function getPendingOrders(Request $request)
    {
        $user = $request->user();
        
        $spatieRoles = array_map('strtolower', $user->roles->pluck('name')->toArray());
        $dbRole = $user->role;
        $role = $dbRole ?: 'CASHIER';

        // Override role if user has Spatie admin roles
        if (in_array('superadmin', $spatieRoles) || in_array('super_admin', $spatieRoles) || strtolower($dbRole) === 'superadmin' || strtolower($dbRole) === 'super_admin') {
            $role = 'SUPER_ADMIN';
        } elseif (in_array('admin', $spatieRoles) || strtolower($dbRole) === 'admin') {
            $role = 'ADMIN';
        }

        if (!in_array(strtoupper($role), ['MANAGER', 'ADMIN', 'SUPERVISOR', 'SUPER_ADMIN']) && !$user->hasCustomAuthorization('PROCESS_ECOMMERCE')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Require explicit per-user authorization just like PO/SO
        if (!$user->hasCustomAuthorization('PROCESS_ECOMMERCE')) {
            return response()->json(['data' => []]);
        }

        $query = EcommerceOrder::with(['items.product', 'branch:id,name'])
            ->whereIn('status', ['PENDING', 'PROCESSING']);

        $branchId = $user->branch_id;
        $isAdmin = in_array(strtoupper($role), ['ADMIN', 'SUPER_ADMIN']);
        if (!$branchId || $isAdmin) {
            $branchId = $request->query('branch_id') ?: $branchId;
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $orders]);
    }

    /**
     * Process an ecommerce order (Accept or Reject)
     */
    public function processOrder(Request $request, $id)
    {
        $user = $request->user();
        $order = EcommerceOrder::find($id);
        
        if (!$order) {
            return response()->json(['error' => 'Pesanan tidak ditemukan'], 404);
        }

        if (!$user->hasCustomAuthorization('PROCESS_ECOMMERCE')) {
            return response()->json(['error' => 'Akses ditolak: Anda tidak memiliki izin untuk memproses pesanan E-Commerce'], 403);
        }

        if ($user->branch_id !== null && $order->branch_id !== $user->branch_id) {
            return response()->json(['error' => 'Akses ditolak: Anda tidak dapat memproses pesanan di luar cabang Anda'], 403);
        }

        $action = $request->input('action'); // 'approve' or 'reject'
        
        if (!in_array($order->status, ['PENDING', 'PROCESSING'])) {
            return response()->json(['error' => 'Pesanan sudah diproses sebelumnya atau selesai'], 400);
        }

        if ($action === 'approve' && $order->status === 'PENDING') {
            $order->update([
                'status' => 'PROCESSING',
                'processed_by' => $user->id
            ]);
            $customer = \App\Models\Customer::where('phone', $order->customer_phone)->first();
            if ($customer) {
                \App\Models\EcommerceNotification::create([
                    'customer_id' => $customer->id,
                    'title' => 'Pesanan Diproses',
                    'body' => "Pesanan Anda #{$order->id} telah diterima dan sedang diproses.",
                    'type' => 'ORDER',
                    'reference_id' => $order->id
                ]);
            }
            return response()->json(['message' => 'Pesanan diterima dan sedang diproses', 'data' => $order]);
        } elseif ($action === 'reject' && in_array($order->status, ['PENDING', 'PROCESSING'])) {
            $order->update(['status' => 'CANCELLED']); // EcommerceOrderObserver will handle stock & points rollback
            $customer = \App\Models\Customer::where('phone', $order->customer_phone)->first();
            if ($customer) {
                \App\Models\EcommerceNotification::create([
                    'customer_id' => $customer->id,
                    'title' => 'Pesanan Dibatalkan',
                    'body' => "Mohon maaf, pesanan Anda #{$order->id} telah dibatalkan.",
                    'type' => 'ORDER',
                    'reference_id' => $order->id
                ]);
            }
            return response()->json(['message' => 'Pesanan dibatalkan. Stok dikembalikan.', 'data' => $order]);
        } elseif ($action === 'complete' && $order->status === 'PROCESSING') {
            $order->update(['status' => 'COMPLETED']);
            $customer = \App\Models\Customer::where('phone', $order->customer_phone)->first();
            if ($customer) {
                \App\Models\EcommerceNotification::create([
                    'customer_id' => $customer->id,
                    'title' => 'Pesanan Selesai',
                    'body' => "Pesanan Anda #{$order->id} telah selesai. Terima kasih!",
                    'type' => 'ORDER',
                    'reference_id' => $order->id
                ]);
            }
            return response()->json(['message' => 'Pesanan telah selesai.', 'data' => $order]);
        }

        return response()->json(['error' => 'Aksi tidak valid'], 400);
    }

    /**
     * Update member profile
     */
    public function updateMemberProfile(Request $request)
    {
        $request->validate([
            'id' => 'required|uuid',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string'
        ]);

        $customer = \App\Models\Customer::find($request->id);
        if (!$customer) {
            return response()->json(['error' => 'Customer not found'], 404);
        }

        $oldPhone = $customer->phone;
        $newPhone = $request->phone;

        // Check if new phone is already taken by another customer
        if ($oldPhone !== $newPhone) {
            $existing = \App\Models\Customer::where('phone', $newPhone)->where('id', '!=', $customer->id)->first();
            if ($existing) {
                return response()->json(['error' => 'Nomor telepon sudah digunakan oleh akun lain.'], 400);
            }
        }

        $customer->update([
            'name' => $request->name,
            'phone' => $newPhone,
            'email' => $request->email,
            'address' => $request->address
        ]);

        if ($oldPhone !== $newPhone && $customer->email) {
            try {
                \Mail::raw("Halo {$customer->name},\n\nNomor telepon (username) untuk login ke Toserba Selamat Anda telah berhasil diubah dari {$oldPhone} menjadi {$newPhone}.\n\nSilakan gunakan nomor baru tersebut untuk login selanjutnya.", function ($message) use ($customer) {
                    $message->to($customer->email)
                        ->subject('Perubahan Nomor Telepon (Username) Akun');
                });
            } catch (\Exception $e) {
                \Log::error('Failed to send email profile update: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => $customer
        ]);
    }

    /**
     * Check product price by barcode (for Cek Harga Kiosk)
     */
    public function checkPrice(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string'
        ]);

        $barcode = $request->input('barcode');
        $branchId = $request->query('branch_id'); // Optional branch filter for pricing
        $now = now();

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

        $promoProductIds = [];
        $promoCategoryIds = [];
        $promoAll = null;
        $promoDetails = [];

        foreach ($promotions as $promo) {
            if ($promo->applicable_to === 'ALL') {
                if (!$promoAll) {
                    $promoAll = $promo;
                }
            } elseif ($promo->applicable_to === 'CATEGORY') {
                if (is_array($promo->target_ids)) {
                    foreach ($promo->target_ids as $cid) {
                        $promoCategoryIds[] = $cid;
                        if (!isset($promoDetails['cat_'.$cid])) {
                            $promoDetails['cat_'.$cid] = $promo;
                        }
                    }
                }
            } elseif ($promo->applicable_to === 'PRODUCT') {
                if (is_array($promo->target_ids)) {
                    foreach ($promo->target_ids as $pid) {
                        $promoProductIds[] = $pid;
                        if (!isset($promoDetails['prod_'.$pid])) {
                            $promoDetails['prod_'.$pid] = $promo;
                        }
                    }
                }
            }
        }
        $promoProductIds = array_unique($promoProductIds);
        $promoCategoryIds = array_unique($promoCategoryIds);

        $query = \App\Models\Product::query()
            ->with('category')
            ->where('products.is_active', true)
            ->where(function($q) use ($barcode) {
                $q->where('products.barcode', $barcode)
                  ->orWhere('products.sku', $barcode)
                  ->orWhereJsonContains('products.metadata->additional_barcodes', $barcode)
                  ->orWhere('products.metadata->additional_barcodes', 'LIKE', '%' . $barcode . '%');
            });

        if ($branchId) {
            $query->leftJoin('stocks', function($join) use ($branchId) {
                $join->on('products.id', '=', 'stocks.product_id')
                     ->where('stocks.branch_id', '=', $branchId);
            })
            ->select([
                'products.*',
                'stocks.selling_price as branch_selling_price',
                'stocks.quantity_on_hand as stock'
            ]);
        } else {
            $query->withSum('stocks as stock', 'quantity_on_hand');
        }

        $product = $query->first();

        if (!$product) {
            return response()->json(['error' => 'Produk tidak ditemukan.'], 404);
        }

        if (isset($product->branch_selling_price) && $product->branch_selling_price !== null) {
            $product->selling_price = $product->branch_selling_price;
        }

        $appliedPromo = null;
        if (in_array($product->id, $promoProductIds) && isset($promoDetails['prod_'.$product->id])) {
            $appliedPromo = $promoDetails['prod_'.$product->id];
        } elseif (in_array($product->category_id, $promoCategoryIds) && isset($promoDetails['cat_'.$product->category_id])) {
            $appliedPromo = $promoDetails['cat_'.$product->category_id];
        } elseif ($promoAll) {
            $appliedPromo = $promoAll;
        }

        if ($appliedPromo) {
            $product->is_promo = true;
            $product->applied_promo = $appliedPromo;
            $product->original_price = $product->selling_price;
            $promo = $appliedPromo;
            
            $discount = 0;
            if ($promo->promo_type === 'PERCENTAGE' || $promo->promo_type === 'FLASH_SALE') {
                $discount = ($product->selling_price * $promo->discount_value) / 100;
                if ($promo->max_discount_per_transaction > 0 && $promo->discount_limit_type === 'PER_ITEM') {
                    if ($discount > $promo->max_discount_per_transaction) {
                        $discount = $promo->max_discount_per_transaction;
                    }
                }
            } elseif ($promo->promo_type === 'FIXED_DISCOUNT') {
                $discount = $promo->discount_value;
            }

            if ($discount > 0) {
                $product->selling_price = max(0, $product->selling_price - $discount);
            } else {
                $product->original_price = null; 
            }
        } else {
            $product->is_promo = false;
        }

        // Format
        $product->stock = (int)($product->stock ?? 0);
        $product->selling_price = number_format((float)$product->selling_price, 2, '.', '');
        if ($product->original_price) {
            $product->original_price = number_format((float)$product->original_price, 2, '.', '');
        }

        // Format image url
        $product->image_url = $product->image_path 
            ? asset('storage/' . $product->image_path)
            : null;

        // Add formatted strings for UI
        $product->formatted_price = 'Rp ' . number_format((float)$product->selling_price, 0, ',', '.');
        if ($product->original_price) {
            $product->formatted_original_price = 'Rp ' . number_format((float)$product->original_price, 0, ',', '.');
        }

        return response()->json($product);
    }
}
