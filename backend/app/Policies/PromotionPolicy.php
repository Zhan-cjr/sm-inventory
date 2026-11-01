<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Promotion;
use Illuminate\Auth\Access\HandlesAuthorization;

class PromotionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Promotion');
    }

    public function view(AuthUser $authUser, Promotion $promotion): bool
    {
        if (!$authUser->can('View:Promotion')) {
            return false;
        }

        // Jika user adalah User Cabang (memiliki branch_id)
        if ($authUser->branch_id !== null) {
            $branchCount = $promotion->branches()->count();
            // Promo all-branches (0 cabang terdaftar) -> boleh dilihat
            // Promo dengan target cabang -> harus mencakup cabang user
            if ($branchCount > 0 && !$promotion->branches()->where('branches.id', $authUser->branch_id)->exists()) {
                return false;
            }
        }

        return true;
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Promotion');
    }

    public function update(AuthUser $authUser, Promotion $promotion): bool
    {
        if (!$authUser->can('Update:Promotion')) {
            return false;
        }

        // Jika user adalah User Cabang (memiliki branch_id)
        if ($authUser->branch_id !== null) {
            $branchCount = $promotion->branches()->count();
            // 1. Promo Nasional (berlaku di semua cabang / 0 pivot) -> Cabang DILARANG edit
            if ($branchCount === 0) {
                return false;
            }
            // 2. Promo bukan milik cabang ini -> Cabang DILARANG edit
            if (!$promotion->branches()->where('branches.id', $authUser->branch_id)->exists()) {
                return false;
            }
            // 3. Promo multi-cabang (dibuat pusat untuk beberapa cabang pilihan) -> Cabang DILARANG edit
            if ($branchCount > 1) {
                return false;
            }
        }

        return true;
    }

    public function delete(AuthUser $authUser, Promotion $promotion): bool
    {
        if (!$authUser->can('Delete:Promotion')) {
            return false;
        }

        // Jika user adalah User Cabang (memiliki branch_id)
        if ($authUser->branch_id !== null) {
            $branchCount = $promotion->branches()->count();
            // 1. Promo Nasional -> Cabang DILARANG hapus
            if ($branchCount === 0) {
                return false;
            }
            // 2. Promo bukan milik cabang ini -> Cabang DILARANG hapus
            if (!$promotion->branches()->where('branches.id', $authUser->branch_id)->exists()) {
                return false;
            }
            // 3. Promo multi-cabang -> Cabang DILARANG hapus
            if ($branchCount > 1) {
                return false;
            }
        }

        return true;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Promotion');
    }

    public function restore(AuthUser $authUser, Promotion $promotion): bool
    {
        return $authUser->can('Restore:Promotion');
    }

    public function forceDelete(AuthUser $authUser, Promotion $promotion): bool
    {
        return $authUser->can('ForceDelete:Promotion');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Promotion');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Promotion');
    }

    public function replicate(AuthUser $authUser, Promotion $promotion): bool
    {
        return $authUser->can('Replicate:Promotion');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Promotion');
    }

}