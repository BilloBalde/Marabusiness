<?php

namespace App\Support;

use App\Models\VendorProduct;

/**
 * Canonical price and stock reading of a vendor's offer, including products sold in
 * variations, where the figure shown is the cheapest variation ("à partir de").
 *
 * The logic was written once in ApiProductsPageController and nowhere else, so the
 * home feed shipped its own version that ignored variations entirely and returned the
 * raw pivot price for display_price, original_price and sale_price alike — three
 * different fields carrying the same number, which is why no discount ever showed.
 *
 * Pass a VendorProduct with `variations` eager-loaded to keep this query-free.
 */
class ProductPricing
{
    public static function for(VendorProduct $vendorProduct, string $currency): array
    {
        $hasVariations = (bool) $vendorProduct->has_variations
            && $vendorProduct->variations
            && $vendorProduct->variations->count() > 0;

        $displayPrice    = 0.0;
        $minPrice        = 0.0;
        $maxPrice        = 0.0;
        $hasPriceRange   = false;
        $salePrice       = null;
        $variationsCount = 0;

        if ($hasVariations) {
            $variationsCount = $vendorProduct->variations->count();
            $prices = $vendorProduct->variations->pluck('price')->filter()->all();

            if (! empty($prices)) {
                $minPrice      = (float) min($prices);
                $maxPrice      = (float) max($prices);
                $displayPrice  = $minPrice;
                $hasPriceRange = $minPrice != $maxPrice;
            }

            $totalStock = (int) $vendorProduct->variations->sum('stock');
        } else {
            $displayPrice = (float) ($vendorProduct->sale_price ?? $vendorProduct->price ?? 0);
            $totalStock   = (int) ($vendorProduct->stock ?? 0);

            if ($vendorProduct->sale_price) {
                $salePrice = (float) $vendorProduct->sale_price;
            }
        }

        $originalPrice = (float) ($vendorProduct->price ?? 0);

        return [
            'has_variations'   => $hasVariations,
            'variations_count' => $variationsCount,
            'display_price'    => $displayPrice,
            'min_price'        => $minPrice,
            'max_price'        => $maxPrice,
            'has_price_range'  => $hasPriceRange,
            'sale_price'       => $salePrice,
            'original_price'   => $hasVariations ? null : $originalPrice,
            'total_stock'      => $totalStock,
            'in_stock'         => $totalStock > 0,
            'display_text'     => self::displayText($hasVariations, $hasPriceRange, $minPrice, $maxPrice, $displayPrice, $salePrice, $currency),
            'original_text'    => self::originalText($hasVariations, $variationsCount, $salePrice, $originalPrice, $currency),
        ];
    }

    /**
     * The headline figure: a range or a floor for variations, the sale price when one
     * genuinely undercuts the list price, otherwise the list price.
     */
    public static function displayText(
        bool $hasVariations,
        bool $hasPriceRange,
        float $minPrice,
        float $maxPrice,
        float $displayPrice,
        ?float $salePrice,
        string $currency
    ): string {
        if ($hasVariations) {
            return $hasPriceRange
                ? number_format($minPrice, 2) . ' - ' . number_format($maxPrice, 2) . ' ' . $currency
                : number_format($minPrice, 2) . ' ' . $currency;
        }

        if ($salePrice && $salePrice < $displayPrice) {
            return number_format($salePrice, 2) . ' ' . $currency;
        }

        return number_format($displayPrice, 2) . ' ' . $currency;
    }

    /**
     * The secondary line: the variation count, or the struck-through list price when a
     * discount applies, or nothing at all.
     */
    public static function originalText(
        bool $hasVariations,
        int $variationsCount,
        ?float $salePrice,
        float $originalPrice,
        string $currency
    ): ?string {
        if ($hasVariations) {
            return $variationsCount . ' variante(s)';
        }

        if ($salePrice && $salePrice < $originalPrice) {
            return number_format($originalPrice, 2) . ' ' . $currency;
        }

        return null;
    }
}
