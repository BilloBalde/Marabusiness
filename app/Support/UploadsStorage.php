<?php

namespace App\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Fait en sorte que les fichiers envoyés survivent à un redéploiement.
 *
 * Sur Render, le disque persistant ne couvre que `storage/` — pas `public/`,
 * où `config/filesystems.php`'s `public_uploads` disk écrit les photos
 * produits et les preuves de paiement (root : `public_path('uploads')`). Tout
 * ce qu'une boutique ajoute après la mise en ligne vit donc sur le disque
 * éphémère du conteneur et disparaît au prochain déploiement, redémarrage, ou
 * changement de machine.
 *
 * Le correctif ne déplace pas les écritures : trente-deux endroits du code
 * écrivent déjà sur le disque `public_uploads`, et les faire tous pointer
 * ailleurs serait le genre de changement large et risqué qu'on évite quand un
 * plus petit suffit. À la place, `public/uploads` devient un lien vers
 * `storage/app/public/uploads`, qui EST sur le disque persistant. Chaque
 * écriture continue de viser `public_path('uploads')` sans le savoir, et
 * atterrit en réalité dans le dossier persistant grâce au lien.
 *
 * `resources/seed-uploads/` porte les fichiers déjà en place avant ce
 * correctif. Ils sont copiés vers l'emplacement persistant une seule fois, à
 * la première exécution qui trouve le lien absent, et jamais recopiés
 * par-dessus un fichier déjà présent.
 *
 * DEUX PIÈGES DE PLATEFORME, découverts en écrivant les tests de cette classe
 * plutôt qu'en la faisant confiance sur la seule apparence du code — sans
 * quoi chaque requête sur une machine Windows aurait supprimé, à travers le
 * lien, tout ce que le dossier persistant contenait :
 *
 * 1. `is_link()` de PHP ne reconnaît pas une jonction NTFS (`mklink /J`, ce
 *    que Windows utilise pour lier un DOSSIER — un lien symbolique classique
 *    demande un privilège que Developer Mode ou l'élévation admin sont seuls
 *    à accorder ; une jonction n'en a besoin d'aucun). `is_dir()` renvoie vrai
 *    aussi bien pour une jonction que pour un dossier ordinaire. Aucune des
 *    deux ne permet donc de distinguer « déjà correctement lié » de
 *    « un vieux dossier réel traîne ici » — d'où le repère écrit dans la
 *    cible (voir MARKER) plutôt qu'une inspection du système de fichiers.
 * 2. `Illuminate\Filesystem\Filesystem::link()` n'a, sous Windows, aucune
 *    instruction `return` après avoir lancé `mklink` — la fonction renvoie
 *    donc toujours `null`, que la commande ait réussi ou non. Se fier à sa
 *    valeur de retour revient à toujours croire que ça a échoué. Le seul
 *    moyen fiable de savoir si le lien existe est de regarder le résultat
 *    (`is_dir($link)` après coup), jamais ce que la fonction a renvoyé.
 */
class UploadsStorage
{
    /**
     * Écrit dans la cible après une pose de lien réussie, donc visible à
     * travers le lien lui aussi. C'est volontairement ce qui prouve que le
     * lien fonctionne : pas une inspection du système de fichiers (piège 1
     * ci-dessus), mais un fait qu'on a soi-même posé et qu'on retrouve.
     */
    private const MARKER = '.uploads-linked';

    /**
     * Crée le lien et amorce le contenu de départ si nécessaire.
     *
     * Chemin rapide volontaire : une fois le repère écrit, chaque appel ne
     * coûte plus qu'un `is_dir()` et un `exists()` — négligeable même appelé
     * à chaque requête. Le travail réel (dossier, amorçage, lien, sondage
     * d'un éventuel vieux dossier) ne s'exécute qu'une fois par disque, la
     * première requête qui trouve le repère absent — typiquement la première
     * après chaque déploiement, puisque `public/uploads` n'existe plus dans
     * git et qu'un `git checkout` frais ne le recrée donc jamais.
     *
     * Les trois chemins sont paramétrables pour que les tests travaillent
     * dans un dossier temporaire isolé plutôt que sur le vrai `public/` et
     * `storage/` du projet.
     *
     * @throws RuntimeException si le lien ne peut vraiment pas être créé
     *   (chemin bloqué par un fichier que le nettoyage n'a pas su lever,
     *   disque plein…) — remonte l'erreur plutôt que de la masquer, pour que
     *   l'appelant décide s'il continue de servir la requête ou pas. En
     *   pratique, l'appelant (AppServiceProvider::boot()) l'attrape et se
     *   contente de logger : une page dont les images manquent encore reste
     *   une page qui répond.
     */
    public static function ensureLinked(
        ?string $link = null,
        ?string $target = null,
        ?string $seedSource = null,
    ): void {
        $link ??= public_path('uploads');
        $target ??= storage_path('app/public/uploads');
        $seedSource ??= base_path('resources/seed-uploads');

        $files = new Filesystem();

        if ($files->isDirectory($link) && $files->exists($link . '/' . self::MARKER)) {
            return;
        }

        try {
            if (! $files->isDirectory($target)) {
                $files->makeDirectory($target, 0755, true);
            }

            if ($files->exists($link) && ! static::alreadyPointsAt($files, $link, $target)) {
                // Un vrai contenu, sans rapport avec la cible — par exemple
                // une machine de développement qui connaissait encore
                // l'ancienne arborescence. On vient de prouver que ce n'est
                // PAS une vue transparente de $target, donc sûr à retirer.
                if ($files->isDirectory($link)) {
                    is_link($link) ? @unlink($link) : $files->deleteDirectory($link);
                } else {
                    $files->delete($link);
                }
            }

            if ($files->isDirectory($seedSource)) {
                static::seed($files, $seedSource, $target);
            }

            if (! $files->exists($link)) {
                // mklink ne crée pas les dossiers intermédiaires : sans
                // cette ligne, tout irait bien tant que public_path('uploads')
                // a un parent qui existe déjà — vrai de tout déploiement réel
                // (public/ fait partie du squelette Laravel), mais faux dans
                // un dossier de test isolé fraîchement créé.
                $files->ensureDirectoryExists(dirname($link));

                $files->link($target, $link);
            }
        } catch (Throwable $e) {
            // Une seule sorte d'échec en sortie, quelle qu'en soit la cause
            // réelle (mkdir échoue avec un warning natif transformé en
            // ErrorException par le gestionnaire de l'application, permission
            // refusée, disque plein…) — l'appelant n'a qu'un type à attraper.
            throw new RuntimeException(
                "Impossible de préparer le lien de {$link} vers {$target} : {$e->getMessage()}",
                previous: $e
            );
        }

        // Ne PAS se fier à la valeur de retour de link() (piège 2 ci-dessus) :
        // on vérifie le résultat, pas ce que la fonction affirme avoir fait.
        if (! $files->isDirectory($link)) {
            throw new RuntimeException("Impossible de créer le lien de {$link} vers {$target}.");
        }

        $files->put($target . '/' . self::MARKER, (string) time());

        Log::info('Lien de stockage des envois créé.', ['link' => $link, 'target' => $target]);
    }

    /**
     * $link redirige-t-il déjà, en pratique, vers $target ?
     *
     * Ni is_link() ni readlink() ne répondent de façon fiable à cette
     * question sous Windows (piège 1 de la classe). On le prouve plutôt
     * fonctionnellement : un fichier posé dans $link doit apparaître dans
     * $target si — et seulement si — les deux désignent réellement le même
     * endroit.
     */
    private static function alreadyPointsAt(Filesystem $files, string $link, string $target): bool
    {
        if (! $files->isDirectory($link)) {
            return false;
        }

        $probe = '.link-probe-' . bin2hex(random_bytes(8));

        try {
            $files->put($link . '/' . $probe, '1');
        } catch (Throwable) {
            return false;
        }

        $confirmed = $files->exists($target . '/' . $probe);

        $files->delete($link . '/' . $probe);

        return $confirmed;
    }

    /**
     * Copie récursivement, sans jamais écraser un fichier déjà présent.
     *
     * `README.md` reste à sa place dans `resources/seed-uploads/` — il
     * documente le dossier pour qui l'ouvre dans le dépôt, mais n'a rien à
     * faire au milieu des vraies images une fois copié sur le disque servi.
     */
    private static function seed(Filesystem $files, string $source, string $target): void
    {
        foreach ($files->allFiles($source) as $file) {
            $relative = $file->getRelativePathname();

            if (basename($relative) === 'README.md') {
                continue;
            }

            $destination = $target . DIRECTORY_SEPARATOR . $relative;

            if ($files->exists($destination)) {
                continue;
            }

            $files->ensureDirectoryExists(dirname($destination));
            $files->copy($file->getPathname(), $destination);
        }
    }
}
