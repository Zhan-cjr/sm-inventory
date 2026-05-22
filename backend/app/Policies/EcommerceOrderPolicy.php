<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EcommerceOrder;
use Illuminate\Auth\Access\HandlesAuthorization;

class EcommerceOrderPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EcommerceOrder');
    }

    public function view(AuthUser $authUser, EcommerceOrder $ecommerceOrder): bool
    {
        return $authUser->can('View:EcommerceOrder');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EcommerceOrder');
    }

    public function update(AuthUser $authUser, EcommerceOrder $ecommerceOrder): bool
    {
        return $authUser->can('Update:EcommerceOrder');
    }

    public function delete(AuthUser $authUser, EcommerceOrder $ecommerceOrder): bool
    {
        return $authUser->can('Delete:EcommerceOrder');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:EcommerceOrder');
    }

    public function restore(AuthUser $authUser, EcommerceOrder $ecommerceOrder): bool
    {
        return $authUser->can('Restore:EcommerceOrder');
    }

    public function forceDelete(AuthUser $authUser, EcommerceOrder $ecommerceOrder): bool
    {
        return $authUser->can('ForceDelete:EcommerceOrder');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EcommerceOrder');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EcommerceOrder');
    }

    public function replicate(AuthUser $authUser, EcommerceOrder $ecommerceOrder): bool
    {
        return $authUser->can('Replicate:EcommerceOrder');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EcommerceOrder');
    }

}