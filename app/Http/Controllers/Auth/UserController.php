<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\MiningCompany;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return User::with('company')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request)
    {
        $validated = $request->validated();

    $validated['password'] = Hash::make($validated['password']);

        // Default to "user" role if not provided
        if (!isset($validated['role'])) {
        $validated['role'] = 'user';
        }

        $user = User::create($validated);

        return $user;
    }

     /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return User::find($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, string $id)
    {
        $user = User::findORFail($id);

        $validated = $request->validated();

        $user->name = $validated['name'];

        $user->save();

        return $user;
    }

    /**
     * Update the email resource in storage.
     */
    public function email(UserRequest $request, string $id)
    {

        $user = User::findORFail($id);

        $validated = $request->validated();

        $user->email = $validated['email'];

        $user->save();

        return $user;
    }

    /**
     * Update the password resource in storage.
     */
    public function password(UserRequest $request, string $id)
    {

        $user = User::findORFail($id);

        $validated = $request->validated();

        $validated['password'] = Hash::make($validated['password']);

        $user->save();

        return $user;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findORFail($id);

        $user->delete();

        return $user;
    }

    /**
     * Update the image of the specified resource from storage.
     */
    public function image(UserRequest $request, string $id)
    {
        $user = User::findORFail($id);

        if (!is_null($user->image)) {
            Storage::disk('public')->delete($user->image);
        }

        $user->image = $request->file('image')->storePublicly('images', 'public');


        $user->save();

        return $user;
    }

    // In your controller (e.g., UserController.php)
public function getUserCompanies(Request $request)
{
    $userId = auth()->id(); // Get the logged-in user's ID
    
    // Fetch companies associated with the user
    $companies = MiningCompany::where('user_id', $userId)
                        ->where(function ($query) use ($request) {
                            if ($request->search) {
                                $query->where('name', 'like', '%' . $request->search . '%');
                            }
                            if ($request->industry) {
                                $query->where('industry', $request->industry);
                            }
                        })
                        ->get();
    
    return response()->json($companies);
}


}
