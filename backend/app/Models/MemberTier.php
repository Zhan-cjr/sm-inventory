<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberTier extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'name',
        'badge',
        'min_points',
        'min_spend_text',
        'discount_percent',
        'color_hex',
        'color_gradient',
        'perks',
    ];

    protected $casts = [
        'perks' => 'array',
        'min_points' => 'integer',
        'discount_percent' => 'float',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    protected static array $requestDiscountMaps = [];

    public static function discountMapCacheKey(?string $organizationId): string
    {
        return 'member_tiers:discount_map:' . ($organizationId ?: 'default');
    }

    public static function getDiscountMap(?string $organizationId): array
    {
        $key = $organizationId ?: 'default';
        if (isset(static::$requestDiscountMaps[$key])) {
            return static::$requestDiscountMaps[$key];
        }

        $loader = function () use ($organizationId): array {
            $tiers = static::where('organization_id', $organizationId)
                ->get(['name', 'discount_percent']);

            $map = [];
            foreach ($tiers as $tier) {
                $map[strtoupper((string) $tier->name)] = (float) $tier->discount_percent;
            }
            return $map;
        };

        try {
            $map = \Illuminate\Support\Facades\Cache::remember(
                static::discountMapCacheKey($organizationId),
                600,
                $loader
            );
        } catch (\Throwable) {
            $map = $loader();
        }

        static::$requestDiscountMaps[$key] = $map;
        return $map;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->organization_id) && auth()->check()) {
                $model->organization_id = auth()->user()->organization_id;
            }
        });

        $clearCache = function ($model): void {
            $key = $model->organization_id ?: 'default';
            unset(static::$requestDiscountMaps[$key]);
            try {
                \Illuminate\Support\Facades\Cache::forget(static::discountMapCacheKey($model->organization_id));
            } catch (\Throwable) {
                // Abaikan jika cache sementara tidak tersedia
            }
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }
}
