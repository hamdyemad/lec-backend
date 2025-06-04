<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\FavoriteProductController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\Client\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SpecificationController;
use App\Http\Controllers\Api\UserCardController;
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


// Countries
Route::get('/countries', [CountryController::class, 'index']);
Route::get('/shipping-methods', [CountryController::class, 'shipping_methods']);



Route::group(['middleware' => 'translate'], function() {
    // Authentication
    Route::group(['prefix' => 'auth'], function() {

        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/profile', [AuthController::class, 'profile'])->middleware('auth:sanctum');

        // Sign up
        Route::group(['prefix' => 'sign-up'], function() {
            Route::post('/step1', [AuthController::class, 'register_step_1']);
            Route::post('/step2', [AuthController::class, 'register_step_2']);
            Route::post('/step3', [AuthController::class, 'register_step_3']);
        });

        // Reset Password
        Route::post('/forget-password', [AuthController::class, 'forget_password']);
        Route::post('/reset-password', [AuthController::class, 'reset_password']);


    });



    // Authorized
    Route::group(['middleware' => 'auth:sanctum'], function() {
        // Categories
        Route::group(['prefix' => 'categories'], function() {
            Route::get('/', [CategoryController::class, 'index']);
            Route::post('/', [CategoryController::class, 'store'])->middleware('auth_type:admin');
            Route::get('/{uuid}', [CategoryController::class, 'show']);
            Route::post('/{uuid}', [CategoryController::class, 'edit'])->middleware('auth_type:admin');
            Route::delete('/{uuid}', [CategoryController::class, 'delete'])->middleware('auth_type:admin');
        });

        // Languages
        Route::group(['prefix' => 'languages'], function() {
            Route::get('/', [LanguageController::class, 'index']);
            Route::post('/', [LanguageController::class, 'update_translation'])->middleware('auth_type:admin');
            Route::get('/{uuid}', [LanguageController::class, 'show']);
        });


        // Products
        Route::group(['prefix' => 'products'], function() {
            Route::get('/', [ProductController::class, 'index']);
            Route::post('/', [ProductController::class, 'store'])->middleware('auth_type:admin');
            Route::post('/{id}', [ProductController::class, 'edit'])->middleware('auth_type:admin');
            Route::get('/{id}', [ProductController::class, 'show']);
            Route::delete('/{id}', [ProductController::class, 'delete'])->middleware('auth_type:admin');
        });




        // specifications
        Route::group(['prefix' => 'specifications'], function() {
            Route::get('/', [SpecificationController::class, 'index']);
            Route::post('/', [SpecificationController::class, 'store'])->middleware('auth_type:admin');
            Route::get('/{uuid}', [SpecificationController::class, 'show']);
            Route::post('/{uuid}', [SpecificationController::class, 'edit'])->middleware('auth_type:admin');
            Route::delete('/{uuid}', [SpecificationController::class, 'delete'])->middleware('auth_type:admin');
        });


        // Home Page
        Route::get('/home', [HomeController::class, 'home']);
        Route::get('/recent-searches', [HomeController::class, 'recent_searches']);
        Route::delete('/recent-searches', [HomeController::class, 'remove_recent_searches']);


    });

});






