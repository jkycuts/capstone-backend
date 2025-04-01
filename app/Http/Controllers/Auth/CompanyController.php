<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CompanyRequest;
use App\Models\MiningCompany;


class CompanyController extends Controller
{
    public function store(CompanyRequest $request)
    {
        // Get validated data
        $validated = $request->validated();

        // Get authenticated user
        $user = Auth::user();

        // Check if the user already has a mining company
        if ($user->Company) {
            return response()->json(['message' => 'User already has a mining company'], 400);
        }

        // Create a new mining company and link it to the user
        $company = MiningCompany::create([
            'name'          => $validated['name'],
            'location'      => $validated['location'],
            'user_id'       => $user->id,
        ]);

        return response()->json($company, 201);
    }

    public function showUserCompany()
    {
        $user = Auth::user();
        return response()->json($user->miningCompany);
    }
}
