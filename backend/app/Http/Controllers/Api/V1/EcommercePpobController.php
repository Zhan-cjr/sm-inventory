<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Snap;

class EcommercePpobController extends Controller
{
    public function getProducts(Request $request)
    {
        $prefix = $request->query('prefix'); // e.g. 0812
        $type = $request->query('type'); // PULSA, DATA, PLN, EWALLET

        $query = Product::where('product_type', 'digital')
                        ->where('is_active', true)
                        ->where('is_ecommerce_active', true);

        // Identifikasi Provider berdasarkan prefix
        $provider = null;
        if ($prefix && strlen($prefix) >= 4) {
            $prefix4 = substr($prefix, 0, 4);
            $telkomsel = ['0811', '0812', '0813', '0821', '0822', '0823', '0852', '0853', '0851'];
            $indosat = ['0814', '0815', '0816', '0855', '0856', '0857', '0858'];
            $xl = ['0817', '0818', '0819', '0859', '0877', '0878', '0831', '0832', '0833', '0838'];
            $tri = ['0895', '0896', '0897', '0898', '0899'];
            $smartfren = ['0881', '0882', '0883', '0884', '0885', '0886', '0887', '0888', '0889'];

            if (in_array($prefix4, $telkomsel)) {
                $provider = 'TELKOMSEL';
            } elseif (in_array($prefix4, $indosat)) {
                $provider = 'INDOSAT';
            } elseif (in_array($prefix4, $xl)) {
                $provider = ['XL', 'AXIS'];
            } elseif (in_array($prefix4, $tri)) {
                $provider = ['TRI', 'THREE'];
            } elseif (in_array($prefix4, $smartfren)) {
                $provider = 'SMARTFREN';
            }
        }

        if ($type === 'PLN') {
            $query->where(function($q) {
                $q->where('name', 'like', '%PLN%')->orWhere('name', 'like', '%TOKEN%');
            });
        } elseif ($type === 'EWALLET') {
            $query->where(function($q) {
                $q->where('name', 'like', '%DANA%')
                  ->orWhere('name', 'like', '%GOPAY%')
                  ->orWhere('name', 'like', '%OVO%')
                  ->orWhere('name', 'like', '%SHOPEE%')
                  ->orWhere('name', 'like', '%LINKAJA%');
            });
        } elseif ($type === 'PULSA' || $type === 'DATA') {
            if ($type === 'DATA') {
                $query->where('name', 'like', '%DATA%');
            } else {
                $query->where('name', 'not like', '%DATA%')
                      ->where('name', 'not like', '%PLN%')
                      ->where('name', 'not like', '%TOKEN%')
                      ->where('name', 'not like', '%DANA%')
                      ->where('name', 'not like', '%GOPAY%')
                      ->where('name', 'not like', '%OVO%')
                      ->where('name', 'not like', '%SHOPEE%')
                      ->where('name', 'not like', '%LINKAJA%');
            }

            if ($provider) {
                if (is_array($provider)) {
                    $query->where(function($q) use ($provider) {
                        foreach ($provider as $prov) {
                            $q->orWhere('name', 'like', "%{$prov}%");
                        }
                    });
                } else {
                    $query->where('name', 'like', "%{$provider}%");
                }
            }
        }

        $products = $query->orderBy('selling_price', 'asc')->get()->map(function($p) {
            $p->image_url = $p->image_url ? asset('storage/' . $p->image_url) : null;
            return $p;
        });

        return response()->json($products);
    }

    public function createOrder(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string',
            'customer_phone' => 'required|string',
            'target_number' => 'required|string', // phone number or PLN meter
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id'
        ]);

        $product = Product::findOrFail($request->product_id);
        if ($product->product_type !== 'digital') {
            return response()->json(['message' => 'Produk bukan digital.'], 400);
        }

        DB::beginTransaction();
        try {
            // Create E-commerce Order specifically for PPOB
            $order = EcommerceOrder::create([
                'organization_id' => $product->organization_id ?? \App\Models\Organization::first()->id,
                'branch_id' => $request->branch_id,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'delivery_method' => 'DIGITAL',
                'delivery_address' => $request->target_number, // Store target number in address
                'shipping_cost' => 0,
                'status' => 'PENDING',
                'total_amount' => $product->selling_price,
                'payment_method' => 'MIDTRANS',
                'payment_status' => 'UNPAID'
            ]);

            EcommerceOrderItem::create([
                'ecommerce_order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => $product->selling_price,
                'subtotal' => $product->selling_price,
            ]);

            // Setup Midtrans Snap
            Config::$serverKey = env('MIDTRANS_SERVER_KEY');
            Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
            Config::$isSanitized = true;
            Config::$is3ds = true;

            $params = [
                'transaction_details' => [
                    'order_id' => $order->id . '-' . time(),
                    'gross_amount' => (int)$order->total_amount,
                ],
                'customer_details' => [
                    'first_name' => $order->customer_name,
                    'phone' => $order->customer_phone,
                ],
                'item_details' => [
                    [
                        'id' => $product->id,
                        'price' => (int)$product->selling_price,
                        'quantity' => 1,
                        'name' => mb_substr($product->name, 0, 50),
                    ]
                ]
            ];

            $snapToken = Snap::getSnapToken($params);
            $order->update(['snap_token' => $snapToken]);

            DB::commit();

            return response()->json([
                'message' => 'Pesanan PPOB dibuat',
                'order' => $order
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal membuat pesanan: ' . $e->getMessage()], 500);
        }
    }
}
