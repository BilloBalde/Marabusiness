<?php

namespace App\Livewire;

use App\Models\Order;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Order Detail - EGPG SA')]
class OrderDetailPage extends Component
{
    public $order;
    public $order_id;

    public function mount($order_id)
    {
        $this->order = Order::where('id', $order_id)->firstOrFail();
    }

    public function render()
    {
        $address = $this->order->address;
        $order_items = $this->order->items;
        return view('livewire.order-detail-page', [
            'order' => $this->order,
            'address' => $address,
            'order_items' => $order_items,
        ]);
    }
}
