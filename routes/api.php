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
use App\Http\Controllers\VariantController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SellerOrderController;

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

// Public Checkout Routes
Route::prefix('checkout')->group(function () {
    // Calculate totals (shipping, tax, etc.)
    Route::post('/calculate', [CheckoutController::class, 'calculateTotals'])->name('checkout.calculate');

    // Get shipping methods
    Route::get('/shipping-methods', [CheckoutController::class, 'getShippingMethods'])->name('checkout.shipping-methods');

    // Get payment methods
    Route::get('/payment-methods', [CheckoutController::class, 'getPaymentMethods'])->name('checkout.payment-methods');
});

// Public Cart Routes
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'viewCart'])->name('cart.view');
    Route::post('/items', [CartController::class, 'addToCart'])->name('cart.add');
    Route::put('/items/{id}', [CartController::class, 'updateCartItem'])->name('cart.update');
    Route::delete('/items/{id}', [CartController::class, 'deleteCartItem'])->name('cart.delete');
    Route::delete('/', [CartController::class, 'clearCart'])->name('cart.clear');
});

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // User Profile Routes
    Route::prefix('user/profile')->group(function() {
        Route::get('/', [UserController::class, 'getProfile']);
        Route::put('/', [UserController::class, 'updateProfile']);
        Route::post('/upload-picture', [UserController::class, 'uploadProfilePicture']);
        Route::get('/roles', [UserController::class, 'getRoles']);
    });

    // Product Management Routes (Protected)
    Route::prefix('products')->group(function () {
        // IMPORTANT: Specific routes must come before wildcard routes
        // Session routes for product creation/editing
        Route::get('/session', [ProductController::class, 'generateSessionId'])
            ->middleware(['role:seller|admin'])
            ->name('products.session');

        Route::get('/form/{sessionId}', [ProductController::class, 'getFormData'])
            ->middleware(['role:seller|admin'])
            ->name('products.form');

        // Seller routes - require seller role
        Route::middleware(['role:seller|admin'])->group(function() {
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

            // Protected variant routes
            Route::post('/{productId}/variants', [VariantController::class, 'store'])->name('variants.store');
            Route::put('/variants/{id}', [VariantController::class, 'update'])->name('variants.update');
            Route::delete('/variants/{id}', [VariantController::class, 'destroy'])->name('variants.destroy');
            Route::post('/variants/{id}/images', [VariantController::class, 'uploadImages'])->name('variants.images.upload');
            Route::delete('/variants/{id}/images/{imageId}', [VariantController::class, 'deleteImage'])->name('variants.images.delete');
            Route::post('/variants/batch/update', [VariantController::class, 'batchUpdate'])->name('variants.batch.update');
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

        // Seller Order Management Routes
        Route::prefix('seller/orders')->group(function () {
            // List all orders containing the seller's products
            Route::get('/', [SellerOrderController::class, 'index'])->name('seller.orders.index');

            // View a specific order with items sold by the seller
            Route::get('/{id}', [SellerOrderController::class, 'show'])->name('seller.orders.show');

            // Update the status of the seller's items in an order
            Route::put('/{id}/status', [SellerOrderController::class, 'updateStatus'])->name('seller.orders.update-status');
        });
    });

    // Protected Cart Routes (require authentication)
    Route::prefix('cart')->group(function () {
        // Route to transfer guest cart to user cart after login
        Route::post('/transfer', [CartController::class, 'transferGuestCart'])->name('cart.transfer');
    });

    // Protected Checkout Routes (require authentication)
    Route::prefix('checkout')->group(function () {
        // Place an order
        Route::post('/place-order', [CheckoutController::class, 'placeOrder'])->name('checkout.place-order');
    });

    // Protected Order Routes (require authentication)
    Route::prefix('orders')->group(function () {
        // List user's orders
        Route::get('/', [OrderController::class, 'index'])->name('orders.index');

        // View a specific order
        Route::get('/{id}', [OrderController::class, 'show'])->name('orders.show');

        // Cancel an order
        Route::post('/{id}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    });
});

// Public Product Routes
Route::prefix('products')->group(function () {
    // List routes first (no parameters)
    Route::get('/', [ProductController::class, 'index'])->name('products.index');
    Route::get('/search', [ProductController::class, 'search'])->name('products.search');

    // Then routes with specific segments
    Route::get('/category/{category}', [ProductController::class, 'byCategory'])->name('products.by-category');
    Route::get('/variants/{id}', [VariantController::class, 'show'])->name('variants.show');

    // Then routes with parameters but specific segments after
    Route::get('/{productId}/variants', [VariantController::class, 'index'])->name('variants.index');

    // Finally, the catch-all route
    Route::get('/{product}', [ProductController::class, 'show'])->name('products.show');
});