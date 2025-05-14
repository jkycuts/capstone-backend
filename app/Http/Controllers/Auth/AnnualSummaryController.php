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
        $totalEmission = $fuel + $electricity + $travel ;

        // Carbon Sequestration from tree planting
        $totalSequestration = TreeGrowth::where('plantation_id', $companyId)->sum('co2_sequestration');

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
            'percentage_contribution' => $percentageContribution
        ]);
    }

    
    
    
    }