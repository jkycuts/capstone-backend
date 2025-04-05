<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TreeGrowth;
use App\Models\Plantation;
use Illuminate\Support\Facades\Cache;

class CarbonSequestrationController extends Controller
{
    // Store plantation data
    public function storePlantation(Request $request)
    {
        // Validate the input
        $validated = $request->validate([
            'area_planted'          => 'required|numeric',
            'seedlings_planted'     => 'required|integer',
            'plantation_age'        => 'required|date',
            'geotag_photos'         => 'required|json',
        ]);

        // Ensure the user is authenticated
        if (auth()->check()) {
            $userId = auth()->id();  // Get the authenticated user's ID
        } else {
            return response()->json(['error' => 'User not authenticated'], 401);  // Return an error if the user is not authenticated
        }

        // Create the plantation and automatically associate it with the authenticated user
        $plantation = Plantation::create([
            'area_planted'          => $request->area_planted,
            'seedlings_planted'     => $request->seedlings_planted,
            'plantation_age'        => $request->plantation_age,
            'geotag_photos'         => $request->geotag_photos,
            'user_id'               => $userId,  // Automatically associate with the authenticated user
        ]);

        return response()->json($plantation, 201);
    }

    // Store tree growth data
    public function storeTreeGrowth(Request $request)
    {
        // Validate input data
        $request->validate([
            'dbh'                   => 'required|numeric',
            'height'                => 'required|numeric',
        ]);

        // Ensure plantation_id is automatically assigned
        if (auth()->check()) {
            $userId = auth()->id(); // Get the authenticated user's ID
        } else {
            return response()->json(['error' => 'User not authenticated'], 401);  // Return an error if the user is not authenticated
        }

        // Find the first plantation of the authenticated user (you may change this logic based on your requirements)
        $plantation = Plantation::where('user_id', $userId)->first();

        if (!$plantation) {
            return response()->json(['error' => 'No plantation found for the user'], 404);
        }

        // Automatically assign plantation_id
        $treeGrowth = TreeGrowth::create([
            'dbh'                   => $request->dbh,
            'height'                => $request->height,
            'plantation_id'         => $plantation->id, // Automatically link the plantation
        ]);

        return response()->json($treeGrowth, 201);
    }


    

    // Calculate total carbon sequestration for a plantation
    public function calculateCarbonSequestration($plantationId)
    {
        // Check if the carbon sequestration data is cached
        $cacheKey = "carbon_sequestration_plantation_{$plantationId}";
        $cachedData = Cache::get($cacheKey);

        if ($cachedData) {
            // If cached data exists, return it
            return response()->json($cachedData);
        }

        // Fetch plantation details
        $plantation = Plantation::find($plantationId);
        if (!$plantation) {
            return response()->json(['error' => 'Plantation not found'], 404);
        }

        // Get all tree data for the plantation
        $trees = TreeGrowth::where('plantation_id', $plantationId)->get();

        $totalCarbonSequestration = 0;
        $treeCarbonData = [];

        // Loop through each tree and perform sequestration calculations
        foreach ($trees as $tree) {

            // Calculate AGB (Above Ground Biomass)
            $AGB = 34.4703 - (8.0671 * $tree->dbh) + (0.6589 * pow($tree->dbh, 2));

            // Calculate BGB (Below Ground Biomass) - 15% of AGB
            $BGB = $AGB * 0.15;

            // Calculate Total Biomass (TB)
            $totalBiomass = $AGB + $BGB;

            // Calculate Carbon Content - 50% of Total Biomass
            $carbonContent = 0.5 * $totalBiomass;

            // Calculate CO2 Sequestration - Carbon Content * 3.67
            $CO2Sequestration = $carbonContent * 3.67;

            // Add to the total sequestration for the plantation
            $totalCarbonSequestration += $CO2Sequestration;

            // Store sequestration data for each tree
            $treeCarbonData[] = [
                'tree_id'                => $tree->id,
                'AGB'                    => $AGB,
                'BGB'                    => $BGB,
                'total_biomass'          => $totalBiomass,
                'carbon_content'         => $carbonContent,
                'CO2_sequestration_kg'   => $CO2Sequestration,
            ];
        }

        // Cache the result for 24 hours
        $cachedResult = [
            'plantation_id'                 => $plantationId,
            'total_carbon_sequestration_kg' => $totalCarbonSequestration,
            'tree_sequestration_details'    => $treeCarbonData,
        ];

        Cache::put($cacheKey, $cachedResult, now()->addHours(24)); // Cache for 24 hours
// Return the response with sequestration data
return response()->json($cachedResult);
}
}