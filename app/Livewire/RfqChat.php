<?php

namespace App\Livewire;

use App\Models\BulkRfq;
use App\Models\BulkRfqMessage;
use App\Models\Vendor;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

#[Title('RFQ Chat - MARA BUSINESS')]
class RfqChat extends Component
{
    use WithPagination;
    
    public $rfqId;
    public BulkRfq $rfq;
    public $messages = [];
    
    #[Validate('required|string|max:2000')]
    public $newMessage = '';
    
    public function mount($rfq = null)
    {
        // Handle both route model binding and direct ID
        if ($rfq instanceof BulkRfq) {
            $this->rfq = $rfq;
        } else {
            $this->rfqId = $rfq ?? request()->route('rfq');
            $this->rfq = BulkRfq::with(['user', 'vendor', 'product'])->findOrFail($this->rfqId);
        }
        
        // Check authorization
        $this->checkAuthorization();
        
        $this->loadMessages();
    }
    
    protected function checkAuthorization()
    {
        $user = auth()->user();
        
        if (!$user) {
            abort(403, 'You must be logged in.');
        }
        
        $isBuyer = $this->rfq->user_id === $user->id;
        $isVendor = $user->vendor && $this->rfq->vendor_id === $user->vendor->id;
        
        if (!$isBuyer && !$isVendor) {
            abort(403, 'You are not authorized to view this chat.');
        }
    }
    
    public function loadMessages()
    {
        $this->messages = $this->rfq->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'created_at' => $message->created_at->format('h:i A'),
                    'date' => $message->created_at->format('M d, Y'),
                    'is_from_user' => $this->isFromUser($message),
                    'is_from_vendor' => $this->isFromVendor($message),
                    'sender_name' => $this->getSenderName($message),
                ];
            })
            ->toArray();
    }
    
    protected function isFromUser($message)
    {
        // Use full namespace
        return $message->sender_type === \App\Models\User::class;
    }
    
    protected function isFromVendor($message)
    {
        // Use full namespace
        return $message->sender_type === \App\Models\Vendor::class;
    }
    
    protected function getSenderName($message)
    {
        if ($this->isFromUser($message)) {
            return $message->sender->name ?? 'Buyer';
        } else {
            return $message->sender->store_name ?? 'Vendor';
        }
    }
    
    public function sendMessage()
    {
        $this->validate();
        
        if (empty(trim($this->newMessage))) {
            return;
        }
        
        $user = auth()->user();
        
        // Use full namespace
        $senderType = $user->vendor ? \App\Models\Vendor::class : \App\Models\User::class;
        
        $this->rfq->messages()->create([
            'sender_type' => $senderType,
            'sender_id' => $user->id,
            'message' => trim($this->newMessage),
        ]);
        
        $this->newMessage = '';
        $this->loadMessages();
        
        // Dispatch event to scroll to bottom
        $this->dispatch('scroll-to-bottom');
    }
    
    public function getOtherParty()
    {
        $user = auth()->user();
        
        if ($user->vendor && $this->rfq->vendor_id === $user->vendor->id) {
            // User is vendor, return buyer
            return [
                'name' => $this->rfq->user->name,
                'type' => 'buyer',
                'email' => $this->rfq->user->email,
            ];
        } else {
            // User is buyer, return vendor
            return [
                'name' => $this->rfq->vendor->store_name,
                'type' => 'vendor',
                'email' => $this->rfq->vendor->user->email ?? '',
            ];
        }
    }
    
    public function render()
    {
        return view('livewire.rfq-chat', [
            'otherParty' => $this->getOtherParty(),
            'user' => auth()->user(),
        ]);
    }
}