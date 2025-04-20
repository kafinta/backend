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
Route::prefix('attributes')->group(function () {
    Route::get('/subcategory/{subcategoryId}', [AttributeController::class, 'getAttributesForSubcategory']);
});

// Public product routes are defined within the products group below

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
        // Public routes - available to all users
        Route::get('/', [ProductController::class, 'index'])->name('products.index');
        Route::get('/category/{category}', [ProductController::class, 'byCategory'])->name('products.by-category');
        Route::get('/search', [ProductController::class, 'search'])->name('products.search');

        // Session route must come before the wildcard product route
        Route::middleware(['auth:sanctum', 'role:seller|admin'])->group(function() {
            Route::get('/session', [ProductController::class, 'generateSessionId'])->name('products.session');
            Route::get('/form/{sessionId}', [ProductController::class, 'getFormData'])->name('products.form');
        });

        // This wildcard route must come after all other specific routes
        Route::get('/{product}', [ProductController::class, 'show'])->name('products.show');

        // Seller routes - require seller role
        Route::middleware(['auth:sanctum', 'role:seller|admin'])->group(function() {
            // Multistep form routes for product creation/editing
            Route::post('/steps', [ProductController::class, 'createStep'])->name('products.steps');
            Route::post('/submit', [ProductController::class, 'submit'])->name('products.submit');

            // Explicit route for updating a specific product
            Route::post('/{product}/submit', [ProductController::class, 'submitUpdate'])->name('products.submit.update');

            // Direct routes (for compatibility and single-step operations)
            Route::post('/', [ProductController::class, 'store'])->name('products.store');
            Route::put('/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::delete('/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
            Route::post('/{product}/images', [ProductController::class, 'uploadImages'])->name('products.images.upload');
            Route::delete('/{product}/images/{imageId}', [ProductController::class, 'deleteImage'])->name('products.images.delete');
        });
    });

    // Seller Routes
    Route::prefix('sellers')->group(function () {
        // Routes for becoming a seller - available to all authenticated users
        Route::get('/session', [SellerController::class, 'generateSessionId'])->name('sellers.session');
        Route::get('/form/{sessionId}', [SellerController::class, 'getFormData'])->name('sellers.form');
        Route::post('/steps', [SellerController::class, 'createStep'])->name('sellers.steps');
        Route::post('/submit', [SellerController::class, 'submit'])->name('sellers.submit');

        // Routes that require seller or admin role
        Route::middleware('role:seller|admin')->group(function() {
            Route::get('{seller}', [SellerController::class, 'show'])->name('sellers.show');
            Route::get('{seller}/document', [SellerController::class, 'downloadDocument'])->name('sellers.document.download');
        });
    });
});