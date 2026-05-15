<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Terminal extends Model
{
    use HasUuids;

    protected $fillable = ['branch_id', 'name', 'code', 'is_active'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
