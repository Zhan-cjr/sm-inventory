<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Product extends Model
{
    use HasUuids;

    protected static function booted()
    {
        static::deleting(function ($product) {
            $hasHistory = \App\Models\TransactionItem::where('product_id', $product->id)->exists() ||
                         \App\Models\PurchaseOrderItem::where('product_id', $product->id)->exists() ||
                         \App\Models\GoodsReceiptItem::where('product_id', $product->id)->exists() ||
                         \App\Models\StockAdjustmentItem::where('product_id', $product->id)->exists();

            if ($hasHistory) {
                throw new \Exception("Produk '{$product->name}' tidak dapat dihapus karena sudah memiliki histori transaksi. Silakan non-aktifkan saja.");
            }
        });
    }

    protected $fillable = [
        'organization_id', 'sku', 'barcode', 'name', 
        'category_id', 'sub_category', 'supplier_id', 'cost_price', 
        'selling_price', 'unit_of_measure', 'reorder_point', 
        'reorder_qty', 'lead_time_days', 'is_active', 'is_taxable', 'metadata', 
        'is_ecommerce_active', 'ecommerce_category', 'image_path'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_ecommerce_active' => 'boolean',
        'is_taxable' => 'boolean',
    ];

    public function organization(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }


    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
