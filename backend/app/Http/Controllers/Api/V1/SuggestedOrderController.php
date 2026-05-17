<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SuggestedOrderService;

class SuggestedOrderController extends Controller
{
    protected $service;

    public function __construct(SuggestedOrderService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $branchId = $request->user()->branch_id;
        if (!$branchId) {
            return response()->json(['error' => 'User has no assigned branch'], 400);
        }

        $suggestions = $this->service->calculateForBranch($branchId, $request->all());

        return response()->json([
            'success' => true,
            'data' => $suggestions
        ]);
    }
}
