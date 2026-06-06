<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PpobTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class PpobTransactionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PpobTransaction');
    }

    public function view(AuthUser $authUser, PpobTransaction $ppobTransaction): bool
    {
        return $authUser->can('View:PpobTransaction');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PpobTransaction');
    }

    public function update(AuthUser $authUser, PpobTransaction $ppobTransaction): bool
    {
        return $authUser->can('Update:PpobTransaction');
    }

    public function delete(AuthUser $authUser, PpobTransaction $ppobTransaction): bool
    {
        return $authUser->can('Delete:PpobTransaction');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PpobTransaction');
    }

    public function restore(AuthUser $authUser, PpobTransaction $ppobTransaction): bool
    {
        return $authUser->can('Restore:PpobTransaction');
    }

    public function forceDelete(AuthUser $authUser, PpobTransaction $ppobTransaction): bool
    {
        return $authUser->can('ForceDelete:PpobTransaction');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PpobTransaction');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PpobTransaction');
    }

    public function replicate(AuthUser $authUser, PpobTransaction $ppobTransaction): bool
    {
        return $authUser->can('Replicate:PpobTransaction');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PpobTransaction');
    }

}