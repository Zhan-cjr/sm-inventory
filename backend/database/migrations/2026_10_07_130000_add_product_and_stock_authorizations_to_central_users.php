<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $users = User::whereNull('branch_id')->get();
        foreach ($users as $user) {
            $auths = $user->custom_authorizations ?: [];
            if (is_string($auths)) {
                $auths = json_decode($auths, true) ?: [];
            }
            if (!in_array('EDIT_PRODUCT_MASTER', $auths)) {
                $auths[] = 'EDIT_PRODUCT_MASTER';
            }
            if (!in_array('EDIT_BRANCH_STOCK', $auths)) {
                $auths[] = 'EDIT_BRANCH_STOCK';
            }
            $user->custom_authorizations = array_values(array_unique($auths));
            $user->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $users = User::whereNull('branch_id')->get();
        foreach ($users as $user) {
            $auths = $user->custom_authorizations ?: [];
            if (is_string($auths)) {
                $auths = json_decode($auths, true) ?: [];
            }
            $auths = array_diff($auths, ['EDIT_PRODUCT_MASTER', 'EDIT_BRANCH_STOCK']);
            $user->custom_authorizations = array_values($auths);
            $user->save();
        }
    }
};
