<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TreeGrowth;
use App\Models\Plantation;
use Illuminate\Support\Facades\Cache;

class CarbonSequestrationController extends Controller
{
    public function storePlantation(Request $request)
    {
        // Validate the input
        $validated = $request->validate([
            'area_planted'      => 'required|numeric',
            'seedlings_planted' => 'required|integer',
            'plantation_age'    => 'required|date',
            'geotag_photos'     => 'required|file',
        ]);
    
        // Ensure the user is authenticated
        if (!auth()->check()) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
    
        $userId = auth()->id();
        $companyId = auth()->user()->company_id;
    
        // Handle photo upload
        $photoPath = $request->file('geotag_photos')->store('geotag_photos', 'public');
    
        // Create the plantation
        $plantation = Plantation::create([
            'area_planted'      => $request->area_planted,
            'seedlings_planted' => $request->seedlings_planted,
            'plantation_age'    => $request->plantation_age,
            'geotag_photos'     => $photoPath,
            'user_id'           => $userId,     // ✅ include user_id
            'company_id'        => $companyId,
        ]);
    
        return response()->json($plantation, 201);
    }
    

    public function storeTreeGrowth(Request $request)
    {
        $request->validate([
            'dbh'        => 'required|numeric',
            'height'     => 'required|numeric',
            'longitude' => 'required|numeric',
            'latitude'   => 'required|numeric',
        ]);

        if (auth()->check()) {
            $companyId = auth()->user()->company_id;
        } else {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $plantation = Plantation::where('company_id', $companyId)->first();

        if (!$plantation) {
            return response()->json(['error' => 'No plantation found for the user'], 404);
        }

        $treeGrowth = TreeGrowth::create([
            'dbh'           => $request->dbh,
            'height'        => $request->height,
            'longitude'    => $request->longitude,
            'latitude'      => $request->latitude,
            'plantation_id' => $plantation->id,
        ]);

        return response()->json($treeGrowth, 201);
    }

    public function calculateCarbonSequestration($plantationId)
    {
        $cacheKey = "carbon_sequestration_plantation_{$plantationId}";

        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }

        $plantation = Plantation::find($plantationId);
        if (!$plantation) {
            return response()->json(['error' => 'Plantation not found'], 404);
        }

        $trees = TreeGrowth::where('plantation_id', $plantationId)->get();
        if ($trees->isEmpty()) {
            return response()->json(['error' => 'No trees found for this plantation'], 404);
        }

        $totalCarbonSequestration = 0;
        $treeCarbonData = [];

        foreach ($trees as $tree) {
            // Step 1: Above Ground Biomass (AGB)
            $AGB = 34.4703 - (8.0671 * $tree->dbh) + (0.6589 * pow($tree->dbh, 2));

            // Step 2: Below Ground Biomass (15% of AGB)
            $BGB = $AGB * 0.15;

            // Step 3: Total Biomass
            $totalBiomass = $AGB + $BGB;

            // Step 4: Carbon Content (50% of Biomass)
            $carbonContent = $totalBiomass * 0.5;

            // Step 5: CO₂ Sequestration (Carbon * 3.67)
            $CO2Sequestration = $carbonContent * 3.67;

            $totalCarbonSequestration += $CO2Sequestration;

            $treeCarbonData[] = [
                'tree_id'                => $tree->id,
                'dbh'                    => $tree->dbh,
                'height'                 => $tree->height,
                'AGB_kg'                 => round($AGB, 2),
                'BGB_kg'                 => round($BGB, 2),
                'total_biomass_kg'       => round($totalBiomass, 2),
                'carbon_content_kg'      => round($carbonContent, 2),
                'CO2_sequestration_kg'   => round($CO2Sequestration, 2),
            ];
        }

        $result = [
            'plantation_id'                  => $plantationId,
            'number_of_trees'                => count($trees),
            'total_carbon_sequestration_kg'  => round($totalCarbonSequestration, 2),
            'tree_sequestration_details'     => $treeCarbonData,
        ];

        Cache::put($cacheKey, $result, now()->addHours(24)); // Cache for 24 hrs

        return response()->json($result);
    }
}
