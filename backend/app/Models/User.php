<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

#[Fillable(['name', 'username', 'email', 'password', 'organization_id', 'branch_id', 'role', 'pos_authorizations', 'custom_authorizations', 'telegram_chat_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pos_authorizations' => 'array',
            'custom_authorizations' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->hasRole(['superadmin', 'super_admin', 'super-admin'])) {
            return true;
        }

        return $this->can('akses_backend_admin') || $this->can('AksesBackendAdmin');
    }

    public function hasCustomAuthorization(string $action): bool
    {
        $auths = $this->custom_authorizations;
        if (is_string($auths)) {
            $auths = json_decode($auths, true) ?: [];
        }
        if (is_array($auths) && in_array($action, $auths)) {
            return true;
        }

        // Cek langsung ke database jika data session belum direfresh
        if ($this->exists && !empty($this->id)) {
            $dbAuths = \Illuminate\Support\Facades\DB::table('users')->where('id', $this->id)->value('custom_authorizations');
            if ($dbAuths) {
                if (is_string($dbAuths)) {
                    $dbAuths = json_decode($dbAuths, true) ?: [];
                }
                if (is_array($dbAuths) && in_array($action, $dbAuths)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getRoleAttribute($value): string
    {
        $spatieRoles = array_map('strtolower', $this->roles->pluck('name')->toArray());
        if (in_array('superadmin', $spatieRoles) || in_array('super_admin', $spatieRoles) || in_array('super-admin', $spatieRoles) || strtolower((string)$value) === 'superadmin' || strtolower((string)$value) === 'super_admin') {
            return 'SUPER_ADMIN';
        }
        if (in_array('admin', $spatieRoles) || strtolower((string)$value) === 'admin') {
            return 'ADMIN';
        }
        if (in_array('manager', $spatieRoles) || strtolower((string)$value) === 'manager') {
            return 'MANAGER';
        }
        if (in_array('supervisor', $spatieRoles) || in_array('spv', $spatieRoles) || strtolower((string)$value) === 'supervisor' || strtolower((string)$value) === 'spv') {
            return 'SUPERVISOR';
        }
        return $value ?: 'CASHIER';
    }
}
