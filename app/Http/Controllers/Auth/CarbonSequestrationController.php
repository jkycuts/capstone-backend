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
        $plantations = Plantation::where('company_id', $companyId)->get();

        return response()->json($plantations);
    }

    // Show create view for tree growth data
    public function create()
    {
        $plantations = Plantation::all();
        return view('tree-growth.create', compact('plantations'));
    }

    // Store plantation data
    public function storePlantation(Request $request)
    {
        $validated = $request->validate([
            'area_planted'      => 'required|numeric',
            'seedlings_planted' => 'required|integer',
            'plantation_age'    => 'required|integer',
            'date_recorded'     => 'nullable|date',
        ]);

        if (!auth()->check()) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $userId = auth()->id();
        $companyId = auth()->user()->company_id;

        $plantation = Plantation::create([
            'area_planted'      => $validated['area_planted'],
            'seedlings_planted' => $validated['seedlings_planted'],
            'plantation_age'    => $validated['plantation_age'],
            'date_recorded'     => $validated['date_recorded'],
            'user_id'           => $userId,
            'company_id'        => $companyId,
        ]);

        return response()->json($plantation, 201);
    }

    // Show single tree growth data
    public function show($id)
    {
        $tree = TreeGrowth::find($id);

        if (!$tree) {
            return response()->json(['message' => 'Tree not found'], 404);
        }

        return response()->json($tree);
    }

    // Store tree growth data
    public function storeTreeGrowth(Request $request)
{
    $validated = $request->validate([
        'species'       => 'required|string',
        'dbh'           => 'required|numeric|min:0.1|max:200',
        'height'        => 'required|numeric|min:0.1|max:100',
        'geotag_photos' => 'image|mimes:jpeg,png,jpg|max:5120',
        'latitude'      => 'required|numeric|between:-90,90',
        'longitude'     => 'required|numeric|between:-180,180',
        'plantation_id' => 'required|exists:plantation,id',
        'year_recorded' => 'required|digits:4|integer|min:2000|max:' . date('Y'),
    ], [
        'dbh.required' => 'Please provide the DBH.',
        'height.required' => 'Please provide the height.',
        'geotag_photos.image' => 'The photo must be an image.',
        'geotag_photos.max' => 'The photo must not exceed 5MB.',
    ]);

    if (!auth()->check()) {
        return response()->json(['error' => 'User not authenticated'], 401);
    }

    $plantation = Plantation::find($request->plantation_id);

    if (!$plantation) {
        return response()->json(['error' => 'Invalid plantation selected'], 404);
    }

    // Only block if species, dbh, and height are ALL the same within the same plantation
    $duplicate = TreeGrowth::where('species', $request->species)
        ->where('dbh', $request->dbh)
        ->where('height', $request->height)
        ->where('latitude', $request->latitude)
        ->where('longitude', $request->longitude)
        ->where('year_recorded', $request->year_recorded)
        ->where('plantation_id', $plantation->id)
        
        ->first();

    if ($duplicate) {
        return response()->json(['error' => 'Tree with same species, DBH, and height already exists in this plantation.'], 409);
    }

    // Save the uploaded geotagged photo if available
    $photoPath = null;
    if ($request->hasFile('geotag_photos')) {
        $photoPath = $request->file('geotag_photos')->store('geotag_photos', 'public');
    }

    // Create the new TreeGrowth record
    $treeGrowth = TreeGrowth::create([
        'species'       => $request->species,
        'dbh'           => $request->dbh,
        'height'        => $request->height,
        'geotag_photos' => $photoPath,
        'longitude'     => $request->longitude,
        'latitude'      => $request->latitude,
        'year_recorded' => $request->year_recorded,
        'plantation_id' => $plantation->id,
    ]);

    // Clear cached sequestration related to the plantation
    Cache::forget("carbon_sequestration_plantation_{$plantation->id}");
    Log::info("Cache cleared for plantation: {$plantation->id}");

    return response()->json([
        'status'  => 'success',
        'message' => 'Tree growth data saved.',
        'data'    => $treeGrowth,
    ], 201);
}

    

    public function indexTreeGrowth()
{
    return response()->json([
        'status' => 'success',
        'data' => TreeGrowth::all(),
    ]);
}



    // Update tree growth data
    public function updateTreeGrowth(Request $request, $id)
    {
        $tree = TreeGrowth::find($id);

        if (!$tree) {
            return response()->json(['message' => 'Tree not found'], 404);
        }

        $validated = $request->validate([
            'dbh'           => 'required|numeric|min:0',
            'height'        => 'required|numeric|min:0',
            'geotag_photos' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $tree->dbh = $request->dbh;
        $tree->height = $request->height;

        if ($request->hasFile('geotag_photos')) {
            if ($tree->geotag_photos && Storage::disk('public')->exists($tree->geotag_photos)) {
                Storage::disk('public')->delete($tree->geotag_photos);
            }

            $photoPath = $request->file('geotag_photos')->store('tree_photos', 'public');
            $tree->geotag_photos = $photoPath;
        }

        Cache::forget("carbon_sequestration_plantation_{$tree->plantation_id}");
        $tree->save();

        return response()->json(['message' => 'Tree data updated successfully', 'data' => $tree]);
    }

    // Calculate carbon sequestration
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
            return response()->json(['error' => 'No tree data found for this plantation'], 404);
        }

        $totalCarbon = 0;
        $totalCarbonTon = 0;
        $treeDetails = [];

        foreach ($trees as $tree) {
            $AGB = 34.4703 - (8.0671 * $tree->dbh) + (0.6589 * pow($tree->dbh, 2));
            $BGB = $AGB * 0.15;
            $biomass = $AGB + $BGB;
            $carbon = $biomass * 0.5;
            $co2 = $carbon * 3.67;
            $co2_ton = $co2 / 1000;

            $totalCarbon += $co2;
            $totalCarbonTon += $co2_ton;

            $treeDetails[] = [
                'tree_id'               => $tree->id,
                'species'               => $tree->species,
                'dbh'                   => $tree->dbh,
                'height'                => $tree->height,
                'AGB_kg'                => round($AGB, 2),
                'BGB_kg'                => round($BGB, 2),
                'total_biomass_kg'      => round($biomass, 2),
                'carbon_content_kg'     => round($carbon, 2),
                'CO2_sequestration_kg'  => round($co2, 2),
                'CO2_sequestration_ton' => round($co2_ton, 2),
            ];
        }

        $result = [
            'plantation_id'                   => $plantationId,
            'number_of_trees'                 => count($trees),
            'total_carbon_sequestration_kg'   => round($totalCarbon, 2),
            'total_carbon_sequestration_ton'  => round($totalCarbonTon, 2),
            'tree_sequestration_details'      => $treeDetails,
        ];

        Cache::put($cacheKey, $result, now()->addHours(24));

        return response()->json($result);
    }

    


public function getYearlySequestrationSummary()
{
    try {
        $treeGrowths = TreeGrowth::select('year_recorded', 'dbh', 'height') // add fields if needed
            ->get();

        $yearlyTotals = [];

        foreach ($treeGrowths as $tree) {
            $dbh = $tree->dbh;
            $height = $tree->height;
            $year = $tree->year_recorded;

            // Sample sequestration calculation (adjust as per your formula)
             $AGB = 34.4703 - (8.0671 * $tree->dbh) + (0.6589 * pow($tree->dbh, 2));
            $BGB = $AGB * 0.15;
            $biomass = $AGB + $BGB;
            $carbon = $biomass * 0.5;
            $co2 = $carbon * 3.67;
            $co2_ton = $co2 / 1000;

            if (!isset($yearlyTotals[$year])) {
                $yearlyTotals[$year] = 0;
            }

            $yearlyTotals[$year] += $co2; // in kg
        }

        // Format results
        $result = [];
        foreach ($yearlyTotals as $year => $total_kg) {
            $result[] = [
                'year' => $year,
                'total_carbon_sequestration_kg' => round($total_kg, 2),
                'total_carbon_sequestration_tco2e' => round($total_kg / 1000, 2)
            ];
        }

        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to compute dynamic sequestration per year.',
            'details' => $e->getMessage()
        ], 500);
    }
}




}
