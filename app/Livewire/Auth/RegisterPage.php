<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Title;
use Spatie\Permission\Models\Role;
use Livewire\Component;

#[Title('Register Page - MARA BUSINESS')]
class RegisterPage extends Component
{
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public function save(){
        // validate the data
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // create a new user
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' =>  Hash::make($this->password),
            'email_verified_at' => Carbon::now()
        ]);

        $user->assignRole('customer');

        // log the user in
        Auth::login($user);
        LivewireAlert::title('Utilisateur Ajouté')
            ->text('Utilisateur ajouté et connecté avec succès')
            ->success()
            ->show();

        // redirect to the home page
        return redirect()->intended();
    }
    public function render()
    {
        return view('livewire.auth.register-page');
    }
}
