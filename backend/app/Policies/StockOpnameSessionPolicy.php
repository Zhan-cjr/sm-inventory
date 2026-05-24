<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\StockOpnameSession;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockOpnameSessionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StockOpnameSession');
    }

    public function view(AuthUser $authUser, StockOpnameSession $stockOpnameSession): bool
    {
        return $authUser->can('View:StockOpnameSession');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StockOpnameSession');
    }

    public function update(AuthUser $authUser, StockOpnameSession $stockOpnameSession): bool
    {
        return $authUser->can('Update:StockOpnameSession');
    }

    public function delete(AuthUser $authUser, StockOpnameSession $stockOpnameSession): bool
    {
        return $authUser->can('Delete:StockOpnameSession');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StockOpnameSession');
    }

    public function restore(AuthUser $authUser, StockOpnameSession $stockOpnameSession): bool
    {
        return $authUser->can('Restore:StockOpnameSession');
    }

    public function forceDelete(AuthUser $authUser, StockOpnameSession $stockOpnameSession): bool
    {
        return $authUser->can('ForceDelete:StockOpnameSession');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StockOpnameSession');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StockOpnameSession');
    }

    public function replicate(AuthUser $authUser, StockOpnameSession $stockOpnameSession): bool
    {
        return $authUser->can('Replicate:StockOpnameSession');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StockOpnameSession');
    }

}