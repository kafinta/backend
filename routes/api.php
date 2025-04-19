<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductControllerV2;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\SellerControllerV2;
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
Route::prefix('attributes')->group(function () {
    Route::get('/subcategory/{subcategoryId}', [AttributeController::class, 'getAttributesForSubcategory']);
});

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // User Profile Routes
    Route::prefix('user/profile')->group(function() {
        Route::get('/', [ProfileController::class, 'getProfile']);
        Route::put('/', [ProfileController::class, 'updateProfile']);
        Route::post('/upload-picture', [ProfileController::class, 'uploadProfilePicture']);
    });

    // Product Management Routes
    Route::prefix('products')->group(function () {
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
    Route::prefix('sellers')->group(function () {
        // Routes for becoming a seller - available to all authenticated users
        Route::get('/form/metadata', [SellerController::class, 'getFormMetadata']);
        Route::get('/form/{sessionId}', [SellerController::class, 'getFormData']);
        Route::post('/steps', [SellerController::class, 'createStep'])->name('sellers.steps');
        Route::post('/submit', [SellerController::class, 'submit'])->name('sellers.submit');

        // Routes that require seller or admin role
        Route::middleware('role:seller|admin')->group(function() {
            Route::get('{seller}', [SellerController::class, 'show'])->name('sellers.show');
            Route::get('{seller}/document', [SellerController::class, 'downloadDocument'])->name('sellers.document.download');
        });
    });

    // Seller Routes V2 (Improved Implementation)
    // These routes provide endpoints for the enhanced seller registration process
    // with improved file handling and session management
    Route::prefix('v2/sellers')->group(function () {
        // Routes for becoming a seller - available to all authenticated users
        Route::get('/session', [SellerControllerV2::class, 'generateSessionId'])->name('sellers.v2.session');
        Route::get('/form/{sessionId}', [SellerControllerV2::class, 'getFormData']);
        Route::post('/steps', [SellerControllerV2::class, 'createStep'])->name('sellers.v2.steps');
        Route::post('/submit', [SellerControllerV2::class, 'submit'])->name('sellers.v2.submit');

        // Routes that require seller or admin role
        Route::middleware('role:seller|admin')->group(function() {
            Route::get('{seller}', [SellerControllerV2::class, 'show'])->name('sellers.v2.show');
            Route::get('{seller}/document', [SellerControllerV2::class, 'downloadDocument'])->name('sellers.v2.document.download');
        });
    });

    // Product Routes V2 (Improved Implementation)
    // These routes provide endpoints for enhanced product management with improved file handling
    Route::prefix('v2/products')->group(function () {
        // Public routes - available to all users
        Route::get('/', [ProductControllerV2::class, 'index'])->name('products.v2.index');
        Route::get('/category/{category}', [ProductControllerV2::class, 'byCategory'])->name('products.v2.by-category');
        Route::get('/search', [ProductControllerV2::class, 'search'])->name('products.v2.search');

        // Session route must come before the wildcard product route
        Route::middleware(['auth:sanctum', 'role:seller|admin'])->group(function() {
            Route::get('/session', [ProductControllerV2::class, 'generateSessionId'])->name('products.v2.session');
            Route::get('/form/{sessionId}', [ProductControllerV2::class, 'getFormData'])->name('products.v2.form');
        });

        // This wildcard route must come after all other specific routes
        Route::get('/{product}', [ProductControllerV2::class, 'show'])->name('products.v2.show');

        // Seller routes - require seller role
        Route::middleware(['auth:sanctum', 'role:seller|admin'])->group(function() {
            // Multistep form routes for product creation/editing
            Route::post('/steps', [ProductControllerV2::class, 'createStep'])->name('products.v2.steps');
            Route::post('/submit', [ProductControllerV2::class, 'submit'])->name('products.v2.submit');

            // Explicit route for updating a specific product
            Route::post('/{product}/submit', [ProductControllerV2::class, 'submitUpdate'])->name('products.v2.submit.update');

            // Direct routes (for compatibility and single-step operations)
            Route::post('/', [ProductControllerV2::class, 'store'])->name('products.v2.store');
            Route::put('/{product}', [ProductControllerV2::class, 'update'])->name('products.v2.update');
            Route::delete('/{product}', [ProductControllerV2::class, 'destroy'])->name('products.v2.destroy');
            Route::post('/{product}/images', [ProductControllerV2::class, 'uploadImages'])->name('products.v2.images.upload');
            Route::delete('/{product}/images/{imageId}', [ProductControllerV2::class, 'deleteImage'])->name('products.v2.images.delete');
        });
    });
});