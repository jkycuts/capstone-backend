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
use Illuminate\Support\Facades\DB;

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
    try {
        // Validate input
        $validated = $request->validate([
            'mode'                      => 'required|in:monthly,quarterly',
            'year'                      => 'required|integer',
            'parameter'                 => 'required|string',
            'fuel_type'                 => 'required|string',
            'fuel_liters_used'          => 'required|numeric|min:0',

            'co2_emission_factor'       => 'required|numeric|min:0',
            'ch4_emission_factor'       => 'required|numeric|min:0',
            'n2o_emission_factor'       => 'required|numeric|min:0',

            'co2_gwp'                   => 'required|numeric|min:0',
            'ch4_gwp'                   => 'required|numeric|min:0',
            'n2o_gwp'                   => 'required|numeric|min:0',

            'month'                     => 'nullable|string',
            'quarter'                   => 'nullable|string',
        ]);

        // Ensure authenticated user
        if (!Auth::check()) {
            return response()->json(['error' => 'User not authenticated.'], 401);
        }

        $companyId = Auth::user()->company_id;
        $litersUsed = $validated['fuel_liters_used'];

        // Calculate emissions per gas
        $co2 = $litersUsed * $validated['co2_emission_factor'] * $validated['co2_gwp'];
        $ch4 = $litersUsed * $validated['ch4_emission_factor'] * $validated['ch4_gwp'];
        $n2o = $litersUsed * $validated['n2o_emission_factor'] * $validated['n2o_gwp'];

        $emissionTotal = $co2 + $ch4 + $n2o;

        // Save to DB
        $scope1 = Scope1Emission::create([
            'company_id'            => $companyId,
            'year'                  => $validated['year'],
            'mode'                  => $validated['mode'],
            'month'                 => $validated['mode'] === 'monthly' ? $validated['month'] : null,
            'quarter'               => $validated['mode'] === 'quarterly' ? $validated['quarter'] : null,
            'parameter'             => ucfirst($validated['parameter']),
            'fuel_type'             => strtolower($validated['fuel_type']),
            'fuel_liters_used'      => $litersUsed,

            'co2_emission_factor'   => $validated['co2_emission_factor'],
            'ch4_emission_factor'   => $validated['ch4_emission_factor'],
            'n2o_emission_factor'   => $validated['n2o_emission_factor'],

            'co2_gwp'               => $validated['co2_gwp'],
            'ch4_gwp'               => $validated['ch4_gwp'],
            'n2o_gwp'               => $validated['n2o_gwp'],

            'emission_co2e'         => round($co2, 4),
            'emission_ch4e'         => round($ch4, 4),
            'emission_n2oe'         => round($n2o, 4),
            'emission_tco2e'        => round($emissionTotal, 4),
        ]);

        return response()->json([
            'message' => 'Scope 1 Emission Data Saved Successfully.',
            'data' => [
                'record' => $scope1,
                'calculation' => [
                    'Fuel Used (liters)' => $litersUsed,
                    'CO2 (tCO2e)'        => round($co2, 4),
                    'CH4 (tCO2e)'        => round($ch4, 4),
                    'N2O (tCO2e)'        => round($n2o, 4),
                    'Total (tCO2e)'      => round($emissionTotal, 4),
                ]
            ]
        ], 201);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Server error: ' . $e->getMessage()
        ], 500);
    }
}



    


    public function getScope2Emissions(Request $request)
    {
        $user = Auth::user();
        $emissions = Scope2Emission::where('company_id', $user->company_id)->get();
    
        return response()->json($emissions); 
    }


    public function storeScope2Emission(Request $request)
{
    $request->validate([
        'mode'              => 'required|in:monthly,quarterly',
        'year'              => 'required|integer',
        'month'             => 'nullable|required_if:mode,monthly|string',
        'quarter'           => 'nullable|required_if:mode,quarterly|string',
        'electricity_kwh'   => 'required|numeric|min:0',
        'emission_factor'   => 'required|numeric|min:0'
    ]);

    // Convert kWh to MWh
    $electricity_mwh = $request->electricity_kwh / 1000;

    // Calculate total emissions using provided factor
    $emission_tco2e = $electricity_mwh * $request->emission_factor;

    // Save record
    $record = Scope2Emission::create([
        'company_id'        => Auth::user()->company_id,
        'mode'              => $request->mode,
        'year'              => $request->year,
        'month'             => $request->mode === 'monthly' ? $request->month : null,
        'quarter'           => $request->mode === 'quarterly' ? $request->quarter : null,
        'electricity_kwh'   => $request->electricity_kwh,
        'emission_factor'   => $request->emission_factor,
        'emission_tco2e'    => $emission_tco2e,
    ]);

    return response()->json([
        'message' => 'Scope 2 Emission Data Saved Successfully.',
        'data'    => $record
    ], 201);
}




public function storeScope3Emission(Request $request)
{
    $validated = $request->validate([
        'year'                      => 'required|integer',
        'mode'                      => 'required|in:monthly,quarterly',
        'month'                     => 'nullable|string',
        'quarter'                   => 'nullable|string',
        'travel_type'               => 'required|in:short,medium,long',
        'travel_distance_miles'     => 'required|numeric|min:0',
        'co2_emission_factor'       => 'required|numeric|min:0',
        'ch4_emission_factor'       => 'required|numeric|min:0',
        'n2o_emission_factor'       => 'required|numeric|min:0',
        'co2_gwp'                   => 'required|numeric|min:0',
        'ch4_gwp'                   => 'required|numeric|min:0',
        'n2o_gwp'                   => 'required|numeric|min:0',
    ]);

    try {
        $companyId = auth()->user()->company_id;

        $distance = $validated['travel_distance_miles'];

        // Calculate emissions by gas
        $co2 = $distance * $validated['co2_emission_factor'] * $validated['co2_gwp'];
        $ch4 = $distance * $validated['ch4_emission_factor'] * $validated['ch4_gwp'];
        $n2o = $distance * $validated['n2o_emission_factor'] * $validated['n2o_gwp'];

        $emissionTotal = $co2 + $ch4 + $n2o;  // in kg CO2e
        $emission_tco2e = $emissionTotal / 1000; // convert to metric tons CO2e

        $record = Scope3Emission::create([
            'company_id'            => $companyId,
            'year'                  => $validated['year'],
            'mode'                  => $validated['mode'],
            'month'                 => $validated['month'],
            'quarter'               => $validated['quarter'],
            'travel_type'           => $validated['travel_type'],
            'travel_distance_miles' => $distance,

            'co2_emission_factor'   => $validated['co2_emission_factor'],
            'ch4_emission_factor'   => $validated['ch4_emission_factor'],
            'n2o_emission_factor'   => $validated['n2o_emission_factor'],
            'co2_gwp'               => $validated['co2_gwp'],
            'ch4_gwp'               => $validated['ch4_gwp'],
            'n2o_gwp'               => $validated['n2o_gwp'],
            'emission_tco2e'        => $emission_tco2e,
        ]);

        return response()->json([
            'message' => 'Scope 3 Emission Data Saved Successfully.',
            'data' => [
                'record' => $record,
                'calculation' => [
                    'Travel Type'           => $validated['travel_type'],
                    'Travel Distance (miles)' => $distance,
                    'CO2 (tCO2e)'           => round($co2 / 1000, 4),
                    'CH4 (tCO2e)'           => round($ch4 / 1000, 4),
                    'N2O (tCO2e)'           => round($n2o / 1000, 4),
                    'Total (tCO2e)'         => round($emission_tco2e, 4),
                ],
            ],
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Server error: ' . $e->getMessage()
        ], 500);
    }
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



public function scope1ReferenceDetails(Request $request)
{
    $companyId = auth()->user()->company_id;

    $records = Scope1Emission::where('company_id', $companyId)
        ->select(
            'year',
            'month',
            'quarter',
            'parameter',
            'fuel_type',
            DB::raw('SUM(fuel_liters_used) as total_liters'),
            DB::raw('MAX(co2_emission_factor) as co2_emission_factor'),
            DB::raw('MAX(ch4_emission_factor) as ch4_emission_factor'),
            DB::raw('MAX(n2o_emission_factor) as n2o_emission_factor'),
            DB::raw('MAX(co2_gwp) as co2_gwp'),
            DB::raw('MAX(ch4_gwp) as ch4_gwp'),
            DB::raw('MAX(n2o_gwp) as n2o_gwp')
        )
        ->groupBy('year', 'month', 'quarter', 'parameter', 'fuel_type')
        ->orderBy('year')
        ->orderBy('month')
        ->get();

    $response = [];

    foreach ($records as $rec) {
        $co2_factor = floatval($rec->co2_emission_factor);
        $ch4_factor = floatval($rec->ch4_emission_factor);
        $n2o_factor = floatval($rec->n2o_emission_factor);

        $co2_gwp = floatval($rec->co2_gwp);
        $ch4_gwp = floatval($rec->ch4_gwp);
        $n2o_gwp = floatval($rec->n2o_gwp);

        // Calculate emissions in kg
        $co2_kg = $rec->total_liters * $co2_factor * $co2_gwp;
        $ch4_kg = $rec->total_liters * $ch4_factor * $ch4_gwp;
        $n2o_kg = $rec->total_liters * $n2o_factor * $n2o_gwp;

        // Convert to tons
        $co2_ton = $co2_kg / 1000;
        $ch4_ton = $ch4_kg / 1000;
        $n2o_ton = $n2o_kg / 1000;

        $totalEmission_ton = $co2_ton + $ch4_ton + $n2o_ton;

        $response[] = [
            'year' => $rec->year,
            'month' => $rec->month,
            'quarter' => $rec->quarter,
            'parameter' => $rec->parameter,
            'fuel_type' => $rec->fuel_type,
            'total_liters' => (float) $rec->total_liters,
            'emissions_kg' => [
                'co2' => round($co2_kg, 2),
                'ch4' => round($ch4_kg, 2),
                'n2o' => round($n2o_kg, 2),
            ],
            'emissions_ton' => [
                'co2' => round($co2_ton, 3),
                'ch4' => round($ch4_ton, 4),
                'n2o' => round($n2o_ton, 4),
            ],
            'emission_tco2e_kg' => round($co2_kg + $ch4_kg + $n2o_kg, 2),
            'emission_tco2e_ton' => round($totalEmission_ton, 3),
            'emission_factors' => [
                'co2' => $co2_factor,
                'ch4' => $ch4_factor,
                'n2o' => $n2o_factor,
            ],
            'gwp' => [
                'co2' => $co2_gwp,
                'ch4' => $ch4_gwp,
                'n2o' => $n2o_gwp,
            ],
        ];
    }

    return response()->json($response);
}









  





public function scope2ReferenceDetails(Request $request)
{
    $companyId = auth()->user()->company_id;

    $records = Scope2Emission::where('company_id', $companyId)
        ->select(
            'year',
            'quarter',
            'month',
            DB::raw('SUM(electricity_kwh) as total_kwh'),
            DB::raw('AVG(emission_factor) as avg_factor')
        )
        ->groupBy('year', 'quarter', 'month')
        ->orderBy('year')
        ->orderBy('quarter')
        ->orderBy('month')
        ->get();

    $response = [];

    foreach ($records as $rec) {
        $emission_tco2e = $rec->total_kwh * $rec->avg_factor;

        $response[] = [
            'year' => $rec->year,
            'quarter' => $rec->quarter,
            'month' => $rec->month,
            'total_kwh' => round($rec->total_kwh, 2),
            'emission_factor' => $rec->avg_factor,
            'emission_tco2e' => round($emission_tco2e, 4),
        ];
    }

    return response()->json($response);
}


public function scope3ReferenceDetails(Request $request)
{
    $companyId = auth()->user()->company_id;

    // Fetch records grouped by year, quarter, month, travel_type, summing distance
    $records = Scope3Emission::where('company_id', $companyId)
        ->select(
            'year',
            'quarter',
            'month',
            'travel_type',
            DB::raw('SUM(travel_distance_miles) as total_distance'),
            DB::raw('MAX(co2_emission_factor) as co2_emission_factor'),
            DB::raw('MAX(ch4_emission_factor) as ch4_emission_factor'),
            DB::raw('MAX(n2o_emission_factor) as n2o_emission_factor'),
            DB::raw('MAX(co2_gwp) as co2_gwp'),
            DB::raw('MAX(ch4_gwp) as ch4_gwp'),
            DB::raw('MAX(n2o_gwp) as n2o_gwp')
        )
        ->groupBy('year', 'quarter', 'month', 'travel_type')
        ->orderBy('year')
        ->orderBy('quarter')
        ->orderBy('month')
        ->get();

    $response = [];

    foreach ($records as $rec) {
        // Calculate emissions per gas in kg
        $co2_kg = $rec->total_distance * $rec->co2_emission_factor * $rec->co2_gwp;
        $ch4_kg = $rec->total_distance * $rec->ch4_emission_factor * $rec->ch4_gwp;
        $n2o_kg = $rec->total_distance * $rec->n2o_emission_factor * $rec->n2o_gwp;

        // Convert to tons
        $co2_ton = $co2_kg / 1000;
        $ch4_ton = $ch4_kg / 1000;
        $n2o_ton = $n2o_kg / 1000;

        $total_emission_ton = $co2_ton + $ch4_ton + $n2o_ton;

        $response[] = [
            'year' => $rec->year,
            'quarter' => $rec->quarter,
            'month' => $rec->month,
            'travel_type' => $rec->travel_type,
            'total_distance_miles' => (float)$rec->total_distance,
            'emissions_kg' => [
                'co2' => round($co2_kg, 2),
                'ch4' => round($ch4_kg, 2),
                'n2o' => round($n2o_kg, 2),
            ],
            'emissions_ton' => [
                'co2' => round($co2_ton, 3),
                'ch4' => round($ch4_ton, 4),
                'n2o' => round($n2o_ton, 4),
            ],
            'emission_tco2e_kg' => round($co2_kg + $ch4_kg + $n2o_kg, 2),
            'emission_tco2e_ton' => round($total_emission_ton, 3),
            'emission_factors' => [
                'co2' => $rec->co2_emission_factor,
                'ch4' => $rec->ch4_emission_factor,
                'n2o' => $rec->n2o_emission_factor,
            ],
            'gwp' => [
                'co2' => $rec->co2_gwp,
                'ch4' => $rec->ch4_gwp,
                'n2o' => $rec->n2o_gwp,
            ],
        ];
    }

    return response()->json($response);
}






public function scopes3ReferenceDetails(Request $request)
{
    $companyId = auth()->user()->company_id;

    $records = Scope3Emission::where('company_id', $companyId)
        ->select('year', 'travel_type', DB::raw('SUM(distance_km) as total_distance'), 'emission_factor')
        ->groupBy('year', 'travel_type', 'emission_factor')
        ->get();

    $response = [];

    foreach ($records as $rec) {
        // Assume emission factor is in kg CO₂e per km — convert to tonnes
        $totalEmissionKg = $rec->total_distance * $rec->emission_factor;
        $emission_tco2e = $totalEmissionKg / 1000;

        $response[] = [
            'year' => $rec->year,
            'travel_type' => $rec->travel_type,
            'total_distance_km' => $rec->total_distance,
            'emission_factor' => $rec->emission_factor,
            'emission_tco2e' => round($emission_tco2e, 3), // tCO₂e
        ];
    }

    return response()->json($response);
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
