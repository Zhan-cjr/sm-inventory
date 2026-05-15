<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdjustmentReason extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $fillable = ['name', 'type'];

    public function adjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }
}
