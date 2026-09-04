<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class NavbarController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $role = $user ? $user->roles->first()->name : null;
        $managerId = \App\Models\User::role('manager')->value('id');

        // Get currencies
        $currencies = Currency::where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        // Get current currency from session or header
        $currencyCode = $request->header('Currency', session('currency_code', 'USD'));

        // Get categories with family structure for menu
        $families = Category::select('family')
            ->distinct()
            ->whereNotNull('family')
            ->pluck('family');

        $categories = Category::orderBy('name')->get();

        $menuData = [];
        foreach ($families as $family) {
            $key = Str::slug($family);
            $menuData[$key] = $categories
                ->where('family', $family)
                ->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                ])
                ->values()
                ->toArray();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar ?? null,
                    'role' => $role,
                ] : null,
                'manager_id' => $managerId,
                'currencies' => $currencies,
                'current_currency' => $currencyCode,
                'cart_count' => $this->getCartCount(),
                'wishlist_count' => $this->getWishlistCount(),
                'families' => $families,
                'menu_data' => $menuData,
                'languages' => [
                    ['code' => 'en', 'name' => 'English', 'flag' => 'us', 'label' => 'EN'],
                    ['code' => 'fr', 'name' => 'Français', 'flag' => 'fr', 'label' => 'FR'],
                    ['code' => 'zh', 'name' => '中文', 'flag' => 'cn', 'label' => '中文'],
                ],
                'current_language' => app()->getLocale(),
                'social_links' => [
                    'facebook' => '#',
                    'instagram' => '#',
                    'whatsapp' => '#',
                    'linkedin' => '#',
                    'snapchat' => '#',
                ],
            ]
        ]);
    }

    public function switchCurrency(Request $request)
    {
        $request->validate(['currency_code' => 'required|string|exists:currencies,code']);
        
        session(['currency_code' => $request->currency_code]);
        
        return response()->json([
            'success' => true,
            'message' => 'Currency updated successfully',
            'currency_code' => $request->currency_code
        ]);
    }

    public function switchLanguage(Request $request)
    {
        $request->validate([
            'language_code' => 'required|string|in:en,fr,zh'
        ]);
        
        // Store in session
        session(['locale' => $request->language_code]);

        // If user is logged in, save to database
        if (Auth::check()) {
            $user = Auth::user();
            $user->locale = $request->language_code;
            $user->save();
        }
        
        // Set the application locale
        app()->setLocale($request->language_code);
        
        return response()->json([
            'success' => true,
            'message' => 'Language switched successfully',
            'language_code' => $request->language_code
        ]);
    }

    private function getCartCount()
    {
        // Implement your cart count logic
        return session('cart_count', 0);
    }

    private function getWishlistCount()
    {
        // Implement your wishlist count logic
        return session('wishlist_count', 0);
    }
}