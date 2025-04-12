<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AnnualSummary;

class AnnualSummaryController extends Controller
{
    public function index()
    {
        $summaries = AnnualSummary::orderBy('year', 'desc')->get();
        return view('annual_summaries.index', compact('summaries'));
    }

    public function create()
    {
        return view('annual_summaries.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|unique:annual_summaries',
            'annual_carbon_emission' => 'required|numeric',
            'annual_carbon_sequestration' => 'required|numeric',
            'carbon_neutrality_variance' => 'required|numeric',
            'percentage_ghg_contribution' => 'required|numeric',
        ]);

        AnnualSummary::create($validated);

        return redirect()->route('annual-summaries.index')->with('success', 'Annual summary added successfully.');
    }

    public function show(AnnualSummary $annualSummary)
    {
        return view('annual_summaries.show', compact('annualSummary'));
    }

    public function edit(AnnualSummary $annualSummary)
    {
        return view('annual_summaries.edit', compact('annualSummary'));
    }

    public function update(Request $request, AnnualSummary $annualSummary)
    {
        $validated = $request->validate([
            'year' => 'required|integer|unique:annual_summaries,year,' . $annualSummary->id,
            'annual_carbon_emission' => 'required|numeric',
            'annual_carbon_sequestration' => 'required|numeric',
            'carbon_neutrality_variance' => 'required|numeric',
            'percentage_ghg_contribution' => 'required|numeric',
        ]);

        $annualSummary->update($validated);

        return redirect()->route('annual-summaries.index')->with('success', 'Annual summary updated.');
    }

    public function destroy(AnnualSummary $annualSummary)
    {
        $annualSummary->delete();
        return redirect()->route('annual-summaries.index')->with('success', 'Annual summary deleted.');
    }
}
