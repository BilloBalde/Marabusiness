<?php

namespace App\Helpers;

use App\Models\VendorProduct;

class WishlistManagement
{
    public static function getWishlistItems(): array
    {
        $items = session('wishlist_items', []);
        return is_array($items) ? $items : [];
    }

    public static function addItem(int $vendor_product_id, ?int $variation_id = null, array $selectedAttributes = []): bool
    {
        $items = self::getWishlistItems();
        $key = self::generateWishlistKey($vendor_product_id, $variation_id, $selectedAttributes);

        foreach ($items as $item) {
            if (($item['wishlist_key'] ?? '') === $key) {
                return true;
            }
        }

        $items[] = [
            'vendor_product_id' => $vendor_product_id,
            'variation_id' => $variation_id,
            'selected_variations' => $selectedAttributes,
            'wishlist_key' => $key,
        ];

        session(['wishlist_items' => $items]);
        return true;
    }

    public static function removeItem(string $wishlist_key): array
    {
        $items = array_values(array_filter(self::getWishlistItems(), function ($item) use ($wishlist_key) {
            return ($item['wishlist_key'] ?? '') !== $wishlist_key;
        }));

        session(['wishlist_items' => $items]);
        return $items;
    }

    public static function clear(): void
    {
        session()->forget('wishlist_items');
    }

    public static function getCount(): int
    {
        return count(self::getWishlistItems());
    }

    public static function getWishlistDetails(): array
    {
        $details = [];

        foreach (self::getWishlistItems() as $item) {
            $vendorProduct = VendorProduct::with(['product', 'vendor.currency', 'variations'])
                ->find($item['vendor_product_id']);

            if (!$vendorProduct) {
                continue;
            }

            $variation = null;
            if (!empty($item['variation_id'])) {
                $variation = $vendorProduct->variations->firstWhere('id', $item['variation_id']);
            }

            $price = $variation ? $variation->price : ($vendorProduct->sale_price ?: $vendorProduct->price);
            $stock = $variation ? $variation->stock : $vendorProduct->stock;

            $image = null;
            $productImages = $vendorProduct->product->images ?? [];
            if (is_array($productImages) && count($productImages)) {
                $image = $productImages[0];
            }

            $details[] = [
                'wishlist_key' => $item['wishlist_key'],
                'vendor_product_id' => $vendorProduct->id,
                'product_id' => $vendorProduct->product_id,
                'vendor_id' => $vendorProduct->vendor_id,
                'product_name' => $vendorProduct->product->name ?? 'Unknown Product',
                'image' => $image,
                'base_price' => (float) $price,
                'currency' => $vendorProduct->vendor->currency->code ?? 'USD',
                'rate_to_usd' => $vendorProduct->vendor->currency->rate_to_usd ?? 1,
                'variation_id' => $item['variation_id'] ?? null,
                'selected_variations' => $item['selected_variations'] ?? [],
                'has_variations' => $vendorProduct->has_variations,
                'stock' => $stock,
            ];
        }

        return $details;
    }

    private static function generateWishlistKey(int $vendor_product_id, ?int $variation_id, array $selectedVariations): string
    {
        if ($variation_id) {
            return 'wish_' . $vendor_product_id . '_var_' . $variation_id;
        }

        if (!empty($selectedVariations)) {
            ksort($selectedVariations);
            return 'wish_' . $vendor_product_id . '_' . md5(json_encode($selectedVariations));
        }

        return 'wish_' . $vendor_product_id . '_simple';
    }
}
