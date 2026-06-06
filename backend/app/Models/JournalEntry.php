<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class JournalEntry extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'reference_number',
        'entry_date',
        'description',
        'status',
        'created_by',
        'journalable_id',
        'journalable_type',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journalable()
    {
        return $this->morphTo();
    }

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }
}
