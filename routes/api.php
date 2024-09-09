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
Route::get('/locations', 'LocationController@getAllLocations');
Route::get('/colors', 'ColorController@getAllColors');

Route::get('/subcategories', 'SubcategoryController@getSubcategories');


Route::prefix('categories')->group(function () {
    Route::get('/', 'CategoryController@getAllCategories');
    Route::get('/{number}', 'CategoryController@getSpecificNumberOfCategories');
});

Route::prefix('subcategories')->group(function () {
    Route::get('/', 'SubcategoryController@index');    // get all subcategories
    Route::get('/{id}', 'SubcategoryController@show');    // get all subcategories
    Route::get('/subcategory', 'SubcategoryController@getSubcategorieswithQuery');    // uses query to fetch subcategories by category or location
    Route::get('/{subcategoryId}/attributes', 'SubcategoryController@getSubcategoryWithAttributes');
});

Route::apiResource('attributes', AttributeController::class);
Route::apiResource('attributes.values', AttributeValueController::class);
