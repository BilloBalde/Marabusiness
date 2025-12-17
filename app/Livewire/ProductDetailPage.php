<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\VendorProduct;
use App\Models\VendorProductReview;
use Illuminate\Support\Facades\Auth;
use App\Livewire\Partials\Navbar;
use App\Helpers\CartManagement;

class ProductDetailPage extends Component
{
    public $product;
    public $vendorProduct;
    public $vendor;
    public $price;
    public $stock;
    public $quantity = 1;

    public $vendorProductId;
    public $basePrice;
    public $wholesaleTiers = [];

    // Variation handling using variation_json
    public $variationOptions = [];
    public $selectedVariations = [];
    public $customNote = '';

    public $similarProducts = [];

    // Review state
    public $reviews;
    public $rating = 0;
    public $comment = '';
    public $hasReviewed = false;
    public $editingReview = false;
    public $userReviewId = null;
    public $verifiedPurchase = false;

    public function mount($slug, $vendor_product_id)
    {
        $this->product = Product::where('slug', $slug)->firstOrFail();

        $this->vendorProduct = VendorProduct::with([
            'vendor.currency', 
            'reviews.user', 
            'vendor', 
            'wholesaleTiers'
        ])->findOrFail($vendor_product_id);

        $this->vendor = $this->vendorProduct->vendor;

        if (!$this->vendor) {
            abort(404, "Product vendor not found.");
        }

        $this->vendorProductId = $this->vendorProduct->id;

        $this->basePrice = $this->vendorProduct->sale_price ?: $this->vendorProduct->price ?: 0;

        $this->quantity = 1;
        $this->stock = $this->vendorProduct->stock ?? 0;

        $this->wholesaleTiers = $this->vendorProduct->wholesaleTiers()
            ->orderBy('min_qty')
            ->get(['min_qty', 'max_qty', 'price'])
            ->map(fn($t) => [
                'min_qty' => (int) $t->min_qty,
                'max_qty' => $t->max_qty !== null ? (int) $t->max_qty : null,
                'price' => (float) $t->price
            ])
            ->toArray();

        // Initialize with correct price
        $this->price = $this->getWholesaleUnitPrice($this->quantity);

        $this->parseVariationJson();

        $this->reviews = $this->vendorProduct->reviews()->with('user')->latest()->get();

        if (Auth::check()) {
            $existing = VendorProductReview::where('vendor_product_id', $this->vendorProduct->id)
                ->where('user_id', Auth::id())
                ->first();

            if ($existing) {
                $this->hasReviewed = true;
                $this->rating = $existing->rating;
                $this->comment = $existing->comment;
                $this->userReviewId = $existing->id;
            }

            $this->verifiedPurchase = \App\Models\OrderItem::where('product_id', $this->product->id)
                ->whereHas('order', fn ($q) => $q->where('user_id', Auth::id()))
                ->exists();
        }

        $this->similarProducts = Product::where('category_id', $this->product->category_id)
            ->where('id', '!=', $this->product->id)
            ->limit(8)
            ->get();
    }

    /**
     * Parse variation_json into usable format
     */
    private function parseVariationJson()
    {
        $variationJson = $this->vendorProduct->variation_json ?? [];
        
        if (!empty($variationJson) && is_array($variationJson)) {
            $this->variationOptions = $variationJson;
            
            foreach ($variationJson as $attribute => $values) {
                if (is_array($values) && count($values) > 0) {
                    $this->selectedVariations[$attribute] = $values[0];
                }
            }
        }
    }

    /**
     * Compute unit price according to wholesale tiers + quantity.
     */
    private function getWholesaleUnitPrice(int $qty): float
    {
        $qty = max(1, $qty);

        if (empty($this->wholesaleTiers)) {
            return (float) $this->basePrice;
        }

        $applicable = collect($this->wholesaleTiers)
            ->filter(function ($tier) use ($qty) {
                $min = (int) ($tier['min_qty'] ?? 0);
                $max = $tier['max_qty'] !== null ? (int) $tier['max_qty'] : null;

                if ($qty < $min) {
                    return false;
                }

                if (!is_null($max) && $qty > $max) {
                    return false;
                }

                return true;
            })
            ->sortByDesc('min_qty') // Get the highest applicable tier
            ->first();

        if (!$applicable) {
            return (float) $this->basePrice;
        }

        return (float) $applicable['price'];
    }

    /**
     * Select variation value for an attribute
     */
    public function selectVariation($attribute, $value)
    {
        $this->selectedVariations[$attribute] = $value;
        $this->dispatch('variation-selected');
    }

    /**
     * Get selected variations as text
     */
    public function getSelectedVariationsText()
    {
        if (empty($this->selectedVariations)) {
            return '';
        }

        $parts = [];
        foreach ($this->selectedVariations as $attribute => $value) {
            $parts[] = ucfirst($attribute) . ': ' . $value;
        }
        
        return implode(', ', $parts);
    }

    /* ==============================
     | Add to Cart
     ============================== */
    public function addToCart()
    {
        //dd($this->vendorProductId);
        $qty = (int) $this->quantity;
        if ($qty < 1) {
            $qty = 1;
        }
        
        // Check stock availability
        if ($this->stock > 0) {
            $qty = min($qty, (int) $this->stock);
        }

        $count = CartManagement::addItemToCart(
            vendor_product_id: $this->vendorProductId,
            quantity: $qty,
            selectedVariations: $this->selectedVariations,
            custom_note: $this->customNote
        );

        $this->dispatch('cart-updated', total_count: $count)->to(Navbar::class);
        $this->dispatch('cart-added');
        
        session()->flash('success', 'Produit ajouté au panier!');
    }

    public function increaseQty()
    {
        if ($this->stock && $this->quantity >= $this->stock) {
            return;
        }

        $this->quantity++;
        $this->price = $this->getWholesaleUnitPrice($this->quantity);
        $this->dispatch('price-updated', price: $this->price);
    }

    public function decreaseQty()
    {
        if ($this->quantity > 1) {
            $this->quantity--;
            $this->price = $this->getWholesaleUnitPrice($this->quantity);
            $this->dispatch('price-updated', price: $this->price);
        }
    }

    /* ==============================
     | Reviews
     ============================== */

    public function submitReview()
    {
        if (!Auth::check()) return;

        $this->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:3',
        ]);

        VendorProductReview::create([
            'vendor_product_id' => $this->vendorProductId,
            'user_id' => Auth::id(),
            'rating' => $this->rating,
            'comment' => $this->comment,
        ]);

        $this->hasReviewed = true;
        $this->editingReview = false;
        $this->reviews = $this->vendorProduct->reviews()->with('user')->latest()->get();
    }

    public function editReview()
    {
        $this->editingReview = true;
    }

    public function updateReview()
    {
        $this->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|min:3',
        ]);

        $review = VendorProductReview::find($this->userReviewId);
        $review->update([
            'rating' => $this->rating,
            'comment' => $this->comment,
        ]);

        $this->editingReview = false;
        $this->reviews = $this->vendorProduct->reviews()->with('user')->latest()->get();
    }

    public function render()
    {
        return view('livewire.product-detail-page', [
            'avgRating' => $this->product->globalRating(),
            'totalReviews' => $this->product->totalReviews(),
            'wholesaleTiers' => $this->wholesaleTiers,
            'vendorProductID' => $this->vendorProduct->id,
            'selectedVariationsText' => $this->getSelectedVariationsText(),
        ]);
    }
}