<?php

namespace App\Livewire;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('My Orders - MARA BUSINESS')]
class MyOrdersPage extends Component
{
    use WithPagination;

    #[On('payment-made')]
    public function refreshOrders()
    {
        // Just re-render – Livewire will refresh the list
    }

    public function render()
    {
        $user = Auth::user();

        $orders = Order::with(['vendor.currency'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        return view('livewire.my-orders-page', [
            'orders' => $orders,
        ]);
    }
}
