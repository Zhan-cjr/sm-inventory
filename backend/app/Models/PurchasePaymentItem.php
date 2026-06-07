<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PurchasePaymentItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'purchase_payment_id',
        'goods_receipt_id',
        'amount_paid',
    ];

    public function payment()
    {
        return $this->belongsTo(PurchasePayment::class, 'purchase_payment_id');
    }

    public function goodsReceipt()
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }
}
