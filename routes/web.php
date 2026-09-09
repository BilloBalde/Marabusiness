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

// Was a second, near-identical copy of lang.switch below (only difference: 404
// on an invalid locale instead of falling back to 'en') — the public navbar now
// points at lang.switch too, same as both Filament panels always did.

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
    Route::get('/login', AuthLoginPage::class)->name('login');
    Route::get('/register', AuthRegisterPage::class);
    Route::get('/forgot-password', ForgotPage::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPasswordPage::class)->name('password.reset');
});
Route::get('/checkout', CheckoutPage::class);
Route::get('/success', SuccessPage::class)->middleware('auth')->name('success');

// Absolute return URL handed to payment gateways (see config/app.php frontend_url).
// On Android the same URL is an App Link caught by the Flutter deep-link listener.
Route::get('/payment/success', SuccessPage::class)->middleware('auth')->name('payment.success');
Route::get('/success-stripe', \App\Livewire\SuccessPageStripe::class)->name('success.stripe');
Route::get('/cancel', CancelPage::class)->name('cancel');
Route::middleware('auth')->group(function (){
    // Was outside the auth group: reachable while logged out, and OrderDetailPage's
    // mount() had no ownership check either — any order id in the URL was viewable
    // by anyone. Both are fixed now (this middleware + the check in mount()).
    Route::get('/my-orders/{order_id}', OrderDetailPage::class)->name('my-orders.show');
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
// Auth here only proves someone is logged in; InvoiceController itself checks that
// this specific order belongs to them (buyer, its vendor, or an admin) — without it,
// anyone could read or download any customer's name, address, phone and full order
// total just by changing the id in the URL.
Route::middleware('auth')->group(function () {
    Route::get('/orders/{order}/invoice/preview', [InvoiceController::class, 'preview'])
        ->name('orders.invoice.preview');

    Route::get('/orders/{order}/invoice/pdf', [InvoiceController::class, 'download'])
        ->name('orders.invoice.pdf');
});

// Removed: /filament/language and its view. It returned a 500 (a Filament 2 page
// component rendered outside any page context) and its links used a ?lang= parameter
// that SetLocale never reads. Both panels already switch language through the
// lang.switch route below; the only thing still pointing here was a commented-out
// menu item in AdminPanelProvider.
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

// Removed: /debug-cookie, /test-add-direct, /test-cookie-limit, /test-419,
// /test-403, /test-500, /test-add-simple — leftover debugging routes with no
// environment gate, reachable in production exactly like any real route.
// /test-add-direct and /test-add-simple were the most serious: they wrote a
// cart line with an attacker-chosen base_price/unit_amount/total_amount straight
// into the session, and CheckoutController trusts those cart amounts when it
// builds the order total and the Stripe charge — so anyone hitting either route
// could check out at whatever price they picked. Removed rather than gated,
// since none of these had any legitimate use once the features they debugged
// (bulk RFQ routing, the cart cookie) were working.
