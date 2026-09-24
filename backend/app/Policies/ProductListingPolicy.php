<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductListing;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProductListingPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Product') || $authUser->hasRole('super_admin');
    }

    public function view(AuthUser $authUser, ProductListing $listing): bool
    {
        return $authUser->can('View:Product') || $authUser->hasRole('super_admin');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Product') || $authUser->hasRole('super_admin');
    }

    public function update(AuthUser $authUser, ProductListing $listing): bool
    {
        return $authUser->can('Update:Product') || $authUser->hasRole('super_admin');
    }

    public function delete(AuthUser $authUser, ProductListing $listing): bool
    {
        return $authUser->can('Delete:Product') || $authUser->hasRole('super_admin');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Product') || $authUser->hasRole('super_admin');
    }
}
