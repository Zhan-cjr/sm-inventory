<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\StockOpnameRack;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockOpnameRackPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StockOpnameRack');
    }

    public function view(AuthUser $authUser, StockOpnameRack $stockOpnameRack): bool
    {
        return $authUser->can('View:StockOpnameRack');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StockOpnameRack');
    }

    public function update(AuthUser $authUser, StockOpnameRack $stockOpnameRack): bool
    {
        return $authUser->can('Update:StockOpnameRack');
    }

    public function delete(AuthUser $authUser, StockOpnameRack $stockOpnameRack): bool
    {
        return $authUser->can('Delete:StockOpnameRack');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StockOpnameRack');
    }

    public function restore(AuthUser $authUser, StockOpnameRack $stockOpnameRack): bool
    {
        return $authUser->can('Restore:StockOpnameRack');
    }

    public function forceDelete(AuthUser $authUser, StockOpnameRack $stockOpnameRack): bool
    {
        return $authUser->can('ForceDelete:StockOpnameRack');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StockOpnameRack');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StockOpnameRack');
    }

    public function replicate(AuthUser $authUser, StockOpnameRack $stockOpnameRack): bool
    {
        return $authUser->can('Replicate:StockOpnameRack');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StockOpnameRack');
    }

}