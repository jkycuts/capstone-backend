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
    $totalEmission = DB::table('ghg_emission')
        ->where('company_id', $companyId)
        ->sum('total_tco2');

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

    return response()->json([
        'total_emission' => round($totalEmission, 2),
        'total_sequestration' => round($totalSequestrationTon, 2),
        'carbon_variance' => round($carbonVariance, 2),
        'percentage_contribution' => round($percentageContribution, 2),
    ]);
}


    
    
    
    }