<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StockOpnameRack extends Model
{
    use HasUuids;

    protected $fillable = [
        'branch_id', 'rack_code', 'rack_name', 'location_description', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function rackSessions()
    {
        return $this->hasMany(StockOpnameRackSession::class, 'rack_id');
    }

    public function stocks(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Stock::class, 'stock_stock_opname_rack', 'rack_id', 'stock_id');
    }
}
