<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PosSetting;
use Illuminate\Auth\Access\HandlesAuthorization;

class PosSettingPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PosSetting');
    }

    public function view(AuthUser $authUser, PosSetting $posSetting): bool
    {
        return $authUser->can('View:PosSetting');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PosSetting');
    }

    public function update(AuthUser $authUser, PosSetting $posSetting): bool
    {
        return $authUser->can('Update:PosSetting');
    }

    public function delete(AuthUser $authUser, PosSetting $posSetting): bool
    {
        return $authUser->can('Delete:PosSetting');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PosSetting');
    }

    public function restore(AuthUser $authUser, PosSetting $posSetting): bool
    {
        return $authUser->can('Restore:PosSetting');
    }

    public function forceDelete(AuthUser $authUser, PosSetting $posSetting): bool
    {
        return $authUser->can('ForceDelete:PosSetting');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PosSetting');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PosSetting');
    }

    public function replicate(AuthUser $authUser, PosSetting $posSetting): bool
    {
        return $authUser->can('Replicate:PosSetting');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PosSetting');
    }

}