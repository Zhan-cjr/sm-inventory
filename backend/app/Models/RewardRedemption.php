<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Exception;

class RewardRedemption extends Model
{
    use HasUuids;

    protected $fillable = [
        'customer_id',
        'reward_id',
        'branch_id',
        'points_redeemed',
        'quantity',
        'status',
        'notes',
    ];

    protected $casts = [
        'points_redeemed' => 'integer',
        'quantity' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($redemption) {
            DB::transaction(function () use ($redemption) {
                $customer = Customer::lockForUpdate()->findOrFail($redemption->customer_id);
                $reward = Reward::lockForUpdate()->findOrFail($redemption->reward_id);

                // 1. Check if point redemption is enabled in organization
                $org = $customer->organization;
                if ($org && !$org->point_redemption_enabled) {
                    throw new Exception('Penukaran poin saat ini dinonaktifkan oleh Perusahaan.');
                }

                $totalPointsNeeded = $reward->points_required * $redemption->quantity;
                $redemption->points_redeemed = $totalPointsNeeded;

                // 2. Check customer points
                if ($customer->points < $totalPointsNeeded) {
                    throw new Exception("Poin pelanggan tidak mencukupi. Dibutuhkan: {$totalPointsNeeded} Poin, Tersedia: {$customer->points} Poin.");
                }

                // 3. Check reward stock
                if ($reward->stock < $redemption->quantity) {
                    throw new Exception("Stok hadiah '{$reward->name}' tidak mencukupi. Tersedia: {$reward->stock} unit.");
                }

                // 4. Deduct points and stock
                $customer->deductPoints(
                    $totalPointsNeeded,
                    'REWARD_REDEMPTION',
                    $redemption->id,
                    "Tukar Hadiah: {$reward->name} ({$redemption->quantity}x)"
                );

                $reward->decrement('stock', $redemption->quantity);
            });
        });

        static::updating(function ($redemption) {
            // Handle cancellation / restoration of points
            if ($redemption->isDirty('status')) {
                $oldStatus = $redemption->getOriginal('status');
                $newStatus = $redemption->status;

                if ($oldStatus === 'COMPLETED' && $newStatus === 'CANCELLED') {
                    DB::transaction(function () use ($redemption) {
                        $customer = Customer::lockForUpdate()->findOrFail($redemption->customer_id);
                        $reward = Reward::lockForUpdate()->findOrFail($redemption->reward_id);

                        // Restore points
                        $customer->addPoints(
                            $redemption->points_redeemed,
                            'REWARD_CANCELLED',
                            $redemption->id,
                            "Batal Tukar Hadiah: {$reward->name} ({$redemption->quantity}x)"
                        );

                        // Restore stock
                        $reward->increment('stock', $redemption->quantity);
                    });
                }
            }
        });
    }
}
