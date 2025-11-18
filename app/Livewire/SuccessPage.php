<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Success Page - MARA BUSINESS')]
class SuccessPage extends Component
{
    public function render()
    {
        $latest_order = Order::with('address')->where('user_id', Auth::check() ? Auth::user()->id : User::first()->id)->latest()->first();
        return view('livewire.success-page', [
            'latest_order' => $latest_order
        ]);
    }
}
