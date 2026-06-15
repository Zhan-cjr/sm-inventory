<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CPPartnership;
use Illuminate\Auth\Access\HandlesAuthorization;

class CPPartnershipPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CPPartnership');
    }

    public function view(AuthUser $authUser, CPPartnership $cPPartnership): bool
    {
        return $authUser->can('View:CPPartnership');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CPPartnership');
    }

    public function update(AuthUser $authUser, CPPartnership $cPPartnership): bool
    {
        return $authUser->can('Update:CPPartnership');
    }

    public function delete(AuthUser $authUser, CPPartnership $cPPartnership): bool
    {
        return $authUser->can('Delete:CPPartnership');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CPPartnership');
    }

    public function restore(AuthUser $authUser, CPPartnership $cPPartnership): bool
    {
        return $authUser->can('Restore:CPPartnership');
    }

    public function forceDelete(AuthUser $authUser, CPPartnership $cPPartnership): bool
    {
        return $authUser->can('ForceDelete:CPPartnership');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CPPartnership');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CPPartnership');
    }

    public function replicate(AuthUser $authUser, CPPartnership $cPPartnership): bool
    {
        return $authUser->can('Replicate:CPPartnership');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CPPartnership');
    }

}