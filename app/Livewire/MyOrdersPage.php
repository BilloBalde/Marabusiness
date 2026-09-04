<?php

namespace App\Livewire;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('My Orders - MARA BUSINESS')]
class MyOrdersPage extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = 'all';

    #[On('payment-made')]
    public function refreshOrders()
    {
        // Just re-render – Livewire will refresh the list
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function applySearch(): void
    {
        $this->resetPage();
    }

    public function setStatus($status)
    {
        $this->status = $status;
        $this->resetPage();
    }

    public function getStatusCounts()
    {
        $user = Auth::user();
        
        $counts = [
            'all' => Order::where('user_id', $user->id)->count(),
            'new' => Order::where('user_id', $user->id)->where('status', 'new')->count(),
            'processing' => Order::where('user_id', $user->id)->where('status', 'processing')->count(),
            'shipped' => Order::where('user_id', $user->id)->where('status', 'shipped')->count(),
            'delivered' => Order::where('user_id', $user->id)->where('status', 'delivered')->count(),
            'cancelled' => Order::where('user_id', $user->id)->where('status', 'cancelled')->count(),
        ];
        
        return $counts;
    }

    public function render()
    {
        $user = Auth::user();

        $orders = Order::with(['vendor.currency'])
            ->where('user_id', $user->id)
            ->when($this->search !== '', function ($query) {
                $query->where('order_number', 'like', '%' . $this->search . '%');
            })
            ->when($this->status !== 'all', function ($query) {
                $query->where('status', $this->status);
            })
            ->latest()
            ->paginate(10);

        return view('livewire.my-orders-page', [
            'orders' => $orders,
            'statusCounts' => $this->getStatusCounts(),
        ]);
    }
}