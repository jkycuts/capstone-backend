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
    // Fetch all annual summaries for the authenticated user's company
    public function AnnualSummary(Request $request)
    {
        try {
            $summaries = AnnualSummary::with('company')->get();
    
            return response()->json([
                'success' => true,
                'data' => $summaries
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error.',
                'error' => $e->getMessage(),  // TEMPORARY: Remove this in production
            ], 500);
        }
    }

    // Generate annual summary for a given year
    public function generate(Request $request)
{
    $year = $request->input('year');
    $companyId = Auth::user()->company_id;

    // 1. Total Emissions for selected year and company
    $totalEmissions = GHGEmission::where('company_id', $companyId)
        ->where('year', $year)
        ->sum('total_tco2');

    // 2. Fetch all plantations planted in or before the selected year
    $plantations = Plantation::where('company_id', $companyId)
        ->whereYear('date_recorded', '<=', $year)
        ->pluck('id');

    // 3. Sum carbon sequestration from tree data for those plantations
    // Ensure you're summing the correct column for sequestration (replace 'total_sequestration' with the correct field)
    $totalSequestration = CarbonSequestration::whereIn('plantation_id', $plantations)
        ->sum('co2_sequestered');  // Ensure this column is correct

    // 4. Compute Carbon Variance (Sequestration minus Emissions)
    $carbonVariance = $totalSequestration - $totalEmissions;

    // 5. Percentage of national GHG emissions (fallback to avoid division by zero)
    $nationalTotal = GHGEmission::where('year', $year)->sum('total_tco2') ?: 1;
    $ghgContribution = ($totalEmissions / $nationalTotal) * 100;

    // 6. Save or update annual summary
    $summary = AnnualSummary::updateOrCreate(
        ['year' => $year, 'company_id' => $companyId],
        [
            'annual_carbon_emission' => $totalEmissions,
            'annual_carbon_sequestration' => $totalSequestration,
            'carbon_neutrality_variance' => $carbonVariance,
            'percentage_ghg_contribution' => $ghgContribution
        ]
    );

    Log::info('Generated Summary: ', ['summary' => $summary]);


    return response()->json([
        'message' => 'Annual summary generated successfully.',
        'data' => $summary
    ]);
}

    
}
