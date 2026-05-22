<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EcommerceController extends Controller
{
    /**
     * Cari cabang terdekat berdasarkan GPS Latitude & Longitude pelanggan
     */
    public function findNearestBranch(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $lat = $request->latitude;
        $lon = $request->longitude;

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
        $organization = \App\Models\Organization::first();
        return response()->json([
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
            'ecommerce_banner_image_url' => $organization && $organization->ecommerce_banner_image 
                ? asset('storage/' . $organization->ecommerce_banner_image) 
                : null,
            'ecommerce_banner_cta_text' => $organization?->ecommerce_banner_cta_text ?? 'Mulai Belanja',
            'ecommerce_announcement' => $organization?->ecommerce_announcement ?? 'Selamat datang di toko online resmi kami! Nikmati promo menarik dan poin di setiap transaksi.',
            'point_redemption_value' => (float)($organization?->point_redemption_value ?? 1.00),
            'minimum_points_to_redeem' => (int)($organization?->minimum_points_to_redeem ?? 100),
        ]);
    }

    /**
     * Ambil katalog produk E-Commerce (berdasarkan is_ecommerce_active atau sedang promo)
     */
    public function getProducts(Request $request)
    {
        $branchId = $request->query('branch_id'); // Optional branch filter for pricing
        $now = now();

        // Cari ID produk yang sedang promo
        $promotions = \App\Models\Promotion::where('is_active', true)
            ->where('applicable_to', 'PRODUCT')
            ->where('valid_from', '<=', $now)
            ->where('valid_until', '>=', $now)
            ->get();

        $promoProductIds = [];
        foreach ($promotions as $promo) {
            if (is_array($promo->target_ids)) {
                $promoProductIds = array_merge($promoProductIds, $promo->target_ids);
            }
        }
        $promoProductIds = array_unique($promoProductIds);

        $query = \App\Models\Product::query()
            ->with('category')
            ->where('is_active', true)
            ->where(function ($q) use ($promoProductIds) {
                $q->where('is_ecommerce_active', true)
                  ->orWhereIn('id', $promoProductIds);
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

        $products = $query->get()->map(function ($product) use ($promoProductIds) {
            if (isset($product->branch_selling_price) && $product->branch_selling_price !== null) {
                $product->selling_price = $product->branch_selling_price;
            }
            $product->is_promo = in_array($product->id, $promoProductIds);
            
            // Format image url
            $product->image_url = $product->image_path 
                ? asset('storage/' . $product->image_path)
                : null;
                
            // Format stock
            $product->stock = (int)($product->stock ?? 0);
                
            return $product;
        });

        return response()->json($products);
    }

    /**
     * Ambil daftar semua cabang aktif
     */
    public function getBranches()
    {
        $branches = Branch::where('is_active', true)->get();
        return response()->json($branches);
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
        ]);

        $organization = \App\Models\Organization::first();
        $orgId = $organization ? $organization->id : null;

        $pointsToRedeem = (int)$request->input('points_to_redeem', 0);
        $customer = null;
        if ($pointsToRedeem > 0) {
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

            // Create Order
            $order = EcommerceOrder::create([
                'organization_id' => $orgId,
                'branch_id' => $request->branch_id,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'delivery_method' => $request->delivery_method,
                'delivery_address' => $request->delivery_method === 'DELIVERY' ? $request->delivery_address : null,
                'status' => 'PENDING',
                'total_amount' => $finalAmount,
                'points_redeemed' => $actualPointsToRedeem,
                'points_redeemed_discount' => $pointsRedeemedDiscount,
                'notes' => $request->notes,
            ]);

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
            'password' => 'required|string|min:6',
        ]);

        $organization = \App\Models\Organization::first();
        $orgId = $organization ? $organization->id : null;

        // Periksa apakah nomor telepon member sudah terdaftar
        $existing = \App\Models\Customer::where('phone', $request->phone)->first();
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

            return response()->json([
                'message' => 'Pendaftaran online berhasil! Akun toko fisik Anda kini terhubung.',
                'member' => $existing,
            ], 200);
        }

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

        return response()->json([
            'message' => 'Pendaftaran member berhasil!',
            'member' => $customer,
        ], 201);
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
        $ecommerceOrders = \App\Models\EcommerceOrder::with(['items.product', 'branch'])
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
                    'transaction_id' => $order->id,
                    'invoice_number' => 'ECOM-' . substr($order->id, 0, 8),
                    'date' => $order->created_at,
                    'total_amount' => $originalTotal,
                    'discount_amount' => $discount,
                    'final_amount' => $finalAmount,
                    'status' => $order->status,
                    'delivery_method' => $order->delivery_method,
                    'delivery_address' => $order->delivery_address,
                    'branch_name' => $order->branch?->name ?? 'Pusat',
                    'cashier_name' => 'Online System',
                    'payment_method' => 'TRANSFER',
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

        return response()->json([
            'message' => 'Kode OTP untuk reset kata sandi telah dikirim ke nomor WhatsApp Anda.',
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
}
