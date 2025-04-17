<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GHGEmission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class GHGEmissionController extends Controller
{
    // Display all records
    public function index()
    {
        return response()->json(GHGEmission::all());
    }

    // Method to calculate and return the annual emissions
    public function calculateAnnualEmissions(Request $request, $year)
    {
        // 1. Fetch the quarterly data for the given year from the database
        $quarterlyData = GHGEmission::where('year', $year)
            ->groupBy('quarter')
            ->get();

        // Initialize totals for fuel, electricity, and travel
        $totalFuelLitersUsed = 0;
        $totalElectricityKwh = 0;
        $totalTrips = 0;
        $totalDistance = 0;

        // 2. Loop through the grouped data and sum the quarterly data for each category
        foreach ($quarterlyData as $data) {
            $totalFuelLitersUsed += $data->fuel_liters_used;
            $totalElectricityKwh += $data->electricity_kwh;
            $totalTrips += $data->travel_number_of_trips;
            $totalDistance += $data->travel_distance_miles;
        }

        // 3. Define emission factors and GWP values
        $emissionFactors = [
            'diesel'    => ['co2' => 2.712681, 'ch4' => 0.000143, 'n2o' => 0.000143],
            'biodiesel' => ['co2' => 0.0, 'ch4' => 0.000382, 'n2o' => 0.000872],
            'ethanol'   => ['co2' => 0.0, 'ch4' => 0.0001, 'n2o' => 0.0001],
            'gasoline'  => ['co2' => 2.297040, 'ch4' => 0.000671, 'n2o' => 0.000210],
        ];

        $gwp = ['co2' => 1, 'ch4' => 21, 'n2o' => 310];

        // 4. Calculate fuel emissions (total for the year)
        $fuelType = $request->fuel_type; // Assuming this is passed in the request
        $fuel_co2 = ($totalFuelLitersUsed * $emissionFactors[$fuelType]['co2'] * $gwp['co2']) / 1000;
        $fuel_ch4 = ($totalFuelLitersUsed * $emissionFactors[$fuelType]['ch4'] * $gwp['ch4']) / 1000;
        $fuel_n2o = ($totalFuelLitersUsed * $emissionFactors[$fuelType]['n2o'] * $gwp['n2o']) / 1000;
        $fuel_total = $fuel_co2 + $fuel_ch4 + $fuel_n2o;

        // 5. Calculate electricity emissions (total for the year)
        $electricity_mwh = $totalElectricityKwh / 1000; // Convert to MWh
        $electricity_total = $electricity_mwh * 0.496;

        // 6. Calculate business travel emissions (total for the year)
        $activity_data = $totalTrips * $totalDistance; // Total trips * distance for the year
        $travel_co2 = $activity_data * 0.277 * $gwp['co2'];
        $travel_ch4 = $activity_data * 0.0000104 * $gwp['ch4'];
        $travel_n2o = $activity_data * 0.0000085 * $gwp['n2o'];
        $travel_total_kg = $travel_co2 + $travel_ch4 + $travel_n2o;
        $travel_total = $travel_total_kg / 1000; // Convert to metric tons (TCO₂)

        // 7. Calculate total emissions for the year
        $total_emissions = $fuel_total + $electricity_total + $travel_total;

        // 8. Return the data to be displayed on the dashboard
        return response()->json([
            'annual_total_emissions' => $total_emissions, // Total emissions for the year in TCO₂
            'annual_fuel_emissions' => $fuel_total,
            'annual_electricity_emissions' => $electricity_total,
            'annual_travel_emissions' => $travel_total,
        ]);
    }

    // Store new emission data and auto-calculate TCO2
    public function store(Request $request)
    {
        try {
            $request->validate([
                'year'                      => 'required|integer',
                'quarter'                   => 'required|in:Q1,Q2,Q3,Q4',
                'fuel_source'               => 'required|string',
                'fuel_type'                 => 'required|in:diesel,biodiesel,ethanol,gasoline',
                'fuel_liters_used'          => 'required|numeric',
                'electricity_kwh'           => 'required|numeric',
                'travel_category'           => 'required|in:short,medium,long,unknown',
                'travel_distance_miles'     => 'required|integer',
                'travel_number_of_trips'    => 'required|integer',
                'date_recorded'             => 'nullable|date',
            ]);

            // Emission factors & GWP
            $emissionFactors = [
                'diesel'    => ['co2' => 2.712681, 'ch4' => 0.000143, 'n2o' => 0.000143],
                'biodiesel' => ['co2' => 0.0,      'ch4' => 0.000382, 'n2o' => 0.000872],
                'ethanol'   => ['co2' => 0.0,      'ch4' => 0.0001,   'n2o' => 0.0001],
                'gasoline'  => ['co2' => 2.297040, 'ch4' => 0.000671, 'n2o' => 0.000210],
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
            $electricity_total = $electricity_mwh * 0.496;

            // Business travel emissions
            $trip_count = $request->travel_number_of_trips;
            $distance = $request->travel_distance_miles;
            $activity_data = $trip_count * $distance;

            $travel_co2 = $activity_data * 0.277 * $gwp['co2'];
            $travel_ch4 = $activity_data * 0.0000104 * $gwp['ch4'];
            $travel_n2o = $activity_data * 0.0000085 * $gwp['n2o'];
            $travel_total_kg = $travel_co2 + $travel_ch4 + $travel_n2o;
            $travel_total = $travel_total_kg / 1000;

            // Save all data
            $ghg = new GHGEmission();
            $ghg->year = $request->year;
            $ghg->quarter = $request->quarter;
            $ghg->fuel_source = $request->fuel_source;
            $ghg->fuel_type = $request->fuel_type;
            $ghg->fuel_liters_used = $litersUsed;
            $ghg->fuel_tco2 = $fuel_total;

            $ghg->electricity_kwh = $request->electricity_kwh;
            $ghg->electricity_tco2 = $electricity_total;

            $ghg->travel_category = $request->travel_category;
            $ghg->travel_distance_miles = $distance;
            $ghg->travel_number_of_trips = $trip_count;
            $ghg->travel_tco2 = $travel_total;

            $ghg->total_tco2 = $fuel_total + $electricity_total + $travel_total;
            $ghg->date_recorded = $request->date_recorded;

            // If user is authenticated, assign company ID
            if (Auth::check()) {
                $ghg->company_id = Auth::user()->company_id;
            } else {
                return response()->json(['error' => 'User not authenticated.'], 401);
            }

            // Save to database
            $ghg->save();

            return response()->json([
                'message' => 'GHG emission recorded successfully.',
                'data' => $ghg,
                'emissions' => [
                    'fuel_tco2' => $fuel_total,
                    'electricity_tco2' => $electricity_total,
                    'business_travel_tco2' => $travel_total,
                    'total_tco2' => $ghg->total_tco2,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('GHG Store Error: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong.', 'details' => $e->getMessage()], 500);
        }
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
