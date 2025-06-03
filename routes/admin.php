<?php

use App\Http\Controllers\Api\Admin\AuthController;
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



Route::get('/login', function () {
    return 'Login page placeholder';
})->name('login');



Route::group(['middleware' => 'translate', 'prefix' => 'admin'], function() {
    // Authentication
    Route::group(['prefix' => 'auth'], function() {
        Route::post('/login', [AuthController::class, 'login']);
        // Sign up
        Route::post('/sign-up', [AuthController::class, 'register']);
    });

});






