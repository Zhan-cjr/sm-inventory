<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasUuids, LogsActivity, Searchable;

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'category' => $this->category ? $this->category->name : '',
            'product_type' => $this->product_type,
            'is_active' => $this->is_active,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

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
        'cost_price_tax',
        'selling_price', 'margin_gol_1', 'harga_jual_1', 'qty_min_gol_1',
        'margin_gol_2', 'harga_jual_2', 'qty_min_gol_2',
        'margin_gol_3', 'harga_jual_3', 'qty_min_gol_3',
        'unit_of_measure', 'reorder_point', 
        'reorder_qty', 'lead_time_days', 'is_active', 'is_taxable', 'metadata', 
        'is_ecommerce_active', 'ecommerce_category', 'image_path',
        'product_type', 'ppob_sku'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'cost_price_tax' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'margin_gol_1' => 'decimal:2',
        'harga_jual_1' => 'decimal:2',
        'margin_gol_2' => 'decimal:2',
        'harga_jual_2' => 'decimal:2',
        'margin_gol_3' => 'decimal:2',
        'harga_jual_3' => 'decimal:2',
        'qty_min_gol_1' => 'integer',
        'qty_min_gol_2' => 'integer',
        'qty_min_gol_3' => 'integer',
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

    public function assemblies()
    {
        return $this->hasMany(ProductAssembly::class, 'parent_product_id');
    }

    public function conversions()
    {
        return $this->hasMany(ProductConversion::class, 'source_product_id');
    }
}
