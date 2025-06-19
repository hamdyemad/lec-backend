<?php

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\ClientController;
use App\Http\Controllers\Api\Admin\EmployeeController;
use App\Http\Controllers\Api\Admin\LanguageController;
use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\SpecificationController;
use App\Http\Controllers\Api\Admin\StatusController;
use App\Http\Controllers\Api\Admin\SupportPageController;
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
        Route::get('/profile', [AuthController::class, 'profile'])->middleware('auth:sanctum');
        Route::post('/forget-password', [AuthController::class, 'forget_password']);
        Route::post('/reset-password', [AuthController::class, 'reset_password']);
    });

    Route::group(['middleware' => ['auth:sanctum' ,'auth_type:admin']], function() {

        // Employees
        Route::group(['prefix' => 'employees'], function() {
            Route::get('/', [EmployeeController::class, 'index']);
            Route::post('/', [EmployeeController::class, 'store']);
            Route::get('/{uuid}', [EmployeeController::class, 'show']);
            Route::post('/{uuid}', [EmployeeController::class, 'edit']);
            Route::delete('/{uuid}', [EmployeeController::class, 'delete']);
        });

        // Clients
        Route::group(['prefix' => 'clients'], function() {
            Route::get('/', [ClientController::class, 'index']);
            Route::get('/{uuid}', [ClientController::class, 'show']);
        });

        // Categories
        Route::group(['prefix' => 'categories'], function() {
            Route::get('/', [CategoryController::class, 'index']);
            Route::post('/', [CategoryController::class, 'store']);
            Route::get('/{uuid}', [CategoryController::class, 'show']);
            Route::post('/{uuid}', [CategoryController::class, 'edit']);
            Route::delete('/{uuid}', [CategoryController::class, 'delete']);
        });

        // Products
        Route::group(['prefix' => 'products'], function() {
            Route::get('/', [ProductController::class, 'index']);
            Route::post('/', [ProductController::class, 'store']);
            Route::post('/{id}', [ProductController::class, 'edit']);
            Route::get('/{id}', [ProductController::class, 'show']);
            Route::delete('/{id}', [ProductController::class, 'delete']);
        });


        // specifications
        Route::group(['prefix' => 'specifications'], function() {
            Route::get('/', [SpecificationController::class, 'index']);
            Route::post('/', [SpecificationController::class, 'store']);
            Route::get('/{uuid}', [SpecificationController::class, 'show']);
            Route::post('/{uuid}', [SpecificationController::class, 'edit']);
            Route::delete('/{uuid}', [SpecificationController::class, 'delete']);
        });


        // Languages
        Route::group(['prefix' => 'languages'], function() {
            Route::get('/', [LanguageController::class, 'index']);
            Route::post('/', [LanguageController::class, 'update_translation']);
            Route::get('/{uuid}', [LanguageController::class, 'show']);
        });

        // Support Page
        Route::group(['prefix' => 'support-pages'], function() {
            Route::get('/', [SupportPageController::class, 'index']);
            Route::post('/', [SupportPageController::class, 'store']);
            Route::get('/{uuid}', [SupportPageController::class, 'show']);
            Route::post('/{uuid}', [SupportPageController::class, 'edit']);
            Route::delete('/{uuid}', [SupportPageController::class, 'delete']);
        });


        // Orders
        Route::group(['prefix' => 'orders'], function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::post('/', [OrderController::class, 'store']);
            Route::get('/{uuid}', [OrderController::class, 'show']);

            Route::group(['prefix' => '{uuid}/statuses'], function () {
                Route::put('/', [OrderController::class, 'update_status']);
            });
        });

        // Statuses
        Route::group(['prefix' => 'statuses'], function () {
            Route::get('/', [StatusController::class, 'index']);
            Route::post('/', [StatusController::class, 'store']);
            Route::put('/{uuid}', [StatusController::class, 'edit']);
            Route::get('/{uuid}', [StatusController::class, 'show']);
            Route::delete('/{uuid}', [StatusController::class, 'delete']);
        });


    });








});






