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
        return $authUser->can('ViewAny:ProductListing');
    }

    public function view(AuthUser $authUser, ProductListing $listing): bool
    {
        return $authUser->can('View:ProductListing');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProductListing');
    }

    public function update(AuthUser $authUser, ProductListing $listing): bool
    {
        return $authUser->can('Update:ProductListing');
    }

    public function delete(AuthUser $authUser, ProductListing $listing): bool
    {
        return $authUser->can('Delete:ProductListing');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ProductListing');
    }
}
