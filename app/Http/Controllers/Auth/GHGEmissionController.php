<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GHGEmission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Scope1Emission;
use App\Models\Scope2Emission;
use App\Models\Scope3Emission;

class GHGEmissionController extends Controller
{
    // Display all records
    public function index()
    {
        return response()->json(GHGEmission::all());
    }

    // Backend (Laravel Controller)
public function getScope1Emissions()
{
    
    $user = Auth::user();
    $emissions = Scope1Emission::where('company_id', $user->company_id)->get();

    return response()->json($emissions); // 👈 Make sure this returns the full record

}
    



   
    public function storeScope1(Request $request)
    {
        $validated = $request->validate([
            'quarter'                   => 'required|in:Q1,Q2,Q3,Q4',
            'year'                      => 'required|integer',
            'parameter'                 => 'required|string',
            'fuel_type'                 => 'required|string',
            'fuel_liters_used'          => 'required|numeric|min:0',
        ]);
    
        // Emission factors and GWP
        $emissionFactors = [
            'diesel'    => ['co2' => 2.712681, 'ch4' => 0.000143, 'n2o' => 0.000143],
            'biodiesel' => ['co2' => 0.0,      'ch4' => 0.000382, 'n2o' => 0.000872],
            'ethanol'   => ['co2' => 0.0,      'ch4' => 0.0001,   'n2o' => 0.0001],
            'gasoline'  => ['co2' => 2.297040, 'ch4' => 0.000671, 'n2o' => 0.000210],
        ];
        $gwp = ['co2' => 1, 'ch4' => 21, 'n2o' => 310];
    
        $fuelType = $validated['fuel_type'];
        $litersUsed = $validated['fuel_liters_used'];
    
        // Calculate emissions
        $fuel_co2 = ($litersUsed * $emissionFactors[$fuelType]['co2'] * $gwp['co2']) / 1000;
        $fuel_ch4 = ($litersUsed * $emissionFactors[$fuelType]['ch4'] * $gwp['ch4']) / 1000;
        $fuel_n2o = ($litersUsed * $emissionFactors[$fuelType]['n2o'] * $gwp['n2o']) / 1000;
        $fuel_total = $fuel_co2 + $fuel_ch4 + $fuel_n2o;

        // Get company_id from authenticated user
        if (!Auth::check()) {
            return response()->json(['error' => 'User not authenticated.'], 401);
        }

        $companyId = Auth::user()->company_id;
    
        // Store to database
        $scope1 = Scope1Emission::create([
            'company_id' => $companyId,
            'year' => $validated['year'],
            'quarter' => $validated['quarter'],
            'parameter' => $validated['parameter'],
            'emission_tco2e' => round($fuel_total, 4),
        ]);
    
        return response()->json([
            'message' => 'Scope 1 Emission Data Saved Successfully.',
            'data' => [
                'record' => $scope1,
                'details' => [
                    'CO2 (tCO2e)'   => round($fuel_co2, 4),
                    'CH4 (tCO2e)'   => round($fuel_ch4, 4),
                    'N2O (tCO2e)'   => round($fuel_n2o, 4),
                    'Total (tCO2e)' => round($fuel_total, 4),
                ]
            ]
        ], 201);
    }
    


    public function getScope2Emissions(Request $request)
    {
        $user = Auth::user();
        $emissions = Scope2Emission::where('company_id', $user->company_id)->get();
    
        return response()->json($emissions); // ✅ This should include emission_tco2e
    }


    public function storeScope2Emission(Request $request)
{
    $request->validate([
        'year'              => 'required|integer',
        'quarter'           => 'required|string',
        'electricity_kwh'    => 'required|numeric|min:0',
    ]);

    // Convert kWh to MWh
    $electricity_mwh = $request->electricity_kwh / 1000;

    // Emission factor for electricity (e.g. Philippines grid) = 0.496 tCO₂/MWh
    $emission_factor = 0.496;

    // Calculate total emissions
    $emission_tco2e = $electricity_mwh * $emission_factor;

    // Save record
    $record = Scope2Emission::create([
        'company_id'        => Auth::user()->company_id,
        'year'              => $request->year,
        'quarter'           => $request->quarter,
        'electricity_kwh'      => $request->electricity_kwh,
        'emission_tco2e'    => $emission_tco2e,
    ]);

    return response()->json([
        'message' => 'Scope 2 Emission Data Saved Successfully.',
        'data' => $record
    ], 201);
}



public function storeScope3Emission(Request $request)
{
    $validated = $request->validate([
        'year' => 'required|integer',
        'travel_type' => 'required|in:short,medium,long',
        'travel_distance_miles' => 'required|integer',
    ]);

    $companyId = auth()->user()->company_id;

    $gwp = [
        'co2' => 1,     // Global Warming Potential for CO₂
        'ch4' => 21,    // GWP for CH₄
        'n2o' => 310    // GWP for N₂O
    ];

    // Business travel emissions (Scope 3)
$activity_data = $request->travel_distance_miles;

// Determine CO₂ emission factor based on travel distance
if ($activity_data <= 300) {
    $co2_factor = 0.277; // Short haul
    $travel_category = 'short';
} elseif ($activity_data > 300 && $activity_data <= 700) {
    $co2_factor = 0.229; // Medium haul
       $travel_category = 'medium';
} else {
    $co2_factor = 0.185; // Long haul
    $travel_category = 'long';
}

// Apply GWP values (assumed passed or defined earlier)
$travel_co2 = $activity_data * $co2_factor * $gwp['co2'];
$travel_ch4 = $activity_data * 0.0000104 * $gwp['ch4'];
$travel_n2o = $activity_data * 0.0000085 * $gwp['n2o'];

$travel_total_kg = $travel_co2 + $travel_ch4 + $travel_n2o;
$total_emissions = $travel_total_kg / 1000; // Convert kg to metric tons (tCO₂e)

    Scope3Emission::create([
        'company_id' => $companyId,
        'year' => $validated['year'],
        'travel_type' => $validated['travel_type'],
        'travel_distance_miles' => $validated['travel_distance_miles'],
        'emission_tco2e' => $total_emissions,  // Store the computed emissions
    ]);

    return response()->json(['message' => 'Scope 3 emissions stored successfully.']);

    $companyId = auth()->user()->company_id;
    Log::info('Company ID:', ['company_id' => $companyId]);

}





public function getScope3Emission()
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $companyId = $user->company_id;

    $data = Scope3Emission::where('company_id', $companyId)->get([
        'year',
        'emission_tco2e',
        'travel_type'
    ]);

    return response()->json($data);
}


    // // Fetch Scope 3 emissions data for a specific company and year
    // public function getScope3EmissionsByYear($companyId, $year)
    // {
    //     // Retrieve Scope 3 emissions for the given company and year
    //     $emissions = Scope3Emission::where('company_id', $companyId)
    //         ->where('year', $year)
    //         ->get();

    //     if ($emissions->isEmpty()) {
    //         return response()->json(['message' => 'No Scope 3 emissions data found for this company in the given year.'], 404);
    //     }

    //     // Return the emissions data as JSON
    //     return response()->json($emissions);
    // }



    

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

        // Fuel emissions (Scope 1)
        $fuel_co2 = ($litersUsed * $emissionFactors[$fuelType]['co2'] * $gwp['co2']) / 1000;
        $fuel_ch4 = ($litersUsed * $emissionFactors[$fuelType]['ch4'] * $gwp['ch4']) / 1000;
        $fuel_n2o = ($litersUsed * $emissionFactors[$fuelType]['n2o'] * $gwp['n2o']) / 1000;
        $fuel_total = $fuel_co2 + $fuel_ch4 + $fuel_n2o;

        // Electricity emissions (Scope 2)
        $electricity_mwh = $request->electricity_kwh / 1000;
        $electricity_total = $electricity_mwh * 0.496;

        // Business travel emissions (Scope 3)
        $activity_data =  $request->travel_distance_miles;
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
        $ghg->travel_distance_miles = $activity_data;
        $ghg->travel_tco2 = $travel_total;

        // Scopes
        $ghg->scope_1 = $fuel_total;
        $ghg->scope_2 = $electricity_total;
        $ghg->scope_3 = $travel_total;

        $ghg->total_tco2 = $fuel_total + $electricity_total + $travel_total;
        $ghg->date_recorded = $request->date_recorded;

        // Authenticated user
        if (Auth::check()) {
            $ghg->company_id = Auth::user()->company_id;
        } else {
            return response()->json(['error' => 'User not authenticated.'], 401);
        }

        $ghg->save();

        return response()->json([
            'years' => [$request->year],
            'emissions' => [
                'Fuel_TCO2' => $fuel_total,
                'Electricity_TCO2' => $electricity_total,
                'Travel_TCO2' => $travel_total,
                'Scope_1' => $fuel_total,
                'Scope_2' => $electricity_total,
                'Scope_3' => $travel_total,
                'Total_TCO2' => $ghg->total_tco2,
            ],
            'message' => 'GHG emission recorded successfully.',
            'data' => $ghg
        ]);
    } catch (\Exception $e) {
        Log::error('GHG Store Error: ' . $e->getMessage());
        return response()->json(['error' => 'Something went wrong.', 'details' => $e->getMessage()], 500);
    }
}

    

   
}
