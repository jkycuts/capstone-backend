<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MiningCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    
       // Ensure only super admins can access this route
    public function index(Request $request)
    {
        // Get the currently authenticated user
        $user = Auth::user();

        // Check if the user is a super_admin
        if ($user->role !== 'super_admin') {
            return redirect('/');  // Redirect to regular user page or login page
        }

        // Return the admin dashboard view if the user is a super admin
        return view('admin.dashboard');
    }
    

    public function someAdminAction(Request $request)
    {
        // Get the user from the token (through Auth)
        $user = Auth::user();

        // Check if the user is not an admin
        if ($user->role !== 'super_admin') {
            return response()->json(['message' => 'Access Denied'], 403);
        }

        // Proceed with admin-specific logic
        return response()->json(['message' => 'Access granted for admin actions.']);
    } 
}