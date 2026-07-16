<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AuthorizationRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'cashier_id',
        'supervisor_id',
        'action',
        'details',
        'status',
        'expires_at',
        'telegram_messages'
    ];

    protected $casts = [
        'details' => 'array',
        'expires_at' => 'datetime',
        'telegram_messages' => 'array',
    ];

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
