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
    Route::post('/signup', [UserController::class, 'register']);
});

// Public Resource Routes
Route::apiResources([
    'categories' => CategoryController::class,
    'locations' => LocationController::class,
    'subcategories' => SubcategoryController::class,
    'attributes' => AttributeController::class,
    'attributes.values' => AttributeValueController::class,
]);

// Additional Public Routes
Route::prefix('attributes')->controller(AttributeController::class)->group(function () {
    Route::get('/subcategory/{subcategoryId}', 'getAttributesForSubcategory');
});

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // User Profile Routes
    Route::prefix('user/profile')->controller(ProfileController::class)->group(function() {
        Route::post('/', 'createProfile');
        Route::get('/', 'getProfile');
        Route::post('/update', 'updateProfile');
        Route::post('/upload-picture', 'uploadProfilePicture');
    });

    // Product Management Routes
    Route::prefix('products')->controller(ProductController::class)->group(function () {
        Route::get('/form/metadata', [ProductController::class, 'getFormMetadata']);
        Route::get('/form/{sessionId}', [ProductController::class, 'getFormData']);
        Route::get('/subcategory/attributes', [ProductController::class, 'getSubcategoryAttributes']);

        Route::post('/steps', [ProductController::class, 'createStep']);
        Route::post('/', [ProductController::class, 'store']);

        Route::post('/{product}/steps', [ProductController::class, 'updateStep']);
        Route::put('/{product}', [ProductController::class, 'update']);
        Route::delete('/{product}', [ProductController::class, 'destroy']);
    });

    // Seller Routes
    Route::prefix('sellers')->controller(SellerController::class)->group(function () {
        // Routes for becoming a seller - available to all authenticated users
        Route::get('/form/metadata', [SellerController::class, 'getFormMetadata']);
        Route::get('/form/{sessionId}', [SellerController::class, 'getFormData']);
        Route::post('/steps', [SellerController::class, 'createStep'])->name('sellers.steps');
        Route::post('/submit', [SellerController::class, 'submit'])->name('sellers.submit');

        // Routes that require seller or admin role
        Route::middleware('role:seller|admin')->group(function() {
            Route::get('{seller}', 'show')->name('sellers.show');
            Route::get('{seller}/document', 'downloadDocument')->name('sellers.document.download');
        });
    });
});