<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CPBranch;
use Illuminate\Auth\Access\HandlesAuthorization;

class CPBranchPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CPBranch');
    }

    public function view(AuthUser $authUser, CPBranch $cPBranch): bool
    {
        return $authUser->can('View:CPBranch');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CPBranch');
    }

    public function update(AuthUser $authUser, CPBranch $cPBranch): bool
    {
        return $authUser->can('Update:CPBranch');
    }

    public function delete(AuthUser $authUser, CPBranch $cPBranch): bool
    {
        return $authUser->can('Delete:CPBranch');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CPBranch');
    }

    public function restore(AuthUser $authUser, CPBranch $cPBranch): bool
    {
        return $authUser->can('Restore:CPBranch');
    }

    public function forceDelete(AuthUser $authUser, CPBranch $cPBranch): bool
    {
        return $authUser->can('ForceDelete:CPBranch');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CPBranch');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CPBranch');
    }

    public function replicate(AuthUser $authUser, CPBranch $cPBranch): bool
    {
        return $authUser->can('Replicate:CPBranch');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CPBranch');
    }

}