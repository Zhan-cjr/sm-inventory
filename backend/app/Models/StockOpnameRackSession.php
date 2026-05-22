<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StockOpnameRackSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'session_id', 'rack_id', 'rack_token',
        'count1_status', 'count2_status',
        'count1_by_name', 'count2_by_name',
        'count1_at', 'count2_at',
        'active_count_at', 'active_check_at',
    ];

    protected $casts = [
        'count1_at' => 'datetime',
        'count2_at' => 'datetime',
        'active_count_at' => 'datetime',
        'active_check_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(StockOpnameSession::class, 'session_id');
    }

    public function rack()
    {
        return $this->belongsTo(StockOpnameRack::class, 'rack_id');
    }

    public function items()
    {
        return $this->hasMany(StockOpnameItem::class, 'rack_session_id');
    }

    public function isCount1Locked(): bool
    {
        return $this->count1_status === 'DONE';
    }

    public function isCount2Locked(): bool
    {
        return $this->count2_status === 'DONE';
    }
}
