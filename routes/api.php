<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\auth\ProfileController;
use App\Http\Controllers\auth\AuthController;
use App\Models\MiningCompany;
use App\Http\Controllers\auth\CompanyController;
use App\Http\Controllers\Auth\CarbonSequestrationController;
use App\Http\Controllers\Auth\GHGEmissionController;
use App\Http\Controllers\Auth\AnnualSummaryController;
use App\Http\Controllers\Auth\TreeGrowthController;



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
    Route::post('/login',   [AuthController::class, 'login'])->name('user.login');
    Route::post('/user',    [UserController::class, 'store'])->name('user.store');

// // Private APIs
    Route::middleware(['auth:sanctum'])->group(function () {
         Route::get('/logout',                   [AuthController::class, 'logout']);
        //  Route::get('/ghg-emission',             [GhgEmissionController::class, 'index']);
        //  Route::post('/ghg-emission',            [GhgEmissionController::class, 'store']);
        //  Route::get('/ghg-emission/{id}',        [GhgEmissionController::class, 'show']);
        //  Route::put('/ghg-emission/{id}',        [GhgEmissionController::class, 'update']);
        //  Route::delete('/ghg-emission/{id}',     [GhgEmissionController::class, 'destroy']);

         Route::post('/companies',               [CompanyController::class, 'store']); // Assign company to user
         Route::get('/companies/{id}',       [CompanyController::class, 'show']);


        // Plantation Routes
        Route::get('/plantation',                [CarbonSequestrationController::class, 'index']);
        Route::post('/plantation',               [CarbonSequestrationController::class, 'storePlantation']);
    
    // Tree Growth Routes
        Route::get('/tree-growth/{id}',         [CarbonSequestrationController::class, 'show']);
        Route::post('/tree-growth',             [CarbonSequestrationController::class, 'storeTreeGrowth']);
        Route::post('/tree-growth/{id}',         [CarbonSequestrationController::class, 'updateTreeGrowth']);
        Route::get('/tree-growth',         [CarbonSequestrationController::class, 'indexTreeGrowth']);

    // Carbon Sequestration Calculation
        Route::get('/carbon-sequestration/{plantationId}', [CarbonSequestrationController::class, 'calculateCarbonSequestration']);
       
        Route::get('/dashboard-summary', [AnnualSummaryController::class, 'fetchDashboardData']);

        Route::get('/calculate-annual-emissions/{year}', [CarbonSequestrationController::class, 'calculateAnnualEmissions']);

    // Scope 1 Emission
        Route::post('/ghg-emission/fuel',                           [GhgEmissionController::class, 'storeScope1']);
        Route::get('/ghg-emission/fuel',                                 [GhgEmissionController::class, 'getScope1Emissions']);

    // Scope 2 Emission
        Route::post('/ghg-emission/electricity',                        [GhgEmissionController::class, 'storeScope2Emission']);
        Route::get('/ghg-emission/electricity',                         [GhgEmissionController::class, 'getScope2Emissions']);

    // Scope 3 Emission
        Route::post('ghg-emission/travel', [GHGEmissionController::class, 'storeScope3Emission']);

        // Fetch all Scope 3 emissions for a specific company
        Route::post('ghg-emission/travel', [GHGEmissionController::class, 'storeScope3Emission']);
        Route::get('ghg-emission/travel', [GHGEmissionController::class, 'getScope3Emission']);

        Route::get('/simulate-growth/{treeId}', [TreeGrowthController::class, 'simulateTreeGrowth']);




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
    Route::post('/companies',        [CompanyController::class, 'store']); // Assign company to user
    Route::get('/user/companies',    [CompanyController::class, 'show']); // Get user's company

    
    
    Route::resource('annual-summaries', AnnualSummaryController::class);

});
