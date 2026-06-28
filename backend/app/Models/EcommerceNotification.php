<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EcommerceNotification extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'customer_id',
        'title',
        'body',
        'type',
        'is_read',
        'reference_id'
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
