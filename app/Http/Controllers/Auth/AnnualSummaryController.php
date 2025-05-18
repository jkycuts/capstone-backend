<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AnnualSummary;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\CarbonSequestration;
use App\Models\GhgEmission;
use Illuminate\Support\Facades\DB;
use App\Models\Plantation;
use App\Models\Scope3Emission;
use App\Models\TreeGrowth;
use App\Models\Scope1Emission;
use App\Models\Scope2Emission;

class AnnualSummaryController extends Controller
{

    // In DashboardController.php
public function fetchDashboardData()
{
    $user = Auth::user();
    $companyId = $user->company_id;

    // Scope 1
    $fuel = Scope1Emission::where('company_id', $companyId)->sum('emission_tco2e');

    // Scope 2
    $electricity = Scope2Emission::where('company_id', $companyId)->sum('emission_tco2e');

    // Scope 3
    $travel = Scope3Emission::where('company_id', $companyId)->sum('emission_tco2e');

    // Sum all emissions
    $totalEmission = $fuel + $electricity + $travel;

    // Dynamic Carbon Sequestration calculation for this company
    $treeGrowths = TreeGrowth::whereHas('plantation', function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
    })->get();

    $totalSequestration = 0;

    foreach ($treeGrowths as $tree) {
        $dbh = $tree->dbh;

        // Your sequestration formula (AGB, BGB, biomass, carbon, CO2)
        $AGB = 34.4703 - (8.0671 * $dbh) + (0.6589 * pow($dbh, 2));
        $BGB = $AGB * 0.15;
        $biomass = $AGB + $BGB;
        $carbon = $biomass * 0.5;
        $co2 = $carbon * 3.67; // in kg CO2

        $totalSequestration += $co2;
    }

    

    // Carbon Neutrality Variance
    $carbonVariance = $totalEmission - $totalSequestration;

    // National contribution (static divisor or configurable later)
    $nationalTotalGHG = 150000000; // Example: 150 million tCO2 national GHG
    $percentageContribution = $nationalTotalGHG > 0
        ? round(($totalEmission / $nationalTotalGHG) * 100, 6)
        : 0;

    return response()->json([
        'totalEmission' => round($totalEmission, 3),
        'total_sequestration' => round($totalSequestration, 3),
        'carbon_variance' => round($carbonVariance, 3),
        'percentage_contribution' => $percentageContribution,
    ]);
}

    
    
    
    }