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
        'geotag_photos' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        'latitude'      => 'required|numeric|between:-90,90',
        'longitude'     => 'required|numeric|between:-180,180',
        'plantation_id' => 'required|exists:plantation,id',
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

    // Check for exact duplicate entry (species, dbh, height, lat, long, plantation)
    $duplicate = TreeGrowth::where('species', $request->species)
        ->where('dbh', $request->dbh)
        ->where('height', $request->height)
        ->where('latitude', $request->latitude)
        ->where('longitude', $request->longitude)
        ->where('plantation_id', $plantation->id)
        ->first();

    if ($duplicate) {
        return response()->json(['error' => 'Duplicate tree entry already exists in this plantation.'], 409);
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

    Cache::forget("carbon_sequestration_plantation_{$plantation->id}");
    Log::info("Cache cleared for plantation: {$plantation->id}");

    return response()->json([
        'status'  => 'success',
        'message' => 'Tree growth data saved.',
        'data'    => $treeGrowth,
    ], 201);
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
}
