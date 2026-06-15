<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CPArticle;
use Illuminate\Auth\Access\HandlesAuthorization;

class CPArticlePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CPArticle');
    }

    public function view(AuthUser $authUser, CPArticle $cPArticle): bool
    {
        return $authUser->can('View:CPArticle');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CPArticle');
    }

    public function update(AuthUser $authUser, CPArticle $cPArticle): bool
    {
        return $authUser->can('Update:CPArticle');
    }

    public function delete(AuthUser $authUser, CPArticle $cPArticle): bool
    {
        return $authUser->can('Delete:CPArticle');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CPArticle');
    }

    public function restore(AuthUser $authUser, CPArticle $cPArticle): bool
    {
        return $authUser->can('Restore:CPArticle');
    }

    public function forceDelete(AuthUser $authUser, CPArticle $cPArticle): bool
    {
        return $authUser->can('ForceDelete:CPArticle');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CPArticle');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CPArticle');
    }

    public function replicate(AuthUser $authUser, CPArticle $cPArticle): bool
    {
        return $authUser->can('Replicate:CPArticle');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CPArticle');
    }

}