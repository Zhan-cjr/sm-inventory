<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PosDevice extends Model
{
    use HasUuids;

    protected $fillable = [
        'device_uuid',
        'name',
        'user_agent',
        'branch_id',
        'terminal_id',
        'status',
        'approved_at',
        'blocked_at'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'blocked_at' => 'datetime'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function terminal()
    {
        return $this->belongsTo(Terminal::class);
    }
}
