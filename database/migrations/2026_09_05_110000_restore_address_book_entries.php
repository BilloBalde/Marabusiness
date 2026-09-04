<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The address book and per-order shipping addresses share the `addresses` table:
     * a book entry has order_id NULL, an order snapshot carries an order_id. Checkout
     * used to write both user_id and order_id on every order address, and the book was
     * read without filtering on order_id, so each order silently added an entry.
     *
     * The reads are now scoped with whereNull('order_id'). On existing data that would
     * empty the book for every customer whose entries were all order snapshots, so this
     * migration promotes their most recent distinct addresses back into real book rows.
     *
     * Only customers with an empty book are touched: anyone who already curated their
     * own entries keeps exactly what they had.
     */
    private const MAX_RESTORED_PER_USER = 5;

    public function up(): void
    {
        $usersWithoutBook = DB::table('addresses')
            ->select('user_id')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('SUM(CASE WHEN order_id IS NULL THEN 1 ELSE 0 END) = 0')
            ->pluck('user_id');

        $restored = 0;

        foreach ($usersWithoutBook as $userId) {
            $candidates = DB::table('addresses')
                ->where('user_id', $userId)
                ->whereNotNull('order_id')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get();

            $seen = [];
            $isFirst = true;

            foreach ($candidates as $address) {
                $signature = implode('|', [
                    $address->first_name,
                    $address->last_name,
                    $address->phone,
                    $address->street_address,
                    $address->city,
                    $address->state,
                    $address->zip_code,
                    $address->country,
                ]);

                if (isset($seen[$signature])) {
                    continue;
                }

                $seen[$signature] = true;

                DB::table('addresses')->insert([
                    'user_id'        => $userId,
                    'order_id'       => null,
                    'first_name'     => $address->first_name,
                    'last_name'      => $address->last_name,
                    'phone'          => $address->phone,
                    'street_address' => $address->street_address,
                    'city'           => $address->city,
                    'state'          => $address->state,
                    'zip_code'       => $address->zip_code,
                    'country'        => $address->country,
                    'latitude'       => $address->latitude,
                    'longitude'      => $address->longitude,
                    'zone'           => $address->zone,
                    // Most recent distinct address becomes the default.
                    'is_default'     => $isFirst,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                $restored++;
                $isFirst = false;

                if (count($seen) >= self::MAX_RESTORED_PER_USER) {
                    break;
                }
            }
        }

        if ($restored > 0) {
            echo "  Restored {$restored} address book entries." . PHP_EOL;
        }
    }

    /**
     * Not reversible: the restored rows are indistinguishable from entries a customer
     * created by hand, so deleting them on rollback would risk destroying real data.
     * The order snapshots this migration reads from are never modified.
     */
    public function down(): void
    {
        //
    }
};
