<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class SupplierDivision extends Model
{
    use HasUuids;

    protected $fillable = [
        'supplier_id',
        'branch_id',
        'name',
        'contact_person',
        'phone',
        'email',
        'notes',
        'created_by',
    ];

    protected static function booted()
    {
        static::creating(function ($division) {
            if (Auth::check()) {
                $division->created_by ??= Auth::id();
                $division->branch_id ??= Auth::user()->branch_id;
            }
        });

        static::deleting(function ($division) {
            $count = $division->products()->count();
            if ($count > 0) {
                throw new \Exception("Sub-Divisi '{$division->name}' tidak dapat dihapus karena masih memiliki {$count} barang terdaftar.");
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_division_id');
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class, 'supplier_division_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'supplier_division_id');
    }
}
