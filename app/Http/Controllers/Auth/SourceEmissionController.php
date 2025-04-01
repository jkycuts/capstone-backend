<?php

namespace App\Http\Controllers;

use App\Models\SourceEmission;
use Illuminate\Http\Request;

class SourceEmissionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'source_id'             => 'required|exists:sources,id',
            'year'                  => 'required|integer',
            'quarter'               => 'required|string|in:Q1,Q2,Q3,Q4',
            'fuel_consumption'      => 'required|numeric',
            'electricity_usage'     => 'required|numeric'
        ]);

        $emission = SourceEmission::create($request->all());

        return response()->json([
            'message'   => 'Emission data stored successfully',
            'emission'  => $emission
        ], 201);
    }

    public function getBySource($sourceID, $year)
    {
        $emissions = SourceEmission::where('sourceID', $sourceID)
            ->where('year', $year)
            ->get();

        return response()->json($emissions);
    }
}
