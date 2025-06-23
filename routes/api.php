<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\FavoriteProductController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SpecificationController;
use App\Http\Controllers\Api\SupportPageController;
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
Route::get('/payment-methods', [PaymentController::class, 'payment_methods']);



Route::group(['middleware' => 'translate'], function() {
    // Authentication
    Route::group(['prefix' => 'auth'], function() {

        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/profile', [AuthController::class, 'profile'])->middleware('auth:sanctum');
        Route::post('/profile', [AuthController::class, 'update_profile'])->middleware('auth:sanctum');

        Route::post('/firebase-token', [AuthController::class, 'firebase_save_token'])->middleware('auth:sanctum');


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
        Route::get('/languages', [LanguageController::class, 'index']);



        // Products
        Route::group(['prefix' => 'products'], function() {
            Route::get('/', [ProductController::class, 'index']);
            Route::post('/', [ProductController::class, 'store'])->middleware('auth_type:admin');
            Route::post('/{id}', [ProductController::class, 'edit'])->middleware('auth_type:admin');
            Route::get('/{id}', [ProductController::class, 'show']);
            Route::delete('/{id}', [ProductController::class, 'delete'])->middleware('auth_type:admin');
        });


        // Orders
        Route::group(['prefix' => 'orders'], function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::post('/', [OrderController::class, 'store']);
            Route::get('/{uuid}', [OrderController::class, 'show']);

            Route::get('/{uuid}/status-history', [OrderController::class, 'status_history']);
        });



        // Cards
        Route::group(['prefix' => 'cards'], function () {
            Route::get('/', [CardController::class, 'index']);
            Route::post('/', [CardController::class, 'store']);
            Route::get('/{uuid}', [CardController::class, 'show']);
            Route::put('/{uuid}', [CardController::class, 'edit']);
            Route::delete('/{uuid}', [CardController::class, 'delete']);
        });


        // Favorite Products
        Route::group(['prefix' => 'favorite-products'], function() {
            Route::get('/', [FavoriteProductController::class, 'index']);
            Route::post('/', [FavoriteProductController::class, 'store']);
        });


        // specifications
        Route::group(['prefix' => 'specifications'], function() {
            Route::get('/', [SpecificationController::class, 'index']);
        });






        Route::get('/notifications', [NotificationController::class, 'index']);

        Route::get('/home', [HomeController::class, 'home']);
        Route::get('/recent-searches', [HomeController::class, 'recent_searches']);
        Route::delete('/recent-searches', [HomeController::class, 'remove_recent_searches']);



        Route::get('/test', [NotificationController::class, 'index']);



    });

});






