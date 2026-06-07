<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KontrabonItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'kontrabon_id',
        'goods_receipt_id',
        'amount',
    ];

    public function kontrabon()
    {
        return $this->belongsTo(Kontrabon::class);
    }

    public function goodsReceipt()
    {
        return $this->belongsTo(GoodsReceipt::class);
    }
}
