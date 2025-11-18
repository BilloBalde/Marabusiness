<?php

namespace App\Livewire\Auth;

use Livewire\Component;

use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;

#[Title('Login Page - ECPG SA')]
class LoginPage extends Component
{

    public $email = '';
    public $password = '';
    public $remember = false;

    public function save()
    {
        $this->validate([
            'email' => ['required', 'string', 'email:rfc'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ]);
        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            /** @var \App\Models\User|\Spatie\Permission\Traits\HasRoles $sender */
            if (Auth::user() && Auth::user()->hasRole('admin')) {
                return redirect()->intended();
            } else {
                return redirect()->intended();
            }
        } else {
            session()->flash('error', 'Invalid credentials');
        }
    }
    public function render()
    {
        return view('livewire.auth.login-page');
    }
}
