<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Organization extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'code', 'timezone', 'currency_code'];

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }
}
