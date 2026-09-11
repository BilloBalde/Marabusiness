{{-- resources/views/filament/pages/database-maintenance.blade.php --}}
<x-filament-panels::page>
    @php
        $pending = $this->pendingMigrations();
        $file = $this->databaseFile();
    @endphp

    @if ($pending === [])
        <x-filament::section>
            <x-slot name="heading">Schéma à jour</x-slot>

            <p class="text-sm text-gray-600 dark:text-gray-400">
                Toutes les migrations du dépôt ont été appliquées à cette base.
            </p>
        </x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">Migrations en attente</x-slot>

            <x-slot name="description">
                Elles seront appliquées dans cet ordre. Le bouton en haut de page
                sauvegarde la base avant de les lancer.
            </x-slot>

            <ol class="space-y-2">
                @foreach ($pending as $migration)
                    <li class="flex items-start gap-3 text-sm">
                        <span class="mt-0.5 shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-400/10 dark:text-amber-400">
                            {{ $loop->iteration }}
                        </span>
                        <code class="font-mono text-gray-800 dark:text-gray-200">{{ $migration }}</code>
                    </li>
                @endforeach
            </ol>
        </x-filament::section>
    @endif

    <x-filament::section>
        <x-slot name="heading">Pourquoi cette page existe</x-slot>

        <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
            <p>
                Sur Render, ni la commande de build ni celle de pré-déploiement n'atteignent
                le disque persistant : elles tournent sur une machine séparée. Comme la base
                vit sur ce disque, une migration lancée depuis l'une d'elles modifierait un
                fichier jeté aussitôt, en annonçant qu'elle a réussi.
            </p>
            <p>
                Ce bouton s'exécute dans le processus qui sert cette page, donc sur la vraie
                base — le même remède que le bouton « Synchroniser le suivi » de la page
                Logistiques, posé face à la même contrainte.
            </p>
            @if ($file)
                <p>
                    Fichier de base : <code class="font-mono text-xs">{{ $file }}</code><br>
                    La sauvegarde est déposée à côté, horodatée. Pensez à faire le ménage
                    de temps en temps : rien ne les supprime.
                </p>
            @else
                <p class="text-amber-700 dark:text-amber-500">
                    Cette connexion n'est pas un fichier SQLite : aucune sauvegarde ne sera
                    faite avant de migrer. Sauvegardez par vos propres moyens d'abord.
                </p>
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page>
