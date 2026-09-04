<?php

namespace App\Livewire;

use App\Models\Message;
use App\Models\User;
use App\Models\Order;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class CustomerChat extends Component
{
    public $chatWithId;
    public $message;
    public $messages = [];
    public $contacts = [];
    public $searchTerm = '';

    public function mount($chatWithId = null)
    {
        $this->chatWithId = $chatWithId;
        $this->loadContacts();
        $this->loadMessages();
    }

    public function loadContacts()
    {
        $user = Auth::user();
        
        if ($user->hasRole('customer')) {
            // Customer can chat with:
            // 1. Managers
            // 2. Vendors they've ordered from
            
            // Get managers
            $managers = User::role('manager')
                ->where(function($query) use ($user) {
                    $query->whereHas('sentMessages', fn ($q) => $q->where('receiver_id', $user->id))
                          ->orWhereHas('receivedMessages', fn ($q) => $q->where('sender_id', $user->id));
                })
                ->orWhere(function($query) {
                    // Include all managers for customers to contact
                    // or you can limit to managers who handle customer support
                    $query->role('manager');
                })
                ->get();
            
            // Get vendors from customer's orders
            $vendorIds = Order::where('user_id', $user->id)
                ->whereNotNull('vendor_id')
                ->pluck('vendor_id')
                ->unique()
                ->toArray();
            
            $vendors = User::role('vendor')
                ->whereHas('vendor', function($query) use ($vendorIds) {
                    $query->whereIn('id', $vendorIds);
                })
                ->with(['vendor'])
                ->get();
            
            // Convert to array for Livewire serialization
            $this->contacts = $managers->merge($vendors)
                ->sortBy('name')
                ->values()
                ->toArray();
                
            // Auto-select first contact if none selected
            if (!$this->chatWithId && !empty($this->contacts)) {
                $this->chatWithId = $this->contacts[0]['id'] ?? null;
            }
        } elseif ($user->hasRole('manager')) {
            // Manager can chat with all customers
            $this->contacts = User::role('customer')
                ->orderBy('name')
                ->get()
                ->toArray();
        } elseif ($user->hasRole('vendor')) {
            // Vendor can chat with customers who have placed orders
            $vendor = $user->vendor;
            if ($vendor) {
                $customerIds = Order::where('vendor_id', $vendor->id)
                    ->whereNotNull('user_id')
                    ->pluck('user_id')
                    ->unique()
                    ->toArray();
                
                $this->contacts = User::whereIn('id', $customerIds)
                    ->where('id', '!=', $user->id)
                    ->orderBy('name')
                    ->get()
                    ->toArray();
            } else {
                $this->contacts = [];
            }
        }
    }

    public function loadMessages()
    {
        if (!$this->chatWithId) {
            $this->messages = [];
            return;
        }

        $userId = Auth::id();

        $messages = Message::where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
              ->where('receiver_id', $this->chatWithId);
        })->orWhere(function ($q) use ($userId) {
            $q->where('sender_id', $this->chatWithId)
              ->where('receiver_id', $userId);
        })
        ->with(['sender', 'receiver'])
        ->orderBy('created_at')
        ->get();
        
        // Convert to array for Livewire serialization
        $this->messages = $messages->toArray();
        
        // Mark messages as read
        $this->markMessagesAsRead();
    }
    
    private function markMessagesAsRead()
    {
        Message::where('sender_id', $this->chatWithId)
            ->where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function sendMessage()
    {
        $this->validate([
            'message' => 'required|string|min:1|max:1000',
        ]);

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
            'is_read' => false,
        ]);

        $this->message = '';
        $this->loadMessages();
    }

    private function canChatWith(User $receiver)
    {
        $sender = Auth::user();

        // Customer to Manager
        if ($sender->hasRole('customer') && $receiver->hasRole('manager')) {
            return true;
        }
        
        // Manager to Customer
        if ($sender->hasRole('manager') && $receiver->hasRole('customer')) {
            return true;
        }
        
        // Customer to Vendor (only if customer has ordered from vendor)
        if ($sender->hasRole('customer') && $receiver->hasRole('vendor')) {
            $vendor = $receiver->vendor;
            if (!$vendor) {
                return false;
            }
            
            return Order::where('vendor_id', $vendor->id)
                ->where('user_id', $sender->id)
                ->exists();
        }
        
        // Vendor to Customer (only if customer has ordered from vendor)
        if ($sender->hasRole('vendor') && $receiver->hasRole('customer')) {
            $vendor = $sender->vendor;
            if (!$vendor) {
                return false;
            }
            
            return Order::where('vendor_id', $vendor->id)
                ->where('user_id', $receiver->id)
                ->exists();
        }

        return false;
    }
    
    public function selectContact($contactId)
    {
        $this->chatWithId = $contactId;
        $this->loadMessages();
    }
    
    public function updatedSearchTerm()
    {
        $user = Auth::user();
        
        if ($user->hasRole('customer')) {
            // Re-fetch managers and vendors based on search
            $searchTerm = strtolower($this->searchTerm);
            
            $managers = User::role('manager')
                ->where(function($query) use ($user) {
                    $query->whereHas('sentMessages', fn ($q) => $q->where('receiver_id', $user->id))
                          ->orWhereHas('receivedMessages', fn ($q) => $q->where('sender_id', $user->id));
                })
                ->orWhere(function($query) {
                    $query->role('manager');
                });
            
            $vendorIds = Order::where('user_id', $user->id)
                ->whereNotNull('vendor_id')
                ->pluck('vendor_id')
                ->unique()
                ->toArray();
            
            $vendors = User::role('vendor')
                ->whereHas('vendor', function($query) use ($vendorIds) {
                    $query->whereIn('id', $vendorIds);
                })
                ->with(['vendor']);
            
            // Apply search filter
            if ($this->searchTerm) {
                $managers->where(function($query) use ($searchTerm) {
                    $query->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"])
                          ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchTerm}%"]);
                });
                
                $vendors->where(function($query) use ($searchTerm) {
                    $query->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"])
                          ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchTerm}%"])
                          ->orWhereHas('vendor', function($q) use ($searchTerm) {
                              $q->whereRaw('LOWER(store_name) LIKE ?', ["%{$searchTerm}%"]);
                          });
                });
            }
            
            $this->contacts = $managers->get()
                ->merge($vendors->get())
                ->sortBy('name')
                ->values()
                ->toArray();
                
        } elseif ($user->hasRole('manager')) {
            $query = User::role('customer');
            
            if ($this->searchTerm) {
                $searchTerm = strtolower($this->searchTerm);
                $query->where(function($q) use ($searchTerm) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"])
                      ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchTerm}%"]);
                });
            }
            
            $this->contacts = $query->orderBy('name')->get()->toArray();
            
        } elseif ($user->hasRole('vendor')) {
            $vendor = $user->vendor;
            if ($vendor) {
                $customerIds = Order::where('vendor_id', $vendor->id)
                    ->whereNotNull('user_id')
                    ->pluck('user_id')
                    ->unique()
                    ->toArray();
                
                $query = User::whereIn('id', $customerIds)
                    ->where('id', '!=', $user->id);
                
                if ($this->searchTerm) {
                    $searchTerm = strtolower($this->searchTerm);
                    $query->where(function($q) use ($searchTerm) {
                        $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"])
                          ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchTerm}%"]);
                    });
                }
                
                $this->contacts = $query->orderBy('name')->get()->toArray();
            } else {
                $this->contacts = [];
            }
        }
    }

    public function render()
    {
        return view('livewire.customer-chat');
    }
}