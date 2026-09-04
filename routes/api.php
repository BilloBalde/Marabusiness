<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\ApiProductsPageController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\TestController; // Add this
use App\Http\Controllers\Api\ProductReviewController;
use App\Http\Controllers\LengoPayWebhookController; 
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SuccessPageController;

// Simple test endpoint (outside v1 prefix for easy testing)
Route::get('/ping', function() {
    return response()->json([
        'success' => true,
        'message' => 'API is working!',
        'timestamp' => now()->toDateTimeString()
    ]);
});
Route::post('/payments/lengopay/callback', [LengoPayWebhookController::class, 'handle'])
    ->name('lengopay.callback');

// Public routes
Route::prefix('v1')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });

    // Public data routes
    Route::get('/navbar', [App\Http\Controllers\Api\NavbarController::class, 'index']);
    Route::post('/navbar/switch-currency', [App\Http\Controllers\Api\NavbarController::class, 'switchCurrency']);
    Route::post('/navbar/switch-language', [App\Http\Controllers\Api\NavbarController::class, 'switchLanguage']); // Add this line
    Route::get('/home', [HomeController::class, 'index']);
    // In routes/api.php - Add these routes

    Route::prefix('products')->group(function () {
        // Existing routes
        Route::get('/', [ProductController::class, 'index']);
        Route::get('{slug}/{vendor_product_id}', [ProductController::class, 'show']);
        
        // NEW ROUTES FOR REVIEWS
        Route::prefix('reviews')->group(function () {
            Route::get('{vendor_product_id}', [ProductReviewController::class, 'index']);
            Route::post('{vendor_product_id}', [ProductReviewController::class, 'store'])->middleware('auth:sanctum');
        });
        
        Route::prefix('review')->group(function () {
            Route::put('{review_id}', [ProductReviewController::class, 'update'])->middleware('auth:sanctum');
            Route::delete('{review_id}', [ProductReviewController::class, 'destroy'])->middleware('auth:sanctum');
        });
    });
    Route::get('/products-page', [ApiProductsPageController::class, 'index']);
    // Brands
    Route::get('/brands', [App\Http\Controllers\Api\BrandController::class, 'index']);
    Route::get('/brands/{id}', [App\Http\Controllers\Api\BrandController::class, 'show']);
    Route::get('/brands/featured', [App\Http\Controllers\Api\BrandController::class, 'featured']);
    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::get('/categories/tree', [CategoryController::class, 'tree']);
    Route::get('/categories/featured', [CategoryController::class, 'featured']);
    // Vendors
    Route::get('/vendors', [VendorController::class, 'index']);
    Route::get('/vendors/featured', [VendorController::class, 'featured']);
    Route::get('/vendors/{slug}', [VendorController::class, 'show']);
    Route::get('/vendors/{vendorId}/products', [VendorController::class, 'products']);
    Route::get('/vendors/{vendorId}/reviews', [VendorController::class, 'reviews']);
    Route::get('/vendors/{vendorId}/stats', [VendorController::class, 'stats']);
    Route::post('/vendor/apply', [VendorController::class, 'apply']);
    
    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/add/{vendorProductId}', [WishlistController::class, 'add']);
    Route::delete('/wishlist/remove/{vendorProductId}', [WishlistController::class, 'remove']);
    Route::delete('/wishlist/remove-key/{wishlistKey}', [WishlistController::class, 'removeByKey']);
    Route::post('/wishlist/clear', [WishlistController::class, 'clear']);
    Route::post('/wishlist/move-to-cart', [WishlistController::class, 'moveToCart']);

    // Add this with your other public routes
    Route::get('/currencies', [App\Http\Controllers\Api\CurrencyController::class, 'index']);

    Route::prefix('reviews')->group(function () {
        Route::get('{vendor_product_id}', [ProductReviewController::class, 'index']);
        Route::post('{vendor_product_id}', [ProductReviewController::class, 'store'])->middleware('auth:sanctum');
    });
    // Inside your v1 prefix group (public routes), add:
    Route::post('/contact', [App\Http\Controllers\Api\ContactController::class, 'submit']);
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/orders/{orderId}/payment/session', [PaymentController::class, 'createPaymentSession']);
        Route::post('/orders/{orderId}/payment/offline', [PaymentController::class, 'submitOfflinePayment']);
        // Success page orders
        Route::get('/success/orders', [App\Http\Controllers\Api\SuccessPageController::class, 'index']);
    
        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        // 👇 ADD THESE USER PROFILE ROUTES HERE 👇
        Route::get('/user/profile', [UserController::class, 'profile']);
        Route::put('/user/profile', [UserController::class, 'updateProfile']);

        Route::get('/user/vendor-status', [VendorController::class, 'userVendorStatus']);
        Route::post('/vendors/{vendorId}/reviews', [VendorController::class, 'submitReview']); // Submit review
        Route::delete('/vendors/{vendorId}/reviews', [VendorController::class, 'deleteReview']);

        // Vendor follow routes
        Route::prefix('vendors')->group(function () {
            Route::post('{vendor}/follow', [VendorController::class, 'toggleFollow']);
            Route::get('{vendor}/follow/check', [VendorController::class, 'checkFollow']);
        });
        // Cart
        Route::prefix('cart')->group(function () {
            Route::get('/', [CartController::class, 'index']);
            Route::post('/add', [CartController::class, 'add']);
            Route::put('/update/{cart_key}', [CartController::class, 'update']);
            Route::delete('/remove/{cart_key}', [CartController::class, 'remove']);
            Route::post('/clear', [CartController::class, 'clear']);
            Route::get('/summary', [CartController::class, 'summary']);
            Route::get('/count', [CartController::class, 'count']);
        });
        
        // Orders
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{orderId}', [OrderController::class, 'show']);
        Route::post('/orders/{orderId}/cancel', [OrderController::class, 'cancel']);
        Route::get('/orders/{orderId}/track', [OrderController::class, 'track']);
        Route::get('/orders/{orderId}/invoice', [OrderController::class, 'invoice']);
        Route::get('/orders/{orderId}/invoice/download', [OrderController::class, 'downloadInvoice']);
        
        // Checkout
        Route::post('/checkout/calculate-shipping', [CheckoutController::class, 'calculateShipping']);
        Route::post('/checkout/place-order', [CheckoutController::class, 'placeOrder']);
        
        // Addresses
        Route::get('/addresses', [AddressController::class, 'index']);
        Route::post('/addresses', [AddressController::class, 'store']);
        Route::get('/addresses/{id}', [AddressController::class, 'show']);
        Route::put('/addresses/{id}', [AddressController::class, 'update']);
        Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);
        Route::post('/addresses/{id}/default', [AddressController::class, 'setDefault']);
        Route::get('/addresses/default/get', [AddressController::class, 'getDefault']);
        
        // Chat
        Route::get('/chats', [ChatController::class, 'index']);
        Route::get('/chats/{userId}', [ChatController::class, 'show']);
        Route::post('/chats/send', [ChatController::class, 'send']);
        Route::get('/chats/unread/count', [ChatController::class, 'unreadCount']);
        Route::post('/chats/mark-read', [ChatController::class, 'markAsRead']);
    });
});