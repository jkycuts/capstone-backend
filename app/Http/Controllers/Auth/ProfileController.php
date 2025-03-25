<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
     /**
     * 
     * Update the image of the Token Bearer resource.
     */
    public function image(UserRequest $request)
    {
        $user = User::findORFail($request->user()->id);

        if (!is_null($user->image)) {
            Storage::disk('public')->delete($user->image);
        }

        $user->image = $request->file('image')->storePublicly('images', 'public');


        $user->save();

        return $user;
    }

    /**
     * Display the specified information of a token bearer.
     */
    public function show(request $request)
    {
        return $request->user();
    }
}
