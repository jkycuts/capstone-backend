<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Source;

class SourceController extends Controller
{
    public function getEmission($sourceID)
    {
        // Find the emission source by ID
        $source = Source::findOrFail($sourceID);

        // Calculate emissions
        $totalEmission = $source->calculateEmissions();

        // Return response
        return response()->json([
            'source' => $source->source_name,
            'fuel_type' => $source->fuel_type,
            'fuel_consumed' => $source->fuel_consumption,
            'electricity_consumed' => $source->electricity_usage,
            'total_emission' => $totalEmission,
        ]);
    }
}
