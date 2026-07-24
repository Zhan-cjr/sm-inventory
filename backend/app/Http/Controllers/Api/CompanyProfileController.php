<?php

namespace App\Http\Controllers\Api;

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

    public function articles(Request $request)
    {
        $query = \App\Models\CPArticle::where('is_published', true);
        
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $articles = $query->orderBy('published_at', 'desc')->get();
        return response()->json($articles);
    }

    public function testimonials()
    {
        $testimonials = \App\Models\CPTestimonial::where('is_published', true)
                            ->orderBy('created_at', 'desc')
                            ->get();
        return response()->json($testimonials);
    }

    public function memberTiers()
    {
        $tiers = \App\Models\MemberTier::orderBy('min_points', 'asc')->get();
        return response()->json($tiers);
    }

    public function storePartnership(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'category' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $partnership = \App\Models\CPPartnership::create($validated);

        return response()->json(['message' => 'Partnership request submitted successfully', 'data' => $partnership], 201);
    }
}
