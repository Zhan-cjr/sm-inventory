<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxInvoiceItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'tax_invoice_id',
        'name',
        'harga_satuan',
        'jumlah_barang',
        'harga_total',
        'diskon',
        'dpp',
        'ppn',
        'ppnbm',
    ];

    protected $casts = [
        'harga_satuan' => 'decimal:2',
        'jumlah_barang' => 'decimal:2',
        'harga_total' => 'decimal:2',
        'diskon' => 'decimal:2',
        'dpp' => 'decimal:2',
        'ppn' => 'decimal:2',
        'ppnbm' => 'decimal:2',
    ];

    public function taxInvoice(): BelongsTo
    {
        return $this->belongsTo(TaxInvoice::class);
    }
}
