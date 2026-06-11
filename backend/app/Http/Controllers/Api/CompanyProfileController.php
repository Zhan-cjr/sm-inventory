<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CPSetting;
use App\Models\CPBranch;
use App\Models\CPFacility;

class CompanyProfileController extends Controller
{
    public function settings()
    {
        $settings = CPSetting::all()->pluck('value', 'key');
        return response()->json($settings);
    }

    public function branches()
    {
        $branches = CPBranch::with('facilities:identifier')->get();
        
        // Transform facilities to match Next.js expectation (array of strings)
        $branches->transform(function ($branch) {
            $branch->facilities_list = $branch->facilities->pluck('identifier')->toArray();
            unset($branch->facilities);
            return collect($branch)->put('facilities', $branch->facilities_list)->except('facilities_list');
        });

        return response()->json($branches);
    }

    public function facilities()
    {
        $facilities = CPFacility::all();
        return response()->json($facilities);
    }
}
