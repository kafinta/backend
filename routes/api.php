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

Route::post('/user/auth/register', 'UserController@signup');
Route::post('/user/auth/login', 'UserController@login');
Route::prefix('user')->middleware('auth:sanctum')->group(function() {
    Route::post('/profile', 'ProfileController@createProfile');
    Route::get('/profile', 'ProfileController@getProfile');
    Route::post('/profile/update', 'ProfileController@updateProfile');
});
Route::get('/locations', 'LocationController@getAllLocations');
Route::get('/categories', 'CategoryController@getAllCategories');
Route::get('/categories/{number}', 'CategoryController@getSpecificNumberOfCategories');
Route::get('/categories/{categoryId}/subcategories', 'CategoryController@getSubcategories');