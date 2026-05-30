<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MemberTier;
use Illuminate\Auth\Access\HandlesAuthorization;

class MemberTierPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MemberTier');
    }

    public function view(AuthUser $authUser, MemberTier $memberTier): bool
    {
        return $authUser->can('View:MemberTier');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MemberTier');
    }

    public function update(AuthUser $authUser, MemberTier $memberTier): bool
    {
        return $authUser->can('Update:MemberTier');
    }

    public function delete(AuthUser $authUser, MemberTier $memberTier): bool
    {
        return $authUser->can('Delete:MemberTier');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MemberTier');
    }

    public function restore(AuthUser $authUser, MemberTier $memberTier): bool
    {
        return $authUser->can('Restore:MemberTier');
    }

    public function forceDelete(AuthUser $authUser, MemberTier $memberTier): bool
    {
        return $authUser->can('ForceDelete:MemberTier');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MemberTier');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MemberTier');
    }

    public function replicate(AuthUser $authUser, MemberTier $memberTier): bool
    {
        return $authUser->can('Replicate:MemberTier');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MemberTier');
    }

}