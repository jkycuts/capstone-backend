<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Source;
use App\Models\SourceEmission;

class SourceController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string',
            'fuel_type' => 'required|string',
            'companyID' => 'required|exists:mining_companies,id'
        ]);

        $source = Source::create($request->all());

        return response()->json([
            'message' => 'Source created successfully',
            'source' => $source
        ], 201);
    }

    public function index()
    {
        $sources = Source::with('emissions')->get();
        return response()->json($sources);
    }

    public function show($id)
    {
        $source = Source::with('emissions')->findOrFail($id);
        return response()->json($source);
    }

    public function calculateEmissions($id, $year)
    {
        $source = Source::findOrFail($id);
        $emissionData = $source->calculateYearlyEmissions($year);

        return response()->json([
            'source' => $source->name,
            'fuel_type' => $source->fuel_type,
            'year' => $year,
            'emissions' => $emissionData
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
