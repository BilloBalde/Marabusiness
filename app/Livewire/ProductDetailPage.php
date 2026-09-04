<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\VendorProduct;
use Illuminate\Support\Facades\Auth;
use App\Livewire\Partials\Navbar;
use App\Helpers\CartManagement;
use App\Helpers\WishlistManagement;

class ProductDetailPage extends Component
{
    public $product;
    public $vendorProduct;
    public $vendor;

    public $price = 0;
    public $stock = 0;
    public $quantity = 1;

    public $vendorProductId;
    public $basePrice = 0;

    // ✅ Wholesale
    public $wholesaleTiers = [];            // tiers for CURRENT selection (variation or product)
    public $wholesaleTiersByVariation = []; // tiers per variation (for display)
    public $useFallbackWholesale = true;    // if variation has no tiers -> fallback to product tiers

    // ✅ Variations
    public $selectedVariation = null;
    public $availableVariations = [];
    public $variationAttributes = []; // ['Color' => ['Black', 'White'], ...]
    public $selectedAttributes = [];

    // UI / misc
    public $customNote = '';
    public $similarProducts = [];
    public $reviews;
    public $rating = 0;
    public $comment = '';
    public $hasReviewed = false;
    public $editingReview = false;
    public $userReviewId = null;
    public $verifiedPurchase = false;

    // Media
    public $currentImage = '';
    public $mainMediaType = 'image'; // 'image' or 'video'
    public $isPlayingVideo = false;

    public function mount($slug, $vendor_product_id)
    {
        $this->product = Product::where('slug', $slug)->firstOrFail();

        if (!empty($this->product->images)) {
            $this->currentImage = $this->product->images[0];
        }

        // ✅ eager load product tiers + variations + variation tiers
        $this->vendorProduct = VendorProduct::with([
            'vendor.currency',
            'reviews.user',
            'vendor',
            'wholesaleTiers',
            'variations.wholesaleTiers',
        ])->findOrFail($vendor_product_id);

        $this->vendor = $this->vendorProduct->vendor;
        $this->vendorProductId = $this->vendorProduct->id;

        // Variations list
        $this->availableVariations = $this->vendorProduct->variations;

        // Build UI options
        $this->buildVariationAttributes();

        // Build tiers-per-variation map (for display + logic)
        $this->buildWholesaleTiersByVariation();

        // Select default (first) variation if exists
        if ($this->vendorProduct->has_variations && $this->availableVariations->count() > 0) {
            $this->selectedVariation = $this->availableVariations->first();
            $this->parseSelectedVariation();

            $this->stock = (int) $this->selectedVariation->stock;
            $this->basePrice = (float) ($this->selectedVariation->sale_price ?? $this->selectedVariation->price);
        } else {
            // Simple product
            $this->stock = (int) ($this->vendorProduct->stock ?? 0);
            $this->basePrice = (float) ($this->vendorProduct->sale_price ?: $this->vendorProduct->price ?: 0);
        }

        // ✅ set CURRENT tiers according to selection
        $this->loadWholesaleTiersForCurrentSelection();

        // Price for qty=1
        $this->quantity = 1;
        $this->price = $this->getWholesaleUnitPrice($this->quantity);

        // Reviews
        $this->reviews = $this->vendorProduct->reviews()->with('user')->latest()->get();

        if (Auth::check()) {
            $existing = $this->vendorProduct->reviews()->where('user_id', Auth::id())->first();
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

    /* ==============================
     | Media
     ============================== */

    public function selectImage($index)
    {
        $this->mainMediaType = 'image';
        $this->currentImage = $this->product->images[$index] ?? $this->currentImage;
        $this->isPlayingVideo = false;
    }

    public function showVideo()
    {
        $this->mainMediaType = 'video';
        $this->isPlayingVideo = true;
    }

    public function getYoutubeId($url = null)
    {
        $url = $url ?? $this->product->video_url;
        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches);
        return $matches[1] ?? null;
    }

    public function getVimeoId($url = null)
    {
        $url = $url ?? $this->product->video_url;
        preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches);
        return $matches[1] ?? null;
    }

    /* ==============================
     | Variations
     ============================== */

    private function buildVariationAttributes()
    {
        if (!$this->vendorProduct->has_variations) {
            return;
        }

        foreach ($this->availableVariations as $variation) {
            $attributes = $variation->attributes ?? [];
            foreach ($attributes as $key => $value) {
                if (!isset($this->variationAttributes[$key])) {
                    $this->variationAttributes[$key] = [];
                }
                if (!in_array($value, $this->variationAttributes[$key], true)) {
                    $this->variationAttributes[$key][] = $value;
                }
            }
        }

        // Initialize selected attributes with first values
        foreach ($this->variationAttributes as $attribute => $values) {
            $this->selectedAttributes[$attribute] = $values[0] ?? null;
        }
    }

    public function selectAttribute($attribute, $value)
    {
        $this->selectedAttributes[$attribute] = $value;
        $this->findMatchingVariation();
    }

    private function findMatchingVariation()
    {
        if (!$this->vendorProduct->has_variations) {
            return;
        }

        foreach ($this->availableVariations as $variation) {
            $match = true;
            foreach ($this->selectedAttributes as $attr => $value) {
                if (($variation->attributes[$attr] ?? '') !== $value) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                $this->selectedVariation = $variation;
                $this->parseSelectedVariation();

                $this->stock = (int) $variation->stock;
                $this->basePrice = (float) ($variation->sale_price ?? $variation->price);

                // ✅ update tiers for this variation
                $this->loadWholesaleTiersForCurrentSelection();

                $this->price = $this->getWholesaleUnitPrice($this->quantity);
                $this->dispatch('variation-updated');
                return;
            }
        }

        // fallback to first variation
        $this->selectedVariation = $this->availableVariations->first();
        $this->parseSelectedVariation();

        if ($this->selectedVariation) {
            $this->stock = (int) $this->selectedVariation->stock;
            $this->basePrice = (float) ($this->selectedVariation->sale_price ?? $this->selectedVariation->price);
        }

        $this->loadWholesaleTiersForCurrentSelection();
        $this->price = $this->getWholesaleUnitPrice($this->quantity);
    }

    private function parseSelectedVariation()
    {
        if ($this->selectedVariation) {
            $this->selectedAttributes = $this->selectedVariation->attributes ?? [];
        }
    }

    public function getSelectedVariationsText()
    {
        if (empty($this->selectedAttributes)) {
            return '';
        }

        $parts = [];
        foreach ($this->selectedAttributes as $attribute => $value) {
            $parts[] = ucfirst($attribute) . ': ' . $value;
        }

        return implode(', ', $parts);
    }

    /* ==============================
     | Wholesale pricing (PRODUCT + VARIATION)
     ============================== */

    private function normalizeTiers($tiers): array
    {
        return collect($tiers)
            ->sortBy('min_qty')
            ->map(function ($tier) {
                return [
                    'min_qty' => (int) $tier->min_qty,
                    'max_qty' => $tier->max_qty !== null ? (int) $tier->max_qty : null,
                    'price'   => (float) $tier->price,
                ];
            })
            ->values()
            ->toArray();
    }

    private function buildWholesaleTiersByVariation(): void
    {
        $map = [];

        foreach ($this->availableVariations as $v) {
            // $v->wholesaleTiers is eager loaded
            $map[$v->id] = $this->normalizeTiers($v->wholesaleTiers ?? []);
        }

        $this->wholesaleTiersByVariation = $map;
    }

    private function loadWholesaleTiersForCurrentSelection(): void
    {
        // product has variations
        if ($this->vendorProduct->has_variations && $this->selectedVariation) {
            $tiers = $this->wholesaleTiersByVariation[$this->selectedVariation->id] ?? [];

            // fallback to product tiers if variation tiers missing
            if (empty($tiers) && $this->useFallbackWholesale) {
                $tiers = $this->normalizeTiers($this->vendorProduct->wholesaleTiers ?? []);
            }

            $this->wholesaleTiers = $tiers;
            return;
        }

        // simple product tiers
        $this->wholesaleTiers = $this->normalizeTiers($this->vendorProduct->wholesaleTiers ?? []);
    }

    /**
     * ✅ Final unit price:
     * - if variation: use variation tier for qty
     * - else fallback to product tier for qty (optional)
     * - else base price
     */
    private function getWholesaleUnitPrice($quantity)
    {
        $qty = (int) $quantity;

        // Variation first
        if ($this->vendorProduct->has_variations && $this->selectedVariation) {
            $variationWholesale = $this->selectedVariation->getWholesalePriceForQty($qty);
            if ($variationWholesale !== null) {
                return (float) $variationWholesale;
            }

            // optional fallback to product tiers
            if ($this->useFallbackWholesale) {
                $productWholesale = $this->vendorProduct->getWholesalePriceForQty($qty);
                if ($productWholesale !== null) {
                    return (float) $productWholesale;
                }
            }

            return (float) ($this->selectedVariation->sale_price ?? $this->selectedVariation->price ?? 0);
        }

        // Simple product tiers
        $productWholesale = $this->vendorProduct->getWholesalePriceForQty($qty);
        if ($productWholesale !== null) {
            return (float) $productWholesale;
        }

        return (float) ($this->vendorProduct->sale_price ?: $this->vendorProduct->price ?: 0);
    }

    /* ==============================
     | Cart / wishlist
     ============================== */

    public function addToCart()
    {
        if (!Auth::check()) {
            $this->dispatch('show-toast', message: 'Please login to add items to cart.', type: 'warning');
            return $this->redirectRoute('customer_login');
        }

        $qty = (int) $this->quantity;
        if ($qty < 1) $qty = 1;

        // Stock check (if you track stock)
        if ($this->stock > 0 && $qty > $this->stock) {
            $this->dispatch('show-toast', message: "Only {$this->stock} items available in stock.", type: 'error');
            return;
        }

        $variationId = $this->selectedVariation ? $this->selectedVariation->id : null;

        $result = CartManagement::addItemToCart(
            vendor_product_id: $this->vendorProductId,
            quantity: $qty,
            variation_id: $variationId,
            selectedAttributes: $this->selectedAttributes,
            custom_note: $this->customNote
        );

        if (!$result || !is_array($result)) {
            $this->dispatch('show-toast', message: 'Error adding item to cart. Please try again.', type: 'error');
            return;
        }

        if ($result['success']) {
            $this->dispatch('cart-updated', total_count: $result['cart_count'])->to(Navbar::class);
            $this->dispatch('cart-added');
            $this->dispatch('show-toast', message: $result['message'], type: 'success');
        } else {
            $this->dispatch('show-toast', message: $result['message'], type: 'error');
        }
    }

    public function addToWishlist()
    {
        $variationId = $this->selectedVariation ? $this->selectedVariation->id : null;

        WishlistManagement::addItem(
            $this->vendorProductId,
            $variationId,
            $this->selectedAttributes
        );

        $this->dispatch('wishlist-updated', total_count: WishlistManagement::getCount());
        $this->dispatch('show-toast', message: 'Added to wishlist.', type: 'success');
    }

    public function increaseQty()
    {
        if ($this->stock && $this->quantity >= $this->stock) {
            return;
        }

        $this->quantity++;
        $this->price = $this->getWholesaleUnitPrice($this->quantity);
    }

    public function decreaseQty()
    {
        if ($this->quantity > 1) {
            $this->quantity--;
            $this->price = $this->getWholesaleUnitPrice($this->quantity);
        }
    }

    /* ==============================
     | Reviews (keep yours, simplified)
     ============================== */

    public function submitReview()
    {
        if (!Auth::check()) return;

        $this->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:3',
        ]);

        \App\Models\VendorProductReview::create([
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

        $review = \App\Models\VendorProductReview::find($this->userReviewId);
        if ($review) {
            $review->update([
                'rating' => $this->rating,
                'comment' => $this->comment,
            ]);
        }

        $this->editingReview = false;
        $this->reviews = $this->vendorProduct->reviews()->with('user')->latest()->get();
    }

    public function render()
    {
        return view('livewire.product-detail-page', [
            'avgRating' => $this->product->globalRating(),
            'totalReviews' => $this->product->totalReviews(),

            // ✅ show current tiers (variation/product)
            'wholesaleTiers' => $this->wholesaleTiers,

            // ✅ show tiers per variation if you want
            'wholesaleTiersByVariation' => $this->wholesaleTiersByVariation,
            'availableVariations' => $this->availableVariations,

            'selectedVariationsText' => $this->getSelectedVariationsText(),
        ]);
    }
}
