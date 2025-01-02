<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('user')->group(function() {
    Route::middleware(['throttle:6,1'])->group(function () {
        Route::post('/login', [UserController::class, 'login']);
        Route::post('/register', [UserController::class, 'register']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('user')->group(function() {
        Route::prefix('profile')->group(function() {
            Route::post('/', 'ProfileController@createProfile');
            Route::get('/', 'ProfileController@getProfile');
            Route::post('/update', 'ProfileController@updateProfile');
        });
    });

    Route::prefix('products')->group(function() {
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
        Route::get('resume-form', [ProductController::class, 'resumeForm']);
    });
});
Route::get('{number}/categories', 'CategoryController@getSpecificNumberOfCategories');

Route::apiResource('categories', CategoryController::class);
Route::apiResource('locations', LocationController::class);
Route::apiResource('colors', ColorController::class);
Route::get('subcategories/find', 'SubcategoryController@getSubcategories');
Route::apiResource('subcategories', SubcategoryController::class);
Route::apiResource('attributes', AttributeController::class);
Route::apiResource('attributes.values', AttributeValueController::class);
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{id}', [ProductController::class, 'show']);

