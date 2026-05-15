<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Branch extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id', 'name', 'code', 'address', 
        'phone', 'manager_id', 'is_active'
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
}
