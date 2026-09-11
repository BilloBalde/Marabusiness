<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\Order;
use App\Models\Message;
use App\Models\Currency;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ChatPage extends Page
{

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string $view = 'filament.pages.chat-page';

    public $chatWithId;

    /**
     * Starts empty rather than null.
     *
     * mount() and loadCustomers() branch on the manager, vendor and customer
     * roles and have no else. A user holding only the 'admin' role — the platform
     * owner, opening their own back office — fell through every branch, left this
     * null, and the view's @forelse threw "foreach() argument must be of type
     * array|object, null given". The page returned a 500 for every admin.
     *
     * An empty collection makes the page render its own empty state instead,
     * which is the honest answer: an admin has no conversations of their own.
     */
    public $customers = [];

    public $searchTerm = '';
    
    // Add public property to store selected customer details
    public $selectedCustomerDetails = null;
    public $vendorCurrency = null;

    public function getHeading(): string
    {
        return __('filament.nav.chat');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.groups.extras');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.chat');
    }

    protected $listeners = ['customerSelected', 'messageSent'];

    public function customerSelected($id)
    {
        $this->chatWithId = $id;
        $this->loadSelectedCustomerDetails($id);
    }

    public function messageSent()
    {
        // Refresh customer list when new message is sent
        $this->loadCustomers();
    }

    private function getConnectedUser()
    {
        return Filament::auth()->user();
    }

    private function loadSelectedCustomerDetails($customerId)
    {
        $user = $this->getConnectedUser();
        
        if ($user->hasRole('vendor') && $user->vendor) {
            $vendor = $user->vendor;
            $this->vendorCurrency = $vendor->currency;
            
            $customer = User::find($customerId);
            if ($customer) {
                $this->selectedCustomerDetails = [
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'total_orders' => Order::where('vendor_id', $vendor->id)
                        ->where('user_id', $customerId)
                        ->count(),
                    'last_order' => Order::where('vendor_id', $vendor->id)
                        ->where('user_id', $customerId)
                        ->latest()
                        ->first(),
                    'total_spent' => Order::where('vendor_id', $vendor->id)
                        ->where('user_id', $customerId)
                        ->sum('grand_total'),
                    'unread_messages' => Message::where('sender_id', $customerId)
                        ->where('receiver_id', $user->id)
                        ->where('is_read', false)
                        ->count(),
                    'currency_symbol' => $this->vendorCurrency->symbol ?? '$',
                    'currency_code' => $this->vendorCurrency->code ?? 'USD',
                ];
            }
        } elseif ($user->hasRole('manager')) {
            $customer = User::find($customerId);
            if ($customer) {
                $this->selectedCustomerDetails = [
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'unread_messages' => Message::where('sender_id', $customerId)
                        ->where('receiver_id', $user->id)
                        ->where('is_read', false)
                        ->count(),
                ];
            }
        } elseif ($user->hasRole('customer')) {
            $contact = User::find($customerId);
            if ($contact) {
                $this->selectedCustomerDetails = [
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'store_name' => $contact->vendor ? $contact->vendor->store_name : null,
                    'unread_messages' => Message::where('sender_id', $customerId)
                        ->where('receiver_id', $user->id)
                        ->where('is_read', false)
                        ->count(),
                ];
            }
        }
    }

    public function mount()
    {
        $user = $this->getConnectedUser();

        /** @var \App\Models\User|\Spatie\Permission\Traits\HasRoles $user */
        
        if ($user->hasRole('manager')) {
            // Manager can chat with all customers
            $this->customers = User::role('customer')
                ->orderBy('name')
                ->get()
                ->map(function($customer) use ($user) {
                    $customer->unread_messages = Message::where('sender_id', $customer->id)
                        ->where('receiver_id', $user->id)
                        ->where('is_read', false)
                        ->count();
                    return $customer;
                });
        } 
        elseif ($user->hasRole('vendor')) {
            // Vendor can only chat with customers who have placed orders with their vendor
            $vendor = $user->vendor;
            $this->vendorCurrency = $vendor->currency ?? null;
            
            if ($vendor) {
                // Get unique customers who have orders with this vendor
                $customerIds = Order::where('vendor_id', $vendor->id)
                    ->whereNotNull('user_id')
                    ->pluck('user_id')
                    ->unique()
                    ->toArray();
                
                $this->customers = User::whereIn('id', $customerIds)
                    ->where('id', '!=', $user->id)
                    ->withCount(['orders' => function($query) use ($vendor) {
                        $query->where('vendor_id', $vendor->id);
                    }])
                    ->with(['orders' => function($query) use ($vendor) {
                        $query->where('vendor_id', $vendor->id)
                              ->latest()
                              ->limit(1)
                              ->select('id', 'user_id', 'created_at', 'grand_total', 'order_number', 'status');
                    }])
                    ->orderBy('name')
                    ->get()
                    ->map(function($customer) use ($vendor, $user) {
                        $customer->total_orders = $customer->orders_count;
                        $customer->last_order = $customer->orders->first();
                        $customer->total_spent = Order::where('vendor_id', $vendor->id)
                            ->where('user_id', $customer->id)
                            ->sum('grand_total');
                        $customer->unread_messages = Message::where('sender_id', $customer->id)
                            ->where('receiver_id', $user->id)
                            ->where('is_read', false)
                            ->count();
                        $customer->currency_symbol = $this->vendorCurrency->symbol ?? '$';
                        $customer->currency_code = $this->vendorCurrency->code ?? 'USD';
                        return $customer;
                    });
            } else {
                $this->customers = collect();
            }
        }
        elseif ($user->hasRole('customer')) {
            // Customer can chat with:
            // 1. Managers they've chatted with
            // 2. Vendors from whom they've placed orders
            
            $managers = User::role('manager')
                ->where(function($query) use ($user) {
                    $query->whereHas('sentMessages', fn ($q) => $q->where('receiver_id', $user->id))
                          ->orWhereHas('receivedMessages', fn ($q) => $q->where('sender_id', $user->id));
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
                ->with(['vendor' => function($query) use ($user) {
                    $query->withCount(['orders' => function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    }])
                    ->with(['orders' => function($q) use ($user) {
                        $q->where('user_id', $user->id)
                          ->latest()
                          ->limit(1);
                    }])
                    ->with('currency');
                }])
                ->get();
            
            // Merge both collections
            $this->customers = $managers->merge($vendors)
                ->map(function($contact) use ($user) {
                    $contact->unread_messages = Message::where('sender_id', $contact->id)
                        ->where('receiver_id', $user->id)
                        ->where('is_read', false)
                        ->count();
                    
                    // Add vendor currency info for vendor contacts
                    if ($contact->vendor) {
                        $contact->vendor->load('currency');
                        $contact->currency_symbol = $contact->vendor->currency->symbol ?? '$';
                        $contact->currency_code = $contact->vendor->currency->code ?? 'USD';
                    }
                    
                    return $contact;
                })
                ->sortByDesc('unread_messages')
                ->values();
            
            // Auto-select first contact if available
            if ($this->customers->isNotEmpty()) {
                $this->chatWithId = $this->customers->first()->id;
                $this->loadSelectedCustomerDetails($this->chatWithId);
            }
        }
    }

    public function updatedSearchTerm()
    {
        $this->loadCustomers();
    }

    public function loadCustomers()
    {
        $user = $this->getConnectedUser();

        /** @var \App\Models\User|\Spatie\Permission\Traits\HasRoles $user */
        
        if ($user->hasRole('manager')) {
            // Managers search all customers
            $this->customers = User::role('customer')
                ->where(function($query) {
                    $query->where('name', 'like', '%' . $this->searchTerm . '%')
                          ->orWhere('email', 'like', '%' . $this->searchTerm . '%');
                })
                ->orderBy('name')
                ->get()
                ->map(function($customer) use ($user) {
                    $customer->unread_messages = Message::where('sender_id', $customer->id)
                        ->where('receiver_id', $user->id)
                        ->where('is_read', false)
                        ->count();
                    return $customer;
                });
        } 
        elseif ($user->hasRole('vendor')) {
            // Vendor searches only their customers (who have placed orders)
            $vendor = $user->vendor;
            $this->vendorCurrency = $vendor->currency ?? null;
            
            if ($vendor) {
                // Get customer IDs from orders
                $customerIds = Order::where('vendor_id', $vendor->id)
                    ->whereNotNull('user_id')
                    ->pluck('user_id')
                    ->unique()
                    ->toArray();
                
                $this->customers = User::whereIn('id', $customerIds)
                    ->where('id', '!=', $user->id)
                    ->where(function($query) {
                        $query->where('name', 'like', '%' . $this->searchTerm . '%')
                              ->orWhere('email', 'like', '%' . $this->searchTerm . '%');
                    })
                    ->withCount(['orders' => function($query) use ($vendor) {
                        $query->where('vendor_id', $vendor->id);
                    }])
                    ->with(['orders' => function($query) use ($vendor) {
                        $query->where('vendor_id', $vendor->id)
                              ->latest()
                              ->limit(1)
                              ->select('id', 'user_id', 'created_at', 'grand_total', 'order_number', 'status');
                    }])
                    ->orderBy('name')
                    ->get()
                    ->map(function($customer) use ($vendor, $user) {
                        $customer->total_orders = $customer->orders_count;
                        $customer->last_order = $customer->orders->first();
                        $customer->total_spent = Order::where('vendor_id', $vendor->id)
                            ->where('user_id', $customer->id)
                            ->sum('grand_total');
                        $customer->unread_messages = Message::where('sender_id', $customer->id)
                            ->where('receiver_id', $user->id)
                            ->where('is_read', false)
                            ->count();
                        $customer->currency_symbol = $this->vendorCurrency->symbol ?? '$';
                        $customer->currency_code = $this->vendorCurrency->code ?? 'USD';
                        return $customer;
                    });
            } else {
                $this->customers = collect();
            }
        }
        elseif ($user->hasRole('customer')) {
            // Customer searches in their contacts (managers + vendors they ordered from)
            $managers = User::role('manager')
                ->where(function($query) use ($user) {
                    $query->whereHas('sentMessages', fn ($q) => $q->where('receiver_id', $user->id))
                          ->orWhereHas('receivedMessages', fn ($q) => $q->where('sender_id', $user->id));
                })
                ->where(function($query) {
                    $query->where('name', 'like', '%' . $this->searchTerm . '%')
                          ->orWhere('email', 'like', '%' . $this->searchTerm . '%');
                })
                ->get();
            
            $vendorIds = Order::where('user_id', $user->id)
                ->whereNotNull('vendor_id')
                ->pluck('vendor_id')
                ->unique()
                ->toArray();
            
            $vendors = User::role('vendor')
                ->whereHas('vendor', function($query) use ($vendorIds) {
                    $query->whereIn('id', $vendorIds);
                })
                ->where(function($query) {
                    $query->where('name', 'like', '%' . $this->searchTerm . '%')
                          ->orWhere('email', 'like', '%' . $this->searchTerm . '%')
                          ->orWhereHas('vendor', function($q) {
                              $q->where('store_name', 'like', '%' . $this->searchTerm . '%');
                          });
                })
                ->with(['vendor' => function($query) use ($user) {
                    $query->withCount(['orders' => function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    }])
                    ->with(['orders' => function($q) use ($user) {
                        $q->where('user_id', $user->id)
                          ->latest()
                          ->limit(1);
                    }])
                    ->with('currency');
                }])
                ->get();
            
            $allContacts = $managers->merge($vendors)
                ->map(function($contact) use ($user) {
                    $contact->unread_messages = Message::where('sender_id', $contact->id)
                        ->where('receiver_id', $user->id)
                        ->where('is_read', false)
                        ->count();
                    
                    // Add vendor currency info for vendor contacts
                    if ($contact->vendor) {
                        $contact->vendor->load('currency');
                        $contact->currency_symbol = $contact->vendor->currency->symbol ?? '$';
                        $contact->currency_code = $contact->vendor->currency->code ?? 'USD';
                    }
                    
                    return $contact;
                })
                ->sortByDesc('unread_messages')
                ->values();
            
            $this->customers = $allContacts;
        }
    }
    
    // Helper method to format currency with vendor's currency
    public function formatCurrency($amount, $currencySymbol = null, $currencyCode = null)
    {
        // Use provided currency or fallback to vendor's currency
        $symbol = $currencySymbol ?? ($this->vendorCurrency->symbol ?? '$');
        $code = $currencyCode ?? ($this->vendorCurrency->code ?? 'USD');
        
        // Get precision from currency or default to 2
        $precision = $this->vendorCurrency->precision ?? 2;
        
        // Format based on currency symbol position preference
        $formattedAmount = number_format($amount, $precision);
        
        // Common currency formats
        $symbolFormats = [
            '$' => '$' . $formattedAmount,          // USD, CAD, AUD, etc.
            '€' => $formattedAmount . ' €',         // Euro (symbol after)
            '£' => '£' . $formattedAmount,          // GBP
            '¥' => '¥' . $formattedAmount,          // JPY, CNY
            '₹' => '₹' . $formattedAmount,          // INR
            '₽' => $formattedAmount . ' ₽',         // RUB
            '₩' => '₩' . $formattedAmount,          // KRW
        ];
        
        return $symbolFormats[$symbol] ?? $symbol . $formattedAmount;
    }
    
    // Helper method to format date
    public function formatDate($date)
    {
        if (!$date) return 'No orders yet';
        
        return $date->diffForHumans();
    }
    
    // Helper method to get order status color
    public function getOrderStatusColor($status)
    {
        $colors = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'processing' => 'bg-blue-100 text-blue-800',
            'shipped' => 'bg-indigo-100 text-indigo-800',
            'delivered' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
        ];
        
        return $colors[$status] ?? 'bg-gray-100 text-gray-800';
    }
    
    // Method to mark messages as read
    public function markMessagesAsRead($senderId)
    {
        Message::where('sender_id', $senderId)
            ->where('receiver_id', $this->getConnectedUser()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
            
        // Refresh customer details
        $this->loadSelectedCustomerDetails($senderId);
        $this->loadCustomers();
    }
}