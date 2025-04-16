<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AnnualSummary;

class AnnualSummaryController extends Controller
{
    public function latest()
    {
        $summary = AnnualSummary::latest('year')->first();

        // Optional: Hardcoded PH national GHG total (TCO₂) - Replace with actual value
        $nationalGHGTotal = 139000000;

        if ($summary) {
            return response()->json([
                'company_name'                  => $summary->name,
                'year'                          => $summary->year,
                'carbon_emission'               => $summary->carbon_emission,
                'carbon_sequestration'          => $summary->carbon_sequestration,
                'carbon_neutrality_variance'    => $summary->carbon_emission - $summary->carbon_sequestration,
                'percentage_ghg_contribution'   => ($summary->carbon_emission / $nationalGHGTotal) * 100
            ]);
        } else {
            return response()->json([
                'message' => 'No summary data found.'
            ], 404);
        }
    }
}
