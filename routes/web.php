<?php

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
use App\Livewire\Auth\RegisterPage as AuthRegisterPage;
use App\Livewire\Chat;
use App\Livewire\ContactPage;
use App\Livewire\CustomerChat;
use App\Livewire\PrivacyPolicy;
use App\Livewire\Terms;
use Illuminate\Support\Facades\Route;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SocialAuthController;

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
Route::get('/vendor/{slug}', \App\Livewire\VendorPage::class)->name('vendor.show');
Route::get('/cart', CartPage::class)->name('cart');
Route::get('/products/{slug}/{vendor_product_id}', ProductDetailPage::class)->name('product-show');
Route::get('/about-us', AboutUsPage::class);
Route::get('/privacy-policy', PrivacyPolicy::class);
Route::get('/terms-of-use', Terms::class);
Route::get('/contact', ContactPage::class);
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
    Route::get('/logout', function () {
        \Illuminate\Support\Facades\Auth::logout();
        session()->flash('success', 'Utilisateur déconnecté avec succès');
        return redirect()->route('home');
    })->name('logout');
});

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

