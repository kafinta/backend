<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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


Route::prefix('user')->middleware('auth:users-web,users-api')->group(function() {
    Route::prefix('profile')->group(function() {
        Route::post('/', 'ProfileController@createProfile');
        Route::get('/', 'ProfileController@getProfile');
        Route::post('/update', 'ProfileController@updateProfile');
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

// Route::middleware('auth:users-web,users-api')->group(function () {
    Route::apiResource('products', ProductController::class);
// });