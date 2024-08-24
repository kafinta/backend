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
Route::get('/categories', 'CategoryController@getAllCategories');
Route::get('/categories/{number}', 'CategoryController@getSpecificNumberOfCategories');
Route::get('/subcategories', 'SubcategoryController@getSubcategories');