<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\InventoryLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class InventoryLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:InventoryLog');
    }

    public function view(AuthUser $authUser, InventoryLog $inventoryLog): bool
    {
        return $authUser->can('View:InventoryLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:InventoryLog');
    }

    public function update(AuthUser $authUser, InventoryLog $inventoryLog): bool
    {
        return $authUser->can('Update:InventoryLog');
    }

    public function delete(AuthUser $authUser, InventoryLog $inventoryLog): bool
    {
        return $authUser->can('Delete:InventoryLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:InventoryLog');
    }

    public function restore(AuthUser $authUser, InventoryLog $inventoryLog): bool
    {
        return $authUser->can('Restore:InventoryLog');
    }

    public function forceDelete(AuthUser $authUser, InventoryLog $inventoryLog): bool
    {
        return $authUser->can('ForceDelete:InventoryLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:InventoryLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:InventoryLog');
    }

    public function replicate(AuthUser $authUser, InventoryLog $inventoryLog): bool
    {
        return $authUser->can('Replicate:InventoryLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:InventoryLog');
    }

}