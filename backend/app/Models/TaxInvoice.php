<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TaxInvoice extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id', 
        'type', 
        'nomor_faktur', 
        'tanggal_faktur', 
        'masa_pajak', 
        'npwp_lawan', 
        'nama_lawan', 
        'dpp', 
        'ppn', 
        'status', 
        'reference_id', 
        'reference_type'
    ];

    protected $casts = [
        'tanggal_faktur' => 'date',
        'dpp' => 'decimal:2',
        'ppn' => 'decimal:2',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
