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
        'emission_factor'           => 'required|numeric|min:0',
        'gwp'                       => 'required|numeric|min:0',
        'month'                     => 'nullable|string',
        'quarter'                   => 'nullable|string',
    ]);

    // Ensure month and quarter are set based on mode
    $month = ($validated['mode'] === 'monthly') ? $validated['month'] : null;
    $quarter = ($validated['mode'] === 'quarterly') ? $validated['quarter'] : null;

    // Ensure authenticated user
    if (!Auth::check()) {
        return response()->json(['error' => 'User not authenticated.'], 401);
    }

    $companyId = Auth::user()->company_id;

    $litersUsed      = $validated['fuel_liters_used'];
    $emissionFactor  = $validated['emission_factor'];
    $gwp             = $validated['gwp'];

    // Compute total emissions (tCO2e)
    $emissionTotal = ($litersUsed * $emissionFactor * $gwp) / 1000;

    // Save to DB
    $scope1 = Scope1Emission::create([
        'company_id'        => $companyId,
        'year'              => $validated['year'],
        'mode'              => $validated['mode'],
        'month'             => $validated['mode'] === 'monthly' ? $validated['month'] : null,
        'quarter'           => $validated['mode'] === 'quarterly' ? $validated['quarter'] : null,
        'parameter'         => ucfirst($validated['parameter']),
        'fuel_type'         => strtolower($validated['fuel_type']),
        'fuel_liters_used'  => $litersUsed,
        'emission_factor'   => $emissionFactor,
        'gwp'               => $gwp,
        'emission_tco2e'    => round($emissionTotal, 4),
    ]);

    return response()->json([
        'message' => 'Scope 1 Emission Data Saved Successfully.',
        'data' => [
            'record' => $scope1,
            'calculation' => [
                'Fuel Used (liters)' => $litersUsed,
                'Emission Factor'    => $emissionFactor,
                'GWP'                => $gwp,
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
        'year' => 'required|integer',
        'mode' => 'required|in:monthly,quarterly',
        'month' => 'nullable|string',
        'quarter' => 'nullable|string',
        'travel_type' => 'required|in:short,medium,long',
        'travel_distance_miles' => 'required|numeric|min:0',
        'gas_type' => 'required|in:co2,ch4,n2o',
        'emission_factor' => 'required|numeric|min:0',
        'gwp' => 'nullable|numeric|min:0',
    ]);

    $companyId = auth()->user()->company_id;

    $gwp = $request->input('gwp'); 
    $travel_distance = $validated['travel_distance_miles'];
    $emission_factor = $validated['emission_factor'];

    // Emissions = distance × emission factor × GWP
    $emissions_kg = $travel_distance * $emission_factor * $gwp;
    $emissions_tco2e = $emissions_kg / 1000;

    Scope3Emission::create([
        'company_id' => $companyId,
        'year' => $validated['year'],
        'mode' => $validated['mode'],
        'month' => $validated['month'],
        'quarter' => $validated['quarter'],
        'travel_type' => $validated['travel_type'],
        'travel_distance_miles' => $travel_distance,
        'gas_type' => $validated['gas_type'],
        'emission_factor' => $emission_factor,
        'gwp' => $gwp,
        'emission_tco2e' => $emissions_tco2e,
    ]);

    return response()->json(['message' => 'Scope 3 travel emissions recorded successfully.']);
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
        ->select('year', 'parameter', 'fuel_type', DB::raw('SUM(fuel_liters_used) as total_liters'))
        ->groupBy('year', 'parameter', 'fuel_type')
        ->get();

    // Emission factors and GWP values
    $emissionFactors = [
        'diesel' => ['co2' => 2.68, 'ch4' => 0.0001, 'n2o' => 0.0002],
        'gasoline' => ['co2' => 2.31, 'ch4' => 0.0002, 'n2o' => 0.0001],
        'biodiesel' => ['co2' => 2.50, 'ch4' => 0.00015, 'n2o' => 0.0001],
        'ethanol' => ['co2' => 1.50, 'ch4' => 0.0001, 'n2o' => 0.0001],
    ];

    $gwp = ['co2' => 1, 'ch4' => 25, 'n2o' => 298];

    $response = [];

    foreach ($records as $rec) {
        $fuel = strtolower($rec->fuel_type);
        $factors = $emissionFactors[$fuel] ?? ['co2' => 0, 'ch4' => 0, 'n2o' => 0];

        // Calculate the emissions
        $co2 = $rec->total_liters * $factors['co2'] * $gwp['co2'];
        $ch4 = $rec->total_liters * $factors['ch4'] * $gwp['ch4'];
        $n2o = $rec->total_liters * $factors['n2o'] * $gwp['n2o'];

        // Convert each emission to tons (by dividing by 1000)
        $co2_ton = $co2 / 1000;
        $ch4_ton = $ch4 / 1000;
        $n2o_ton = $n2o / 1000;

        // Calculate the total emissions in tons
        $totalEmission = $co2_ton + $ch4_ton + $n2o_ton;

        $response[] = [
            'year' => $rec->year,
            'parameter' => $rec->parameter,
            'fuel_type' => $rec->fuel_type,
            'total_liters' => $rec->total_liters,
            'emissions' => [
                'co2' => round($co2_ton, 3),  // Rounded to 3 decimal places
                'ch4' => round($ch4_ton, 4),  // Rounded to 4 decimal places
                'n2o' => round($n2o_ton, 4),  // Rounded to 4 decimal places
            ],
            'emission_tco2e' => round($totalEmission, 3),  // Total emission in tons (tCO2e)
            'emission_factors' => $factors,
            'gwp' => $gwp,
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

    

    // Fetch records for the specified company
    $records = Scope3Emission::where('company_id', $companyId)
        ->select(
            'year',
            'quarter',
            'month',
            'travel_type',
            'travel_distance_miles',
            'emission_factor',
            'gwp',
            'gas_type'
        )
        ->get();

    // Initialize the grouped data array
    $grouped = [];

    // Process the records and calculate emissions
    foreach ($records as $record) {
        // Use a composite key to group by year, quarter, month, and travel type
        $key = "{$record->year}-{$record->quarter}-{$record->month}-{$record->travel_type}";

        // Initialize the group if it doesn't exist
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'year' => $record->year,
                'category' => $record->travel_type,
                'quarter' => $record->quarter,
                'month' => $record->month,
                'total_distance' => 0,
                'emissions' => [
                    'co2' => ['value' => 0, 'gwp' => null, 'emission_factor' => null],
                    'ch4' => ['value' => 0, 'gwp' => null, 'emission_factor' => null],
                    'n2o' => ['value' => 0, 'gwp' => null, 'emission_factor' => null],
                ],
            ];
        }

        // Get the gas type and make sure it's in the acceptable list
        $gas = strtolower($record->gas_type);
        if (!in_array($gas, ['co2', 'ch4', 'n2o'])) continue;

        // Calculate the emission for the given gas
        $emission = $record->travel_distance_miles * $record->emission_factor * $record->gwp;

        // Add to the corresponding group
        $grouped[$key]['total_distance'] += $record->travel_distance_miles;
        $grouped[$key]['emissions'][$gas]['value'] += $emission;
        $grouped[$key]['emissions'][$gas]['gwp'] = $record->gwp;
        $grouped[$key]['emissions'][$gas]['emission_factor'] = $record->emission_factor;
    }

    // Calculate the total emissions for each group
    foreach ($grouped as &$group) {
        $totalEmissions = 0;
        foreach ($group['emissions'] as $gas => $emissionData) {
            $totalEmissions += $emissionData['value']; // Sum up all gas emissions
        }
        $group['emission_tco2e'] = $totalEmissions; // Store the total emissions in the group
    }

    // Return the grouped data as a JSON response
    return response()->json(array_values($grouped));
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
