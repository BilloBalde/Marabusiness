<?php

namespace App\Livewire;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('My Orders - EGPG SA')]
class MyOrdersPage extends Component
{
    use WithPagination;

    #[On('payment-made')]
    public function refreshOrders()
    {
        // Just trigger re-render by Livewire
    }

    public function render()
    {
        $orders = Auth::user()->orders()->latest()->paginate(10);
        return view('livewire.my-orders-page', [
            'orders' => $orders
        ]);
    }
}
