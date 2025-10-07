<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SellerController;
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
use App\Http\Controllers\InventoryController;

use App\Http\Controllers\VerificationTokenController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\NotificationController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::middleware(['throttle:6,1'])->prefix('user')->group(function () {
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/signup', [UserController::class, 'register']);

    // Protected routes
    Route::middleware(['auth:sanctum,web'])->group(function () {
        Route::post('/resend-verification-email', [UserController::class, 'resendVerificationEmail']);
        Route::get('/email-verification-status', [UserController::class, 'checkEmailVerification']);
        // Allow users to update their email address (verified or unverified)
        Route::patch('/update-email', [UserController::class, 'updateEmail'])->middleware('throttle:3,60');
    });
});

// Password Reset Routes (Public)
Route::middleware(['throttle:6,1'])->group(function () {
    Route::post('/forgot-password', [UserController::class, 'forgotPassword'])->name('password.email');
    Route::post('/reset-password/token', [UserController::class, 'resetPasswordWithToken'])->name('password.reset.token');
    Route::post('/reset-password/code', [UserController::class, 'resetPasswordWithCode'])->name('password.reset.code');
    Route::post('/verify-reset-token', [UserController::class, 'verifyResetToken'])->name('password.verify.token');
    Route::post('/verify-reset-code', [UserController::class, 'verifyResetCode'])->name('password.verify.code');
});

// OAuth Routes (Public)
Route::middleware(['throttle:10,1'])->prefix('auth')->group(function () {
    // Test route to verify Socialite is working
    Route::get('/test-socialite', function () {
        try {
            $providers = ['google', 'facebook', 'apple'];
            return response()->json([
                'success' => true,
                'message' => 'Socialite is working',
                'data' => [
                    'socialite_class_exists' => class_exists(\Laravel\Socialite\Facades\Socialite::class),
                    'supported_providers' => $providers
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Socialite error: ' . $e->getMessage()
            ]);
        }
    });

    // Test route to verify Google OAuth configuration
    Route::get('/test-google-config', function () {
        try {
            $config = config('services.google');
            return response()->json([
                'success' => true,
                'message' => 'Google OAuth configuration',
                'data' => [
                    'client_id_set' => !empty($config['client_id']),
                    'client_secret_set' => !empty($config['client_secret']),
                    'redirect_uri' => $config['redirect'],
                    'can_create_driver' => true
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Google OAuth config error: ' . $e->getMessage()
            ]);
        }
    });

    // Get supported OAuth providers
    Route::get('/providers', [SocialAuthController::class, 'getSupportedProviders'])->name('oauth.providers');

    // OAuth redirect (for web-based flow)
    Route::get('/{provider}/redirect', [SocialAuthController::class, 'redirectToProvider'])->name('oauth.redirect');

    // OAuth callback (for web-based flow)
    Route::get('/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback'])->name('oauth.callback');

    // OAuth token authentication (for mobile/SPA)
    Route::post('/oauth/token', [SocialAuthController::class, 'authenticateWithToken'])->name('oauth.token');
});

// OAuth Protected Routes
Route::middleware(['auth:sanctum,web'])->prefix('auth')->group(function () {
    // Unlink OAuth provider
    Route::post('/unlink-provider', [SocialAuthController::class, 'unlinkProvider'])->name('oauth.unlink');
});

// Email Verification Routes (Public)
Route::post('/verify-email/token', [UserController::class, 'verifyEmailToken'])->name('verify.email.token');
Route::get('/verify-email/{token}', [UserController::class, 'verifyEmailToken'])->name('verify.email');
Route::post('/verify-email/code', [UserController::class, 'verifyEmailCode'])->name('verify.email.code');



// Verification Token Routes (Development Only)
Route::prefix('verification-tokens')->group(function () {
    Route::get('/', [VerificationTokenController::class, 'index'])->name('verification-tokens.index');
    Route::get('/{token}', [VerificationTokenController::class, 'show'])->name('verification-tokens.show');
    Route::delete('/{token}', [VerificationTokenController::class, 'destroy'])->name('verification-tokens.destroy');
    Route::delete('/', [VerificationTokenController::class, 'destroyAll'])->name('verification-tokens.destroy-all');
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
    Route::post('/validate/{subcategoryId}', [AttributeController::class, 'validateAttributeCombination']);
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

// Protected Routes - accessible via both session and token auth
Route::middleware(['auth:sanctum,web'])->group(function () {
    // User Auth Routes
    Route::post('/user/logout', [UserController::class, 'logout']);

    // User Profile Routes
    Route::prefix('user/profile')->group(function() {
        Route::get('/', [UserController::class, 'getProfile']);
        Route::put('/', [UserController::class, 'updateProfile']);
        Route::post('/upload-picture', [UserController::class, 'uploadProfilePicture']);
        Route::get('/roles', [UserController::class, 'getRoles']);
    });

    // Image Management Routes (Protected)
    Route::prefix('images')->middleware(['role:seller|admin'])->group(function() {
        Route::delete('/{imageId}', [\App\Http\Controllers\ImageController::class, 'destroy'])->name('images.destroy');
    });

    // Product Management Routes (Protected)
    Route::prefix('products')->group(function () {
        // IMPORTANT: Specific routes must come before wildcard routes
        // Seller routes - require seller role
        Route::middleware(['role:seller|admin'])->group(function() {

            // Step-by-step product management (create AND update)
            Route::post('/basic-info', [ProductController::class, 'createBasicInfo'])->name('products.basic-info.create');
            Route::put('/{product}/basic-info', [ProductController::class, 'updateBasicInfo'])->name('products.basic-info.update');
            Route::post('/{product}/attributes', [ProductController::class, 'addAttributes'])->name('products.attributes');
            Route::post('/{product}/images', [ProductController::class, 'uploadImages'])->name('products.images');
            Route::post('/{product}/publish', [ProductController::class, 'reviewAndPublish'])->name('products.publish');

            // Product management
            Route::delete('/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
            Route::patch('/{product}/status', [ProductController::class, 'updateStatus'])->name('products.update-status');

            // Seller-specific product management
            Route::get('/my-products', [ProductController::class, 'myProducts'])->name('products.my-products');
            Route::get('/my-stats', [ProductController::class, 'myProductStats'])->name('products.my-stats');
            Route::patch('/bulk-status', [ProductController::class, 'bulkUpdateStatus'])->name('products.bulk-status');

            // Protected variant routes
            Route::post('/{productId}/variants', [VariantController::class, 'store'])->name('variants.store');
            Route::put('/variants/{id}', [VariantController::class, 'update'])->name('variants.update');
            Route::delete('/variants/{id}', [VariantController::class, 'destroy'])->name('variants.destroy');
            Route::post('/variants/{id}/images', [VariantController::class, 'uploadImages'])->name('variants.images.upload');
            Route::delete('/variants/{id}/images/{imageId}', [VariantController::class, 'deleteImage'])->name('variants.images.delete');
            Route::post('/variants/batch/update', [VariantController::class, 'batchUpdate'])->name('variants.batch.update');
        });
    });

    // Inventory Management Routes (Protected - Sellers only)
    Route::prefix('inventory')->middleware(['role:seller|admin'])->group(function () {
        // Get inventory summary
        Route::get('/summary', [InventoryController::class, 'getSummary'])->name('inventory.summary');

        // Get out of stock items
        Route::get('/out-of-stock/products', [InventoryController::class, 'getOutOfStockProducts'])->name('inventory.out-of-stock.products');
        Route::get('/out-of-stock/variants', [InventoryController::class, 'getOutOfStockVariants'])->name('inventory.out-of-stock.variants');

        // Stock adjustments
        Route::post('/products/{product}/adjust', [InventoryController::class, 'adjustProductStock'])->name('inventory.products.adjust');
        Route::post('/variants/{variant}/adjust', [InventoryController::class, 'adjustVariantStock'])->name('inventory.variants.adjust');
        Route::post('/bulk-adjust', [InventoryController::class, 'bulkAdjustment'])->name('inventory.bulk-adjust');

        // Stock management settings
        Route::post('/products/{product}/manage-stock', [InventoryController::class, 'setProductStockManagement'])->name('inventory.products.manage-stock');
    });

    // Seller Routes
    Route::prefix('sellers')->group(function () {
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

        // Notification Management Routes
        Route::prefix('notifications')->group(function () {
            // List user notifications with pagination
            Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');

            // Get unread notification count
            Route::get('/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');

            // Mark specific notification as read
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');

            // Mark all notifications as read
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');

            // Delete specific notification
            Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

            // Get user notification preferences
            Route::get('/preferences', [NotificationController::class, 'getPreferences'])->name('notifications.preferences.get');

            // Update user notification preferences
            Route::put('/preferences', [NotificationController::class, 'updatePreferences'])->name('notifications.preferences.update');
        });
    });

    // New Seller Verification Routes - Step by Step Approach
    Route::prefix('seller')->middleware('auth:sanctum')->group(function () {
        Route::post('/verify-phone', [SellerController::class, 'verifyPhone'])->name('seller.verify-phone');
        Route::post('/update-profile', [SellerController::class, 'updateProfile'])->name('seller.update-profile');
        Route::post('/verify-kyc', [SellerController::class, 'verifyKYC'])->name('seller.verify-kyc');
        Route::post('/accept-agreement', [SellerController::class, 'acceptAgreement'])->name('seller.accept-agreement');
        Route::post('/update-payment-info', [SellerController::class, 'updatePaymentInfo'])->name('seller.update-payment-info');
        Route::post('/update-social-media', [SellerController::class, 'updateSocialMedia'])->name('seller.update-social-media');
        Route::post('/complete-onboarding', [SellerController::class, 'completeOnboarding'])->name('seller.complete-onboarding');
        Route::get('/progress', [SellerController::class, 'getProgress'])->name('seller.progress');
    });

    // Logout route (requires authentication)
    Route::post('/logout', [UserController::class, 'logout'])->name('user.logout');

    // Protected Cart Routes (require authentication)
    Route::prefix('cart')->group(function () {
        // Route to transfer guest cart to user cart after login
        Route::post('/transfer', [CartController::class, 'transferGuestCart'])->name('cart.transfer');
        // Automatic cart transfer after login (no parameters needed)
        Route::get('/transfer-after-login', [CartController::class, 'transferGuestCart'])->name('cart.transfer-after-login');
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
    // Unified product listing with comprehensive filtering
    Route::get('/', [ProductController::class, 'index'])->name('products.index');
    // Get product by ID
    Route::get('/{product}', [ProductController::class, 'show'])->name('products.show');
    // Get product by slug
    Route::get('/slug/{slug}', [ProductController::class, 'showBySlug'])->name('products.show-by-slug');
    // Get product attributes
    Route::get('/{product}/attributes', [ProductController::class, 'getAttributes'])->name('products.get-attributes');

    // Variant routes
    Route::get('/variants/{id}', [VariantController::class, 'show'])->name('variants.show');
    Route::get('/{productId}/variants', [VariantController::class, 'index'])->name('variants.index');

    // Single product view (catch-all route - must be last)
    Route::get('/{product}', [ProductController::class, 'show'])->name('products.show');
});

// Review system
Route::get('/products/{product}/reviews', [\App\Http\Controllers\ReviewController::class, 'index']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/products/{product}/reviews', [\App\Http\Controllers\ReviewController::class, 'store']);
    Route::patch('/reviews/{id}', [\App\Http\Controllers\ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [\App\Http\Controllers\ReviewController::class, 'destroy']);
    Route::post('/reviews/{id}/reply', [\App\Http\Controllers\ReviewController::class, 'reply']);
    Route::post('/reviews/{id}/flag', [\App\Http\Controllers\ReviewController::class, 'flag']);
    Route::post('/reviews/{id}/helpful', [\App\Http\Controllers\ReviewController::class, 'helpful']);
});



// Product discount management
Route::middleware('auth:sanctum')->group(function () {
    Route::put('products/{product}/discount', [\App\Http\Controllers\ProductController::class, 'updateDiscount']);
    Route::delete('products/{product}/discount', [\App\Http\Controllers\ProductController::class, 'removeDiscount']);
});



// RESTful slug-based fetch routes
Route::get('/categories/slug/{slug}', [CategoryController::class, 'showBySlug']);
Route::get('/subcategories/slug/{slug}', [SubcategoryController::class, 'showBySlug']);
Route::get('/locations/slug/{slug}', [LocationController::class, 'showBySlug']);
Route::get('/attributes/slug/{slug}', [AttributeController::class, 'showBySlug']);
Route::get('/attributes/{attribute}/values/slug/{slug}', [AttributeValueController::class, 'showBySlug']);
// Subcategory filter by category/location slug
Route::get('/subcategories/filter', [SubcategoryController::class, 'filterByCategoryAndLocation']);