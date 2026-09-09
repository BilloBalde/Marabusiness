<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Changes a user's password from the console.
 *
 * The password is read with a hidden prompt rather than taken as an argument, so it
 * never lands in the shell history, in a process list, or in a log.
 */
class ChangeUserPassword extends Command
{
    protected $signature = 'user:password {email? : L\'adresse e-mail du compte}';

    protected $description = "Change le mot de passe d'un utilisateur (saisie masquée)";

    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Adresse e-mail du compte');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Aucun compte avec l'adresse {$email}.");

            return self::FAILURE;
        }

        $roles = $user->getRoleNames()->implode(', ') ?: 'aucun rôle';
        $this->line("Compte : <info>{$user->name}</info> <{$user->email}> — {$roles}");

        if (! $this->confirm('Confirmer le changement de mot de passe pour ce compte ?', true)) {
            $this->comment('Annulé, aucun changement.');

            return self::SUCCESS;
        }

        $password = $this->secret('Nouveau mot de passe');
        $confirmation = $this->secret('Confirmer le mot de passe');

        if ($password !== $confirmation) {
            $this->error('Les deux saisies ne correspondent pas.');

            return self::FAILURE;
        }

        $validator = Validator::make(
            ['password' => $password],
            ['password' => ['required', 'string', 'min:12']],
            ['password.min' => 'Le mot de passe doit faire au moins 12 caractères.'],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        // User::setPasswordAttribute() hashes on assignment, so the plain value is
        // never written as-is and must not be hashed again here.
        $user->password = $password;
        $user->save();

        // Any other session stays signed in with the old credentials otherwise.
        $user->tokens()->delete();

        $this->info('Mot de passe modifié. Les jetons d\'API de ce compte ont été révoqués.');
        $this->comment('Les sessions web ouvertes restent actives : déconnectez-vous puis reconnectez-vous.');

        return self::SUCCESS;
    }
}
