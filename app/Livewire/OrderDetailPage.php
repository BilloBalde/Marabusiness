<?php

namespace App\Livewire;

use App\Models\Order;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Order Detail - MARA BUSINESS')]
class OrderDetailPage extends Component
{
    public $order;

    public function mount($order_id)
    {
        $this->order = Order::with(['address', 'items.product', 'vendor.currency'])
            ->findOrFail($order_id);
    }

    public function render()
    {
        //dd($this->order);
        return view('livewire.order-detail-page', [
            'order' => $this->order,
            'address' => $this->order->address ?? 'no address',
            'order_items' => $this->order->items,
        ]);
    }
}
