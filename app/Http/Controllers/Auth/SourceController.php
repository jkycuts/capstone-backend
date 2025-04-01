<?php

namespace App\Http\Controllers;

use App\Models\Source;
use Illuminate\Http\Request;

class SourceController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string',
            'fuel_type'     => 'required|string',
            'companyID'     => 'required|integer'
        ]);

        $source = Source::create($request->all());

        return response()->json([
            'message'       => 'Source created successfully',
            'source'        => $source
        ], 201);
    }

    public function calculateEmissions($id, $year)
    {
        $source = Source::findOrFail($id);
        $emissions = $source->calculateYearlyEmissions($year);

        return response()->json([
            'source'        => $source->name,
            'fuel_type'     => $source->fuel_type,
            'year'          => $year,
            'emissions'     => $emissions
        ]);
    }
}
