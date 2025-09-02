<?php

use App\Http\Controllers\Api\Web\AuthController;
use App\Http\Controllers\Api\Web\CardController;
use App\Http\Controllers\Api\Web\CategoryController;
use App\Http\Controllers\Api\Web\CountryController;
use App\Http\Controllers\Api\Web\FavoriteProductController;
use App\Http\Controllers\Api\Web\HomeController;
use App\Http\Controllers\Api\Web\LanguageController;
use App\Http\Controllers\Api\Web\NewsLetterController;
use App\Http\Controllers\Api\Web\NotificationController;
use App\Http\Controllers\Api\Web\OrderController;
use App\Http\Controllers\Api\Web\PaymentController;
use App\Http\Controllers\Api\Web\ProductController;
use App\Http\Controllers\Api\Web\ReviewController;
use App\Http\Controllers\Api\Web\SpecificationController;
use App\Http\Controllers\Api\Web\SupportPageController;
use App\Http\Controllers\Api\Web\UserCardController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'translate', 'prefix' => 'client'], function() {
    // Authentication
    Route::group(['prefix' => 'auth'], function() {

        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/profile', [AuthController::class, 'profile'])->middleware('auth:sanctum');
        Route::post('/profile', [AuthController::class, 'update_profile'])->middleware('auth:sanctum');
        Route::post('/passwords', [AuthController::class, 'update_password'])->middleware('auth:sanctum');

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
            Route::get('/{uuid}', [ProductController::class, 'show']);
            Route::post('/{uuid}/reviews', [ReviewController::class, 'store']);
        });

        // Reviews
        Route::group(['prefix' => 'reviews'], function() {
            Route::get('/', [ReviewController::class, 'index']);
        });


        Route::post('/newsletters', [NewsLetterController::class, 'store']);
        Route::post('/messages', [HomeController::class, 'send_message']);


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





