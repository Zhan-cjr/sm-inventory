<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\RewardRedemption;
use Illuminate\Auth\Access\HandlesAuthorization;

class RewardRedemptionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RewardRedemption');
    }

    public function view(AuthUser $authUser, RewardRedemption $rewardRedemption): bool
    {
        return $authUser->can('View:RewardRedemption');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RewardRedemption');
    }

    public function update(AuthUser $authUser, RewardRedemption $rewardRedemption): bool
    {
        return $authUser->can('Update:RewardRedemption');
    }

    public function delete(AuthUser $authUser, RewardRedemption $rewardRedemption): bool
    {
        return $authUser->can('Delete:RewardRedemption');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RewardRedemption');
    }

    public function restore(AuthUser $authUser, RewardRedemption $rewardRedemption): bool
    {
        return $authUser->can('Restore:RewardRedemption');
    }

    public function forceDelete(AuthUser $authUser, RewardRedemption $rewardRedemption): bool
    {
        return $authUser->can('ForceDelete:RewardRedemption');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RewardRedemption');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RewardRedemption');
    }

    public function replicate(AuthUser $authUser, RewardRedemption $rewardRedemption): bool
    {
        return $authUser->can('Replicate:RewardRedemption');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RewardRedemption');
    }

}