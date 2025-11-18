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
use App\Livewire\ProducDetailPage;
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

Route::get('/', HomePage::class)->name('home');
Route::get('/categories', CategoriesPage::class);
Route::get('/products', ProductsPage::class)->name('products');
Route::get('/cart', CartPage::class);
Route::get('/products/{slug}', ProducDetailPage::class);
Route::get('/about-us', AboutUsPage::class);
Route::get('/privacy-policy', PrivacyPolicy::class);
Route::get('/terms-of-use', Terms::class);
Route::get('/contact', ContactPage::class);


Route::middleware('guest')->group(function () {
    Route::get('/login', AuthLoginPage::class);
    Route::get('/register', AuthRegisterPage::class);
    Route::get('/forgot-password', ForgotPage::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPasswordPage::class)->name('password.reset');
});
Route::get('/checkout', CheckoutPage::class);
Route::get('/success', SuccessPage::class)->name('success');
Route::get('/cancel', CancelPage::class)->name('cancel');
Route::get('/my-orders/{order_id}', OrderDetailPage::class)->name('my-orders.show');
Route::middleware('auth')->group(function (){
    Route::get('/orders', MyOrdersPage::class)->name('my-orders');
    Route::get('/chats/{chatWithId}', CustomerChat::class)->name('chats');
    Route::get('/logout', function () {
        \Illuminate\Support\Facades\Auth::logout();
        session()->flash('success', 'Utilisateur déconnecté avec succès');
        return redirect()->route('home');
    })->name('logout');
});
