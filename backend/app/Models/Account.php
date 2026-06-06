<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Account extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'account_code',
        'name',
        'type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function journalLines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }
}
