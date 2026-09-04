<?php

namespace App\Livewire;

use App\Models\Message;
use App\Models\User;
use App\Models\Order;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class Chat extends Component
{
    public $chatWithId;
    public $message = '';
    public $chatMessages; // Renamed from $messages to avoid conflict

    public function mount($chatWithId)
    {
        $this->chatWithId = $chatWithId;

        if ($chatWithId) {
            $this->loadMessages();
        }
    }

    public function updatedChatWithId($value)
    {
        $this->loadMessages();
    }

    private function getConnectedUser()
    {
        return Auth::user();
    }

    public function loadMessages()
    {
        if (!$this->chatWithId || !$this->getConnectedUser()) {
            $this->chatMessages = collect(); // Updated property name
            return;
        }

        $currentUserId = $this->getConnectedUser()->id;

        $this->chatMessages = Message::where(function ($q) use ($currentUserId) {
            $q->where('sender_id', $currentUserId)
              ->where('receiver_id', $this->chatWithId);
        })->orWhere(function ($q) use ($currentUserId) {
            $q->where('sender_id', $this->chatWithId)
              ->where('receiver_id', $currentUserId);
        })
        ->with(['sender', 'receiver'])
        ->orderBy('created_at')
        ->get();
        
        // Mark messages as read when loading
        $this->markMessagesAsRead();
    }

    private function markMessagesAsRead()
    {
        Message::where('sender_id', $this->chatWithId)
            ->where('receiver_id', $this->getConnectedUser()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function sendMessage()
    {
        // Validate input
        $this->validate([
            'message' => 'required|string|min:1|max:1000',
        ]);

        // Check authorization
        if (!$this->canChatWith($this->chatWithId)) {
            Notification::make()
                ->title('Access Denied')
                ->body('You are not authorized to send messages to this user')
                ->danger()
                ->persistent()
                ->send();
            return;
            //$this->redirect(CategoryResource::getUrl('index'));
            //abort(403, 'You are not authorized to send messages to this user');
        }

        // Create message
        Message::create([
            'sender_id' => $this->getConnectedUser()->id,
            'receiver_id' => $this->chatWithId,
            'content' => $this->message,
            'is_read' => false,
        ]);

        // Clear input and reload messages
        $this->message = '';
        $this->loadMessages();
        
        // Emit event to parent component
        $this->dispatch('message-sent');
    }

    private function canChatWith($receiverId)
    {
        $sender = $this->getConnectedUser();
        $receiver = User::with('vendor')->find($receiverId);

        if (!$sender || !$receiver) {
            return false;
        }

        // If sender is vendor and receiver is customer
        if ($sender->hasRole('vendor') && $receiver->hasRole('customer')) {
            $vendor = $sender->vendor;
            if (!$vendor) {
                return false;
            }
            
            // Check if customer has placed orders with this vendor
            return Order::where('vendor_id', $vendor->id)
                ->where('user_id', $receiver->id)
                ->exists();
        }

        // If sender is customer and receiver is vendor
        if ($sender->hasRole('customer') && $receiver->hasRole('vendor')) {
            $vendor = $receiver->vendor;
            if (!$vendor) {
                return false;
            }
            
            // Check if customer has placed orders with this vendor
            return Order::where('vendor_id', $vendor->id)
                ->where('user_id', $sender->id)
                ->exists();
        }

        // If sender is manager and receiver is customer
        if ($sender->hasRole('manager') && $receiver->hasRole('customer')) {
            return true;
        }

        // If sender is customer and receiver is manager
        if ($sender->hasRole('customer') && $receiver->hasRole('manager')) {
            return true;
        }

        return false;
    }

    public function render()
    {
        return view('livewire.chat');
    }
}