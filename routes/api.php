<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\auth\ProfileController;
use App\Http\Controllers\auth\AuthController;
use App\Models\MiningCompany;
use App\Http\Controllers\auth\CompanyController;
use App\Http\Controllers\auth\SourceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public APIs
    Route::post('/login', [AuthController::class, 'login'])->name('user.login');
    Route::post('/user', [UserController::class, 'store'])->name('user.store');

// // Private APIs
    Route::middleware(['auth:sanctum'])->group(function () {
         Route::get('/logout',         [AuthController::class, 'logout']);
         Route::post('/company',        [CompanyController::class, 'store']); // Assign company to user
         Route::get('/user/company',    [CompanyController::class, 'show']); // Get user's company

    // Admin APIs
    Route::controller(UserController::class)->group(function () {
        Route::get('/user',                 'index');
        Route::get('/user/{id}',            'show');
        Route::put('/user/{id}',            'update')->name('user.update');
        Route::put('/user/email/{id}',      'email')->name('user.email');
        Route::put('/user/password/{id}',   'password')->name('user.password');
        Route::put('/user/image/{id}',      'image')->name('user.image');
        Route::delete('/user/{id}',         'destroy');
    });

    // User Specific APIs
    Route::get('/profile/show',  [ProfileController::class, 'show']);
    Route::put('/profile/image', [ProfileController::class, 'image'])->name('profile.image');

    // Mining Company APIs

    // Source APIs
    Route::controller('source')->group(function () {

        Route::get('/source',           [SourceController::class, 'index']); // Get all source
        Route::post('/source',          [SourceController::class, 'store']); // Create a new source
        Route::get('/source/{id}',      [SourceController::class, 'show']); // Get a single source
        Route::put('/source/{id}',      [SourceController::class, 'update']); // Update source
        Route::delete('/source{id}',    [SourceController::class, 'destroy']); // Delete source
    });
    


});
