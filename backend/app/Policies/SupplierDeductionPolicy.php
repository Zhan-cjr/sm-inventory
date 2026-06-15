<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SupplierDeduction;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierDeductionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SupplierDeduction');
    }

    public function view(AuthUser $authUser, SupplierDeduction $supplierDeduction): bool
    {
        return $authUser->can('View:SupplierDeduction');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SupplierDeduction');
    }

    public function update(AuthUser $authUser, SupplierDeduction $supplierDeduction): bool
    {
        return $authUser->can('Update:SupplierDeduction');
    }

    public function delete(AuthUser $authUser, SupplierDeduction $supplierDeduction): bool
    {
        return $authUser->can('Delete:SupplierDeduction');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SupplierDeduction');
    }

    public function restore(AuthUser $authUser, SupplierDeduction $supplierDeduction): bool
    {
        return $authUser->can('Restore:SupplierDeduction');
    }

    public function forceDelete(AuthUser $authUser, SupplierDeduction $supplierDeduction): bool
    {
        return $authUser->can('ForceDelete:SupplierDeduction');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SupplierDeduction');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SupplierDeduction');
    }

    public function replicate(AuthUser $authUser, SupplierDeduction $supplierDeduction): bool
    {
        return $authUser->can('Replicate:SupplierDeduction');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SupplierDeduction');
    }

}