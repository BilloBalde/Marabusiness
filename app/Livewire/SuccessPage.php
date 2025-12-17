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
    public $orders = [];

    public function mount()
    {
        $userId = Auth::check() ? Auth::id() : User::first()->id;

        // Retrieve ALL orders created IN THIS SESSION
        $this->orders = Order::with(['address', 'items.product', 'vendor', 'latestShipment'])
            ->where('user_id', $userId)
            ->whereDate('created_at', now()->toDateString()) // ⬅ same day
            ->latest()
            ->get();
    }

    public function render()
    {
        return view('livewire.success-page', [
            'orders' => $this->orders
        ]);
    }
}
