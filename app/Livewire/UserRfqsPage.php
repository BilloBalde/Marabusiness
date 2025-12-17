<?php

// app/Livewire/UserRfqsPage.php
namespace App\Livewire;

use App\Models\BulkRfq;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;

#[Title('My RFQs - MARA BUSINESS')]
class UserRfqsPage extends Component
{
    use WithPagination;
    
    public $search = '';
    public $status = '';
    
    public function render()
    {
        $rfqs = BulkRfq::with(['vendor', 'product', 'latestOffer'])
            ->where('user_id', auth()->id())
            ->when($this->search, function ($query) {
                $query->whereHas('product', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('livewire.user-rfqs-page', [
            'rfqs' => $rfqs,
            'statuses' => [
                'pending' => 'Pending',
                'quoted' => 'Quoted',
                'accepted' => 'Accepted',
                'rejected' => 'Rejected',
                'cancelled' => 'Cancelled',
            ]
        ]);
    }
    
    public function cancelRfq($rfqId)
    {
        $rfq = BulkRfq::findOrFail($rfqId);
        
        if ($rfq->user_id !== auth()->id()) {
            session()->flash('error', 'Unauthorized action');
            return;
        }
        
        $rfq->update(['status' => 'cancelled']);
        session()->flash('success', 'RFQ cancelled successfully');
    }
}
