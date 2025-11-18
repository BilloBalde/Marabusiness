<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Component;

use Livewire\Attributes\Title;

#[Title('Forgot Password Page - ECPG SA')]
class ForgotPage extends Component
{
    public $email;

    public function save(){
        $this->validate([
            'email' => 'required|email|exists:users',
        ]);

        $status = Password::sendResetLink([
            'email' => $this->email,
        ]);
        if ($status === Password::RESET_LINK_SENT) {
            session()->flash('success', 'Mot de passe envoyé avec succès à votre adresse e-mail.');
            $this->email = '';

        } else {
            session()->flash('error', 'Une erreur s\'est produite lors de l\'envoi du lien de réinitialisation.');
        }
        // Here you would typically send a password reset link to the email
        // For example:
        // Password::sendResetLink(['email' => $this->email]);

        session()->flash('message', 'Mot de passe envoyé avec succès à votre adresse e-mail.');
    }
    public function render()
    {
        return view('livewire.auth.forgot-page');
    }
}
