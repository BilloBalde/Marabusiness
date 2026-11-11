<?php

namespace Tests\Feature\Filament;

use App\Filament\Vendor\Pages\VendorProfile;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * vendor_product.price is a bare number in the vendor's own currency. The profile page
 * let a vendor switch that currency at any time — with a helper text claiming it only
 * affected "future orders" — which silently re-read every price they had already
 * entered (500,000 GNF became $500,000). The field also listed every currency row,
 * inactive and test ones included.
 */
class VendorProfileCurrencyTest extends TestCase
{
    use RefreshDatabase;

    private function shop(bool $withOffer): array
    {
        Role::findOrCreate('vendor', 'web');

        $gnf = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012, 'is_active' => true]);
        $usd = Currency::factory()->create(['code' => 'USD', 'rate_to_usd' => 1, 'is_active' => true]);

        $owner = User::factory()->create();
        $owner->assignRole('vendor');

        $vendor = Vendor::factory()->create([
            'user_id' => $owner->id,
            'currency_id' => $gnf->id,
            'is_active' => true,
            'approved_at' => now(),
            'store_name' => 'Ancien nom',
            'country' => 'Guinée',
        ]);

        if ($withOffer) {
            $product = Product::create([
                'name' => 'Article ' . uniqid(),
                'slug' => 'article-' . uniqid(),
                'category_id' => Category::create(['name' => 'C' . uniqid(), 'slug' => 'c-' . uniqid()])->id,
                'brand_id' => Brand::create(['name' => 'B' . uniqid(), 'slug' => 'b-' . uniqid()])->id,
                'is_active' => true,
                'images' => [],
            ]);
            VendorProduct::create([
                'vendor_id' => $vendor->id, 'product_id' => $product->id,
                'price' => 500000, 'stock' => 5, 'is_active' => true,
            ]);
        }

        $this->actingAs($owner);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));

        return compact('owner', 'vendor', 'gnf', 'usd');
    }

    #[Test]
    public function a_shop_with_an_offer_cannot_switch_currency(): void
    {
        $s = $this->shop(withOffer: true);

        Livewire::test(VendorProfile::class)
            ->fillForm(['currency_id' => $s['usd']->id, 'store_name' => 'Nouveau nom'])
            ->call('save')
            ->assertHasNoFormErrors();

        $vendor = $s['vendor']->fresh();
        $this->assertSame($s['gnf']->id, $vendor->currency_id, 'the currency must not follow the request');
        $this->assertSame('Nouveau nom', $vendor->store_name, 'the rest of the profile still saves');
    }

    #[Test]
    public function the_field_is_shown_locked_with_an_explanation(): void
    {
        $this->shop(withOffer: true);

        Livewire::test(VendorProfile::class)
            ->assertFormFieldIsDisabled('currency_id')
            ->assertSee('contactez le support');
    }

    #[Test]
    public function a_shop_with_no_offer_or_order_can_still_choose_its_currency(): void
    {
        $s = $this->shop(withOffer: false);

        Livewire::test(VendorProfile::class)
            ->assertFormFieldIsEnabled('currency_id')
            ->fillForm(['currency_id' => $s['usd']->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($s['usd']->id, $s['vendor']->fresh()->currency_id);
    }

    #[Test]
    public function inactive_currencies_are_not_offered(): void
    {
        $this->shop(withOffer: false);
        $inactive = Currency::factory()->create(['code' => 'ZXD', 'is_active' => false]);

        Livewire::test(VendorProfile::class)
            ->assertFormFieldExists(
                'currency_id',
                fn ($field): bool => ! array_key_exists($inactive->id, $field->getOptions())
            );
    }
}
