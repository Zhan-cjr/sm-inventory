<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasBranchScope
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        // Jika user memiliki branch_id (bukan super admin/global), batasi query ke branch tersebut
        if ($user && $user->branch_id) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }
}
