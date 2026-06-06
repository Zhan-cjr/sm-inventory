<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StockTransfer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'reference_number',
        'from_branch_id',
        'to_branch_id',
        'status',
        'transfer_date',
        'received_date',
        'created_by',
        'received_by',
        'notes',
        'total_amount',
    ];

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }

    protected static function booted()
    {
        static::deleting(function ($transfer) {
            $userId = auth()->id() ?? 1;

            if (in_array($transfer->status, ['in_transit', 'received'])) {
                // 1. Kembalikan stok ke cabang asal (karena status in_transit atau received sudah memotong asal)
                foreach ($transfer->items as $item) {
                    $stockOrigin = \App\Models\Stock::firstOrCreate(
                        ['branch_id' => $transfer->from_branch_id, 'product_id' => $item->product_id],
                        ['id' => \Illuminate\Support\Str::uuid()->toString(), 'quantity_on_hand' => 0]
                    );
                    $stockOrigin->quantity_on_hand += $item->quantity;
                    $stockOrigin->log_type = 'TRANSFER_DELETED_RESTORE';
                    $stockOrigin->reason_code = 'DELETE_TRANSFER';
                    $stockOrigin->reference_doc_type = 'STOCK_TRANSFER';
                    $stockOrigin->reference_doc_id = $transfer->id;
                    $stockOrigin->recorded_by = $userId;
                    $stockOrigin->notes = "Restore stok karena transfer dihapus";
                    $stockOrigin->save();
                }
            }

            if ($transfer->status === 'received') {
                // 2. Tarik kembali/kurangi stok di cabang tujuan (karena status received sudah menambah tujuan)
                foreach ($transfer->items as $item) {
                    $stockDest = \App\Models\Stock::firstOrCreate(
                        ['branch_id' => $transfer->to_branch_id, 'product_id' => $item->product_id],
                        ['id' => \Illuminate\Support\Str::uuid()->toString(), 'quantity_on_hand' => 0]
                    );
                    $stockDest->quantity_on_hand -= $item->quantity;
                    $stockDest->log_type = 'TRANSFER_DELETED_REVERT';
                    $stockDest->reason_code = 'DELETE_TRANSFER';
                    $stockDest->reference_doc_type = 'STOCK_TRANSFER';
                    $stockDest->reference_doc_id = $transfer->id;
                    $stockDest->recorded_by = $userId;
                    $stockDest->notes = "Revert stok karena transfer diterima dihapus";
                    $stockDest->save();
                }
            }

            // 3. Hapus jurnal akuntansi terkait
            $journals = \App\Models\JournalEntry::where('journalable_id', $transfer->id)
                ->where('journalable_type', \App\Models\StockTransfer::class)
                ->get();

            foreach ($journals as $journal) {
                \App\Models\JournalEntryLine::where('journal_entry_id', $journal->id)->delete();
                $journal->delete();
            }

            // 4. Hapus detail item (opsional jika cascade delete belum diset di DB)
            $transfer->items()->delete();
        });
    }
}
