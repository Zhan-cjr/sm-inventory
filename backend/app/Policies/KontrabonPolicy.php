<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Kontrabon;
use Illuminate\Auth\Access\HandlesAuthorization;

class KontrabonPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Kontrabon');
    }

    public function view(AuthUser $authUser, Kontrabon $kontrabon): bool
    {
        return $authUser->can('View:Kontrabon');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Kontrabon');
    }

    public function update(AuthUser $authUser, Kontrabon $kontrabon): bool
    {
        return $authUser->can('Update:Kontrabon');
    }

    public function delete(AuthUser $authUser, Kontrabon $kontrabon): bool
    {
        return $authUser->can('Delete:Kontrabon');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Kontrabon');
    }

    public function restore(AuthUser $authUser, Kontrabon $kontrabon): bool
    {
        return $authUser->can('Restore:Kontrabon');
    }

    public function forceDelete(AuthUser $authUser, Kontrabon $kontrabon): bool
    {
        return $authUser->can('ForceDelete:Kontrabon');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Kontrabon');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Kontrabon');
    }

    public function replicate(AuthUser $authUser, Kontrabon $kontrabon): bool
    {
        return $authUser->can('Replicate:Kontrabon');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Kontrabon');
    }

}