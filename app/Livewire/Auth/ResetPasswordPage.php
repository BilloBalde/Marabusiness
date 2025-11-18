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

#[Title('Reset Password Page - ECPG SA')]
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
            'password' => 'required|min:6|confirmed',
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
        // Here you would typically reset the password using the token
        // For example:
        return $status = Password::PASSWORD_RESET?redirect('/login'):session()->flash('error', 'Une erreur s\'est produite lors de la réinitialisation du mot de passe.');

    }
    public function render()
    {
        return view('livewire.auth.reset-password-page');
    }
}
