<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EcommerceOrder extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'customer_name',
        'customer_phone',
        'delivery_method',
        'delivery_address',
        'destination_area_id',
        'destination_latitude',
        'destination_longitude',
        'destination_postal_code',
        'shipping_cost',
        'courier_name',
        'courier_service',
        'awb_number',
        'biteship_order_id',
        'status',
        'total_amount',
        'points_redeemed',
        'points_redeemed_discount',
        'payment_method',
        'payment_status',
        'snap_token',
        'notes',
        'processed_by'
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items()
    {
        return $this->hasMany(EcommerceOrderItem::class, 'ecommerce_order_id');
    }

    public function ppobTransaction()
    {
        return $this->hasOne(PpobTransaction::class, 'ecommerce_order_id');
    }

    public function getCogsAttribute()
    {
        return $this->items->sum(function ($item) {
            if (!$item->product_id) {
                return 0;
            }

            // 1. Cek stock_batch_deductions (FIFO Riil)
            $batchCogs = \Illuminate\Support\Facades\DB::table('stock_batch_deductions')
                ->join('stock_batches', 'stock_batch_deductions.stock_batch_id', '=', 'stock_batches.id')
                ->where('stock_batch_deductions.ecommerce_order_item_id', $item->id)
                ->sum(\Illuminate\Support\Facades\DB::raw('stock_batch_deductions.quantity * stock_batches.cost_price'));

            if ($batchCogs > 0) {
                return (float) $batchCogs;
            }

            // 2. Fallback
            $stock = \App\Models\Stock::where('product_id', $item->product_id)
                ->where('branch_id', $this->branch_id)
                ->first();

            $stCostPriceTax = $stock ? $stock->cost_price_tax : 0;
            $stCostPrice = $stock ? $stock->cost_price : 0;
            $pCostPriceTax = $item->product ? $item->product->cost_price_tax : 0;
            $pCostPrice = $item->product ? $item->product->cost_price : 0;
            
            $fallbackPrice = $stCostPriceTax > 0 ? $stCostPriceTax : 
                ($stCostPrice > 0 ? $stCostPrice : 
                ($pCostPriceTax > 0 ? $pCostPriceTax : 
                ($pCostPrice > 0 ? $pCostPrice : 0)));

            return $item->quantity * (float) $fallbackPrice;
        });
    }

    public function getGrossProfitAttribute()
    {
        return $this->total_amount - $this->cogs;
    }
}
