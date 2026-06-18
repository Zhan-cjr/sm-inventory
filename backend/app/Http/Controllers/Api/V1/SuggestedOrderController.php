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
        $branchId = $request->input('branch_id') ?: $request->user()->branch_id;

        // If still no branch ID, maybe pick the first branch for this user's organization
        if (!$branchId) {
            $firstBranch = \App\Models\Branch::where('organization_id', $request->user()->organization_id)->first();
            if ($firstBranch) {
                $branchId = $firstBranch->id;
            } else {
                return response()->json(['error' => 'No branch available for this organization'], 400);
            }
        }

        $suggestions = $this->service->calculateForBranch($branchId, $request->all());

        return response()->json([
            'success' => true,
            'data' => $suggestions
        ]);
    }
}
