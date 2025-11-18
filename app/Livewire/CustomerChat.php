<?php

namespace App\Livewire;

use App\Models\Message;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class CustomerChat extends Component
{
    public $chatWithId;
    public $message;
    public $messages = [];

    public function mount($chatWithId)
    {
        $this->chatWithId = $chatWithId;
        $this->loadMessages();
    }

    public function loadMessages()
    {
        $userId = Auth::id();

        $this->messages = Message::where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
              ->where('receiver_id', $this->chatWithId);
        })->orWhere(function ($q) use ($userId) {
            $q->where('sender_id', $this->chatWithId)
              ->where('receiver_id', $userId);
        })
        ->orderBy('created_at')
        ->get();

        /* dd([
            'userId' => $userId,
            'chatWithId' => $this->chatWithId,
            'messages' => Message::all()->toArray(), // just to see what's in DB
        ]); */
    }

    public function sendMessage()
    {
        $sender = Auth::user();
        $receiver = User::find($this->chatWithId);

        if (!$receiver) {
            session()->flash('error', 'Receiver not found.');
            return;
        }

        if (!$this->canChatWith($receiver)) {
            session()->flash('error', 'You are not allowed to chat with this user.');
            return;
        }

        Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'content' => $this->message,
        ]);

        $this->message = '';
        $this->loadMessages();
    }

    private function canChatWith(User $receiver)
    {
        $sender = Auth::user();

        return (
            ($sender->hasRole('customer') && $receiver->hasRole('manager')) ||
            ($sender->hasRole('manager') && $receiver->hasRole('customer'))
        );
    }

    public function render()
    {
        return view('livewire.customer-chat');
    }
}

