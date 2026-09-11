<?php

namespace Tests\Feature\Negotiation;

use App\Livewire\CheckoutPage;
use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Ce que le client voit réellement au checkout.
 *
 * Les tests existants appellent openNegotiation() puis set() sur les propriétés :
 * ils prouvent que la méthode marche, pas que le panneau s'ouvre. Un bouton qui
 * ne révèle rien passe tous ces tests.
 */
class CheckoutPanelRenderTest extends TestCase
{
    use RefreshDatabase;

    private function basket(): array
    {
        $currency = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        $shop = Vendor::factory()->create(['currency_id' => $currency->id, 'is_active' => true]);

        $product = Product::create([
            'name' => 'Article A',
            'slug' => 'article-a-' . uniqid(),
            'category_id' => Category::create(['name' => 'C' . uniqid(), 'slug' => 'c-' . uniqid()])->id,
            'brand_id' => Brand::create(['name' => 'B' . uniqid(), 'slug' => 'b-' . uniqid()])->id,
            'is_active' => true,
        ]);

        $listing = VendorProduct::create([
            'vendor_id' => $shop->id,
            'product_id' => $product->id,
            'price' => 100000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $buyer = User::factory()->create();

        CartItem::create([
            'user_id' => $buyer->id,
            'vendor_product_id' => $listing->id,
            'quantity' => 2,
            'cart_key' => 'key-' . $listing->id,
        ]);

        return compact('buyer', 'shop', 'listing');
    }

    private function checkout(array $ids): \Livewire\Features\SupportTesting\Testable
    {
        return Livewire::withQueryParams(['selected' => implode(',', $ids)])
            ->test(CheckoutPage::class);
    }

    #[Test]
    public function the_button_is_on_the_page(): void
    {
        $s = $this->basket();
        $this->actingAs($s['buyer']);

        $this->checkout([$s['listing']->id])->assertSee('Discuter le prix');
    }

    #[Test]
    public function clicking_it_opens_the_panel(): void
    {
        // Le test qui manquait : $negotiatingVendorId est comparé en === à la clé
        // du groupe. Si les types diffèrent — un entier contre une clé de tableau
        // en chaîne — le bouton ne révèle rien et rien ne le dit.
        $s = $this->basket();
        $this->actingAs($s['buyer']);

        $this->checkout([$s['listing']->id])
            ->call('openNegotiation', $s['shop']->id)
            ->assertSee('Votre prix souhaité')
            ->assertSee('Envoyer au vendeur');
    }

    #[Test]
    public function the_panel_closes_again(): void
    {
        $s = $this->basket();
        $this->actingAs($s['buyer']);

        $this->checkout([$s['listing']->id])
            ->call('openNegotiation', $s['shop']->id)
            ->call('cancelNegotiation')
            ->assertDontSee('Envoyer au vendeur')
            ->assertSee('Discuter le prix');
    }
}
