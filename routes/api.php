<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubcategoryController;

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
Route::get('/{number}/categories', 'CategoryController@getSpecificNumberOfCategories');

Route::prefix('categories')->group(function () {
    Route::get('/', 'CategoryController@getAllCategories');
});

Route::prefix('subcategories')->group(function () {
    Route::get('/', [SubcategoryController::class, 'index']);    // get all subcategories
    Route::get('/find', [SubcategoryController::class, 'getSubcategories']);
    Route::get('/{id}', [SubcategoryController::class, 'show']);

});

Route::apiResource('attributes', AttributeController::class);
Route::apiResource('attributes.values', AttributeValueController::class);
