<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id', 'name', 'email', 'password', 'phone', 
        'address', 'member_tier', 'points', 'is_active'
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'points' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($customer) {
            $customer->member_tier = static::calculateTier($customer->points);
        });
    }

    public static function calculateTier($points)
    {
        $points = (int) $points;
        if ($points >= 10000) {
            return 'PLATINUM';
        } elseif ($points >= 5000) {
            return 'GOLD';
        } elseif ($points >= 1000) {
            return 'SILVER';
        } else {
            return 'BRONZE';
        }
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function pointHistories(): HasMany
    {
        return $this->hasMany(PointHistory::class);
    }

    public function addPoints(int $points, string $refType, ?string $refId, string $description): void
    {
        if ($points <= 0) return;

        $before = $this->points;
        $this->points += $points;
        $this->save();

        \App\Models\PointHistory::create([
            'customer_id' => $this->id,
            'points' => $points,
            'before_points' => $before,
            'after_points' => $this->points,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'description' => $description,
        ]);
    }

    public function deductPoints(int $points, string $refType, ?string $refId, string $description): void
    {
        if ($points <= 0) return;

        $before = $this->points;
        $this->points = max(0, $this->points - $points);
        $this->save();

        \App\Models\PointHistory::create([
            'customer_id' => $this->id,
            'points' => -$points,
            'before_points' => $before,
            'after_points' => $this->points,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'description' => $description,
        ]);
    }
}
