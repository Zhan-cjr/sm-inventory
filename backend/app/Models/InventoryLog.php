<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class InventoryLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'branch_id', 'product_id', 'log_type', 'quantity_before', 'quantity_change', 'quantity_after',
        'reason_code', 'reference_doc_type', 'reference_doc_id',
        'recorded_by', 'notes'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function getDisplayDocNumberAttribute(): string
    {
        $doc = $this->reference_doc_id;

        // 1. Jika sudah ada nomor dan bukan format UUID 36 karakter
        if (!empty($doc) && !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $doc)) {
            return $doc;
        }

        // 2. Cek apakah di kolom notes terdapat nomor dokumen (misal ADJ-..., OP-..., GR-..., PR-..., TR-..., PH-...)
        if (!empty($this->notes)) {
            if (preg_match('/\b(ADJ-[A-Z0-9]+|OP-[A-Z0-9-]+|GR-[0-9]+|PR-[A-Z0-9-]+|TR-[A-Z0-9-]+|PO-[0-9]+|PH-[A-Z0-9-]+)\b/i', $this->notes, $matches)) {
                return $matches[1];
            }
        }

        // 3. Jika doc adalah UUID, resolve dari model transaksi terkait
        if (!empty($doc)) {
            if (in_array($this->log_type, ['SALE', 'SALE_CANCEL', 'SALE_VOID'])) {
                $tx = \App\Models\Transaction::find($doc);
                if ($tx) return $tx->receipt_number ?: ($tx->local_transaction_id ?: $doc);
            }

            if (in_array($this->log_type, ['PURCHASE', 'PURCHASE_CANCEL'])) {
                $gr = \App\Models\GoodsReceipt::find($doc);
                if ($gr) return $gr->receipt_number ?: $doc;
            }

            if (in_array($this->log_type, ['ADJUSTMENT', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT'])) {
                if ($this->reference_doc_type === 'STOCK_OPNAME') {
                    $op = \App\Models\StockOpnameSession::find($doc);
                    if ($op) return $op->session_number ?: $doc;
                }
                $adj = \App\Models\StockAdjustment::find($doc);
                if ($adj) return $adj->adjustment_number ?: $doc;
            }

            if (in_array($this->log_type, ['TRANSFER_IN', 'TRANSFER_OUT'])) {
                $st = \App\Models\StockTransfer::find($doc);
                if ($st) return $st->transfer_number ?: $doc;
            }

            if ($this->log_type === 'PURCHASE_RETURN') {
                $pr = \App\Models\PurchaseReturn::find($doc);
                if ($pr) return $pr->return_number ?: $doc;
            }
        }

        return $doc ?: '-';
    }
}
