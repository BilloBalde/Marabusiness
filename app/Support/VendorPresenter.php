<?php

namespace App\Support;

use App\Models\Vendor;
use App\Models\VendorReview;

/**
 * One serialisation of a vendor for every API endpoint.
 *
 * HomeController and VendorController used to build this payload separately and had
 * drifted apart: the home listing selected `logo`, `banner`, `currency`, `rating`,
 * `is_verified` and `is_featured` as columns, none of which exist on `vendors`. SQLite
 * silently turns an unknown double-quoted identifier into a string literal, so the
 * query ran, every logo came back null and every rating fell through to a hardcoded
 * 4.5 — while MySQL or PostgreSQL would raise "Unknown column 'logo'" outright.
 *
 * Prefer the counts loaded by withCount() on the caller's query; the fallbacks below
 * exist so a bare model still presents correctly.
 */
class VendorPresenter
{
    public static function present(Vendor $vendor, bool $detailed = false): array
    {
        $payload = [
            'id'              => $vendor->id,
            'store_name'      => $vendor->store_name,
            'slug'            => $vendor->slug,
            'description'     => $vendor->description,
            'logo'            => $vendor->logo_path ? url('uploads/' . $vendor->logo_path) : null,
            'currency'        => $vendor->currency?->code ?? 'USD',
            'currency_rate'   => $vendor->currency?->rate_to_usd ?? 1,
            'rating'          => round((float) $vendor->vendor_rating, 1),
            'reviews_count'   => (int) ($vendor->reviews_count ?? $vendor->approvedVendorReviews()->count()),
            'followers_count' => (int) ($vendor->followers_count ?? $vendor->followers()->count()),
            'products_count'  => (int) ($vendor->products_count ?? $vendor->vendorProducts()->where('is_active', true)->count()),
            'created_at'      => $vendor->created_at?->toDateTimeString(),

            // No column backs these three. They are kept so the payload shape stays
            // stable for clients already reading them, rather than silently vanishing.
            'banner'          => null,
            'is_verified'     => false,
            'is_featured'     => false,
        ];

        if ($detailed) {
            $payload['address'] = $vendor->full_address;
            $payload['city']    = $vendor->city;
            $payload['country'] = $vendor->country;

            $payload['rating_breakdown'] = VendorReview::query()
                ->where('vendor_id', $vendor->id)
                ->where('is_approved', true)
                ->selectRaw('rating, COUNT(*) as total')
                ->groupBy('rating')
                ->pluck('total', 'rating')
                ->union(collect([5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]))
                ->all();
        }

        return $payload;
    }

    /**
     * Eager-loads everything present() reads, so a listing costs a fixed number of
     * queries instead of four per vendor.
     */
    public static function eagerLoad($query)
    {
        return $query
            ->with('currency')
            ->withCount([
                'followers',
                'approvedVendorReviews as reviews_count',
                'vendorProducts as products_count' => fn ($q) => $q->where('is_active', true),
            ]);
    }
}
