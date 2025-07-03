<?php

use App\Http\Controllers\Api\AccountsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\CaseController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\ContactUsController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\FavoriteProductController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ServiceController;
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



Route::group(['middleware' => 'api_key'], function() {

    Route::get('/login', function () {
        return 'Login page placeholder';
    })->name('login');





    Route::group(['middleware' => 'translate'], function() {
        Route::get('/countries', [CountryController::class, 'index']);
        Route::get('/cities', [CityController::class, 'index']);
        Route::get('/shipping-methods', [CountryController::class, 'shipping_methods']);
        Route::get('/languages', [LanguageController::class, 'index']);
        Route::get('/accounts', [AccountsController::class, 'index']);
        Route::get('/cases-types', [CaseController::class, 'index']);


        // Authentication
        Route::group(['prefix' => 'auth'], function() {
            Route::post('/login', [AuthController::class, 'login']);
            // Registerration
            Route::group(['prefix' => 'register'], function() {
                Route::post('/', [AuthController::class, 'register']);
                Route::post('/verify', [AuthController::class, 'verify_register']);
            });
            // profile
            Route::group(['prefix' => 'profile', 'middleware' => 'auth:sanctum'], function() {
                Route::get('/', [AuthController::class, 'profile']);
                Route::post('/', [AuthController::class, 'update_profile']);
                Route::post('/password', [AuthController::class, 'update_profile_password']);
            });
            // password forgetten
            Route::post('/forget-password', [AuthController::class, 'forget_password']);
            Route::post('/reset-password', [AuthController::class, 'reset_password']);




        });



        // // Authorized
        Route::group(['middleware' => 'auth:sanctum'], function() {
            Route::post('/contact-us', [ContactUsController::class, 'store']);

            // services
            Route::group(['prefix' => 'services'], function() {
                Route::get('/', [ServiceController::class, 'index']);
                Route::post('/', [ServiceController::class, 'store']);
                Route::post('/action', [ServiceController::class, 'action']);
                Route::get('/{uuid}', [ServiceController::class, 'show']);
            });

        });

    });



});






