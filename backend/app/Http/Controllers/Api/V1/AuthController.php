<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('username', $request->username)
                    ->orWhere('email', $request->username)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Username atau Password salah.'
            ], 401);
        }

        $spatieRoles = array_map('strtolower', $user->roles->pluck('name')->toArray());
        $dbRole = $user->role;
        $role = $dbRole ?: 'CASHIER';

        // Override role if user has Spatie admin roles
        if (in_array('superadmin', $spatieRoles) || in_array('super_admin', $spatieRoles) || strtolower($dbRole) === 'superadmin' || strtolower($dbRole) === 'super_admin') {
            $role = 'SUPER_ADMIN';
        } elseif (in_array('admin', $spatieRoles) || strtolower($dbRole) === 'admin') {
            $role = 'ADMIN';
        }
        
        $branchId = $user->branch_id;
        // Hanya fallback ke mock branch jika role nya bukan tingkatan admin/manajer (untuk kompatibilitas testing)
        if (!$branchId && !in_array(strtoupper($role), ['ADMIN', 'SUPER_ADMIN', 'MANAGER'])) {
            $branchId = '00000000-0000-0000-0000-000000000002';
        }

        $token = $user->createToken('pos-terminal')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'branch_id' => $branchId,
                'branch_name' => $user->branch?->name ?? ($branchId ? 'Cabang Lain' : null),
                'branch_code' => $user->branch?->code,
                'organization_id' => $user->organization_id,
                'organization_name' => $user->organization?->name,
                'point_conversion_rate' => $user->organization?->point_conversion_rate ?? 1000,
                'point_redemption_value' => $user->organization?->point_redemption_value ?? 1,
                'minimum_points_to_redeem' => $user->organization?->minimum_points_to_redeem ?? 100,
                'point_redemption_enabled' => (bool) ($user->organization?->point_redemption_enabled ?? true),
                'allow_minus_stock' => (bool) ($user->organization?->allow_minus_stock ?? true),
                'scale_barcode_enabled' => (bool) ($user->organization?->scale_barcode_enabled ?? false),
                'scale_barcode_prefix' => $user->organization?->scale_barcode_prefix ?? '20',
                'scale_barcode_item_code_length' => $user->organization?->scale_barcode_item_code_length ?? 5,
                'scale_barcode_weight_length' => $user->organization?->scale_barcode_weight_length ?? 5,
                'scale_barcode_weight_decimal_places' => $user->organization?->scale_barcode_weight_decimal_places ?? 3,
                'pos_authorizations' => $user->pos_authorizations,
                'custom_authorizations' => $user->custom_authorizations,
                'can_access_pos' => $user->can('access_pos') || $user->can('AccessPos'),
            ],
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        
        $spatieRoles = array_map('strtolower', $user->roles->pluck('name')->toArray());
        $dbRole = $user->role;
        $role = $dbRole ?: 'CASHIER';

        // Override role if user has Spatie admin roles
        if (in_array('superadmin', $spatieRoles) || in_array('super_admin', $spatieRoles) || strtolower($dbRole) === 'superadmin' || strtolower($dbRole) === 'super_admin') {
            $role = 'SUPER_ADMIN';
        } elseif (in_array('admin', $spatieRoles) || strtolower($dbRole) === 'admin') {
            $role = 'ADMIN';
        }
        
        $branchId = $user->branch_id;
        if (!$branchId && !in_array(strtoupper($role), ['ADMIN', 'SUPER_ADMIN', 'MANAGER'])) {
            $branchId = '00000000-0000-0000-0000-000000000002';
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'branch_id' => $branchId,
                'branch_name' => $user->branch?->name ?? ($branchId ? 'Cabang Lain' : null),
                'branch_code' => $user->branch?->code,
                'organization_id' => $user->organization_id,
                'organization_name' => $user->organization?->name,
                'point_conversion_rate' => $user->organization?->point_conversion_rate ?? 1000,
                'point_redemption_value' => $user->organization?->point_redemption_value ?? 1,
                'minimum_points_to_redeem' => $user->organization?->minimum_points_to_redeem ?? 100,
                'point_redemption_enabled' => (bool) ($user->organization?->point_redemption_enabled ?? true),
                'allow_minus_stock' => (bool) ($user->organization?->allow_minus_stock ?? true),
                'scale_barcode_enabled' => (bool) ($user->organization?->scale_barcode_enabled ?? false),
                'scale_barcode_prefix' => $user->organization?->scale_barcode_prefix ?? '20',
                'scale_barcode_item_code_length' => $user->organization?->scale_barcode_item_code_length ?? 5,
                'scale_barcode_weight_length' => $user->organization?->scale_barcode_weight_length ?? 5,
                'scale_barcode_weight_decimal_places' => $user->organization?->scale_barcode_weight_decimal_places ?? 3,
                'pos_authorizations' => $user->pos_authorizations,
                'custom_authorizations' => $user->custom_authorizations,
                'can_access_pos' => $user->can('access_pos') || $user->can('AccessPos'),
            ]
        ]);
    }
}
