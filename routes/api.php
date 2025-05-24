<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
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
use App\Http\Controllers\SimulatedEmailController;
use App\Http\Controllers\VerificationTokenController;

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

// Email Verification Routes (Public)
Route::post('/verify-email/token', [UserController::class, 'verifyEmailToken'])->name('verify.email.token');
Route::get('/verify-email/{token}', [UserController::class, 'verifyEmailToken'])->name('verify.email');
Route::post('/verify-email/code', [UserController::class, 'verifyEmailCode'])->name('verify.email.code');

// Simulated Email Routes (Development Only)
Route::prefix('simulated-emails')->group(function () {
    Route::get('/', [SimulatedEmailController::class, 'index'])->name('simulated-emails.index');
    Route::get('/{filename}', [SimulatedEmailController::class, 'show'])->name('simulated-emails.show');
    Route::delete('/{filename}', [SimulatedEmailController::class, 'destroy'])->name('simulated-emails.destroy');
    Route::delete('/', [SimulatedEmailController::class, 'destroyAll'])->name('simulated-emails.destroy-all');
});

// Debug route for simulated emails
Route::get('/debug/simulated-emails', function() {
    $emailsDir = storage_path('simulated-emails');
    $files = \Illuminate\Support\Facades\File::files($emailsDir);

    $emails = [];
    foreach ($files as $file) {
        $emails[] = [
            'filename' => $file->getFilename(),
            'size' => $file->getSize(),
            'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
            'content' => \Illuminate\Support\Facades\File::get($file->getPathname()),
        ];
    }

    return response()->json([
        'success' => true,
        'message' => 'Debug: Simulated emails retrieved directly',
        'data' => [
            'emails_directory' => $emailsDir,
            'directory_exists' => \Illuminate\Support\Facades\File::exists($emailsDir),
            'email_count' => count($emails),
            'emails' => $emails,
        ]
    ]);
});

// Verification Token Routes (Development Only)
Route::prefix('verification-tokens')->group(function () {
    Route::get('/', [VerificationTokenController::class, 'index'])->name('verification-tokens.index');
    Route::get('/{token}', [VerificationTokenController::class, 'show'])->name('verification-tokens.show');
    Route::delete('/{token}', [VerificationTokenController::class, 'destroy'])->name('verification-tokens.destroy');
    Route::delete('/', [VerificationTokenController::class, 'destroyAll'])->name('verification-tokens.destroy-all');
});

// Debug route for verification tokens
Route::get('/debug/verification-tokens', function() {
    $tokens = \App\Models\EmailVerificationToken::with('user')->get();

    $tokenData = $tokens->map(function ($token) {
        return [
            'id' => $token->id,
            'user_id' => $token->user_id,
            'username' => $token->user->username,
            'email' => $token->email,
            'token' => $token->token,
            'verification_code' => $token->verification_code,
            'created_at' => $token->created_at->format('Y-m-d H:i:s'),
            'expires_at' => $token->expires_at->format('Y-m-d H:i:s'),
            'is_expired' => $token->isExpired(),
        ];
    });

    return response()->json([
        'success' => true,
        'message' => 'Debug: Verification tokens retrieved directly',
        'data' => [
            'token_count' => $tokens->count(),
            'tokens' => $tokenData,
        ]
    ]);
});

// Debug route for auth testing
Route::get('/debug/auth-test', function() {
    return response()->json([
        'success' => true,
        'message' => 'Public route accessible',
        'authenticated' => auth()->check(),
        'user' => auth()->check() ? auth()->user()->only(['id', 'email', 'username']) : null,
        'session_id' => session()->getId(),
        'cookies' => request()->cookies->all(),
        'auth_driver' => Auth::getDefaultDriver(),
        'guards' => array_keys(config('auth.guards'))
    ]);
});

// Debug route for CSRF token
Route::get('/debug/csrf', function() {
    return response()->json([
        'success' => true,
        'message' => 'CSRF token generated',
        'token' => csrf_token(),
        'session_id' => session()->getId(),
        'cookies' => request()->cookies->all()
    ]);
});

// Debug route for cookie settings
Route::get('/debug/cookie-settings', function() {
    $sessionConfig = config('session');
    $corsConfig = config('cors');
    $sanctumConfig = config('sanctum');

    // Clean up sensitive or verbose data
    unset($sessionConfig['lottery'], $sessionConfig['files']);

    return response()->json([
        'success' => true,
        'message' => 'Cookie and CORS settings',
        'session_config' => $sessionConfig,
        'cors_config' => $corsConfig,
        'sanctum_config' => [
            'stateful_domains' => $sanctumConfig['stateful'],
            'supports_credentials' => $corsConfig['supports_credentials'],
            'same_site' => $sessionConfig['same_site'],
            'secure' => $sessionConfig['secure'],
            'domain' => $sessionConfig['domain']
        ],
        'request_info' => [
            'origin' => request()->header('Origin'),
            'host' => request()->getHost(),
            'port' => request()->getPort(),
            'url' => request()->url(),
            'full_url' => request()->fullUrl()
        ]
    ]);
});

// Test route for setting a test cookie
Route::get('/debug/set-test-cookie', function() {
    $response = response()->json([
        'success' => true,
        'message' => 'Test cookie set',
        'session_id' => session()->getId(),
        'cookies' => request()->cookies->all()
    ]);

    // Set a test cookie with the same settings as the session cookie
    $response->cookie(
        'test_cookie',
        'test_value',
        60, // 1 hour
        config('session.path'),
        config('session.domain'),
        config('session.secure'),
        config('session.http_only'),
        false,
        config('session.same_site')
    );

    return $response;
});

// Test route for setting cookies with different SameSite values
Route::get('/debug/cookie-test/{same_site?}', function($sameSite = null) {
    $sameSite = $sameSite ?: config('session.same_site');
    $secure = request()->query('secure') !== 'false'; // Default to true unless explicitly set to false

    $response = response()->json([
        'success' => true,
        'message' => 'Test cookies set with different configurations',
        'settings' => [
            'same_site' => $sameSite,
            'secure' => $secure,
            'domain' => config('session.domain'),
            'path' => config('session.path')
        ],
        'session_id' => session()->getId()
    ]);

    // Set cookies with different configurations
    $response->cookie(
        'test_cookie_' . $sameSite . ($secure ? '_secure' : '_insecure'),
        'test_value_' . time(),
        60, // 1 hour
        config('session.path'),
        config('session.domain'),
        $secure, // Secure flag
        false, // Not HTTP only so JavaScript can read it
        false,
        $sameSite
    );

    return $response;
});

// Protected debug route for auth testing
Route::middleware(['auth:sanctum,web'])->get('/debug/auth-protected', function() {
    return response()->json([
        'success' => true,
        'message' => 'Protected route accessible - you are authenticated!',
        'user' => auth()->user()->only(['id', 'email', 'username']),
        'session_id' => session()->getId(),
        'cookies' => request()->cookies->all()
    ]);
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

    // New Seller Verification Routes - Step by Step Approach
    Route::prefix('seller')->middleware('auth:sanctum')->group(function () {
        Route::post('/verify-phone', [SellerController::class, 'verifyPhone'])->name('seller.verify-phone');
        Route::post('/update-profile', [SellerController::class, 'updateProfile'])->name('seller.update-profile');
        Route::post('/verify-kyc', [SellerController::class, 'verifyKYC'])->name('seller.verify-kyc');
        Route::post('/accept-agreement', [SellerController::class, 'acceptAgreement'])->name('seller.accept-agreement');
        Route::post('/update-payment-info', [SellerController::class, 'updatePaymentInfo'])->name('seller.update-payment-info');
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