<?php

namespace Tests\Feature\Negotiation;

use App\Filament\Vendor\Resources\OrderResource;
use App\Filament\Vendor\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Vendor\Resources\OrderResource\Pages\ViewOrder;
use App\Livewire\CheckoutPage;
use App\Livewire\RfqChat;
use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Currency;
use App\Models\FinancialTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Le parcours vendeur en entier, écran par écran.
 *
 * Les autres tests de négociation appellent OrderNegotiation directement, ou
 * passent les données d'une action sans ouvrir sa fenêtre. Ici rien n'est
 * court-circuité : le client ouvre la discussion depuis le checkout, le vendeur
 * la trouve dans sa liste, ouvre la commande, lit le fil, répond, fixe un prix
 * par le formulaire de la fenêtre, et le client accepte. C'est le seul test qui
 * échouerait si l'un de ces écrans ne rendait pas ce qu'il faut.
 */
class VendorEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private function shopAndBasket(): array
    {
        $currency = Currency::factory()->create([
            'code' => 'GNF',
            'rate_to_usd' => 0.00012,
            'symbol' => 'FG',
        ]);

        $vendorUser = User::factory()->create(['name' => 'Patron Boutique']);
        $vendor = Vendor::factory()->create([
            'user_id' => $vendorUser->id,
            'currency_id' => $currency->id,
            'is_active' => true,
            'store_name' => 'Boutique Kaloum',
        ]);

        $buyer = User::factory()->create(['name' => 'Fatou Diallo']);

        $product = Product::create([
            'name' => 'Sac de riz 50kg',
            'slug' => 'riz-' . uniqid(),
            'category_id' => Category::create(['name' => 'C' . uniqid(), 'slug' => 'c-' . uniqid()])->id,
            'brand_id' => Brand::create(['name' => 'B' . uniqid(), 'slug' => 'b-' . uniqid()])->id,
            'is_active' => true,
        ]);

        $listing = VendorProduct::create([
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'price' => 100000,
            'stock' => 10,
            'is_active' => true,
        ]);

        CartItem::create([
            'user_id' => $buyer->id,
            'vendor_product_id' => $listing->id,
            'quantity' => 2,
            'cart_key' => 'key-' . $listing->id,
        ]);

        return compact('vendor', 'vendorUser', 'buyer', 'listing', 'product');
    }

    /** Le client ouvre la discussion depuis le checkout, comme il le ferait. */
    private function buyerOpensNegotiation(array $s, float $target = 150000): Order
    {
        $this->actingAs($s['buyer']);

        Livewire::withQueryParams(['selected' => (string) $s['listing']->id])
            ->test(CheckoutPage::class)
            ->set('first_name', 'Fatou')
            ->set('last_name', 'Diallo')
            ->set('phone', '600000000')
            ->set('street_address', 'Rue 1')
            ->set('city', 'Conakry')
            ->set('state', 'Conakry')
            ->set('country', 'Guinea')
            ->call('openNegotiation', $s['vendor']->id)
            ->set('negotiationTargetPrice', $target)
            ->set('negotiationMessage', "Bonjour, seriez-vous d'accord pour {$target} ?")
            ->call('startNegotiation')
            ->assertHasNoErrors();

        $order = Order::where('vendor_id', $s['vendor']->id)->firstOrFail();

        $this->assertSame(Order::STATUS_NEGOTIATING, $order->status);

        return $order;
    }

    private function actingAsVendor(User $user): void
    {
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        $this->actingAs($user);
    }

    #[Test]
    public function the_whole_thing_from_the_vendors_side(): void
    {
        $s = $this->shopAndBasket();
        $order = $this->buyerOpensNegotiation($s);

        // ---- 1. Le vendeur est prévenu ------------------------------------
        $this->actingAsVendor($s['vendorUser']);

        $this->assertSame(
            '1',
            OrderResource::getNavigationBadge(),
            'la pastille de navigation doit signaler le client qui attend'
        );
        $this->assertStringContainsString(
            'attend votre prix',
            OrderResource::getNavigationBadgeTooltip()
        );

        // ---- 2. Il trouve la commande dans l'onglet Négociations ----------
        $list = Livewire::test(ListOrders::class)->set('activeTab', 'negotiating');

        $list->assertCanSeeTableRecords([$order]);
        $this->assertSame('1', (string) $list->instance()->getTabs()['negotiating']->getBadge());

        // ---- 3. Il ouvre la commande et voit ce qui lui est demandé -------
        $page = Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()]);

        $subheading = $page->instance()->getSubheading();
        $this->assertStringContainsString('Souhait du client', $subheading);
        $this->assertStringContainsString('150,000.00', $subheading);
        $this->assertStringContainsString('En attente de votre prix', $subheading);

        $page->assertActionVisible('repondreNegociation')
            ->assertActionVisible('fixerPrixConvenu');

        // ---- 4. Il lit le fil et répond -----------------------------------
        $page->mountAction('repondreNegociation');
        // Sans apostrophe dans l'aiguille : le rendu l'échappe en &#039;, et
        // l'assertion porterait alors sur la mise en forme du HTML plutôt que sur
        // ce que le vendeur lit.
        $page->assertSee('seriez-vous');

        $page->setActionData(['message' => 'Bonjour, je peux faire un effort.'])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertStringContainsString(
            'je peux faire un effort',
            $order->negotiation->messages()->pluck('message')->implode("\n")
        );

        // ---- 5. Il fixe le prix par le formulaire de la fenêtre -----------
        $page = Livewire::test(ViewOrder::class, ['record' => $order->fresh()->getRouteKey()]);
        $page->mountAction('fixerPrixConvenu');

        // Le formulaire s'ouvre pré-rempli sur le total actuel : le vendeur
        // corrige un chiffre, il ne le saisit pas de mémoire. Comparé au total
        // réel de la commande et non à un nombre écrit ici : la livraison est
        // calculée par le vrai calculateur au moment du checkout, et la figer
        // dans le test ne prouverait que l'arithmétique du test.
        $this->assertEqualsWithDelta(
            (float) $order->grand_total,
            (float) $page->instance()->mountedActionsData[0]['total'],
            0.01
        );

        $page->setActionData([
            'total' => 180000,
            'valid_days' => 3,
            'note' => 'Meilleur prix pour cette quantité.',
        ])->callMountedAction()->assertHasNoActionErrors();

        $order->refresh();

        $this->assertSame(Order::NEGOTIATION_PRICED, $order->negotiation_status);
        $this->assertEqualsWithDelta(180000, (float) $order->negotiated_total, 0.01);
        $this->assertTrue($order->hasLiveOffer());
        // Nommer un prix n'est pas conclure : la commande reste impayable.
        $this->assertTrue($order->isNegotiating());
        $this->assertSame(0, FinancialTransaction::where('order_id', $order->id)->count());

        // ---- 6. Le prix et la note sont partis dans le fil ----------------
        $thread = $order->negotiation->messages()->pluck('message')->implode("\n");
        $this->assertStringContainsString('Prix proposé', $thread);
        $this->assertStringContainsString('180,000.00', $thread);
        $this->assertStringContainsString('Meilleur prix pour cette quantité', $thread);

        // ---- 7. Le vendeur n'a plus rien à faire : la pastille retombe ----
        $this->assertNull(
            OrderResource::getNavigationBadge(),
            "rien n'attend plus le vendeur une fois le prix envoyé"
        );
        $this->assertStringContainsString('Votre prix', $page->instance()->getSubheading());

        // ---- 8. Le client accepte depuis sa page de discussion ------------
        $this->actingAs($s['buyer']);

        Livewire::actingAs($s['buyer'])
            ->test(RfqChat::class, ['rfq' => $order->negotiation])
            ->call('acceptNegotiatedPrice');

        $order->refresh();

        $this->assertSame('new', $order->status);
        $this->assertSame(Order::NEGOTIATION_AGREED, $order->negotiation_status);
        $this->assertEqualsWithDelta(180000, (float) $order->grand_total, 0.01);
        $this->assertEqualsWithDelta(180000, (float) $order->total_remaining, 0.01);
        // Le prix convenu est réparti sur les lignes, sinon la première
        // sauvegarde Filament recalculerait le total depuis celles-ci et
        // effacerait la remise. La somme des lignes vaut donc le prix convenu
        // moins la livraison, qui n'est pas négociée.
        $this->assertEqualsWithDelta(
            180000 - (float) $order->shipping_amount,
            (float) $order->items()->sum('total_amount'),
            0.01
        );
        // Les écritures n'existent qu'à partir d'un montant accepté des deux côtés.
        $this->assertGreaterThan(0, FinancialTransaction::where('order_id', $order->id)->count());
        // Et le stock est décompté à ce moment-là, pas avant.
        $this->assertSame(8, (int) $s['listing']->fresh()->stock);

        // ---- 9. Côté vendeur, c'est redevenu une commande ordinaire -------
        $this->actingAsVendor($s['vendorUser']);

        $page = Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()]);

        $page->assertActionHidden('fixerPrixConvenu')
            ->assertActionHidden('repondreNegociation');

        $this->assertStringContainsString(
            'Prix accepté par le client',
            $page->instance()->getSubheading()
        );

        Livewire::test(ListOrders::class)
            ->set('activeTab', 'new')
            ->assertCanSeeTableRecords([$order]);
    }

    #[Test]
    public function the_buyer_sees_the_shops_name_on_the_shops_messages(): void
    {
        // sender_id portait l'id de l'UTILISATEUR de la boutique sous un
        // sender_type Vendor, dans les trois écritures du dépôt. Le morphTo
        // résolvait donc Vendor::find($userId) : une autre boutique, ou rien — et
        // l'acheteur lisait le mot « Vendor » à la place du nom du magasin.
        $s = $this->shopAndBasket();
        $order = $this->buyerOpensNegotiation($s);

        $this->actingAsVendor($s['vendorUser']);

        // Les deux chemins par lesquels une boutique parle.
        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->mountAction('repondreNegociation')
            ->setActionData(['message' => 'Je regarde cela.'])
            ->callMountedAction();

        Livewire::test(ViewOrder::class, ['record' => $order->fresh()->getRouteKey()])
            ->mountAction('fixerPrixConvenu')
            ->setActionData(['total' => 180000, 'valid_days' => 3, 'note' => null])
            ->callMountedAction();

        $chat = Livewire::actingAs($s['buyer'])
            ->test(RfqChat::class, ['rfq' => $order->fresh()->negotiation]);

        $fromShop = collect($chat->instance()->messages)
            ->where('is_from_vendor', true);

        $this->assertCount(2, $fromShop, 'les deux messages de la boutique doivent être là');

        foreach ($fromShop as $message) {
            $this->assertSame('Boutique Kaloum', $message['sender_name']);
        }

        // Sur la page, et pas seulement dans l'état du composant.
        $chat->assertSee('Boutique Kaloum');
    }

    #[Test]
    public function the_buyer_refuses_and_the_vendor_names_another_price(): void
    {
        $s = $this->shopAndBasket();
        $order = $this->buyerOpensNegotiation($s);

        $this->actingAsVendor($s['vendorUser']);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->mountAction('fixerPrixConvenu')
            ->setActionData(['total' => 200000, 'valid_days' => 3, 'note' => null])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        // Le client trouve cela encore trop cher.
        Livewire::actingAs($s['buyer'])
            ->test(RfqChat::class, ['rfq' => $order->fresh()->negotiation])
            ->call('refuseNegotiatedPrice');

        $order->refresh();

        $this->assertSame(Order::NEGOTIATION_OPEN, $order->negotiation_status);
        $this->assertNull($order->negotiated_total);

        // Le vendeur revient dans sa file : la commande lui est rendue.
        $this->actingAsVendor($s['vendorUser']);
        $this->assertSame('1', OrderResource::getNavigationBadge());

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->mountAction('fixerPrixConvenu')
            ->setActionData(['total' => 175000, 'valid_days' => 5, 'note' => null])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertTrue($order->fresh()->hasLiveOffer());
        $this->assertEqualsWithDelta(175000, (float) $order->fresh()->negotiated_total, 0.01);
    }

    #[Test]
    public function a_price_of_zero_is_refused_by_the_form(): void
    {
        $s = $this->shopAndBasket();
        $order = $this->buyerOpensNegotiation($s);

        $this->actingAsVendor($s['vendorUser']);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->mountAction('fixerPrixConvenu')
            ->setActionData(['total' => 0, 'valid_days' => 3, 'note' => null])
            ->callMountedAction()
            ->assertHasActionErrors(['total']);

        $this->assertNull($order->fresh()->negotiated_total);
    }

    #[Test]
    public function a_vendor_cannot_reach_another_shops_negotiation(): void
    {
        $s = $this->shopAndBasket();
        $order = $this->buyerOpensNegotiation($s);

        $intruderUser = User::factory()->create();
        Vendor::factory()->create(['user_id' => $intruderUser->id, 'is_active' => true]);

        $this->actingAsVendor($intruderUser);

        // La ressource est déjà cadrée sur la boutique connectée, donc la
        // commande ne se résout tout simplement pas.
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()]);
    }

    #[Test]
    public function the_shop_is_not_told_about_another_shops_waiting_buyer(): void
    {
        $s = $this->shopAndBasket();
        $this->buyerOpensNegotiation($s);

        $intruderUser = User::factory()->create();
        Vendor::factory()->create(['user_id' => $intruderUser->id, 'is_active' => true]);

        $this->actingAsVendor($intruderUser);

        $this->assertNull(OrderResource::getNavigationBadge());
        $this->assertNull(OrderResource::getNavigationBadgeTooltip());
    }

    #[Test]
    public function stock_that_disappeared_during_the_discussion_stops_the_acceptance(): void
    {
        // Le stock n'est pas réservé pendant la discussion — décision du
        // propriétaire. La garde est donc au moment de l'acceptation, et le
        // vendeur ne doit pas se retrouver avec une commande qu'il ne peut servir.
        $s = $this->shopAndBasket();
        $order = $this->buyerOpensNegotiation($s);

        $this->actingAsVendor($s['vendorUser']);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->mountAction('fixerPrixConvenu')
            ->setActionData(['total' => 180000, 'valid_days' => 3, 'note' => null])
            ->callMountedAction();

        // Entre-temps, tout est parti.
        $s['listing']->update(['stock' => 0]);

        Livewire::actingAs($s['buyer'])
            ->test(RfqChat::class, ['rfq' => $order->fresh()->negotiation])
            ->call('acceptNegotiatedPrice');

        $order->refresh();

        $this->assertTrue($order->isNegotiating(), "la commande ne doit pas devenir payable");
        $this->assertSame(0, FinancialTransaction::where('order_id', $order->id)->count());
    }
}
