<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AdjustmentReason;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdjustmentReasonPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AdjustmentReason');
    }

    public function view(AuthUser $authUser, AdjustmentReason $adjustmentReason): bool
    {
        return $authUser->can('View:AdjustmentReason');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AdjustmentReason');
    }

    public function update(AuthUser $authUser, AdjustmentReason $adjustmentReason): bool
    {
        return $authUser->can('Update:AdjustmentReason');
    }

    public function delete(AuthUser $authUser, AdjustmentReason $adjustmentReason): bool
    {
        return $authUser->can('Delete:AdjustmentReason');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AdjustmentReason');
    }

    public function restore(AuthUser $authUser, AdjustmentReason $adjustmentReason): bool
    {
        return $authUser->can('Restore:AdjustmentReason');
    }

    public function forceDelete(AuthUser $authUser, AdjustmentReason $adjustmentReason): bool
    {
        return $authUser->can('ForceDelete:AdjustmentReason');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AdjustmentReason');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AdjustmentReason');
    }

    public function replicate(AuthUser $authUser, AdjustmentReason $adjustmentReason): bool
    {
        return $authUser->can('Replicate:AdjustmentReason');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AdjustmentReason');
    }

}