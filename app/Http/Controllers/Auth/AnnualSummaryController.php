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
use App\Models\TreeGrowth;

class AnnualSummaryController extends Controller
{

    // In DashboardController.php
public function fetchDashboardData()
{
    $companyId = auth()->user()->company_id;

    // GHG Emissions
   // Calculate Scope 1 Emissions 
   $scope1Emission = DB::table('scope1')
   ->where('company_id', $companyId)
   ->sum('emission_tco2e'); // 

// Calculate Scope 2 Emissions 
$scope2Emission = DB::table('scope2_emission')
   ->where('company_id', $companyId)
   ->sum('emission_tco2e'); // Same for column name

// Calculate Scope 3 Emissions 
$scope3Emission = DB::table('scope3_emission')
   ->where('company_id', $companyId)
   ->sum('emission_tco2e'); // Same for column name

// Calculate the total GHG emission by summing Scope 1, 2, and 3 emissions
$totalEmission = $scope1Emission + $scope2Emission + $scope3Emission;

    // Carbon Sequestration
    $plantationIds = Plantation::where('company_id', $companyId)->pluck('id');
    $totalSequestrationKg = 0;

    foreach ($plantationIds as $pid) {
        $trees = TreeGrowth::where('plantation_id', $pid)->get();
        foreach ($trees as $tree) {
            $AGB = 34.4703 - (8.0671 * $tree->dbh) + (0.6589 * pow($tree->dbh, 2));
            $BGB = $AGB * 0.15;
            $biomass = $AGB + $BGB;
            $carbon = $biomass * 0.5;
            $co2 = $carbon * 3.67;
            $totalSequestrationKg += $co2;
        }
    }

    $totalSequestrationTon = $totalSequestrationKg / 1000;
    $carbonVariance = $totalSequestrationTon - $totalEmission;

    $nationalGHG = 256150000; // 256.15 million tonnes in CO₂ equivalent
    $percentageContribution = $nationalGHG > 0
        ? ($totalEmission / $nationalGHG) * 100
        : 0;

        Log::debug('Dashboard Data:', [
            'totalEmission' => round($totalEmission, 2),
            'total_sequestration' => round($totalSequestrationTon, 2),
            'carbon_variance' => round($carbonVariance, 2),
            'percentage_contribution' => round($percentageContribution, 2),
        ]);

    return response()->json([
        'totalEmission' => round($totalEmission, 2),
        'total_sequestration' => round($totalSequestrationTon, 2),
        'carbon_variance' => round($carbonVariance, 2),
        'percentage_contribution' => round($percentageContribution, 2),
    ]);
}


    
    
    
    }