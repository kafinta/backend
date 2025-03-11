<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AttributeValueController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::middleware(['throttle:6,1'])->prefix('user')->group(function () {
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/register', [UserController::class, 'register']);
});

// Public Resource Routes
Route::apiResources([
    'categories' => CategoryController::class,
    'locations' => LocationController::class,
    'subcategories' => SubcategoryController::class,
    'attributes' => AttributeController::class,
    'attributes.values' => AttributeValueController::class,
]);

Route::prefix('attributes')->controller(AttributeController::class)->group(function () {
    Route::get('/subcategory/{subcategoryId}', 'getAttributesForSubcategory');
});

Route::get('{number}/categories', [CategoryController::class, 'getSpecificNumberOfCategories']);
Route::get('subcategories/find', [SubcategoryController::class, 'getSubcategories']);
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{id}', [ProductController::class, 'show']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // User Profile Routes
    Route::prefix('user/profile')->controller(ProfileController::class)->group(function() {
        Route::post('/', 'createProfile');
        Route::get('/', 'getProfile');
        Route::post('/update', 'updateProfile');
    });

    // Product Management Routes
    Route::prefix('products')->controller(ProductController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{product}', 'show');
        
        // Product Form Routes
        Route::post('/step', 'saveStep')->name('products.create.step');
        Route::post('/submit', 'store')->name('products.create.submit');
        Route::post('/{product}/step', 'updateStep');
        Route::put('/{product}', 'update');
        Route::delete('/{product}', 'destroy');
    });

    // Seller Routes
    Route::prefix('sellers')->controller(SellerController::class)->group(function () {
        // Application Routes
        Route::post('/apply/step', 'saveStep')->name('sellers.apply.step');
        Route::post('/apply/submit', 'submit')->name('sellers.apply.submit');
        
        // Seller Profile Routes
        Route::get('/{seller}', 'show')->name('sellers.show');
        Route::get('/{seller}/document', 'downloadDocument')->name('sellers.document.download');
        
        // Admin Only Routes
        Route::middleware('role:admin')->group(function() {
            Route::post('/{seller}/verify', 'verify')->name('sellers.verify');
        });
    });


    // Attribute Values Routes

});