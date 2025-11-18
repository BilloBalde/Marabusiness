<?php

namespace App\Http\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CustomerSidebar extends Component
{
    public $customerId;
    public $searchTerm = '';
    public $customers = [];

    public function mount($customerId = null)
    {
        $this->customerId = $customerId;
        $this->loadCustomers();
    }

    public function updatedSearchTerm()
    {
        $this->loadCustomers();
    }

    public function loadCustomers()
    {
        $userId = Auth::id();

        $this->customers = User::where(function ($query) use ($userId) {
                $query->whereHas('sentMessages', fn ($q) => $q->where('receiver_id', $userId))
                      ->orWhereHas('receivedMessages', fn ($q) => $q->where('sender_id', $userId));
            })
            ->where('name', 'like', '%' . $this->searchTerm . '%')
            ->get();
    }

    public function selectCustomer($id)
    {
        $this->customerId = $id;
        $this->emitUp('customerSelected', $id); // Emit event to parent
    }

    public function render()
    {
        return view('livewire.customer-sidebar');
    }
}
