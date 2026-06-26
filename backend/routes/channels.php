<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('branch.{branchId}.stock', function ($user, $branchId) {
    // Only allow users belonging to the branch or admins
    return (string) $user->branch_id === (string) $branchId || in_array($user->role, ['SUPERADMIN', 'ADMIN', 'Superadmin', 'Manager', 'Direktur']);
});

Broadcast::channel('branch.{branchId}.transactions', function ($user, $branchId) {
    // Only allow users belonging to the branch or admins
    return (string) $user->branch_id === (string) $branchId || in_array($user->role, ['SUPERADMIN', 'ADMIN', 'Superadmin', 'Manager', 'Direktur']);
});
