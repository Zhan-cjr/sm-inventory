<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PurchaseReturn;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseReturnPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PurchaseReturn');
    }

    public function view(AuthUser $authUser, PurchaseReturn $purchaseReturn): bool
    {
        return $authUser->can('View:PurchaseReturn');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PurchaseReturn');
    }

    public function update(AuthUser $authUser, PurchaseReturn $purchaseReturn): bool
    {
        return $authUser->can('Update:PurchaseReturn');
    }

    public function delete(AuthUser $authUser, PurchaseReturn $purchaseReturn): bool
    {
        return $authUser->can('Delete:PurchaseReturn');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PurchaseReturn');
    }

    public function restore(AuthUser $authUser, PurchaseReturn $purchaseReturn): bool
    {
        return $authUser->can('Restore:PurchaseReturn');
    }

    public function forceDelete(AuthUser $authUser, PurchaseReturn $purchaseReturn): bool
    {
        return $authUser->can('ForceDelete:PurchaseReturn');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PurchaseReturn');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PurchaseReturn');
    }

    public function replicate(AuthUser $authUser, PurchaseReturn $purchaseReturn): bool
    {
        return $authUser->can('Replicate:PurchaseReturn');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PurchaseReturn');
    }

}