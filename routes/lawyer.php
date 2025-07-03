<?php

use App\Http\Controllers\Api\Lawyer\CaseController;
use App\Http\Controllers\Api\Lawyer\CaseOrderController;
use App\Http\Controllers\Api\Lawyer\CaseSessionController;
use App\Http\Controllers\Api\Lawyer\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => ['translate', 'auth:sanctum', 'auth_type:lawyer'], 'prefix' => 'lawyer'], function() {

    Route::get('/dashboard', [DashboardController::class, 'index']);

    // cases orders
    Route::group(['prefix' => 'cases-orders'], function() {
        Route::get('/', [CaseOrderController::class, 'index']);
        Route::get('/{uuid}', [CaseOrderController::class, 'show']);
        Route::post('/action', [CaseOrderController::class, 'action']);

    });

    // cases
    Route::group(['prefix' => 'cases'], function() {
        Route::get('/', [CaseController::class, 'index']);
        Route::get('/{uuid}', [CaseController::class, 'show']);
        // Route::post('/action', [CaseController::class, 'action']);
    });

    // cases-sessions
    Route::group(['prefix' => 'cases-sessions'], function() {
        Route::get('/', [CaseSessionController::class, 'index']);
        Route::get('/statuses', [CaseSessionController::class, 'statuses']);
        Route::get('/{uuid}', [CaseSessionController::class, 'show']);
        Route::post('/', [CaseSessionController::class, 'store']);
        Route::post('/action', [CaseSessionController::class, 'action']);
    });



});






