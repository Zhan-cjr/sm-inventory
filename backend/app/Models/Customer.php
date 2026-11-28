<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id', 'name', 'email', 'password', 'phone', 
        'address', 'member_tier', 'points', 'is_active'
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'points' => 'integer',
    ];

    protected $appends = ['tier_discount_percent'];

    public function getTierDiscountPercentAttribute()
    {
        if (empty($this->member_tier)) {
            return 0;
        }

        $map = \App\Models\MemberTier::getDiscountMap($this->organization_id);

        return $map[strtoupper((string) $this->member_tier)] ?? 0;
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($customer) {
            $customer->member_tier = static::calculateTier($customer);
        });
    }

    public static function calculateTier($customer)
    {
        $points = (int) $customer->points;
        $organizationId = $customer->organization_id;

        // Fetch the highest tier the user qualifies for
        $tier = \App\Models\MemberTier::where('organization_id', $organizationId)
            ->where('min_points', '<=', $points)
            ->orderBy('min_points', 'desc')
            ->first();

        return $tier ? strtoupper($tier->name) : 'REGULAR';
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function pointHistories(): HasMany
    {
        return $this->hasMany(PointHistory::class);
    }

    public function addPoints(int $points, string $refType, ?string $refId, string $description): void
    {
        if ($points <= 0) return;

        $before = $this->points;
        $this->points += $points;
        $this->save();

        \App\Models\PointHistory::create([
            'customer_id' => $this->id,
            'points' => $points,
            'before_points' => $before,
            'after_points' => $this->points,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'description' => $description,
        ]);
    }

    public function deductPoints(int $points, string $refType, ?string $refId, string $description): void
    {
        if ($points <= 0) return;

        $before = $this->points;
        $this->points = max(0, $this->points - $points);
        $this->save();

        \App\Models\PointHistory::create([
            'customer_id' => $this->id,
            'points' => -$points,
            'before_points' => $before,
            'after_points' => $this->points,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'description' => $description,
        ]);
    }

    public function createMemberToken(int $ttlDays = 90): string
    {
        $payload = [
            'cid' => $this->id,
            'pwd' => substr(hash('sha256', (string) $this->password), 0, 16),
            'exp' => now()->addDays($ttlDays)->timestamp,
        ];

        return \Illuminate\Support\Facades\Crypt::encryptString(json_encode($payload));
    }

    public static function resolveFromMemberToken(?string $token): ?self
    {
        if (empty($token)) {
            return null;
        }

        try {
            $decoded = json_decode(\Illuminate\Support\Facades\Crypt::decryptString($token), true);
            if (!is_array($decoded) || empty($decoded['cid']) || empty($decoded['exp'])) {
                return null;
            }
            if ((int) $decoded['exp'] < now()->timestamp) {
                return null;
            }

            $customer = static::where('id', $decoded['cid'])
                ->where('is_active', true)
                ->first();

            if (!$customer) {
                return null;
            }

            $expectedPwdHash = substr(hash('sha256', (string) $customer->password), 0, 16);
            if (!isset($decoded['pwd']) || !hash_equals($expectedPwdHash, (string) $decoded['pwd'])) {
                return null;
            }

            return $customer;
        } catch (\Throwable) {
            return null;
        }
    }

    public function toMaskedKioskArray(): array
    {
        $words = preg_split('/\s+/', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) > 1) {
            $maskedName = $words[0] . ' ' . mb_substr($words[1], 0, 1) . '***';
        } else {
            $rawName = $words[0] ?? 'Member';
            $maskedName = mb_substr($rawName, 0, min(3, mb_strlen($rawName))) . '***';
        }

        $phone = (string) $this->phone;
        $len = strlen($phone);
        $maskedPhone = $len > 7
            ? substr($phone, 0, 4) . str_repeat('*', max(3, $len - 7)) . substr($phone, -3)
            : $phone;

        return [
            'name' => $maskedName,
            'phone' => $maskedPhone,
            'points' => (int) $this->points,
            'member_tier' => $this->member_tier ?: 'REGULAR',
        ];
    }
}
