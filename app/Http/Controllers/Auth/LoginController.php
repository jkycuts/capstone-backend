<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{

    public function superAdminLogin(Request $request)
    {
        $credentials = $request->only('email', 'password');

    if (!Auth::attempt($credentials)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    

    $user = Auth::user();
    $token = $user->createToken('authToken')->plainTextToken;

    return response()->json([
        'token' => $token,
        'role' => $user->role,
        'user' => $user,
    ], 200);
    
    }
    
        
}
