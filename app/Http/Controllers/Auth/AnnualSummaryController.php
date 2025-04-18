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

    public function getByYear(Request $request)
{
    try {
        $year = $request->query('year');

        $summaries = AnnualSummary::with('company')
            ->when($year, function ($query) use ($year) {
                return $query->where('year', $year);
            })
            ->get();

        $mapped = $summaries->map(function ($item) {
            return [
                'year' => $item->year,
                'company_name' => optional($item->company)->name ?? 'Unknown',
                'total_tco2' => $item->total_tco2,
                'carbon_sequestered_tco2' => $item->carbon_sequestered_tco2,
                'carbon_neutrality_variance' => $item->carbon_neutrality_variance,
                'ghg_country_percent' => $item->ghg_country_percent,
            ];
        });

        return response()->json($mapped);
    } catch (\Exception $e) {
        Log::error("Annual Summary API Error: " . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(['error' => 'Server error'], 500);
    }
    
}


public function getQuarterlySummary(Request $request)
{
    try {
        $year = $request->query('year');
        $quarter = $request->query('quarter'); // Quarter input (Q1, Q2, Q3, Q4)

        // Define the start and end months for each quarter
        $quarters = [
            'Q1' => ['01', '03'], // January to March
            'Q2' => ['04', '06'], // April to June
            'Q3' => ['07', '09'], // July to September
            'Q4' => ['10', '12'], // October to December
        ];

        // Ensure a valid quarter is passed
        if (!isset($quarters[$quarter])) {
            return response()->json(['error' => 'Invalid quarter specified'], 400);
        }

        // Get the months for the selected quarter
        list($startMonth, $endMonth) = $quarters[$quarter];

        // Fetch the summary data for the selected year and quarter
        $summaries = AnnualSummary::with('company')
            ->where('year', $year)
            ->whereBetween(DB::raw('MONTH(created_at)'), [$startMonth, $endMonth])
            ->get();

        // Aggregate the results by summing the values
        $aggregatedSummary = $summaries->reduce(function ($carry, $item) {
            $carry['total_tco2'] += $item->total_tco2;
            $carry['carbon_sequestered_tco2'] += $item->carbon_sequestered_tco2;
            $carry['carbon_neutrality_variance'] += $item->carbon_neutrality_variance;
            $carry['ghg_country_percent'] += $item->ghg_country_percent;

            return $carry;
        }, [
            'total_tco2' => 0,
            'carbon_sequestered_tco2' => 0,
            'carbon_neutrality_variance' => 0,
            'ghg_country_percent' => 0,
        ]);

        // Format the final response to return aggregated data
        $aggregatedSummary['year'] = $year;
        $aggregatedSummary['quarter'] = $quarter;
        $aggregatedSummary['company_name'] = 'Aggregated Data'; // Or any logic to show a specific company

        return response()->json($aggregatedSummary);
    } catch (\Exception $e) {
        Log::error("Annual Summary API Error: " . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(['error' => 'Server error'], 500);
    }
}


public function getAnnualSummary(Request $request)
{
    $year = $request->query('year');

    // Fetch the data for the selected year
    $summary = AnnualSummary::where('year', $year)->get();

    return response()->json($summary);
}

    
    
    
    }