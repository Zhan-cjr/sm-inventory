<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CPSetting;
use Illuminate\Auth\Access\HandlesAuthorization;

class CPSettingPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CPSetting');
    }

    public function view(AuthUser $authUser, CPSetting $cPSetting): bool
    {
        return $authUser->can('View:CPSetting');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CPSetting');
    }

    public function update(AuthUser $authUser, CPSetting $cPSetting): bool
    {
        return $authUser->can('Update:CPSetting');
    }

    public function delete(AuthUser $authUser, CPSetting $cPSetting): bool
    {
        return $authUser->can('Delete:CPSetting');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CPSetting');
    }

    public function restore(AuthUser $authUser, CPSetting $cPSetting): bool
    {
        return $authUser->can('Restore:CPSetting');
    }

    public function forceDelete(AuthUser $authUser, CPSetting $cPSetting): bool
    {
        return $authUser->can('ForceDelete:CPSetting');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CPSetting');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CPSetting');
    }

    public function replicate(AuthUser $authUser, CPSetting $cPSetting): bool
    {
        return $authUser->can('Replicate:CPSetting');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CPSetting');
    }

}