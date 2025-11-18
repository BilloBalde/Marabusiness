<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Order;
use App\Models\Paiement;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class PaiementModal extends Component
{
    use WithFileUploads;

    public $showModal = false;
    public $order;
    public $amount;
    public $image;
    public $payment_method = 'om'; // default
    public $currency = 'GNF';

    #[On('open-paiement-modal')]
    public function openModal($orderId)
    {
        $this->mount($orderId);
        $this->showModal = true;
    }

    protected $rules = [
        'amount' => 'required|numeric|min:1',
        'image' => 'nullable|image|max:2048',
    ];

    public function mount($orderId = null)
    {
        if ($orderId) {
            $this->order = Order::findOrFail($orderId);
        }
    }

    public function save()
    {
        $this->validate();

        $imagePath = $this->image ? $this->image->store('payments', 'public') : 'payments/default.png';

        Paiement::create([
            'order_id' => $this->order->id,
            'amount' => $this->amount,
            'image' => $imagePath,
            'payment_method' => $this->payment_method,
            'currency' => $this->currency,
            'payment_status' => $this->amount >= $this->order->grand_total ? 'paid' : 'partial',
            'transaction_id' => \App\Models\Order::generateTransactionNumber(),
        ]);

        $this->order->update([
            'total_paid' => $this->order->paiements()->sum('amount'),
            'total_remaining' => max(0, $this->order->grand_total - $this->order->paiements()->sum('amount')),
        ]);

        $this->reset(['amount', 'image', 'order', 'showModal']);

        // This works in Livewire 3
        $this->dispatch('payment-made');
        $this->dispatch('close-modal');
    }

    public function render()
    {
        return view('livewire.paiement-modal');
    }
}
