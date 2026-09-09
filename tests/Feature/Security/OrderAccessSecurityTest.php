<?php

namespace Tests\Feature\Security;

use App\Livewire\OrderDetailPage;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regression tests for the order/invoice IDOR: /my-orders/{id} sat outside the
 * 'auth' middleware group and OrderDetailPage::mount() never checked ownership, and
 * /orders/{order}/invoice/preview|pdf had no auth or ownership check at all — either
 * one let anyone read another customer's order (items, address, phone) or download
 * their invoice PDF just by changing the id in the URL. Both are fixed by requiring
 * auth plus an explicit ownership/role check.
 */
class OrderAccessSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('db:seed', ['--class' => 'RoleSeeder']);
    }

    private function makeOrderWithVendor(): array
    {
        $vendor = Vendor::factory()->create();
        $order = Order::factory()->create(['vendor_id' => $vendor->id]);

        return [$order, $vendor];
    }

    // --- /my-orders/{id} (OrderDetailPage) -----------------------------------

    #[Test]
    public function a_guest_is_redirected_to_login_instead_of_seeing_any_order(): void
    {
        [$order] = $this->makeOrderWithVendor();

        $this->get('/my-orders/' . $order->id)->assertRedirect('/login');
    }

    #[Test]
    public function a_logged_in_stranger_cannot_open_someone_elses_order_detail_page(): void
    {
        [$order] = $this->makeOrderWithVendor();
        $stranger = User::factory()->create();
        $stranger->assignRole('customer');

        Livewire::actingAs($stranger)
            ->test(OrderDetailPage::class, ['order_id' => $order->id])
            ->assertStatus(403);
    }

    #[Test]
    public function the_owning_buyer_can_open_their_own_order_detail_page(): void
    {
        [$order] = $this->makeOrderWithVendor();
        $owner = User::find($order->user_id);

        Livewire::actingAs($owner)
            ->test(OrderDetailPage::class, ['order_id' => $order->id])
            ->assertStatus(200)
            ->assertSet('order.id', $order->id);
    }

    // --- /orders/{order}/invoice/preview and /pdf ----------------------------

    #[Test]
    public function a_guest_is_redirected_to_login_instead_of_seeing_any_invoice(): void
    {
        [$order] = $this->makeOrderWithVendor();

        $this->get("/orders/{$order->id}/invoice/preview")->assertRedirect('/login');
        $this->get("/orders/{$order->id}/invoice/pdf")->assertRedirect('/login');
    }

    #[Test]
    public function a_logged_in_stranger_cannot_view_or_download_someone_elses_invoice(): void
    {
        [$order] = $this->makeOrderWithVendor();
        $stranger = User::factory()->create();
        $stranger->assignRole('customer');

        $this->actingAs($stranger)
            ->get("/orders/{$order->id}/invoice/preview")
            ->assertForbidden();

        $this->actingAs($stranger)
            ->get("/orders/{$order->id}/invoice/pdf")
            ->assertForbidden();
    }

    #[Test]
    public function the_owning_buyer_can_preview_their_own_invoice(): void
    {
        [$order] = $this->makeOrderWithVendor();
        $owner = User::find($order->user_id);

        $this->actingAs($owner)
            ->get("/orders/{$order->id}/invoice/preview")
            ->assertOk();
    }

    #[Test]
    public function the_orders_own_vendor_can_preview_the_invoice(): void
    {
        [$order, $vendor] = $this->makeOrderWithVendor();
        $vendorUser = User::find($vendor->user_id);

        $this->actingAs($vendorUser)
            ->get("/orders/{$order->id}/invoice/preview")
            ->assertOk();
    }

    #[Test]
    public function a_vendor_unrelated_to_the_order_cannot_preview_its_invoice(): void
    {
        [$order] = $this->makeOrderWithVendor();
        $otherVendor = Vendor::factory()->create();
        $otherVendorUser = User::find($otherVendor->user_id);

        $this->actingAs($otherVendorUser)
            ->get("/orders/{$order->id}/invoice/preview")
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_can_preview_any_invoice(): void
    {
        [$order] = $this->makeOrderWithVendor();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get("/orders/{$order->id}/invoice/preview")
            ->assertOk();
    }
}
