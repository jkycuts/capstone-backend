<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TreeGrowth;
use App\Models\Plantation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CarbonSequestrationController extends Controller
{
    // Fetch plantations for the authenticated user's company
    public function index()
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $companyId = auth()->user()->company_id;
        $plantation = Plantation::where('company_id', $companyId)->get();

        return response()->json($plantation);
    }

    // Show create view for tree growth data
    public function create()
    {
        $plantation = Plantation::all(); // Get all plantations
        return view('tree-growth.create', compact('plantation'));
    }

    // Store plantation data
    public function storePlantation(Request $request)
    {
        $validated = $request->validate([
            'area_planted'      => 'required|numeric',
            'seedlings_planted' => 'required|integer',
            'plantation_age'    => 'required|integer',
            'date_recorded'    => 'nullable|date',
        ]);

        if (!auth()->check()) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $userId = auth()->id();
        $companyId = auth()->user()->company_id;

        $plantation = Plantation::create([
            'area_planted'      => $request->area_planted,
            'seedlings_planted' => $request->seedlings_planted,
            'plantation_age'    => $request->plantation_age,
            'date_recorded'     => $request->date_recorded,
            'user_id'           => $userId,
            'company_id'        => $companyId,
        ]);

        return response()->json($plantation, 201);
    }


    // GET /api/tree-growth/{id}
    public function show($id)
    {
        $tree = TreeGrowth::find($id);

        if (!$tree) {
            return response()->json(['message' => 'Tree not found'], 404);
        }

        return response()->json($tree);
    }



    // Store tree growth data (updated to use selected plantation_id)
    public function storeTreeGrowth(Request $request)
    {
        $validated = $request->validate([
            'species'           => 'required|string',
            'dbh'               => 'required|numeric',
            'height'            => 'required|numeric',
            'geotag_photos'     => 'image|mimes:jpeg,png,jpg|max:5120',
            'longitude'         => 'required|numeric',
            'latitude'          => 'required|numeric',
            'plantation_id'     => 'required|exists:plantation,id',
        ], [
            'dbh.required'    => 'Please provide the DBH (diameter at breast height).',
            'height.required' => 'Please provide the tree height.',
        ]);

        if (!auth()->check()) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $plantation = Plantation::find($request->plantation_id);

        if (!$plantation) {
            return response()->json(['error' => 'Invalid plantation selected'], 404);
        }

        $photoPath = null;
        if ($request->hasFile('geotag_photos')) {
            $photoPath = $request->file('geotag_photos')->store('geotag_photos', 'public');
        }

        $treeGrowth = TreeGrowth::create([
            'species'       => $request->species,
            'dbh'           => $request->dbh,
            'height'        => $request->height,
            'geotag_photos' => $photoPath,
            'longitude'     => $request->longitude,
            'latitude'      => $request->latitude,
            'plantation_id' => $plantation->id,
        ]);

        Log::info("Clearing cache for plantation: {$plantation->id}");

        Cache::forget("carbon_sequestration_plantation_{$plantation->id}");

        return response()->json($treeGrowth, 201);
    }

    public function updateTreeGrowth(Request $request, $id) {
        $tree = TreeGrowth::find($id);

        if (!$tree) {
            return response()->json(['message' => 'Tree not found'], 404);
        }

        $validated = $request->validate([
            'dbh' => 'required|numeric|min:0',
            'height' => 'required|numeric|min:0',
            'geotag_photos' => 'nullable|image|max:5120', // max 5MB
        ]);

        $tree->dbh = $validated['dbh'];
        $tree->height = $validated['height'];

        if ($request->hasFile('geotag_photos')) {

            // Delete old photo if needed
            if ($tree->geotag_photos && Storage::disk('public')->exists($tree->geotag_photos)) {
                Storage::disk('public')->delete($tree->geotag_photos);
            }

            $photoPath = $request->file('geotag_photos')->store('tree_photos', 'public');
            $tree->geotag_photos = $photoPath;
        }

        $tree->save();

        return response()->json(['message' => 'Tree data updated successfully', 'data' => $tree]);
    }
    

    // Calculate and cache carbon sequestration for a plantation
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
        $totalCarbonSequestrationInTons = 0;
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

            // Step 6: Convert to tons
            $CO2Sequestration_in_ton = $CO2Sequestration / 1000;

            $totalCarbonSequestration += $CO2Sequestration;
            $totalCarbonSequestrationInTons += $CO2Sequestration_in_ton;

            $treeCarbonData[] = [
                'tree_id'               => $tree->id,
                'species'               => $tree->species,
                'dbh'                   => $tree->dbh,
                'height'                => $tree->height,
                'AGB_kg'                => round($AGB, 2),
                'BGB_kg'                => round($BGB, 2),
                'total_biomass_kg'      => round($totalBiomass, 2),
                'carbon_content_kg'     => round($carbonContent, 2),
                'CO2_sequestration_kg'  => round($CO2Sequestration, 2),
                'CO2_sequestration_ton' => round($CO2Sequestration_in_ton, 2),
            ];
        }

        $result = [
            'plantation_id'                   => $plantationId,
            'number_of_trees'                 => count($trees),
            'total_carbon_sequestration_kg'   => round($totalCarbonSequestration, 2),
            'total_carbon_sequestration_ton'  => round($totalCarbonSequestrationInTons, 2),
            'tree_sequestration_details'      => $treeCarbonData,
        ];

        Cache::put($cacheKey, $result, now()->addHours(24));

        return response()->json($result);
    }
}
