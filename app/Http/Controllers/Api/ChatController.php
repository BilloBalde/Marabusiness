<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Get user's chat contacts
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $contacts = [];

            if ($user->hasRole('customer')) {
                // Get managers customer has chatted with
                $managerIds = Message::where('sender_id', $user->id)
                    ->orWhere('receiver_id', $user->id)
                    ->pluck('sender_id')
                    ->merge(Message::where('receiver_id', $user->id)->pluck('sender_id'))
                    ->unique()
                    ->filter(fn($id) => $id != $user->id)
                    ->values();

                $managers = User::whereIn('id', $managerIds)
                    ->role('manager')
                    ->get(['id', 'name', 'email', 'avatar']);

                // Get vendors from orders
                $vendorIds = Order::where('user_id', $user->id)
                    ->whereNotNull('vendor_id')
                    ->pluck('vendor_id')
                    ->unique()
                    ->toArray();

                $vendors = User::role('vendor')
                    ->whereHas('vendor', fn($q) => $q->whereIn('id', $vendorIds))
                    ->with('vendor')
                    ->get(['id', 'name', 'email', 'avatar']);

                $contacts = $managers->concat($vendors)->map(function ($contact) use ($user) {
                    return $this->formatContact($contact, $user);
                });
            } 
            elseif ($user->hasRole('vendor')) {
                // Get customers who ordered from this vendor
                $vendor = $user->vendor;
                if ($vendor) {
                    $customerIds = Order::where('vendor_id', $vendor->id)
                        ->whereNotNull('user_id')
                        ->pluck('user_id')
                        ->unique()
                        ->toArray();

                    $customers = User::whereIn('id', $customerIds)
                        ->get(['id', 'name', 'email', 'avatar']);

                    $contacts = $customers->map(fn($customer) => $this->formatContact($customer, $user));
                }
            }
            elseif ($user->hasRole('manager')) {
                // Get all customers who have messaged
                $customerIds = Message::where('sender_id', $user->id)
                    ->orWhere('receiver_id', $user->id)
                    ->pluck('sender_id')
                    ->merge(Message::where('receiver_id', $user->id)->pluck('sender_id'))
                    ->unique()
                    ->filter(fn($id) => $id != $user->id)
                    ->values();

                $customers = User::whereIn('id', $customerIds)
                    ->role('customer')
                    ->get(['id', 'name', 'email', 'avatar']);

                $contacts = $customers->map(fn($customer) => $this->formatContact($customer, $user));
            }

            return response()->json([
                'success' => true,
                'data' => $contacts->values(),
                'message' => 'Contacts retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve contacts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get messages with specific user
     */
    public function show($userId)
    {
        try {
            $currentUser = Auth::user();

            // Check if user can chat
            if (!$this->canChat($currentUser->id, $userId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to view these messages'
                ], 403);
            }

            $messages = Message::where(function ($q) use ($currentUser, $userId) {
                    $q->where('sender_id', $currentUser->id)
                      ->where('receiver_id', $userId);
                })
                ->orWhere(function ($q) use ($currentUser, $userId) {
                    $q->where('sender_id', $userId)
                      ->where('receiver_id', $currentUser->id);
                })
                ->with(['sender', 'receiver'])
                ->orderBy('created_at')
                ->get();

            // Mark messages as read
            Message::where('sender_id', $userId)
                ->where('receiver_id', $currentUser->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json([
                'success' => true,
                'data' => [
                    'messages' => $messages->map(fn($msg) => $this->formatMessage($msg)),
                    'unread_count' => 0
                ],
                'message' => 'Messages retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve messages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send message
     */
    public function send(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string|max:1000'
        ]);

        try {
            $sender = Auth::user();
            $receiverId = $request->receiver_id;

            // Check authorization
            if (!$this->canChat($sender->id, $receiverId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to send messages to this user'
                ], 403);
            }

            $message = Message::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiverId,
                'content' => $request->message,
                'is_read' => false
            ]);

            // Load relationships
            $message->load(['sender', 'receiver']);

            // Broadcast event (if using websockets)
            // broadcast(new MessageSent($message))->toOthers();

            return response()->json([
                'success' => true,
                'data' => $this->formatMessage($message),
                'message' => 'Message sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unread message count
     */
    public function unreadCount()
    {
        try {
            $user = Auth::user();

            $count = Message::where('receiver_id', $user->id)
                ->where('is_read', false)
                ->count();

            return response()->json([
                'success' => true,
                'data' => ['unread_count' => $count],
                'message' => 'Unread count retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get unread count',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(Request $request)
    {
        $request->validate([
            'sender_id' => 'required|exists:users,id'
        ]);

        try {
            $user = Auth::user();

            Message::where('sender_id', $request->sender_id)
                ->where('receiver_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Messages marked as read'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark messages as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if users can chat
     */
    private function canChat($senderId, $receiverId)
    {
        $sender = User::find($senderId);
        $receiver = User::with('vendor')->find($receiverId);

        if (!$sender || !$receiver) {
            return false;
        }

        // Customer to Manager
        if ($sender->hasRole('customer') && $receiver->hasRole('manager')) {
            return true;
        }

        // Manager to Customer
        if ($sender->hasRole('manager') && $receiver->hasRole('customer')) {
            return true;
        }

        // Customer to Vendor
        if ($sender->hasRole('customer') && $receiver->hasRole('vendor')) {
            $vendor = $receiver->vendor;
            if (!$vendor) return false;

            return Order::where('vendor_id', $vendor->id)
                ->where('user_id', $sender->id)
                ->exists();
        }

        // Vendor to Customer
        if ($sender->hasRole('vendor') && $receiver->hasRole('customer')) {
            $vendor = $sender->vendor;
            if (!$vendor) return false;

            return Order::where('vendor_id', $vendor->id)
                ->where('user_id', $receiver->id)
                ->exists();
        }

        return false;
    }

    /**
     * Format contact for response
     */
    private function formatContact($contact, $currentUser)
    {
        $lastMessage = Message::where(function ($q) use ($contact, $currentUser) {
                $q->where('sender_id', $currentUser->id)
                  ->where('receiver_id', $contact->id);
            })
            ->orWhere(function ($q) use ($contact, $currentUser) {
                $q->where('sender_id', $contact->id)
                  ->where('receiver_id', $currentUser->id);
            })
            ->latest()
            ->first();

        $unreadCount = Message::where('sender_id', $contact->id)
            ->where('receiver_id', $currentUser->id)
            ->where('is_read', false)
            ->count();

        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email,
            'avatar' => $contact->avatar,
            'role' => $contact->roles->first()?->name,
            'last_message' => $lastMessage ? [
                'content' => $lastMessage->content,
                'created_at' => $lastMessage->created_at->diffForHumans(),
                'is_from_me' => $lastMessage->sender_id == $currentUser->id
            ] : null,
            'unread_count' => $unreadCount,
            'online' => false // You can implement online status if needed
        ];
    }

    /**
     * Format message for response
     */
    private function formatMessage($message)
    {
        return [
            'id' => $message->id,
            'content' => $message->content,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender->name,
            'sender_avatar' => $message->sender->avatar,
            'receiver_id' => $message->receiver_id,
            'receiver_name' => $message->receiver->name,
            'created_at' => $message->created_at->toDateTimeString(),
            'created_at_human' => $message->created_at->diffForHumans(),
            'is_read' => (bool) $message->is_read,
            'is_from_me' => $message->sender_id == Auth::id()
        ];
    }
}