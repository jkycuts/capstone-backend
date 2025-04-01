<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Source;

class SourceController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();

        // Check if user has an associated company
        if (!$user->company_id) {
            return response()->json(['message' => 'No company associated with this user'], 403);
        }

        // Validate request (excluding companyID since it's auto-assigned)
        $validatedData = $request->validate([
            'name'          => 'required|string',
            'fuel_type'     => 'required|string|in:diesel,gasoline',
            
        ]);

        // Assign user's companyID automatically
        $validatedData['company_id'] = $user->company_id;

        // Create the Source
        $source = Source::create($validatedData);

        return response()->json([
            'message' => 'Source created successfully',
            'source'  => $source
        ], 201);
    }

    public function calculateEmissions($id, $year)
    {
        $source = Source::findOrFail($id);
        $emissions = $source->calculateYearlyEmissions($year);

        return response()->json([
            'source'    => $source->name,
            'fuel_type' => $source->fuel_type,
            'year'      => $year,
            'emissions' => $emissions
        ]);
    }
}
