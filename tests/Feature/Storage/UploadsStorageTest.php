<?php

namespace Tests\Feature\Storage;

use App\Support\UploadsStorage;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Sur Render, seul `storage/` est sur le disque persistant — pas `public/`,
 * où `config/filesystems.php`'s `public_uploads` disk écrit réellement les
 * photos produits et les preuves de paiement. Tout envoi fait après la mise
 * en ligne disparaissait donc au redéploiement suivant.
 *
 * Ces tests travaillent dans un dossier temporaire à eux, jamais sur le vrai
 * public/uploads ni storage/app/public/uploads du projet — sans quoi chaque
 * lancement de la suite modifierait l'arborescence réelle du dépôt.
 */
class UploadsStorageTest extends TestCase
{
    private string $sandbox;

    private string $link;

    private string $target;

    private string $seedSource;

    private Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem();
        $this->sandbox = storage_path('framework/testing/uploads-storage-' . uniqid());
        $this->link = $this->sandbox . '/public/uploads';
        $this->target = $this->sandbox . '/storage/app/public/uploads';
        $this->seedSource = $this->sandbox . '/seed-uploads';

        $this->files->makeDirectory($this->sandbox, 0755, true);
    }

    protected function tearDown(): void
    {
        // Un lien vers un dossier temporaire déjà supprimé fait planter
        // deleteDirectory() sur certains systèmes — le lien lui-même part
        // d'abord. is_link() ne reconnaît pas une jonction Windows, donc on
        // tente aussi rmdir() dessus : sans effet sur un vrai dossier non
        // vide (ce qui est voulu, deleteDirectory() s'en charge ensuite), il
        // retire proprement une jonction.
        if (is_link($this->link)) {
            @unlink($this->link);
        } else {
            @rmdir($this->link);
        }

        $this->files->deleteDirectory($this->sandbox);

        parent::tearDown();
    }

    private function seedFile(string $relativePath, string $content = 'contenu'): void
    {
        $full = $this->seedSource . '/' . $relativePath;
        $this->files->ensureDirectoryExists(dirname($full));
        $this->files->put($full, $content);
    }

    private function ensureLinked(): void
    {
        UploadsStorage::ensureLinked($this->link, $this->target, $this->seedSource);
    }

    #[Test]
    public function it_creates_a_link_where_none_existed(): void
    {
        $this->assertFalse($this->files->exists($this->link));

        $this->ensureLinked();

        // Ni is_link() (silencieux sur une jonction Windows) ni is_dir()
        // seul (vrai pour n'importe quel dossier) ne suffisent à prouver
        // qu'il s'agit d'un LIEN plutôt que d'un dossier ordinaire du même
        // nom : la preuve est qu'un fichier posé dans la cible apparaît à
        // travers le lien.
        $this->files->put($this->target . '/preuve.txt', 'x');
        $this->assertFileExists($this->link . '/preuve.txt');
    }

    #[Test]
    public function it_seeds_the_shipped_placeholder_into_the_persisted_target(): void
    {
        $this->seedFile('default.png', 'un vrai contenu de placeholder');

        $this->ensureLinked();

        $this->assertSame(
            'un vrai contenu de placeholder',
            file_get_contents($this->target . '/default.png')
        );
    }

    #[Test]
    public function a_file_seen_through_the_link_matches_the_persisted_target(): void
    {
        // Le point de tout ceci : le code applicatif écrit sur
        // public_path('uploads'), et ce doit être exactement ce qui est servi
        // — c'est-à-dire ce qui vit réellement dans le dossier persistant.
        $this->seedFile('brands/logo.png', 'logo de marque');

        $this->ensureLinked();

        $this->assertSame(
            'logo de marque',
            file_get_contents($this->link . '/brands/logo.png')
        );
    }

    #[Test]
    public function it_never_overwrites_a_file_already_at_the_target(): void
    {
        // Le cas qui compte le plus : une vraie boutique a déjà envoyé un
        // fichier au même nom qu'un fichier de départ. Le réamorçage ne doit
        // jamais l'écraser.
        $this->files->ensureDirectoryExists($this->target . '/products');
        $this->files->put($this->target . '/products/01ABC.png', 'photo envoyee par une boutique');

        $this->seedFile('products/01ABC.png', 'contenu de depart, ne doit jamais apparaitre');

        $this->ensureLinked();

        $this->assertSame(
            'photo envoyee par une boutique',
            file_get_contents($this->target . '/products/01ABC.png')
        );
    }

    #[Test]
    public function calling_it_twice_is_a_no_op_the_second_time(): void
    {
        $this->seedFile('default.png');
        $this->ensureLinked();

        // Rejoué : le chemin rapide (repère déjà présent) ne doit ni lever,
        // ni toucher au contenu déjà en place — en particulier, il ne doit
        // PAS supprimer ce qu'un vendeur a ajouté depuis, ce qui est
        // précisément le piège que le repère évite (voir la classe testée).
        $this->files->put($this->target . '/uploaded-since.png', 'envoi recent');

        $this->ensureLinked();

        $this->assertSame('envoi recent', file_get_contents($this->target . '/uploaded-since.png'));
    }

    #[Test]
    public function an_already_working_link_missing_only_its_marker_is_left_untouched(): void
    {
        // Le cas central de tout ce fichier : le lien fonctionne déjà (par
        // exemple posé par un déploiement précédent sur ce même disque), mais
        // le repère a disparu — le disque persistant a été restauré depuis
        // une sauvegarde plus ancienne, disons. Sans la vérification
        // fonctionnelle (alreadyPointsAt), ce cas serait indistinguable d'un
        // vieux dossier réel sans rapport, et deleteDirectory() sur un lien
        // qui pointe déjà vers la cible supprimerait, À TRAVERS le lien,
        // tout le contenu réel qu'il contient.
        $this->files->makeDirectory($this->target, 0755, true);
        $this->files->put($this->target . '/produit-existant.png', 'photo deja en ligne depuis des semaines');
        // mklink ne crée pas les dossiers intermédiaires — mêmes précautions
        // que celles ajoutées à ensureLinked() elle-même.
        $this->files->ensureDirectoryExists(dirname($this->link));
        $this->files->link($this->target, $this->link);

        $this->assertFileExists($this->link . '/produit-existant.png', 'le lien doit fonctionner avant le test');

        $this->ensureLinked();

        $this->assertFileExists(
            $this->target . '/produit-existant.png',
            "le contenu reel a ete supprime alors que le lien fonctionnait deja"
        );
        $this->assertSame(
            'photo deja en ligne depuis des semaines',
            file_get_contents($this->target . '/produit-existant.png')
        );
    }

    #[Test]
    public function a_stale_real_directory_at_the_link_path_is_replaced_by_the_link(): void
    {
        // Le cas d'une machine qui connaissait encore l'ancienne
        // arborescence — un vrai dossier existe déjà où le lien doit se
        // trouver, et ce n'est PAS une vue de la cible (contrairement au test
        // précédent) : sûr à remplacer.
        $this->files->makeDirectory($this->link, 0755, true);
        $this->files->put($this->link . '/ancien-fichier.png', 'ancien contenu local');

        $this->ensureLinked();

        $this->files->put($this->target . '/preuve.txt', 'x');
        $this->assertFileExists($this->link . '/preuve.txt');
    }

    #[Test]
    public function the_readme_is_not_copied_alongside_the_real_uploads(): void
    {
        $this->seedFile('README.md', 'documentation, pas une image');
        $this->seedFile('brands/logo.png', 'logo');

        $this->ensureLinked();

        $this->assertFileDoesNotExist($this->target . '/README.md');
        $this->assertFileExists($this->target . '/brands/logo.png');
    }

    #[Test]
    public function writing_through_the_public_uploads_disk_lands_on_the_persisted_target(): void
    {
        // Bout en bout, avec le vrai disque Laravel plutôt qu'avec
        // file_put_contents : c'est ainsi que les 32 endroits du code qui
        // envoient un fichier le font réellement.
        config(['filesystems.disks.public_uploads_test' => [
            'driver' => 'local',
            'root' => $this->link,
        ]]);

        $this->ensureLinked();

        Storage::disk('public_uploads_test')->put('products/nouveau.png', 'nouvelle photo');

        $this->assertSame(
            'nouvelle photo',
            file_get_contents($this->target . '/products/nouveau.png')
        );
    }

    #[Test]
    public function a_link_creation_failure_is_reported_rather_than_silently_swallowed(): void
    {
        // Un fichier bloque la CHAÎNE DE DOSSIERS qui mène à la cible elle-même
        // (pas le lien) : makeDirectory($target, …) ne peut pas réussir tant
        // qu'un segment du chemin est un fichier plutôt qu'un dossier. Le
        // nettoyage de la classe testée ne s'occupe que de ce qui se trouve
        // AU chemin du lien, jamais du chemin de la cible — ce blocage-ci
        // reste donc réellement irrécupérable, ce qu'un fichier bloquant le
        // lien lui-même ne serait plus (voir le test du dossier réel obsolète
        // ci-dessus).
        $this->files->ensureDirectoryExists(dirname(dirname($this->target)));
        $this->files->put(dirname($this->target), 'ceci est un fichier, pas un dossier');

        $this->expectException(RuntimeException::class);

        $this->ensureLinked();
    }
}
