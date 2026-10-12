<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PpobTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'transaction_id', 'ecommerce_order_id', 'provider', 'ref_id', 'customer_no', 'customer_name', 'customer_wa_phone',
        'buyer_sku_code', 'price', 'status', 'rc', 'sn', 'message', 'raw_response',
        'refund_status', 'refund_method', 'refund_amount', 'refunded_by', 'refunded_at', 'refund_notes'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'refunded_at' => 'datetime',
        'raw_response' => 'array',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function ecommerceOrder()
    {
        return $this->belongsTo(EcommerceOrder::class, 'ecommerce_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'buyer_sku_code', 'ppob_sku');
    }

    public function refundedBy()
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }

    public function getModalPriceAttribute(): float
    {
        if (strtolower($this->status ?? '') === 'gagal') {
            return 0;
        }

        if ((float) $this->price > 0) {
            return (float) $this->price;
        }

        return (float) ($this->product?->cost_price ?? 0);
    }

    public function getSellingPriceAttribute(): float
    {
        if (strtolower($this->status ?? '') === 'gagal') {
            return 0;
        }

        // 1. Dari transaksi POS
        if ($this->transaction) {
            $items = $this->transaction->items;
            if ($items && $items->isNotEmpty()) {
                $item = $items->first(function ($it) {
                    return $it->product && $it->product->ppob_sku === $this->buyer_sku_code;
                });

                if ($item) {
                    return (float) $item->unit_price;
                }

                if ($items->count() === 1) {
                    return (float) $items->first()->unit_price;
                }
            }

            if ((float) $this->transaction->final_amount > 0) {
                return (float) $this->transaction->final_amount;
            }
        }

        // 2. Dari pesanan eCommerce
        if ($this->ecommerceOrder) {
            $items = $this->ecommerceOrder->items;
            if ($items && $items->isNotEmpty()) {
                $item = $items->first(function ($it) {
                    return $it->product && $it->product->ppob_sku === $this->buyer_sku_code;
                });

                if ($item) {
                    return (float) $item->price;
                }
            }

            if ((float) $this->ecommerceOrder->total_amount > 0) {
                return (float) $this->ecommerceOrder->total_amount;
            }
        }

        // 3. Fallback master produk
        if ($this->product) {
            return (float) $this->product->selling_price;
        }

        return 0;
    }

    public function getProfitAttribute(): float
    {
        if (strtolower($this->status ?? '') === 'gagal') {
            return 0;
        }

        return max(0, $this->selling_price - $this->modal_price);
    }
}
