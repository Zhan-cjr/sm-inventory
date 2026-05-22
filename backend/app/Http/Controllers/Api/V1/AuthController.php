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
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email atau Password salah.'
            ], 401);
        }

        // Get first branch ID if assigned, or fallback to the mock one for testing
        $branchId = $user->branch_id ?? '00000000-0000-0000-0000-000000000002';

        $token = $user->createToken('pos-terminal')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'CASHIER',
                'branch_id' => $branchId,
                'branch_name' => $user->branch?->name ?? 'Cabang Lain',
                'branch_code' => $user->branch?->code,
                'organization_id' => $user->organization_id,
                'organization_name' => $user->organization?->name,
                'point_conversion_rate' => $user->organization?->point_conversion_rate ?? 1000,
                'allow_minus_stock' => (bool) ($user->organization?->allow_minus_stock ?? true),
            ],
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
