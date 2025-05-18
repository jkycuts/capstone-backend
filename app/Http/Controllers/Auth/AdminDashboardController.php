<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AnnualSummary;
use App\Models\Scope1Emission;
use App\Models\Scope2Emission;
use App\Models\Scope3Emission;
use App\Models\Plantation;
use Illuminate\Support\Facades\DB;
use App\Models\MiningCompany;
use App\Models\TreeGrowth;
use Illuminate\Http\JsonResponse;


class AdminDashboardController extends Controller
{
    public function getAdminGHGSummary()
{
    // Sum total emissions across all companies
    $totalScope1 = Scope1Emission::sum('emission_tco2e');
    $totalScope2 = Scope2Emission::sum('emission_tco2e');
    $totalScope3 = Scope3Emission::sum('emission_tco2e');

    $totalEmissions = $totalScope1 + $totalScope2 + $totalScope3;

    // Sum total carbon sequestration across all companies
    $totalSequestration = TreeGrowth::sum('CO2_sequestration'); // Adjust to your actual column name

    // Compute Net Variance
    $netVariance = $totalSequestration - $totalEmissions;

    // Optional: Define PH national GHG emission baseline (example: 150M tCO₂e)
    $nationalGHG = 150000000; // Update this to real data or config value

    // Compute % Contribution
    $percentContribution = $nationalGHG > 0 
        ? ($totalEmissions / $nationalGHG) * 100 
        : 0;

    return response()->json([
        'total_emissions' => round($totalEmissions, 2),
        'total_sequestration' => round($totalSequestration, 2),
        'net_variance' => round($netVariance, 2),
        'national_contribution_percent' => round($percentContribution, 4),
    ]);
}



public function getCompanySummaries(Request $request): JsonResponse
{
    $year = $request->input('year');
    
    // Default to current year if not a valid number
    if (empty($year) || !is_numeric($year)) {
        $year = now()->year;
    }

    $companies = MiningCompany::select('id', 'name')->get();

    $data = $companies->map(function ($company) use ($year) {
        $scope1 = Scope1Emission::where('company_id', $company->id)
            ->where('year', $year)
            ->sum('emission_tco2e') ?? 0;

        $scope2 = Scope2Emission::where('company_id', $company->id)
            ->where('year', $year)
            ->sum('emission_tco2e') ?? 0;

        $scope3 = Scope3Emission::where('company_id', $company->id)
            ->where('year', $year)
            ->sum('emission_tco2e') ?? 0;

        $totalEmissions = $scope1 + $scope2 + $scope3;

        // Filter TreeGrowth by year and company
        $treeGrowths = TreeGrowth::where('year_recorded', $year) // <- Year filter here
            ->whereHas('plantation', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })->get();

        $totalSequestration = 0;

        foreach ($treeGrowths as $tree) {
            $dbh = $tree->dbh;

            // Sequestration formula
            $AGB = 34.4703 - (8.0671 * $dbh) + (0.6589 * pow($dbh, 2));
            $BGB = $AGB * 0.15;
            $biomass = $AGB + $BGB;
            $carbon = $biomass * 0.5;
            $co2 = $carbon * 3.67;

            $totalSequestration += $co2;
        }

        $netVariance = $totalSequestration - $totalEmissions;

        $status = $netVariance >= 0 ? 'Carbon Neutral' : 'Not Neutral';
        $statusColor = $netVariance >= 0 ? 'green' : 'red';

        return [
            'company_id'          => $company->id,
            'company_name'        => $company->name,
            'year'                => $year,
            'total_emissions'     => round($totalEmissions, 2),
            'total_sequestration' => round($totalSequestration, 2),
            'net_variance'        => round($netVariance, 2),
            'status'              => $status,
            'status_color'        => $statusColor,
        ];
    });

    return response()->json($data);
}




public function getAvailableYears(): JsonResponse
{
    try {
        // Use select('year') directly if year is stored as INT
        $scope1Years = DB::table('scope1')
            ->select('year')
            ->whereNotNull('year')
            ->distinct()
            ->pluck('year');

        $scope2Years = DB::table('scope2_emission')
            ->select('year')
            ->whereNotNull('year')
            ->distinct()
            ->pluck('year');

        $scope3Years = DB::table('scope3_emission')
            ->select('year')
            ->whereNotNull('year')
            ->distinct()
            ->pluck('year');

        $treeGrowthYears = DB::table('tree_growth')
            ->select('year_recorded as year') // Use alias if needed
            ->whereNotNull('year_recorded')
            ->distinct()
            ->pluck('year');

        // Merge all years, remove duplicates, sort descending
        $allYears = collect()
            ->merge($scope1Years)
            ->merge($scope2Years)
            ->merge($scope3Years)
            ->merge($treeGrowthYears)
            ->unique()
            ->sortDesc()
            ->values();

        return response()->json($allYears->map(fn($year) => ['year' => $year]));
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to fetch years',
            'details' => $e->getMessage(),
        ], 500);
    }
}




}
