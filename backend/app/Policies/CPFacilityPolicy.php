<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CPFacility;
use Illuminate\Auth\Access\HandlesAuthorization;

class CPFacilityPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CPFacility');
    }

    public function view(AuthUser $authUser, CPFacility $cPFacility): bool
    {
        return $authUser->can('View:CPFacility');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CPFacility');
    }

    public function update(AuthUser $authUser, CPFacility $cPFacility): bool
    {
        return $authUser->can('Update:CPFacility');
    }

    public function delete(AuthUser $authUser, CPFacility $cPFacility): bool
    {
        return $authUser->can('Delete:CPFacility');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CPFacility');
    }

    public function restore(AuthUser $authUser, CPFacility $cPFacility): bool
    {
        return $authUser->can('Restore:CPFacility');
    }

    public function forceDelete(AuthUser $authUser, CPFacility $cPFacility): bool
    {
        return $authUser->can('ForceDelete:CPFacility');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CPFacility');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CPFacility');
    }

    public function replicate(AuthUser $authUser, CPFacility $cPFacility): bool
    {
        return $authUser->can('Replicate:CPFacility');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CPFacility');
    }

}