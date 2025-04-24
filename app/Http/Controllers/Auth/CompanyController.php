<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CompanyRequest;
use App\Models\MiningCompany;
use Illuminate\Support\Facades\Log;



class CompanyController extends Controller
{

        // Laravel controller example
public function show($id)
{
    $company = MiningCompany::find($id);

    if (!$company) {
        return response()->json(['message' => 'Company not found'], 404);
    }

    return response()->json($company);
}






    public function store(CompanyRequest $request)
    {
        try {
            $validated = $request->validated();
            $user = Auth::user();
    
            if (!$user) {
                Log::error("Unauthenticated user tried to create company");
                return response()->json(['message' => 'Unauthorized'], 401);
            }
    
            if ($user->miningCompany) {
                return response()->json(['message' => 'User already has a mining company'], 400);
            }
    
            $company = MiningCompany::create([
                'name'     => $validated['name'],
                'location' => $validated['location'],
                'user_id'  => $user->id,
            ]);
    
            return response()->json($company, 201);
    
        } catch (\Exception $e) {
            Log::error("Company store error: " . $e->getMessage());
            return response()->json(['message' => 'Server error occurred'], 500);
        }
    }
}
