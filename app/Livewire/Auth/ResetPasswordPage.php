<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Component;

use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Title('Réinitialiser le mot de passe - MARA BUSINESS')]
class ResetPasswordPage extends Component
{
    #[Url()]
    public $email;
    public $password;
    public $password_confirmation;
    public $token;

    public function mount($token)
    {
        $this->token = $token;
    }

    public function save()
    {
        $this->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users',
            // Same floor as registration: the reset form let a strong account be
            // re-secured with a six-character password the sign-up form would refuse.
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token
            ],
            function (User $user, string $password) {
                $password = $this->password;
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );
        // The old last line assigned the constant instead of comparing it
        // (`$status = Password::PASSWORD_RESET ? ... : ...`), so it was always true:
        // an expired or already-used link redirected to the login page as though the
        // password had been changed, and the error message below was unreachable.
        if ($status === Password::PASSWORD_RESET) {
            session()->flash('success', 'Votre mot de passe a été réinitialisé. Vous pouvez vous connecter.');

            return redirect('/login');
        }

        session()->flash('error', 'Ce lien de réinitialisation est invalide ou a expiré. Demandez-en un nouveau.');
    }
    public function render()
    {
        return view('livewire.auth.reset-password-page');
    }
}
