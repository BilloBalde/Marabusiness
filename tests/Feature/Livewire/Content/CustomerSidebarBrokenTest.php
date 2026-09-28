<?php

namespace Tests\Feature\Livewire\Content;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Two bugs, now fixed: app/Livewire/CustomerSidebar.php declared
 * `namespace App\Http\Livewire`, not matching its own directory under
 * composer.json's `"App\\": "app/"` PSR-4 map, and selectCustomer() called
 * $this->emitUp(...), a Livewire v2 method with no runtime shim in v3.5
 * (confirmed against vendor/livewire/livewire/src at the time). Both are why
 * this file used to be named "Broken".
 */
class CustomerSidebarBrokenTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_class_the_service_provider_registers_now_autoloads(): void
    {
        $this->assertTrue(
            class_exists(\App\Livewire\CustomerSidebar::class),
            'AppServiceProvider registers App\Livewire\CustomerSidebar for the '
            . '"customer-sidebar" tag; it should resolve under standard PSR-4.'
        );
    }

    #[Test]
    public function selecting_a_customer_in_the_sidebar_dispatches_instead_of_crashing(): void
    {
        $this->actingAs(User::factory()->create());
        $contact = User::factory()->create();

        Livewire::test('customer-sidebar')
            ->call('selectCustomer', $contact->id)
            ->assertDispatched('customerSelected', $contact->id);
    }
}
