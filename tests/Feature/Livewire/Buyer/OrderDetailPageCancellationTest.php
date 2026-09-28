<?php

namespace Tests\Feature\Livewire\Buyer;

use App\Livewire\OrderDetailPage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use App\Models\VendorProductVariation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Two independent bugs in OrderDetailPage::cancelOrder() (app/Livewire/OrderDetailPage.php):
 *
 *  A. reverseFinancialTransactions() (line 192) updates FinancialTransaction
 *     rows with `DB::raw("CONCAT(description, ' - CANCELLED')")`. CONCAT() is
 *     MySQL syntax; SQLite has no such function ('||' is the concatenation
 *     operator there). This app's connection is SQLite everywhere that matters
 *     — phpunit.xml sets DB_DATABASE=:memory: for tests, and
 *     App\Models\Order carries a comment noting production on Render also runs
 *     on a SQLite file, not MySQL. Verified directly against a live SQLite
 *     connection: an UPDATE whose SET clause calls CONCAT() throws "no such
 *     function: CONCAT" even when zero rows match the WHERE clause — the
 *     statement fails to compile before row-matching is even considered. Since
 *     this call runs inside cancelOrder()'s DB::beginTransaction()/commit()
 *     block, the exception is caught by the method's own try/catch, the whole
 *     transaction rolls back, and the buyer sees a generic "Une erreur est
 *     survenue" flash — every single cancellation attempt fails, silently,
 *     regardless of whether the order has any FinancialTransaction rows.
 *
 *  B. restoreStock() (line 148) only restores a variation's own stock when
 *     $item->variation_json['variation_id'] is set. Nothing ever sets it:
 *     CheckoutPage::placeOrder() only ever writes selected_variations (e.g.
 *     ['size' => 'M']) and an optional 'note' into variation_json — never the
 *     variation's numeric id — and order_items has no separate variation_id
 *     column to fall back to (see database/migrations/2025_04_19_051434_create_order_items_table.php
 *     and the later variation_json migration: no such column exists). So for
 *     every order that ever contained a product variation, restoreStock()
 *     silently takes the "regular product" branch instead, crediting the
 *     *parent* VendorProduct.stock rather than the VendorProductVariation.stock
 *     that checkout actually decremented — the variation's real, buyable stock
 *     is never returned. This is masked today by bug A (the transaction never
 *     commits at all), but it is a second, independent defect: fixing A alone
 *     would start silently losing vendor stock on every cancelled variation
 *     order. Tested here directly via the private method so it is not
 *     entangled with bug A.
 *
 * A third, smaller issue worth fixing in the same pass, not given its own test
 * here because it is currently unreachable (bug A throws first): step 5
 * unconditionally sets payment_status to 'refunded', even though step 4 only
 * calls handleRefund() `if (in_array(payment_status, ['paid','partial']))`. An
 * unpaid COD order cancelled before any money changed hands would — once bug A
 * is fixed — flip straight from 'pending' to 'refunded' with no Refund row and
 * nothing ever having been collected.
 */
class OrderDetailPageCancellationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function cancelling_an_order_fails_because_reverse_financial_transactions_uses_mysql_only_concat(): void
    {
        $vendor = Vendor::factory()->create();
        $buyer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $buyer->id,
            'vendor_id' => $vendor->id,
            'status' => 'new',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
        ]);

        Livewire::actingAs($buyer)
            ->test(OrderDetailPage::class, ['order_id' => $order->id])
            ->set('cancellationReason', 'Changement de plan')
            ->call('cancelOrder');

        $this->assertSame(
            'cancelled',
            $order->fresh()->status,
            "cancelling should succeed, but reverseFinancialTransactions()'s CONCAT() call is invalid SQLite "
            . 'syntax, so the whole DB transaction (including this status change) silently rolls back'
        );
    }

    #[Test]
    public function restore_stock_credits_the_variation_that_was_actually_sold_not_the_parent_product(): void
    {
        $currency = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        $vendor = Vendor::factory()->create(['currency_id' => $currency->id, 'is_active' => true]);

        $product = Product::create([
            'name' => 'T-shirt',
            'slug' => 'tshirt-' . uniqid(),
            'category_id' => Category::create(['name' => 'C' . uniqid(), 'slug' => 'c-' . uniqid()])->id,
            'brand_id' => Brand::create(['name' => 'B' . uniqid(), 'slug' => 'b-' . uniqid()])->id,
            'is_active' => true,
        ]);

        // Stock already reflects the sale: started at 10, 2 were bought.
        $vendorProduct = VendorProduct::create([
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'price' => 50000,
            'stock' => 8,
            'is_active' => true,
        ]);

        $variation = VendorProductVariation::create([
            'vendor_product_id' => $vendorProduct->id,
            'attributes' => ['size' => 'M'],
            'price' => 50000,
            'stock' => 8,
        ]);

        $buyer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $buyer->id,
            'vendor_id' => $vendor->id,
            'status' => 'new',
        ]);

        // Exactly what CheckoutPage::placeOrder() writes for a variation line
        // (lines ~675-689): selected_variations goes into variation_json, the
        // variation's own id never does.
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_amount' => 50000,
            'total_amount' => 100000,
            'variation_json' => ['size' => 'M'],
        ]);

        // Calls the private restoreStock() directly so this is isolated from
        // the unrelated CONCAT() crash proved above — cancelOrder() as a whole
        // cannot be exercised end-to-end today because of bug A.
        $page = Livewire::actingAs($buyer)->test(OrderDetailPage::class, ['order_id' => $order->id]);
        $restoreStock = new \ReflectionMethod(OrderDetailPage::class, 'restoreStock');
        $restoreStock->setAccessible(true);
        $restoreStock->invoke($page->instance(), $item);

        $this->assertSame(
            10,
            $variation->fresh()->stock,
            'the 2 restored units should land on the M variation that checkout actually decremented'
        );
    }
}
