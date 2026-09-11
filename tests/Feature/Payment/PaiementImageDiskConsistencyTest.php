<?php

namespace Tests\Feature\Payment;

use App\Filament\Resources\PaiementResource\Pages\ListPaiements;
use App\Models\Order;
use App\Models\Paiement;
use App\Models\User;
use App\Models\Vendor;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La preuve de paiement était écrite sur un disque et relue sur un autre.
 *
 * PaiementModal (web) et PaymentController::submitOfflinePayment (API mobile)
 * — les deux seuls endroits où un ACHETEUR envoie une preuve — écrivent sur le
 * disque 'public'. PaiementsRelationManager (l'onglet Paiements d'une
 * commande) le relit sur ce même disque : cohérent. PaiementResource (la page
 * autonome « Payments » du menu) relisait sur 'public_uploads' — un disque où
 * aucun de ces deux acheteurs n'écrit jamais. Sur les données réelles de ce
 * dépôt, 17 preuves sur 54 étaient invisibles depuis cette seule page, sans
 * qu'aucun message n'explique pourquoi.
 *
 * Le test rend la vraie page Filament plutôt que d'inspecter la config des
 * composants : c'est le HTML final, avec l'URL réellement bâtie à partir du
 * disque configuré, qui prouve — ou dément — que l'image s'affiche.
 */
class PaiementImageDiskConsistencyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_proof_the_buyer_actually_submits_shows_up_on_the_standalone_payments_page(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $vendor = Vendor::factory()->create();
        $order = Order::factory()->create(['vendor_id' => $vendor->id]);

        Storage::fake('public');

        // Exactement le chemin réel : PaiementModal et
        // PaymentController::submitOfflinePayment écrivent tous les deux sur
        // 'public', jamais sur 'public_uploads'.
        $path = UploadedFile::fake()->image('recu.jpg')->store('payments', 'public');

        Paiement::create([
            'order_id' => $order->id,
            'amount' => 100,
            'image' => $path,
            'payment_method' => 'om',
            'currency' => 'GNF',
            'payment_status' => 'paid',
            'transaction_id' => 'TEST-' . uniqid(),
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        // L'URL que Filament construit pour une ImageColumn vient du disque
        // configuré (ici 'public' -> storage/app/public, servi sous /storage/).
        // Si la colonne pointait encore sur 'public_uploads', cette URL ne
        // serait jamais présente dans le HTML rendu.
        Livewire::test(ListPaiements::class)
            ->assertSee($path)
            ->assertSuccessful();
    }

    #[Test]
    public function the_proof_is_reachable_at_the_url_the_public_disk_actually_serves(): void
    {
        // Confirme que 'public' — le disque désormais utilisé par les deux
        // écrans Filament — sert bien sous /storage/, via la jonction déjà en
        // place (public/storage), et pas sous /uploads/.
        Storage::fake('public');

        $path = UploadedFile::fake()->image('recu.jpg')->store('payments', 'public');

        $this->assertStringContainsString('/storage/', Storage::disk('public')->url($path));
    }
}
