<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Appliquer les migrations en attente, depuis le navigateur.
 *
 * Render ne laisse aucun crochet de déploiement atteindre le disque persistant :
 * sa documentation dit que le buildCommand comme le preDeployCommand
 * « s'exécutent sur une machine séparée » et n'ont « pas accès au disque
 * persistant d'un service ». Comme la base SQLite vit précisément sur ce disque,
 * le conseil habituel — migrer au pre-deploy — migrerait un fichier jeté juste
 * après, en affichant « Migrated » sans avoir rien fait. Seul le processus web
 * lui-même voit la vraie base.
 *
 * D'où cette page, qui est le même remède que le bouton « Synchroniser le suivi »
 * de ListShipments, posé face à la même contrainte : Artisan::call() dans le
 * processus qui sert les requêtes.
 *
 * Trois garde-fous, parce qu'une migration ne se rejoue pas :
 *
 *  - la page dit ce qui va être appliqué avant de proposer de l'appliquer ;
 *  - le fichier SQLite est copié d'abord, et le bouton s'arrête là si la copie
 *    échoue ou ne fait pas la même taille que l'original ;
 *  - l'accès est réservé aux comptes admin ou manager.
 */
class DatabaseMaintenance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Base de données';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.database-maintenance';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'manager']) ?? false;
    }

    public function getTitle(): string
    {
        return 'Base de données';
    }

    public function getSubheading(): ?string
    {
        $pending = $this->pendingMigrations();

        if ($pending === []) {
            return 'Le schéma est à jour.';
        }

        return count($pending) === 1
            ? '1 migration attend d\'être appliquée.'
            : count($pending) . ' migrations attendent d\'être appliquées.';
    }

    /**
     * Les migrations présentes dans le dépôt que cette base n'a pas encore vues.
     *
     * Lue par `migrate:status --pending`, et non en comparant des fichiers à la
     * table `migrations` à la main : c'est Artisan qui décide de ce qui est en
     * attente, et deux réponses divergentes seraient pires qu'aucune.
     *
     * @return list<string>
     */
    public function pendingMigrations(): array
    {
        try {
            Artisan::call('migrate:status', ['--pending' => true]);
        } catch (Throwable $e) {
            report($e);

            return [];
        }

        $lines = preg_split('/\R/', trim(Artisan::output())) ?: [];

        // Un nom de migration est extrait, pas déduit d'un filtrage par
        // soustraction. Écarter les lignes connues laissait passer tout ce qui
        // n'avait pas été prévu : sur une base à jour, Artisan écrit
        // « INFO  No pending migrations. », que la page annonçait alors comme une
        // migration en attente, bouton compris. Les noms suivent tous la
        // convention Laravel AAAA_MM_JJ_HHMMSS_ — vérifié sur ce dépôt.
        return collect($lines)
            ->map(function (string $line): ?string {
                preg_match('/\b(\d{4}_\d{2}_\d{2}_\d{6}_\S+)/', $line, $matches);

                return $matches[1] ?? null;
            })
            ->filter()
            ->map(fn (string $name) => rtrim($name, '.'))
            ->values()
            ->all();
    }

    /** Le chemin du fichier SQLite, ou null si cette base n'en est pas une. */
    public function databaseFile(): ?string
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return null;
        }

        $path = DB::connection()->getDatabaseName();

        // ':memory:' pendant les tests, et rien à sauvegarder dans ce cas.
        return is_string($path) && is_file($path) ? $path : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('migrate')
                ->label('Sauvegarder et migrer')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->visible(fn () => $this->pendingMigrations() !== [])
                ->requiresConfirmation()
                ->modalHeading('Appliquer les migrations en attente ?')
                ->modalDescription(
                    'Une copie de la base est faite juste avant. Une migration ne se '
                    . 'rejoue pas : en cas d\'échec, c\'est cette copie qui vous ramène en arrière.'
                )
                ->modalSubmitActionLabel('Sauvegarder et migrer')
                ->action(fn () => $this->runMigrations()),
        ];
    }

    public function runMigrations(): void
    {
        $pending = $this->pendingMigrations();

        if ($pending === []) {
            Notification::make()->title('Rien à migrer.')->success()->send();

            return;
        }

        $backup = null;

        if ($file = $this->databaseFile()) {
            $backup = $this->backup($file);

            if ($backup === null) {
                // Pas de sauvegarde, pas de migration. L'inverse reviendrait à
                // modifier un schéma sans filet, ce qui est exactement ce que ce
                // bouton est censé éviter.
                return;
            }
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title('La migration a échoué')
                ->body(
                    $e->getMessage()
                    . ($backup ? "\n\nLa base d'avant est conservée : " . basename($backup) : '')
                )
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        Log::info('Migrations appliquées depuis le panneau d\'administration.', [
            'user_id' => auth()->id(),
            'migrations' => $pending,
            'backup' => $backup ? basename($backup) : null,
        ]);

        Notification::make()
            ->title(count($pending) . ' migration(s) appliquée(s)')
            ->body(
                trim(Artisan::output())
                . ($backup ? "\n\nSauvegarde : " . basename($backup) : '')
            )
            ->success()
            ->persistent()
            ->send();
    }

    /**
     * Copie le fichier de base à côté de lui, horodatée.
     *
     * Renvoie le chemin de la copie, ou null en signalant l'échec — la taille est
     * revérifiée après coup, parce qu'un disque plein produit une copie tronquée
     * sans lever d'erreur.
     */
    private function backup(string $file): ?string
    {
        $target = dirname($file) . '/' . pathinfo($file, PATHINFO_FILENAME)
            . '.backup-' . now()->format('Y-m-d-His') . '.sqlite';

        try {
            if (! @copy($file, $target)) {
                throw new \RuntimeException('La copie a échoué.');
            }

            clearstatcache(true, $target);

            if (filesize($target) !== filesize($file)) {
                @unlink($target);

                throw new \RuntimeException('La copie est incomplète : disque plein ?');
            }
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title('Sauvegarde impossible — rien n\'a été migré')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return null;
        }

        return $target;
    }
}
