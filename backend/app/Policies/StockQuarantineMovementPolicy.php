<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\StockQuarantineMovement;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockQuarantineMovementPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StockQuarantineMovement') 
            || $authUser->can('ViewAny:StockAdjustment')
            || $authUser->hasRole(['superadmin', 'super_admin', 'admin', 'manager', 'kepala_toko', 'gudang']);
    }

    public function view(AuthUser $authUser, StockQuarantineMovement $movement): bool
    {
        return $this->viewAny($authUser);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StockQuarantineMovement') 
            || $authUser->can('Create:StockAdjustment')
            || $authUser->hasRole(['superadmin', 'super_admin', 'admin', 'manager', 'kepala_toko', 'gudang']);
    }

    public function update(AuthUser $authUser, StockQuarantineMovement $movement): bool
    {
        return $this->create($authUser);
    }

    public function delete(AuthUser $authUser, StockQuarantineMovement $movement): bool
    {
        return $authUser->hasRole(['superadmin', 'super_admin', 'admin']);
    }
}
