<?php

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Client\CardController;
use App\Http\Controllers\Api\Client\FavoriteProductController;
use App\Http\Controllers\Api\Client\OrderController;
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


Route::group(['middleware' => 'translate', 'prefix' => 'clients', 'auth:sanctum'], function () {
    // Authentication
    Route::group(['prefix' => 'auth'], function () {
        Route::post('/login', [AuthController::class, 'login']);
        // Sign up
        Route::post('/sign-up', [AuthController::class, 'register']);
    });

    Route::group(['prefix' => 'cards'], function () {
        Route::get('/', [CardController::class, 'index']);
        Route::post('/', [CardController::class, 'store']);
        Route::get('/{uuid}', [CardController::class, 'show']);
        Route::put('/{uuid}', [CardController::class, 'edit']);
        Route::delete('/{uuid}', [CardController::class, 'delete']);
    });

    // orders
    Route::group(['prefix' => 'orders'], function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{uuid}', [OrderController::class, 'show']);
        // Route::post('/{uuid}', [OrderController::class, 'edit']);
        // Route::delete('/{uuid}', [OrderController::class, 'delete']);
    });



    // Favorite Products
    Route::group(['prefix' => 'favorite-products'], function() {
        Route::get('/', [FavoriteProductController::class, 'index']);
        Route::post('/', [FavoriteProductController::class, 'store']);
    });
});
