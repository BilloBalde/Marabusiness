<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Livewire\AboutUsPage;
use App\Livewire\Auth\ForgotPage;
use App\Livewire\Auth\LoginPage as AuthLoginPage;
use App\Livewire\Auth\ResetPasswordPage;
use App\Livewire\CancelPage;
use App\Livewire\CartPage;
use App\Livewire\CategoriesPage;
use App\Livewire\CheckoutPage;
use App\Livewire\HomePage;
use App\Livewire\MyOrdersPage;
use App\Livewire\OrderDetailPage;
use App\Livewire\ProductDetailPage;
use App\Livewire\ProductsPage;
use App\Livewire\SuccessPage;
use App\Livewire\WishlistPage;
use App\Livewire\Auth\RegisterPage as AuthRegisterPage;
use App\Livewire\Chat;
use App\Livewire\ContactPage;
use App\Livewire\CustomerChat;
use App\Livewire\PrivacyPolicy;
use App\Livewire\IntellectualPropertyPage;
use App\Livewire\ReturnsPage;
use App\Livewire\DeliveryPage;
use App\Livewire\ProductSafetyPage;
use App\Livewire\SecurityPage;
use App\Livewire\CookiesPage;
use App\Livewire\Terms;
use App\Livewire\FaqPage;
use App\Livewire\LegalPage;
use App\Livewire\BuyerProtectionPage;
use App\Livewire\DigitalServicesPage;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SocialAuthController;
use App\Livewire\MyAddresses;

Route::middleware('auth')->get('/my-addresses', MyAddresses::class)->name('my.addresses');

Route::get('/locale/{locale}', function (string $locale) {
    $available = ['en', 'fr', 'zh'];

    abort_unless(in_array($locale, $available, true), 404);

    session(['locale' => $locale]);
    app()->setLocale($locale);

    return back();
})->name('locale.switch');

Route::get('/', HomePage::class)->name('home');
Route::get('/categories', CategoriesPage::class);
Route::get('/products', ProductsPage::class)->name('products');
Route::get('/vendors', \App\Livewire\VendorsPage::class)->name('vendors.list');
use App\Livewire\VendorApplyPage;

Route::get('/vendor/apply', VendorApplyPage::class)->name('vendor.apply');

Route::get('/vendor/{slug}', \App\Livewire\VendorPage::class)->name('vendor.show');
Route::get('/cart', CartPage::class)->name('cart');
Route::get('/products/{slug}/{vendor_product_id}', ProductDetailPage::class)->name('product-show');
Route::get('/wishlist', WishlistPage::class)->name('wishlist');
Route::get('/about-us', AboutUsPage::class);
Route::get('/privacy-policy', PrivacyPolicy::class);
Route::get('/terms-of-use', Terms::class);
Route::get('/contact', ContactPage::class);
Route::get('/faq', FaqPage::class);
Route::get('/legal', LegalPage::class);
Route::get('/intellectual-property', IntellectualPropertyPage::class);
Route::get('/returns', ReturnsPage::class);
Route::get('/delivery', DeliveryPage::class);
Route::get('/product-safety', ProductSafetyPage::class);
Route::get('/buyer-protection', BuyerProtectionPage::class);
Route::get('/security', SecurityPage::class);
Route::get('/cookies', CookiesPage::class);
Route::get('/digital-services', DigitalServicesPage::class);
// Services routes
Route::get('/services', \App\Livewire\ServicePage::class)->name('services');
Route::get('/service/{slug}', \App\Livewire\ServicePage::class)->name('service.show');


Route::middleware('guest')->group(function () {
    Route::get('/login', AuthLoginPage::class)->name('customer_login');
    Route::get('/register', AuthRegisterPage::class);
    Route::get('/forgot-password', ForgotPage::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPasswordPage::class)->name('password.reset');
});
Route::get('/checkout', CheckoutPage::class);
Route::get('/success', SuccessPage::class)->name('success');
Route::get('/success-stripe', \App\Livewire\SuccessPageStripe::class)->name('success.stripe');
Route::get('/cancel', CancelPage::class)->name('cancel');
Route::get('/my-orders/{order_id}', OrderDetailPage::class)->name('my-orders.show');
Route::middleware('auth')->group(function (){
    Route::get('/orders', MyOrdersPage::class)->name('my-orders');
    Route::get('/chats/{chatWithId}', CustomerChat::class)->name('chats');
    Route::get('/my-rfqs', \App\Livewire\UserRfqsPage::class)->name('user.rfqs');
    Route::get('/rfq/{rfq}/chat', \App\Livewire\RfqChat::class)->name('rfq.chat');
    Route::get('/api/rfq/{rfq}', function (App\Models\BulkRfq $rfq) {
        // Authorization check
        if ($rfq->user_id !== auth()->id()) {
            abort(403);
        }
        
        return response()->json([
            'rfq' => $rfq->load(['product', 'vendor', 'user']),
            'quotes' => $rfq->offers()->latest()->get(),
        ]);
    })->name('api.rfq.details');
    /* Route::get('/logout', function () {
        \Illuminate\Support\Facades\Auth::logout();
        session()->flash('success', 'Utilisateur déconnecté avec succès');
        return redirect()->route('home');
    })->name('logout'); */
    Route::post('/logout', function () {
        \Illuminate\Support\Facades\Auth::logout();
        session()->flash('success', 'Utilisateur déconnecté avec succès');
        return redirect()->route('home');
    })->name('logout');

    // Keep GET for backward compatibility but redirect to POST
    Route::get('/logout', function () {
        return redirect()->route('home');
    })->name('logout.get');
});
Route::get('/cookies', \App\Livewire\CookieSettings::class)->name('cookies');
Route::get('/orders/{order}/invoice/preview', [InvoiceController::class, 'preview'])
    ->name('orders.invoice.preview');

Route::get('/orders/{order}/invoice/pdf', [InvoiceController::class, 'download'])
    ->name('orders.invoice.pdf');

Route::get('/filament/language', function () {
    return view('filament.language-menu');
})->name('filament.language.menu');

Route::get('/lang/{locale}', function ($locale) {
    $allowed = ['en', 'fr', 'zh'];
    if (! in_array($locale, $allowed)) {
        $locale = 'en';
    }

    session(['locale' => $locale]);
    app()->setLocale($locale);

    return redirect()->back();
})->name('lang.switch');

// GOOGLE
Route::get('/auth/google', [SocialAuthController::class, 'redirectGoogle'])->name('google.redirect');
Route::get('/auth/google/callback', [SocialAuthController::class, 'callbackGoogle']);

// FACEBOOK
Route::get('/auth/facebook', [SocialAuthController::class, 'redirectFacebook'])->name('facebook.redirect');
Route::get('/auth/facebook/callback', [SocialAuthController::class, 'callbackFacebook']);
// routes/web.php (temporary)
Route::get('/debug-routes', function() {
    $routes = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
        ->filter(function($route) {
            return str_contains($route->uri, 'bulk-rfq') || 
                   str_contains($route->getName() ?? '', 'bulk-rfq');
        })
        ->map(function($route) {
            return [
                'name' => $route->getName(),
                'uri' => $route->uri,
                'methods' => $route->methods,
                'action' => $route->action['controller'] ?? $route->action['uses'] ?? 'Closure',
            ];
        });
    
    return response()->json($routes->values());
});
// Debug route
Route::get('/debug-bulk-rfq-route', function() {
    $record = \App\Models\BulkRfq::first();
    if (!$record) {
        return "No BulkRfq records found";
    }
    
    return [
        'route_exists' => route_exists('filament.vendor.resources.bulk-rfqs.quote'),
        'route_url' => \App\Filament\Vendor\Resources\BulkRfqResource::getUrl('quote', ['record' => $record->id]),
        'panel_path' => config('filament-vendor.path'),
        'record_id' => $record->id,
    ];
});
// CSRF Token Refresh Route
Route::get('/refresh-csrf', function (Request $request) {
    // Regenerate token if needed
    if ($request->query('force') === 'true') {
        $request->session()->regenerateToken();
    }
    
    return response()->json([
        'token' => csrf_token(),
        'expires_in' => config('session.lifetime', 120) * 60,
        'last_activity' => session('last_activity', time())
    ]);
})->middleware('web')->name('csrf.refresh');

// Session Check Route
Route::match(['get', 'head'], '/check-session', function (Request $request) {
    if (Auth::check()) {
        session(['last_activity' => time()]);
        return response()->noContent();
    }
    return response()->json(['message' => 'Session expired'], 419);
})->middleware('web')->name('session.check');

// Keep-alive route for long-running sessions
Route::post('/keep-alive', function (Request $request) {
    if (Auth::check()) {
        session(['last_activity' => time()]);
        
        // Update panel-specific activity if in a panel
        if (session('current_panel')) {
            session(['last_activity_' . session('current_panel') => time()]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Session extended',
            'token' => csrf_token() // Return new token
        ]);
    }
    
    return response()->json([
        'success' => false,
        'message' => 'Not authenticated'
    ], 401);
})->middleware('web')->name('keep-alive');

Route::get('/debug-cookie', function() {
    $cart_items = session('cart_items', []);
    $cart_items = is_array($cart_items) ? $cart_items : [];

    return response()->json([
        'session_count' => count($cart_items),
        'items' => array_map(function($item) {
            return [
                'cart_key' => $item['cart_key'] ?? 'no_key',
                'vendor_product_id' => $item['vendor_product_id'] ?? null,
                'product_name' => $item['product_name'] ?? 'no_name'
            ];
        }, $cart_items)
    ]);
});
Route::get('/test-add-direct/{vendor_product_id}/{variation_id?}', function($vendor_product_id, $variation_id = null) {
    $cart_items = \App\Helpers\CartManagement::getCartItemsFromCookie();
    
    $new_item = [
        'vendor_product_id' => (int)$vendor_product_id,
        'product_id' => 999,
        'vendor_id' => 2,
        'product_name' => 'Test Direct Add',
        'image' => 'test.jpg',
        'quantity' => 1,
        'base_price' => 100,
        'currency' => 'USD',
        'rate_to_usd' => 1,
        'variation_id' => $variation_id ? (int)$variation_id : null,
        'selected_variations' => $variation_id ? ['Color' => 'Test'] : [],
        'variation_note' => $variation_id ? 'Color: Test' : '',
        'wholesale_applied' => false,
        'cart_key' => $variation_id ? "cart_{$vendor_product_id}_var_{$variation_id}" : "cart_{$vendor_product_id}_simple",
        'wholesale_tiers' => [],
        'total_amount' => 100,
        'unit_amount' => 100
    ];
    
    $cart_items[] = $new_item;
    
    \App\Helpers\CartManagement::addCartItemsToCookie($cart_items);
    
    // Verify
    $saved = \App\Helpers\CartManagement::getCartItemsFromCookie();
    
    return response()->json([
        'added_item' => $new_item['cart_key'],
        'saved_count' => count($saved),
        'saved_items' => array_map(function($item) {
            return $item['cart_key'] ?? 'no_key';
        }, $saved)
    ]);
});
Route::get('/test-cookie-limit', function() {
    // Clear current cart
    session()->forget('cart_items');
    
    // Create 5 simple test items
    $test_items = [];
    for ($i = 1; $i <= 5; $i++) {
        $test_items[] = [
            'vendor_product_id' => $i,
            'product_id' => $i,
            'vendor_id' => 1,
            'product_name' => 'Test Product ' . $i,
            'image' => 'test' . $i . '.jpg',
            'quantity' => 1,
            'base_price' => 100,
            'currency' => 'USD',
            'rate_to_usd' => 1,
            'variation_id' => null,
            'selected_variations' => [],
            'variation_note' => '',
            'wholesale_applied' => false,
            'cart_key' => 'cart_test_' . $i,
            'wholesale_tiers' => [],
            'total_amount' => 100,
            'unit_amount' => 100
        ];
    }
    
    // Save using CartManagement
    \App\Helpers\CartManagement::addCartItemsToCookie($test_items);
    
    // Check what was saved
    $saved = \App\Helpers\CartManagement::getCartItemsFromCookie();
    
    return response()->json([
        'tried_to_save' => count($test_items),
        'actually_saved' => count($saved),
        'saved_keys' => array_map(function($item) {
            return $item['cart_key'];
        }, $saved),
        'session_count' => count(session('cart_items', []))
    ]);
});
Route::get('/test-419', function () {
    throw new \Illuminate\Session\TokenMismatchException;
});

Route::get('/test-403', function () {
    abort(403);
});

Route::get('/test-500', function () {
    abort(500);
});

Route::get('/test-add-simple', function() {
    $cart_items = \App\Helpers\CartManagement::getCartItemsFromCookie();
    
    // Add a simple item
    $cart_items[] = [
        'vendor_product_id' => 999,
        'product_id' => 999,
        'vendor_id' => 1,
        'product_name' => 'Simple Test',
        'image' => 'test.jpg',
        'quantity' => 1,
        'base_price' => 100,
        'currency' => 'USD',
        'rate_to_usd' => 1,
        'variation_id' => null,
        'selected_variations' => [],
        'variation_note' => '',
        'wholesale_applied' => false,
        'cart_key' => 'simple_test_' . time(),
        'wholesale_tiers' => [],
        'total_amount' => 100,
        'unit_amount' => 100
    ];
    
    \App\Helpers\CartManagement::addCartItemsToCookie($cart_items, true);
    
    // Read back
    $saved = \App\Helpers\CartManagement::getCartItemsFromCookie();
    
    return response()->json([
        'added_count' => count($cart_items),
        'saved_count' => count($saved),
        'saved_keys' => array_map(function($item) {
            return $item['cart_key'] ?? 'no_key';
        }, $saved)
    ]);
});
