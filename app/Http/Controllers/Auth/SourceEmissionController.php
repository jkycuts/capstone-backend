<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SourceEmission;
use App\Models\Source;

class SourceEmissionController extends Controller
{
    public function store(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'source_id'          => 'required|exists:sources,id', 
            'year'               => 'required|integer',
            'quarter'            => 'required|string|in:Q1,Q2,Q3,Q4',
            'fuel_consumption'   => 'required|numeric',
            'electricity_usage'  => 'required|numeric'
        ]);

        // Find the source
        $source = Source::find($validated['source_id']);
        if (!$source) {
            return response()->json(['error' => 'Source not found'], 404);
        }

        // Calculate emissions from the input data
        $calculatedEmissions = $source->calculateEmissionsFromData((object) [
            'fuel_consumption'  => $validated['fuel_consumption'],
            'electricity_usage' => $validated['electricity_usage']
        ]);

        // Create the emission record with the calculated emissions
        $emission = SourceEmission::create([
            'source_id'           => $validated['source_id'],
            'year'                => $validated['year'],
            'quarter'             => $validated['quarter'],
            'fuel_consumption'    => $validated['fuel_consumption'],
            'electricity_usage'   => $validated['electricity_usage'],
            'co2_emission'        => $calculatedEmissions['fuel_emissions']['co2_emission'],
            'n2o_emission'        => $calculatedEmissions['fuel_emissions']['n2o_emission'],
            'electricity_emission'=> $calculatedEmissions['electricity_emission'],
            'total_emission'      => $calculatedEmissions['total_emission']
        ]);

        // Trigger the calculation for the whole year
        $yearlyEmissions = $source->calculateYearlyEmissions($validated['year']);

        // Return the response with the calculated yearly emissions
        return response()->json([
            'message'              => 'Emission data stored successfully',
            'emission'             => $emission,
            'yearly_emissions' => $yearlyEmissions
        ], 201);
    }

    public function getBySource($source_id, $year)
    {
        // Retrieve the emissions for the given source and year
        $emissions = SourceEmission::where('source_id', $source_id)
            ->where('year', $year)
            ->get();

        // Return the emissions as a response
        return response()->json($emissions);
    }
}
