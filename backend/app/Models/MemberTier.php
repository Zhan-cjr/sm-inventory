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
        'min_points',
        'discount_percent',
        'color_hex'
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->organization_id) && auth()->check()) {
                $model->organization_id = auth()->user()->organization_id;
            }
        });
    }
}
