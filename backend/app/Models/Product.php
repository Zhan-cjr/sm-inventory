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
            'supplier_id' => $this->supplier_id,
            'supplier_division_id' => $this->supplier_division_id,
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
        static::creating(function ($product) {
            if (empty($product->organization_id)) {
                $product->organization_id = \Illuminate\Support\Facades\Auth::user()?->organization_id
                    ?? \App\Models\Organization::first()?->id;
            }
        });

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

            // Pastikan jika barcode kosong selalu disimpan sebagai NULL (bukan string kosong '')
            // agar indeks UNIQUE di MySQL mengizinkan banyak produk tanpa barcode tanpa pernah bentrok.
            if (empty($primaryBarcode)) {
                $product->barcode = null;
            } else {
                $product->barcode = $primaryBarcode;
            }

            // 1. Validasi Barcode Utama
            if (!empty($primaryBarcode)) {
                if (!empty($sku) && strtolower($primaryBarcode) === strtolower($sku)) {
                    throw new \InvalidArgumentException("Barcode utama '{$primaryBarcode}' tidak boleh sama dengan SKU produk ini.");
                }

                $exists = static::where('id', '!=', $productId)
                    ->where(function ($q) use ($primaryBarcode) {
                        $q->where('barcode', $primaryBarcode)
                          ->orWhere('sku', $primaryBarcode)
                          ->orWhereExists(function ($sub) use ($primaryBarcode) {
                              $sub->selectRaw('1')
                                  ->from('product_barcodes')
                                  ->whereColumn('product_barcodes.product_id', 'products.id')
                                  ->where('product_barcodes.barcode', $primaryBarcode);
                          });
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
                              ->orWhereExists(function ($sub) use ($code) {
                                  $sub->selectRaw('1')
                                      ->from('product_barcodes')
                                      ->whereColumn('product_barcodes.product_id', 'products.id')
                                      ->where('product_barcodes.barcode', $code);
                              });
                        })->first();

                    if ($exists) {
                        throw new \InvalidArgumentException("Multi Barcode '{$code}' sudah digunakan oleh produk '{$exists->name}' (SKU/Barcode/Multi Barcode).");
                    }
                }
            }
        });

        static::saved(function ($product) {
            // Keep normalized product_barcodes table in sync with metadata->additional_barcodes
            if ($product->wasRecentlyCreated || $product->wasChanged('metadata')) {
                $product->syncNormalizedBarcodes();
            }

            if ($product->wasChanged(['supplier_id', 'supplier_division_id']) && $product->supplier_id) {
                \App\Services\SuggestedOrderService::syncBranchStocksCycle($product->supplier_id, $product->supplier_division_id);
            }

            \Illuminate\Support\Facades\Cache::forget('ecommerce_products_all');
            foreach (\App\Models\Branch::pluck('id') as $branchId) {
                \Illuminate\Support\Facades\Cache::forget('ecommerce_products_' . $branchId);
                \Illuminate\Support\Facades\Cache::forget('pos_products_json_gz_branch_' . $branchId);
            }
            
            // Broadcast the product update to the POS catalog channel
            try {
                event(new \App\Events\ProductUpdated($product));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Broadcast error for ProductUpdated: ' . $e->getMessage());
            }
        });

        static::deleted(function ($product) {
            \App\Models\ProductBarcode::where('product_id', $product->id)->delete();
            \Illuminate\Support\Facades\Cache::forget('ecommerce_products_all');
            foreach (\App\Models\Branch::pluck('id') as $branchId) {
                \Illuminate\Support\Facades\Cache::forget('ecommerce_products_' . $branchId);
                \Illuminate\Support\Facades\Cache::forget('pos_products_json_gz_branch_' . $branchId);
            }
        });
    }

    protected $fillable = [
        'organization_id', 'sku', 'barcode', 'name', 
        'category_id', 'sub_category', 'supplier_id', 'supplier_division_id', 'cost_price', 
        'cost_price_tax',
        'selling_price', 'margin_gol_1', 'harga_jual_1', 'qty_min_gol_1',
        'margin_gol_2', 'harga_jual_2', 'qty_min_gol_2',
        'margin_gol_3', 'harga_jual_3', 'qty_min_gol_3',
        'unit_of_measure', 'reorder_point', 
        'reorder_qty', 'lead_time_days', 'is_active', 'is_taxable', 'metadata', 
        'is_ecommerce_active', 'ecommerce_category', 'image_path',
        'product_type', 'ppob_sku', 'ppob_provider',
        'listing_status', 'listing_fee', 'trial_start_date', 'trial_end_date', 'allowed_branch_ids', 'listing_notes',
        'product_listing_id'
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
        'listing_fee' => 'decimal:2',
        'trial_start_date' => 'date',
        'trial_end_date' => 'date',
        'allowed_branch_ids' => 'array',
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

    public function supplierDivision(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SupplierDivision::class, 'supplier_division_id');
    }

    public function productListing(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProductListing::class, 'product_listing_id');
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

    public function listingDeduction(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SupplierDeduction::class, 'reference_id')
            ->where('deduction_type', 'LISTING_FEE');
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->trial_end_date) {
            return null;
        }
        return (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($this->trial_end_date)->startOfDay(), false);
    }

    public function isTrialActive(): bool
    {
        return $this->listing_status === 'TRIAL' && ($this->trial_end_date === null || \Carbon\Carbon::parse($this->trial_end_date)->endOfDay()->isFuture());
    }

    public function isTrialExpired(): bool
    {
        return $this->listing_status === 'TRIAL' && $this->trial_end_date !== null && \Carbon\Carbon::parse($this->trial_end_date)->endOfDay()->isPast();
    }

    public function isBranchAllowed(string $branchId): bool
    {
        if ($this->listing_status === 'REGULAR' || empty($this->allowed_branch_ids)) {
            return true;
        }
        return in_array($branchId, (array) $this->allowed_branch_ids);
    }

    public function scopeListingProducts($query)
    {
        return $query->where('listing_status', '!=', 'REGULAR');
    }

    public function scopeInTrial($query)
    {
        return $query->where('listing_status', 'TRIAL');
    }

    public function scopePassed($query)
    {
        return $query->where('listing_status', 'PASSED');
    }

    public function scopeDelisted($query)
    {
        return $query->where('listing_status', 'DELISTED');
    }

    /**
     * Check if product was an existing master product prior to being added to a listing.
     */
    public function getIsExistingListingAttribute(): bool
    {
        if (isset($this->metadata['is_existing_listing'])) {
            return (bool) $this->metadata['is_existing_listing'];
        }

        if ($this->productListing && $this->created_at && $this->productListing->created_at) {
            return $this->created_at->lt($this->productListing->created_at->subMinutes(2));
        }

        return false;
    }

    /**
     * Generate next sequential SKU for the given organization in format SKU-DDMMYYxxxx.
     */
    public static function generateNextSku(?string $organizationId = null, array $reservedSkus = []): string
    {
        $orgId = $organizationId ?? \Illuminate\Support\Facades\Auth::user()?->organization_id
            ?? \App\Models\Organization::first()?->id;

        $prefixSku = 'SKU-' . date('dmy');

        $query = static::where('sku', 'like', $prefixSku . '%');
        if ($orgId) {
            $query->where('organization_id', $orgId);
        }

        $lastSkuRecord = $query->orderBy('sku', 'desc')->first();
        $lastNum = $lastSkuRecord ? (int) substr($lastSkuRecord->sku, -4) : 0;

        do {
            $lastNum++;
            $candidateSku = $prefixSku . str_pad($lastNum, 4, '0', STR_PAD_LEFT);
        } while (
            in_array($candidateSku, $reservedSkus) ||
            static::where('sku', $candidateSku)
                ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
                ->exists()
        );

        return $candidateSku;
    }

    public function barcodes()
    {
        return $this->hasMany(ProductBarcode::class);
    }

    /**
     * Sync metadata->additional_barcodes into the indexed product_barcodes table.
     */
    public function syncNormalizedBarcodes(): void
    {
        $metadata = $this->metadata;
        $raw = (is_array($metadata) && !empty($metadata['additional_barcodes']))
            ? $metadata['additional_barcodes']
            : [];

        $codes = is_array($raw)
            ? $raw
            : array_map('trim', explode(',', (string) $raw));

        $clean = [];
        $seen = [];
        foreach ($codes as $code) {
            $code = trim((string) $code);
            if ($code === '' || mb_strlen($code) > 100) {
                continue;
            }
            $lower = strtolower($code);
            if (isset($seen[$lower])) {
                continue;
            }
            $seen[$lower] = true;
            $clean[] = $code;
        }

        ProductBarcode::where('product_id', $this->id)
            ->when(!empty($clean), fn ($q) => $q->whereNotIn('barcode', $clean))
            ->delete();

        if (!empty($clean)) {
            $now = now();
            $rows = array_map(fn (string $code) => [
                'product_id' => $this->id,
                'organization_id' => $this->organization_id,
                'barcode' => $code,
                'created_at' => $now,
                'updated_at' => $now,
            ], $clean);

            \Illuminate\Support\Facades\DB::table('product_barcodes')->insertOrIgnore($rows);
        }
    }

    /**
     * Format a product label consistently for Filament Select dropdowns.
     */
    public static function formatSelectLabel(self $product): string
    {
        $codes = array_filter([$product->sku, $product->barcode]);
        $suffix = !empty($codes) ? ' [' . implode(' | ', $codes) . ']' : '';

        return $product->name . $suffix;
    }

    /**
     * Resolve a single option label for Filament Select::getOptionLabelUsing().
     */
    public static function getSelectOptionLabel($id): ?string
    {
        if (empty($id)) {
            return null;
        }

        $product = static::select(['id', 'name', 'sku', 'barcode'])->find($id);

        return $product ? static::formatSelectLabel($product) : null;
    }

    /**
     * Resolve multiple option labels for Filament Select::getOptionLabelsUsing().
     */
    public static function getSelectOptionLabels(array $ids): array
    {
        $ids = array_values(array_filter($ids));
        if (empty($ids)) {
            return [];
        }

        return static::select(['id', 'name', 'sku', 'barcode'])
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (self $p) => [$p->id => static::formatSelectLabel($p)])
            ->all();
    }

    /**
     * Hybrid AJAX search for Filament Select dropdowns (handles 29k+ SKUs):
     * 1. Exact barcode / SKU / additional_barcode match -> returns ONLY exact match(es).
     * 2. Meilisearch (matchingStrategy = 'all', prioritizing active products).
     * 3. MySQL multi-word substring fallback if Meilisearch returns < 10 hits or fails.
     */
    public static function searchForSelect(string $search, int $limit = 100, ?string $productType = null): array
    {
        $search = trim($search);
        if ($search === '') {
            return [];
        }

        $columns = ['id', 'name', 'sku', 'barcode', 'is_active', 'product_type'];

        // Layer 1: Exact barcode / SKU match (strict isolated result)
        $exact = collect();
        if (mb_strlen($search) >= 2) {
            $exact = static::select($columns)
                ->when($productType, fn ($q) => $q->where('product_type', $productType))
                ->where(function ($q) use ($search) {
                    $q->where('barcode', $search)
                        ->orWhere('sku', $search);
                })
                ->get();

            if ($exact->isEmpty() && !str_contains($search, ' ')) {
                $exact = static::select($columns)
                    ->when($productType, fn ($q) => $q->where('product_type', $productType))
                    ->whereExists(function ($sub) use ($search) {
                        $sub->selectRaw('1')
                            ->from('product_barcodes')
                            ->whereColumn('product_barcodes.product_id', 'products.id')
                            ->where('product_barcodes.barcode', $search);
                    })
                    ->get();
            }
        }

        if ($exact->isNotEmpty()) {
            return $exact
                ->mapWithKeys(fn (self $p) => [$p->id => static::formatSelectLabel($p)])
                ->all();
        }

        // Layer 2: Meilisearch (fast prefix + typo-tolerant, matchingStrategy = 'all')
        $results = [];
        try {
            $scoutQuery = static::search($search, function ($meilisearch, string $query, array $options) use ($limit, $productType) {
                $options['limit'] = $limit;
                $options['matchingStrategy'] = 'all';
                if ($productType) {
                    $options['filter'] = 'product_type = "' . addslashes($productType) . '"';
                }

                return $meilisearch->search($query, $options);
            });

            $hits = $scoutQuery->query(fn ($q) => $q->select($columns))->get();

            // Prioritize active products while preserving Meilisearch relevance order
            $activeHits = $hits->filter(fn (self $p) => (bool) $p->is_active);
            $inactiveHits = $hits->reject(fn (self $p) => (bool) $p->is_active);

            foreach ($activeHits->concat($inactiveHits) as $p) {
                $results[$p->id] = static::formatSelectLabel($p);
            }
        } catch (\Throwable $e) {
            // Fall through cleanly to MySQL fallback
        }

        // Layer 3: MySQL multi-word substring fallback when Meilisearch has < 10 hits
        // (covers mid-word / trailing-barcode searches and newly created unindexed products)
        if (count($results) < 10) {
            $tokens = array_values(array_filter(preg_split('/\s+/', $search) ?: []));
            $remaining = max(1, $limit - count($results));

            $fallbackQuery = static::select($columns)
                ->when($productType, fn ($q) => $q->where('product_type', $productType))
                ->when(!empty($results), fn ($q) => $q->whereNotIn('id', array_keys($results)));

            foreach ($tokens as $token) {
                $like = '%' . $token . '%';
                $fallbackQuery->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('sku', 'like', $like)
                        ->orWhere('barcode', 'like', $like);
                });
            }

            $fallbackHits = $fallbackQuery
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->limit($remaining)
                ->get();

            foreach ($fallbackHits as $p) {
                $results[$p->id] = static::formatSelectLabel($p);
            }
        }

        return array_slice($results, 0, $limit, true);
    }
}
