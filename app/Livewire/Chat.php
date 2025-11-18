<?php

namespace App\Livewire;

use App\Models\Message;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Component;

class Chat extends Component
{
    public $chatWithId;
    public $message;
    public $messages;

    public function mount($chatWithId)
    {
        $this->chatWithId = $chatWithId;

        if ($chatWithId) {
            $this->loadMessages();
        }
    }

    private static function getConnectedUser(){
        return auth()->user(); // Use Laravel’s default auth outside Filament
    }


    public function loadMessages()
    {
        $currentUserId = self::getConnectedUser()->id;

        $this->messages = Message::where(function ($q) use ($currentUserId) {
            $q->where('sender_id', $currentUserId)
            ->where('receiver_id', $this->chatWithId);
        })->orWhere(function ($q) use ($currentUserId) {
            $q->where('sender_id', $this->chatWithId)
            ->where('receiver_id', $currentUserId);
        })
        ->orderBy('created_at')
        ->get();
    }

    public function sendMessage()
    {
        if (!$this->canChatWith($this->chatWithId)) {
            abort(403, 'Unauthorized');
        }

        Message::create([
            'sender_id' => $this->getConnectedUser()->id,
            'receiver_id' => $this->chatWithId,
            'content' => $this->message,
        ]);

        $this->message = '';
        $this->loadMessages();
    }

    private function canChatWith($receiverId)
    {
        $sender = $this->getConnectedUser();
        $receiver = User::find($receiverId);

        if (!$receiver) return false;

        // If sender is customer and receiver is manager
        if ($sender->hasRole('customer') && $receiver->hasRole('manager')) {
            return true;
        }

        // If sender is manager and receiver is customer
        if ($sender->hasRole('manager') && $receiver->hasRole('customer')) {
            return true;
        }

        return false;
    }


    public function render()
    {
        return view('livewire.chat');
    }
}
