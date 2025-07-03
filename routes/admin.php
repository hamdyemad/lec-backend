<?php

use App\Http\Controllers\Api\Admin\AccountsController;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\CaseController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\ClientController;
use App\Http\Controllers\Api\Admin\ContactUsController;
use App\Http\Controllers\Api\Admin\EmployeeController;
use App\Http\Controllers\Api\Admin\LanguageController;
use App\Http\Controllers\Api\Admin\LawyersController;
use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\ServiceController;
use App\Http\Controllers\Api\Admin\SpecificationController;
use App\Http\Controllers\Api\Admin\StatusController;
use App\Http\Controllers\Api\Admin\SupportPageController;
use App\Http\Controllers\Api\Admin\CityController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\InvoicesController;
use App\Http\Controllers\Api\Admin\PagesController;
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
        Route::post('/profile', [AuthController::class, 'update_profile'])->middleware('auth:sanctum');
        Route::post('/forget-password', [AuthController::class, 'forget_password']);
        Route::post('/reset-password', [AuthController::class, 'reset_password']);
    });

    Route::group(['middleware' => ['auth:sanctum' ,'auth_type:admin']], function() {

        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Accounts
        Route::group(['prefix' => 'accounts'], function () {
            Route::get('/', [AccountsController::class, 'index']);
            Route::post('/', [AccountsController::class, 'store']);
            Route::put('/{uuid}', [AccountsController::class, 'edit']);
            Route::get('/{uuid}', [AccountsController::class, 'show']);
            Route::delete('/{uuid}', [AccountsController::class, 'delete']);
        });

        // lawyers
        Route::group(['prefix' => 'lawyers'], function() {
            Route::get('/', [LawyersController::class, 'index']);
            Route::post('/', [LawyersController::class, 'store']);
            Route::get('/{uuid}', [LawyersController::class, 'show']);
            Route::post('/{uuid}', [LawyersController::class, 'edit']);
            Route::delete('/{uuid}', [LawyersController::class, 'delete']);
        });

        // Clients
        Route::group(['prefix' => 'clients'], function() {
            Route::get('/', [ClientController::class, 'index']);
            Route::post('/', [ClientController::class, 'store']);
            Route::get('/{uuid}', [ClientController::class, 'show']);
            Route::post('/{uuid}', [ClientController::class, 'edit']);
            Route::delete('/{uuid}', [ClientController::class, 'delete']);
        });


        // Languages
        Route::group(['prefix' => 'languages'], function() {
            Route::get('/', [LanguageController::class, 'index']);
            Route::post('/', [LanguageController::class, 'update_translation']);
            Route::get('/{uuid}', [LanguageController::class, 'show']);
        });

        // Statuses
        Route::group(['prefix' => 'statuses'], function () {
            Route::get('/', [StatusController::class, 'index']);
            Route::post('/', [StatusController::class, 'store']);
            Route::put('/{uuid}', [StatusController::class, 'edit']);
            Route::get('/{uuid}', [StatusController::class, 'show']);
            Route::delete('/{uuid}', [StatusController::class, 'delete']);
        });

        // Contact us
        Route::group(['prefix' => 'contact-us'], function () {
            Route::get('/', [ContactUsController::class, 'index']);
            Route::post('/', [ContactUsController::class, 'store']);
            Route::put('/{uuid}', [ContactUsController::class, 'edit']);
            Route::get('/{uuid}', [ContactUsController::class, 'show']);
            Route::delete('/{uuid}', [ContactUsController::class, 'delete']);
        });

        // Cities
        Route::group(['prefix' => 'cities'], function () {
            Route::get('/', [CityController::class, 'index']);
            Route::post('/', [CityController::class, 'store']);
            Route::put('/{uuid}', [CityController::class, 'edit']);
            Route::get('/{uuid}', [CityController::class, 'show']);
            Route::delete('/{uuid}', [CityController::class, 'delete']);
        });


        // Services
        Route::group(['prefix' => 'services'], function() {
            Route::get('/', [ServiceController::class, 'index']);
            Route::get('/{uuid}', [ServiceController::class, 'show']);
            Route::post('/{uuid}/action', [ServiceController::class, 'action']);
        });


        // Invoices
        Route::group(['prefix' => 'invoices'], function() {
            Route::get('/', [InvoicesController::class, 'index']);
            Route::post('/pay', [InvoicesController::class, 'pay']);
            Route::get('/{uuid}', [InvoicesController::class, 'show']);
        });

        // cases
        Route::group(['prefix' => 'cases'], function() {
            Route::get('/', [CaseController::class, 'index']);
            Route::get('/statuses', [CaseController::class, 'statuses']);
            Route::post('/statuses/change', [CaseController::class, 'change_case_status']);
            Route::post('/assign-lawyer', [CaseController::class, 'assign_lawyer']);
            // Route::put('/{uuid}', [CaseController::class, 'edit']);
            Route::get('/{uuid}', [CaseController::class, 'show']);
            // Route::delete('/{uuid}', [CaseController::class, 'delete']);
        });


        // Pages
        Route::group(['prefix' => 'settings/pages'], function() {
            Route::get('/privacy-policy', [PagesController::class, 'privacy_policy_show']);
            Route::post('/privacy-policy', [PagesController::class, 'privacy_policy']);
        });



    });








});






