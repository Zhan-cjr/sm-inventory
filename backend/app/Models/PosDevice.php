<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PosDevice extends Model
{
    use HasUuids;

    protected $fillable = [
        'device_uuid',
        'name',
        'user_agent',
        'branch_id',
        'terminal_id',
        'status',
        'approved_at',
        'blocked_at'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'blocked_at' => 'datetime'
    ];

    public static function cacheKey(string $deviceUuid): string
    {
        return 'pos_device:v2:uuid:' . $deviceUuid;
    }

    protected static function booted(): void
    {
        $clearCache = function (PosDevice $device): void {
            try {
                if (!empty($device->device_uuid)) {
                    \Illuminate\Support\Facades\Cache::forget(static::cacheKey($device->device_uuid));
                }
                $originalUuid = $device->getOriginal('device_uuid');
                if (!empty($originalUuid) && $originalUuid !== $device->device_uuid) {
                    \Illuminate\Support\Facades\Cache::forget(static::cacheKey($originalUuid));
                }
            } catch (\Throwable) {
                // Abaikan jika cache sementara tidak tersedia
            }
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function terminal()
    {
        return $this->belongsTo(Terminal::class);
    }
}
