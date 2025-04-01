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
            'sourceID'           => 'required|exists:sources,id', // Make sure sourceID matches your DB column
            'year'                => 'required|integer',
            'quarter'             => 'required|string|in:Q1,Q2,Q3,Q4',
            'fuel_consumption'    => 'required|numeric',
            'electricity_usage'   => 'required|numeric'
        ]);

        // Get the source associated with this emission
        $source = Source::findOrFail($validated['sourceID']);

        // Calculate emissions from the input data
        $calculatedEmissions = $source->calculateEmissionsFromData((object) [
            'fuel_consumption' => $validated['fuel_consumption'],
            'electricity_usage' => $validated['electricity_usage']
        ]);

        // Create the emission record with the calculated emissions
        $emission = SourceEmission::create([
            'sourceID'           => $validated['sourceID'],
            'year'               => $validated['year'],
            'quarter'            => $validated['quarter'],
            'fuel_consumption'   => $validated['fuel_consumption'],
            'electricity_usage'  => $validated['electricity_usage'],
            'co2_emission'       => $calculatedEmissions['fuel_emissions']['co2_emission'],
            'n2o_emission'       => $calculatedEmissions['fuel_emissions']['n2o_emission'],
            'electricity_emission' => $calculatedEmissions['electricity_emission'],
            'total_emission'     => $calculatedEmissions['total_emission']
        ]);

        // Trigger the calculation for the whole year if needed
        $yearlyEmissions = $source->calculateYearlyEmissions($validated['year']);

        // Return the response with the calculated yearly emissions
        return response()->json([
            'message'             => 'Emission data stored successfully',
            'emission'            => $emission,
            'calculated_emissions'=> $yearlyEmissions
        ], 201);
    }

    public function getBySource($source_id, $year)
    {
        // Retrieve the emissions for the given source and year
        $emissions = SourceEmission::where('sourceID', $source_id)
            ->where('year', $year)
            ->get();

        // Return the emissions as a response
        return response()->json($emissions);
    }
}
