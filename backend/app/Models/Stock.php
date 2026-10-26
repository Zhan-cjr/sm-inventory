<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Stock extends Model
{
    use HasUuids, LogsActivity;

    protected $touches = ['product'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function booted()
    {
        static::creating(function ($stock) {
            if ($stock->product_id) {
                $stock->syncDefaultPricesFromProduct();
            }
        });

        static::saved(function ($stock) {
            \Illuminate\Support\Facades\Cache::forget('ecommerce_products_all');
            if ($stock->branch_id) {
                \Illuminate\Support\Facades\Cache::forget('ecommerce_products_' . $stock->branch_id);
                \Illuminate\Support\Facades\Cache::forget('pos_products_json_gz_branch_' . $stock->branch_id);
                
                // Broadcast stock update
                event(new \App\Events\StockUpdated($stock));
            }
        });

        static::deleted(function ($stock) {
            \Illuminate\Support\Facades\Cache::forget('ecommerce_products_all');
            if ($stock->branch_id) {
                \Illuminate\Support\Facades\Cache::forget('ecommerce_products_' . $stock->branch_id);
                \Illuminate\Support\Facades\Cache::forget('pos_products_json_gz_branch_' . $stock->branch_id);
                
                // Broadcast stock update (as 0)
                $stock->quantity_on_hand = 0;
                event(new \App\Events\StockUpdated($stock));
            }
        });
    }
    
    public $log_type;
    public $reason_code;
    public $notes;
    public $reference_doc_type;
    public $reference_doc_id;
    public $recorded_by;
    public $log_date;

    protected $fillable = [
        'branch_id', 'product_id', 'supplier_id', 'supplier_division_id', 'cost_price', 'cost_price_tax', 'selling_price', 
        'margin_gol_1', 'harga_jual_1', 'qty_min_gol_1',
        'margin_gol_2', 'harga_jual_2', 'qty_min_gol_2',
        'margin_gol_3', 'harga_jual_3', 'qty_min_gol_3',
        'quantity_on_hand', 'quantity_damaged',
        'quantity_reserved', 'last_count_date', 'min_qty', 
        'max_qty', 'lead_time', 'safety_stock', 'desired_inventory_days', 'version', 'is_active'
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
        'quantity_on_hand' => 'decimal:2',
        'quantity_damaged' => 'decimal:2',
        'qty_min_gol_1' => 'integer',
        'qty_min_gol_2' => 'integer',
        'qty_min_gol_3' => 'integer',
        'lead_time' => 'integer',
        'safety_stock' => 'integer',
        'desired_inventory_days' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Hitung stok layak jual saat ini (stok fisik dikurangi stok karantina BS).
     */
    public function getSalableQuantityAttribute(): float
    {
        return max(0, (float)$this->quantity_on_hand - (float)($this->quantity_damaged ?? 0));
    }

    /**
     * Pindahkan stok dari Layak Jual ke Karantina BS (Bad Stock).
     */
    public function moveToQuarantine(float $qty, string $reason = '', ?string $notes = null, ?int $userId = null, ?string $referenceDocId = null): StockQuarantineMovement
    {
        if ($qty <= 0) {
            throw new \InvalidArgumentException("Kuantitas karantina harus lebih dari 0.");
        }

        $salable = $this->salable_quantity;
        if ($qty > $salable) {
            throw new \InvalidArgumentException("Kuantitas melebihi stok layak jual yang tersedia ({$salable} pcs).");
        }

        $prevDamaged = (float)($this->quantity_damaged ?? 0);
        $this->quantity_damaged = $prevDamaged + $qty;
        $this->save();

        $recordedBy = (string)($userId ?: (auth()->id() ?: (\App\Models\User::value('id') ?: '00000000-0000-0000-0000-000000000000')));

        // Buat record riwayat mutasi karantina
        $movement = StockQuarantineMovement::create([
            'organization_id' => $this->branch->organization_id ?? null,
            'branch_id' => $this->branch_id,
            'product_id' => $this->product_id,
            'movement_type' => 'MOVE_TO_BS',
            'quantity' => $qty,
            'reason' => $reason,
            'reference_doc_type' => $referenceDocId ? 'STOCK_QUARANTINE_BATCH' : null,
            'reference_doc_id' => $referenceDocId,
            'notes' => $notes,
            'created_by' => $userId ?: auth()->id(),
        ]);

        // Catat ke Kartu Stok (InventoryLog) sebagai catatan pemindahan status
        try {
            $docId = $referenceDocId ?: $movement->id;
            InventoryLog::create([
                'branch_id' => $this->branch_id,
                'product_id' => $this->product_id,
                'log_type' => 'QUARANTINE_IN',
                'quantity_before' => (float)$this->quantity_on_hand,
                'quantity_change' => 0, // Fisik di toko belum keluar
                'quantity_after' => (float)$this->quantity_on_hand,
                'reason_code' => $reason ?: 'BAD_STOCK',
                'reference_doc_type' => 'STOCK_QUARANTINE',
                'reference_doc_id' => $docId,
                'notes' => ($referenceDocId ? "[{$referenceDocId}] " : '') . "Pindah ke Karantina BS ({$qty} pcs). Alasan: {$reason}. " . ($notes ? "Catatan: {$notes}. " : '') . "Saldo Layak Jual: {$this->salable_quantity} pcs, BS: {$this->quantity_damaged} pcs.",
                'recorded_by' => $recordedBy,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Gagal mencatat InventoryLog Karantina BS: " . $e->getMessage());
        }

        return $movement;
    }

    /**
     * Kembalikan stok dari Karantina BS ke Stok Layak Jual (Pembatalan/Restorasi).
     */
    public function restoreFromQuarantine(float $qty, ?string $notes = null, ?int $userId = null): StockQuarantineMovement
    {
        if ($qty <= 0) {
            throw new \InvalidArgumentException("Kuantitas pengembalian harus lebih dari 0.");
        }

        $damaged = (float)($this->quantity_damaged ?? 0);
        if ($qty > $damaged) {
            throw new \InvalidArgumentException("Kuantitas melebihi stok karantina BS yang tersedia ({$damaged} pcs).");
        }

        $this->quantity_damaged = max(0, $damaged - $qty);
        $this->save();

        $recordedBy = (string)($userId ?: (auth()->id() ?: (\App\Models\User::value('id') ?: '00000000-0000-0000-0000-000000000000')));

        $movement = StockQuarantineMovement::create([
            'organization_id' => $this->branch->organization_id ?? null,
            'branch_id' => $this->branch_id,
            'product_id' => $this->product_id,
            'movement_type' => 'RESTORE_TO_SALABLE',
            'quantity' => $qty,
            'reason' => 'PEMBATALAN_KARANTINA',
            'notes' => $notes,
            'created_by' => $userId ?: auth()->id(),
        ]);

        try {
            InventoryLog::create([
                'branch_id' => $this->branch_id,
                'product_id' => $this->product_id,
                'log_type' => 'QUARANTINE_RESTORE',
                'quantity_before' => (float)$this->quantity_on_hand,
                'quantity_change' => 0,
                'quantity_after' => (float)$this->quantity_on_hand,
                'reason_code' => 'RESTORE_SALABLE',
                'reference_doc_type' => 'STOCK_QUARANTINE',
                'reference_doc_id' => $movement->id,
                'notes' => "Dikembalikan ke Stok Layak Jual ({$qty} pcs). " . ($notes ? "Catatan: {$notes}. " : '') . "Saldo Layak Jual: {$this->salable_quantity} pcs, BS: {$this->quantity_damaged} pcs.",
                'recorded_by' => $recordedBy,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Gagal mencatat InventoryLog Restorasi Karantina: " . $e->getMessage());
        }

        return $movement;
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function supplierDivision(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SupplierDivision::class, 'supplier_division_id');
    }

    public function getEffectiveSupplierIdAttribute(): ?string
    {
        return $this->supplier_id ?: $this->product?->supplier_id;
    }

    public function getEffectiveSupplierDivisionIdAttribute(): ?string
    {
        return $this->supplier_id ? $this->supplier_division_id : $this->product?->supplier_division_id;
    }

    public function racks(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(StockOpnameRack::class, 'stock_stock_opname_rack', 'stock_id', 'rack_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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

    /**
     * Sinkronisasi harga dan margin default dari master produk jika belum diset atau kosong.
     */
    public function syncDefaultPricesFromProduct(?Product $product = null, bool $force = false): self
    {
        $product = $product ?? ($this->relationLoaded('product') ? $this->product : Product::find($this->product_id));
        if (!$product) {
            return $this;
        }

        if ($force || ($this->selling_price === null && (float) ($this->harga_jual_1 ?? 0) <= 0)) {
            if ($this->cost_price === null || (float) $this->cost_price <= 0) {
                $this->cost_price = $product->cost_price;
            }
            if ($this->cost_price_tax === null || (float) $this->cost_price_tax <= 0) {
                $this->cost_price_tax = $product->cost_price_tax;
            }
            $this->selling_price = $product->selling_price ?? $product->harga_jual_1;
            $this->harga_jual_1 = $product->harga_jual_1 ?? $product->selling_price;
            $this->margin_gol_1 = $this->margin_gol_1 ?? $product->margin_gol_1;
            $this->qty_min_gol_1 = $this->qty_min_gol_1 ?? $product->qty_min_gol_1 ?? 1;

            $this->margin_gol_2 = $this->margin_gol_2 ?? $product->margin_gol_2;
            $this->harga_jual_2 = $this->harga_jual_2 ?? $product->harga_jual_2;
            $this->qty_min_gol_2 = $this->qty_min_gol_2 ?? $product->qty_min_gol_2;

            $this->margin_gol_3 = $this->margin_gol_3 ?? $product->margin_gol_3;
            $this->harga_jual_3 = $this->harga_jual_3 ?? $product->harga_jual_3;
            $this->qty_min_gol_3 = $this->qty_min_gol_3 ?? $product->qty_min_gol_3;
        }

        return $this;
    }
}
