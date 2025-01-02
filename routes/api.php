<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

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
    Route::post('register', 'UserController@register');
    Route::post('login', 'UserController@login');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('user')->group(function() {
        Route::prefix('profile')->group(function() {
            Route::post('/', 'ProfileController@createProfile');
            Route::get('/', 'ProfileController@getProfile');
            Route::post('/update', 'ProfileController@updateProfile');
        });
    });
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
});
Route::get('{number}/categories', 'CategoryController@getSpecificNumberOfCategories');

Route::apiResource('categories', CategoryController::class);
Route::apiResource('locations', LocationController::class);
Route::apiResource('colors', ColorController::class);
Route::get('subcategories/find', 'SubcategoryController@getSubcategories');
Route::apiResource('subcategories', SubcategoryController::class);
Route::apiResource('attributes', AttributeController::class);
Route::apiResource('attributes.values', AttributeValueController::class);
Route::apiResource('products', ProductController::class);
