<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CPTestimonial;
use Illuminate\Auth\Access\HandlesAuthorization;

class CPTestimonialPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CPTestimonial');
    }

    public function view(AuthUser $authUser, CPTestimonial $cPTestimonial): bool
    {
        return $authUser->can('View:CPTestimonial');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CPTestimonial');
    }

    public function update(AuthUser $authUser, CPTestimonial $cPTestimonial): bool
    {
        return $authUser->can('Update:CPTestimonial');
    }

    public function delete(AuthUser $authUser, CPTestimonial $cPTestimonial): bool
    {
        return $authUser->can('Delete:CPTestimonial');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CPTestimonial');
    }

    public function restore(AuthUser $authUser, CPTestimonial $cPTestimonial): bool
    {
        return $authUser->can('Restore:CPTestimonial');
    }

    public function forceDelete(AuthUser $authUser, CPTestimonial $cPTestimonial): bool
    {
        return $authUser->can('ForceDelete:CPTestimonial');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CPTestimonial');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CPTestimonial');
    }

    public function replicate(AuthUser $authUser, CPTestimonial $cPTestimonial): bool
    {
        return $authUser->can('Replicate:CPTestimonial');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CPTestimonial');
    }

}