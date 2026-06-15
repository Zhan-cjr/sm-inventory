<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PurchasePayment;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchasePaymentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PurchasePayment');
    }

    public function view(AuthUser $authUser, PurchasePayment $purchasePayment): bool
    {
        return $authUser->can('View:PurchasePayment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PurchasePayment');
    }

    public function update(AuthUser $authUser, PurchasePayment $purchasePayment): bool
    {
        return $authUser->can('Update:PurchasePayment');
    }

    public function delete(AuthUser $authUser, PurchasePayment $purchasePayment): bool
    {
        return $authUser->can('Delete:PurchasePayment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PurchasePayment');
    }

    public function restore(AuthUser $authUser, PurchasePayment $purchasePayment): bool
    {
        return $authUser->can('Restore:PurchasePayment');
    }

    public function forceDelete(AuthUser $authUser, PurchasePayment $purchasePayment): bool
    {
        return $authUser->can('ForceDelete:PurchasePayment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PurchasePayment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PurchasePayment');
    }

    public function replicate(AuthUser $authUser, PurchasePayment $purchasePayment): bool
    {
        return $authUser->can('Replicate:PurchasePayment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PurchasePayment');
    }

}