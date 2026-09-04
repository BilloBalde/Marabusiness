<?php

namespace App\Livewire\Partials;

use App\Helpers\CartManagement;
use App\Helpers\WishlistManagement;
use App\Models\Currency;
use App\Models\Category;
use Livewire\Component;
use Livewire\Attributes\On;

class Navbar extends Component
{
    public $total_count = 0;
    public $wishlist_count = 0;
    public $currencyCode = '';
    public $currencies = [];

    public $families = [];
    public $menuData = [];

    public function hydrate()
    {
        logger()->info("NAVBAR HYDRATING...");
    }


    public function mount()
    {
        logger()->info("NAVBAR MOUNTED!");
        // 1. Load currencies (MUST be a public property!)
        $this->currencies = Currency::where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->toArray();

        // 2. Load initial cart count
        $this->total_count = CartManagement::getCartCount();
        $this->wishlist_count = WishlistManagement::getCount();

        // 3. Handle currency from session (fallback to first currency)
        $default = Currency::first()?->code ?? 'USD';
        $this->currencyCode = session('currency_code', $default);
        session(['currency_code' => $this->currencyCode]);

        // 4. Load categories (avoid doing it inside Blade)
        $families = Category::select('family')->distinct()->pluck('family');

        $categories = Category::orderBy('name')->get();

        $menuData = [];
        foreach ($families as $family) {
            $key = \Str::slug($family);
            $menuData[$key] = $categories
                ->where('family', $family)
                ->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                ])
                ->values()
                ->toArray();
        }

        $this->families = $families->toArray();
        $this->menuData = $menuData;
    }

    // Redirect after selecting currency
    public function updatedCurrencyCode()
    {
        \Log::info('NAVBAR: Currency changed to', ['code' => $this->currencyCode]);
        
        session(['currency_code' => $this->currencyCode]);

        // Broadcast to all components
        $this->dispatch('currency-changed', code: $this->currencyCode);
        
        \Log::info('NAVBAR: Event dispatched');
    }

    public function incrementTest()
    {
        $this->total_count++;
        logger()->info("TEST INCREMENT: {$this->total_count}");
    }


    #[On('cart-updated')]
    public function updateCartCount($total_count = null)
    {
        \Log::info('Navbar updateCartCount: Called', [
            'passed_total_count' => $total_count,
            'session_count' => count(session('cart_items', []))
        ]);
        $this->total_count = $total_count ?? CartManagement::getCartCount();
        logger()->info("CART MOUNTED!");
    }

    #[On('wishlist-updated')]
    public function updateWishlistCount($total_count = null)
    {
        $this->wishlist_count = $total_count ?? WishlistManagement::getCount();
    }

    public function render()
    {
        return view('livewire.partials.navbar');
    }
}
