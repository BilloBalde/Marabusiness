<?php

namespace Tests\Feature\Maintenance;

use App\Filament\Pages\DatabaseMaintenance;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Le bouton qui applique les migrations depuis le navigateur.
 *
 * Il existe parce qu'aucun crochet de déploiement Render n'atteint le disque
 * persistant où vit la base : le buildCommand et le preDeployCommand tournent
 * sur une machine séparée, et migreraient un fichier jeté aussitôt en annonçant
 * un succès. Seul le processus web voit la vraie base.
 *
 * Ce qui compte ici, c'est moins qu'il migre que ce qu'il refuse de faire : pas
 * de sauvegarde, pas de migration, et pas d'accès pour qui n'est pas admin.
 */
class DatabaseMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function actingAsAdminPanel(User $user): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
    }

    #[Test]
    public function an_admin_can_open_the_page(): void
    {
        $this->actingAsAdminPanel($this->admin());

        $this->assertTrue(DatabaseMaintenance::canAccess());

        Livewire::test(DatabaseMaintenance::class)->assertOk();
    }

    #[Test]
    public function an_ordinary_customer_cannot(): void
    {
        // La page applique des changements de schéma : elle n'a rien à faire
        // devant un compte qui n'administre pas la plateforme.
        Role::findOrCreate('customer', 'web');
        $user = User::factory()->create();
        $user->assignRole('customer');

        $this->actingAsAdminPanel($user);

        $this->assertFalse(DatabaseMaintenance::canAccess());
    }

    #[Test]
    public function a_signed_out_visitor_cannot(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->assertFalse(DatabaseMaintenance::canAccess());
    }

    #[Test]
    public function a_vendor_cannot(): void
    {
        Role::findOrCreate('vendor', 'web');
        $user = User::factory()->create();
        $user->assignRole('vendor');

        $this->actingAsAdminPanel($user);

        $this->assertFalse(DatabaseMaintenance::canAccess());
    }

    #[Test]
    public function it_reports_nothing_pending_on_an_up_to_date_schema(): void
    {
        // RefreshDatabase vient de tout migrer.
        $this->actingAsAdminPanel($this->admin());

        $page = Livewire::test(DatabaseMaintenance::class);

        $this->assertSame([], $page->instance()->pendingMigrations());
        $this->assertSame('Le schéma est à jour.', $page->instance()->getSubheading());

        // Rien à appliquer, donc rien à proposer : un bouton qui ne peut rien
        // faire finit par être cliqué quand même.
        $page->assertActionHidden('migrate');
    }

    #[Test]
    public function running_it_with_nothing_pending_changes_nothing(): void
    {
        $this->actingAsAdminPanel($this->admin());

        Livewire::test(DatabaseMaintenance::class)
            ->call('runMigrations')
            ->assertOk();

        $this->assertSame([], $this->backupsBeside());
    }

    #[Test]
    public function a_real_pending_migration_is_listed_and_offered(): void
    {
        // Le cas vide seul ne prouve rien : il passait déjà quand l'analyse
        // prenait « INFO  No pending migrations. » pour un nom de migration.
        // Celui-ci vérifie qu'un vrai fichier en attente ressort, et lui seul.
        $this->actingAsAdminPanel($this->admin());

        $name = '2099_01_01_000000_une_migration_de_test';
        $path = database_path("migrations/{$name}.php");

        file_put_contents($path, <<<'PHP'
        <?php

        use Illuminate\Database\Migrations\Migration;

        return new class extends Migration
        {
            public function up(): void {}
            public function down(): void {}
        };
        PHP);

        try {
            $page = Livewire::test(DatabaseMaintenance::class);

            $this->assertSame([$name], $page->instance()->pendingMigrations());
            $this->assertSame("1 migration attend d'être appliquée.", $page->instance()->getSubheading());

            // Et cette fois le bouton doit être là.
            $page->assertActionVisible('migrate');
            $page->assertSee($name);
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function artisan_chrome_is_not_taken_for_a_migration(): void
    {
        // La ligne exacte qui a fait tomber la première version.
        $this->actingAsAdminPanel($this->admin());

        Artisan::call('migrate:status', ['--pending' => true]);

        $this->assertStringContainsString('No pending migrations', Artisan::output());
        $this->assertSame(
            [],
            Livewire::test(DatabaseMaintenance::class)->instance()->pendingMigrations()
        );
    }

    #[Test]
    public function the_page_names_the_database_file_it_would_back_up(): void
    {
        $this->actingAsAdminPanel($this->admin());

        $file = Livewire::test(DatabaseMaintenance::class)->instance()->databaseFile();

        // En test la connexion est un sqlite en mémoire ou un fichier temporaire ;
        // dans les deux cas databaseFile() doit répondre sans lever, et ne
        // désigner un chemin que s'il existe vraiment.
        $this->assertTrue($file === null || is_file($file));
    }

    /** @return list<string> */
    private function backupsBeside(): array
    {
        $file = Livewire::test(DatabaseMaintenance::class)->instance()->databaseFile();

        if (! $file) {
            return [];
        }

        return glob(dirname($file) . '/*.backup-*.sqlite') ?: [];
    }
}
