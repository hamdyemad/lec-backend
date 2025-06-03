<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ClientProdutsController;
use App\Http\Controllers\Api\ClientRatesController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\Driver\DriverInvoiceController;
use App\Http\Controllers\Api\Driver\DriverOrderController;
use App\Http\Controllers\Api\Driver\DriverWithdrawlController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\FavoriteProductController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\IbanController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\LogistiController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PagesController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductsController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\SellerController;
use App\Http\Controllers\Api\SellerCouponController;
use App\Http\Controllers\Api\Seller\SellerOrdersController;
use App\Http\Controllers\Api\Seller\SellerPickupsController;
use App\Http\Controllers\Api\SellerProductController;
use App\Http\Controllers\Api\SellerRatesController;
use App\Http\Controllers\Api\SpecificationController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\SubCategoryController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserCouponsController;
use App\Http\Controllers\Api\UserLocaotionsController;
use App\Http\Controllers\Api\UserProductsController;
use App\Http\Controllers\Api\WithdrawlsController;
use Illuminate\Http\Request;
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

        // Favorite Products
        Route::group(['prefix' => 'favorite-products'], function() {
            Route::get('/', [FavoriteProductController::class, 'index']);
            Route::post('/', [FavoriteProductController::class, 'store']);
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






