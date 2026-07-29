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
            'additional_barcodes' => isset($this->metadata['additional_barcodes']) ? implode(', ', $this->metadata['additional_barcodes']) : '',
            'product_type' => $this->product_type,
            'is_active' => $this->is_active,
            'available_branch_ids' => $this->stocks()->pluck('branch_id')->toArray(),
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

        static::saving(function ($product) {
            $productId = $product->id;
            $sku = trim($product->sku ?? '');
            $primaryBarcode = trim($product->barcode ?? '');

            // 1. Validasi Barcode Utama
            if (!empty($primaryBarcode)) {
                if (!empty($sku) && strtolower($primaryBarcode) === strtolower($sku)) {
                    throw new \InvalidArgumentException("Barcode utama '{$primaryBarcode}' tidak boleh sama dengan SKU produk ini.");
                }

                $exists = static::where('id', '!=', $productId)
                    ->where(function ($q) use ($primaryBarcode) {
                        $q->where('barcode', $primaryBarcode)
                          ->orWhere('sku', $primaryBarcode)
                          ->orWhereJsonContains('metadata->additional_barcodes', $primaryBarcode)
                          ->orWhere('metadata->additional_barcodes', 'LIKE', '%' . $primaryBarcode . '%');
                    })->first();

                if ($exists) {
                    throw new \InvalidArgumentException("Barcode '{$primaryBarcode}' sudah digunakan oleh produk '{$exists->name}' (SKU/Barcode/Multi Barcode).");
                }
            }

            // 2. Validasi Multi Barcode (metadata.additional_barcodes)
            $metadata = $product->metadata;
            if (is_array($metadata) && !empty($metadata['additional_barcodes'])) {
                $additionalBarcodes = is_array($metadata['additional_barcodes'])
                    ? $metadata['additional_barcodes']
                    : array_map('trim', explode(',', (string) $metadata['additional_barcodes']));

                $seen = [];
                foreach ($additionalBarcodes as $code) {
                    $code = trim($code);
                    if (empty($code)) continue;

                    $lowerCode = strtolower($code);
                    if (in_array($lowerCode, $seen)) {
                        throw new \InvalidArgumentException("Multi Barcode '{$code}' terduplikasi dalam daftar yang Anda masukkan.");
                    }
                    $seen[] = $lowerCode;

                    if (!empty($primaryBarcode) && strtolower($code) === strtolower($primaryBarcode)) {
                        throw new \InvalidArgumentException("Multi Barcode '{$code}' tidak boleh sama dengan Barcode Utama produk ini.");
                    }

                    if (!empty($sku) && strtolower($code) === strtolower($sku)) {
                        throw new \InvalidArgumentException("Multi Barcode '{$code}' tidak boleh sama dengan SKU produk ini.");
                    }

                    $exists = static::where('id', '!=', $productId)
                        ->where(function ($q) use ($code) {
                            $q->where('barcode', $code)
                              ->orWhere('sku', $code)
                              ->orWhereJsonContains('metadata->additional_barcodes', $code)
                              ->orWhere('metadata->additional_barcodes', 'LIKE', '%' . $code . '%');
                        })->first();

                    if ($exists) {
                        throw new \InvalidArgumentException("Multi Barcode '{$code}' sudah digunakan oleh produk '{$exists->name}' (SKU/Barcode/Multi Barcode).");
                    }
                }
            }
        });

        static::saved(function ($product) {
            \Illuminate\Support\Facades\Cache::forget('ecommerce_products_all');
            foreach (\App\Models\Branch::pluck('id') as $branchId) {
                \Illuminate\Support\Facades\Cache::forget('ecommerce_products_' . $branchId);
                \Illuminate\Support\Facades\Cache::forget('pos_products_json_gz_branch_' . $branchId);
            }
            
            // Broadcast the product update to the POS catalog channel
            event(new \App\Events\ProductUpdated($product));
        });

        static::deleted(function ($product) {
            \Illuminate\Support\Facades\Cache::forget('ecommerce_products_all');
            foreach (\App\Models\Branch::pluck('id') as $branchId) {
                \Illuminate\Support\Facades\Cache::forget('ecommerce_products_' . $branchId);
                \Illuminate\Support\Facades\Cache::forget('pos_products_json_gz_branch_' . $branchId);
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

    public static function parseIndonesianNumber($value): float
    {
        if (is_null($value) || $value === '') {
            return 0.0;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $str = (string) $value;
        if (str_contains($str, ',')) {
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
            return (float) $str;
        }
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $str)) {
            $str = str_replace('.', '', $str);
        }
        return (float) $str;
    }

    public function setCostPriceAttribute($value)
    {
        $this->attributes['cost_price'] = static::parseIndonesianNumber($value);
    }

    public function setCostPriceTaxAttribute($value)
    {
        $this->attributes['cost_price_tax'] = static::parseIndonesianNumber($value);
    }

    public function setSellingPriceAttribute($value)
    {
        $this->attributes['selling_price'] = static::parseIndonesianNumber($value);
    }

    public function setHargaJual1Attribute($value)
    {
        $this->attributes['harga_jual_1'] = static::parseIndonesianNumber($value);
    }

    public function setHargaJual2Attribute($value)
    {
        $this->attributes['harga_jual_2'] = static::parseIndonesianNumber($value);
    }

    public function setHargaJual3Attribute($value)
    {
        $this->attributes['harga_jual_3'] = static::parseIndonesianNumber($value);
    }

    public function setMarginGol1Attribute($value)
    {
        $this->attributes['margin_gol_1'] = static::parseIndonesianNumber($value);
    }

    public function setMarginGol2Attribute($value)
    {
        $this->attributes['margin_gol_2'] = static::parseIndonesianNumber($value);
    }

    public function setMarginGol3Attribute($value)
    {
        $this->attributes['margin_gol_3'] = static::parseIndonesianNumber($value);
    }
}
