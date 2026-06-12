<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id', 'code', 'name', 'contact_person', 
        'phone', 'email', 'address', 'is_active', 'default_due_days', 'default_po_expired_days', 'payment_method', 'is_consignment'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_consignment' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
