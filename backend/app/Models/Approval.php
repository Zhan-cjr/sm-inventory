<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Approval extends Model
{
    use HasUuids;

    protected $fillable = [
        'approvable_type', 'approvable_id', 'user_id', 'status', 'notes', 'level'
    ];

    public function approvable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
