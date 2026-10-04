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

    public function refundedBy()
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }
}
