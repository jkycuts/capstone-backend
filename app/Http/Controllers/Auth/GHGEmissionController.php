<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GHGEmission;

class GHGEmissionController extends Controller
{
    // Display all records
    public function index()
    {
        return response()->json(GHGEmission::all());
    }

    // Store new emission data and auto-calculate TCO2
    public function store(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'quarter' => 'required|in:Q1,Q2,Q3,Q4',
            'fuel_source' => 'required|in:vehicle,generator',
            'fuel_type' => 'required|in:diesel,biodiesel,ethanol,gasoline',
            'fuel_liters_used' => 'required|numeric',
            'electricity_kwh' => 'required|numeric',
            'travel_category' => 'required|in:short,medium,long,unknown',
            'travel_distance_miles' => 'required|integer',
            'travel_number_of_trips' => 'required|integer',
        ]);

        // Emission factors & GWP
        $emissionFactors = [
            'diesel' => ['co2' => 2.712681, 'ch4' => 0.000143, 'n2o' => 0.000143],
            'biodiesel' => ['co2' => 0.0, 'ch4' => 0.000382, 'n2o' => 0.000872],
            'ethanol' => ['co2' => 0.0, 'ch4' => 0.0001, 'n2o' => 0.0001], // assumed
            'gasoline' => ['co2' => 2.297040, 'ch4' => 0.000671, 'n2o' => 0.000210],
        ];

        $gwp = ['co2' => 1, 'ch4' => 21, 'n2o' => 310];

        $fuelType = $request->fuel_type;
        $litersUsed = $request->fuel_liters_used;

        // Fuel emissions
        $fuel_co2 = ($litersUsed * $emissionFactors[$fuelType]['co2'] * $gwp['co2']) / 1000;
        $fuel_ch4 = ($litersUsed * $emissionFactors[$fuelType]['ch4'] * $gwp['ch4']) / 1000;
        $fuel_n2o = ($litersUsed * $emissionFactors[$fuelType]['n2o'] * $gwp['n2o']) / 1000;
        $fuel_total = $fuel_co2 + $fuel_ch4 + $fuel_n2o;

        // Electricity emissions
        $electricity_mwh = $request->electricity_kwh / 1000;
        $electricity_total = $electricity_mwh * 0.496; // TCO2

        // Business travel emissions
        $trip_count = $request->travel_number_of_trips;
        $distance = $request->travel_distance_miles;
        $activity_data = $trip_count * $distance;

        $travel_co2 = $activity_data * 0.277 * $gwp['co2'];
        $travel_ch4 = $activity_data * 0.0000104 * $gwp['ch4'];
        $travel_n2o = $activity_data * 0.0000085 * $gwp['n2o'];
        $travel_total_kg = $travel_co2 + $travel_ch4 + $travel_n2o;
        $travel_total = $travel_total_kg / 1000; // convert to metric tons

        // Get company_id from authenticated user
        $company_id = auth()->user()->company_id;

        // Save input data and calculated emissions
        $ghg = new GHGEmission();
        $ghg->year = $request->year;
        $ghg->quarter = $request->quarter;
        $ghg->fuel_source = $request->fuel_source;
        $ghg->fuel_type = $request->fuel_type;
        $ghg->fuel_liters_used = $litersUsed;
        $ghg->electricity_kwh = $request->electricity_kwh;
        $ghg->travel_category = $request->travel_category;
        $ghg->travel_distance_miles = $distance;
        $ghg->travel_number_of_trips = $trip_count;
        $ghg->company_id = $company_id; // Assign the company_id
        $ghg->save();

        // Return response with emission data
        return response()->json([
            'message' => 'GHG emission recorded successfully.',
            'data' => $ghg,
            'emissions' => [
                'fuel_tco2' => $fuel_total,
                'electricity_tco2' => $electricity_total,
                'business_travel_tco2' => $travel_total,
                'total_tco2' => $fuel_total + $electricity_total + $travel_total,
            ]
        ]);
    }

    // Display a specific record
    public function show($id)
    {
        $record = GHGEmission::findOrFail($id);
        return response()->json($record);
    }

    // Update a specific record
    public function update(Request $request, $id)
    {
        $record = GHGEmission::findOrFail($id);
        $record->update($request->all());
        return response()->json(['message' => 'Updated successfully.', 'data' => $record]);
    }

    // Delete a specific record
    public function destroy($id)
    {
        GHGEmission::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted successfully.']);
    }
}
