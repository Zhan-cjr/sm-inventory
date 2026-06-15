<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PosAuthController extends Controller
{
    public function getAuthorizers(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        
        $authorizers = User::whereNotNull('pos_authorizations')
            ->where('pos_authorizations', '!=', '[]')
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id'); // Admin might have no branch
            })
            ->select('id', 'name', 'username', 'email', 'role', 'pos_authorizations')
            ->get();
            
        return response()->json($authorizers);
    }

    public function authorizeAction(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required',
            'password' => 'required|string',
            'action' => 'required|string',
        ]);

        $user = User::where('username', $validated['username'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'authorized' => false,
                'message' => 'Username atau Password salah.'
            ], 401);
        }

        // Check if user is an admin OR has the specific pos_authorizations
        $authorizations = $user->pos_authorizations ?? [];
        
        $isAuthorized = false;
        
        if ($user->role === 'ADMIN' || in_array($validated['action'], $authorizations)) {
            $isAuthorized = true;
        }

        if (!$isAuthorized) {
            return response()->json([
                'authorized' => false,
                'message' => 'User ini tidak memiliki izin untuk aksi: ' . $validated['action']
            ], 403);
        }

        return response()->json([
            'authorized' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
            'message' => 'Otorisasi berhasil.'
        ]);
    }
}
