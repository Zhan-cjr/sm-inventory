<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reward extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'points_required',
        'stock',
        'image_path',
        'is_active',
    ];

    protected $casts = [
        'points_required' => 'integer',
        'stock' => 'integer',
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
