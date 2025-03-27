<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Source;

class SourceController extends Controller
{

    // ✅ GET ALL SOURCE
    public function index()
    {
        return Source::all();
    }

    public function store(Request $request)
    {
       // Get the authenticated user's company ID
    $companyID = auth()->user()->companyID; // Adjust based on your user structure

    // Validate request data
    $validatedData = $request->validate([
        'name' => 'required|string',
        'fuel_type' => 'required|string',
        'fuel_consumption' => 'required|numeric',
        'electricity_usage' => 'required|numeric',
    ]);

    // Create source with the authenticated user's company ID
    $source = Source::create([
        'name' => $validatedData['name'],
        'fuel_type' => $validatedData['fuel_type'],
        'fuel_consumption' => $validatedData['fuel_consumption'],
        'electricity_usage' => $validatedData['electricity_usage'],
        'companyID' => $companyID, // Auto-assign based on the logged-in user
    ]);

    return response()->json([
        'message' => 'Source created successfully',
        'source' => $source
    ], 201);
    }

    // ✅ GET SINGLE SOURCE
    public function show($id)
    {
        return Source::find($id);
    }

      // ✅ UPDATE SOURCE
      public function update(Request $request, $id)
      {

          $source = Source::findOrFail($id);
  
          // Validate request
          $request->validate([
              'name'                => 'sometimes|string',
              'fuel_type'           => 'sometimes|string|in:gasoline,diesel',
              'fuel_consumption'    => 'sometimes|numeric|min:0',
              'electricity_usage'   => 'sometimes|numeric|min:0',
          ]);
  
          // Update source
          $source->update($request->all());
  
          // Recalculate emissions
          $totalEmission = $source->calculateEmissions();
  
          return response()->json([
              'source'                  => $source->name,
              'fuel_type'               => $source->fuel_type,
              'fuel_consumed'           => $source->fuel_consumption,
              'electricity_consumed'    => $source->electricity_usage,
              'total_emission'          => $totalEmission,
          ]);
      }

      // ✅ DELETE SOURCE
    public function destroy($id)
    {
        $source = Source::findOrFail($id);
        $source->delete();

        return response()->json(['message' => 'Source deleted successfully']);
    }
    
}
