<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class ChatPage extends Page
{

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string $view = 'filament.pages.chat-page';

    public $chatWithId;
    public $customers;
    public $searchTerm = '';

    protected $listeners = ['customerSelected'];

    public function customerSelected($id)
    {
        $this->chatWithId = $id;
    }

    private function getConnectedUser()
    {
        return Filament::auth()->user();
    }

    /* public function updatedSearchTerm()
    {
        $userId = $this->getConnectedUser()->id;

        $this->customers = User::where(function ($query) use ($userId) {
            $query->whereHas('sentMessages', fn ($q) => $q->where('receiver_id', $userId))
                  ->orWhereHas('receivedMessages', fn ($q) => $q->where('sender_id', $userId));
        })
        ->where('name', 'like', '%' . $this->searchTerm . '%')
        ->get();
    } */

    public function mount()
    {
        $user = $this->getConnectedUser();

        /** @var \App\Models\User|\Spatie\Permission\Traits\HasRoles $user */
        if ($user->hasRole('manager')) {
            $this->customers = User::role('customer')->get();
        }

        if ($user->hasRole('customer')) {
            // Find the manager(s) this customer has chatted with
                $manager = User::role('manager')
                ->whereHas('sentMessages', fn ($q) => $q->where('receiver_id', $user->id))
                ->orWhereHas('receivedMessages', fn ($q) => $q->where('sender_id', $user->id))
                ->first();

            $this->chatWithId = $manager?->id;

            // Also load the manager into customers so it renders in UI
            if ($manager) {
                $this->customers = collect([$manager]);
            } else {
                $this->customers = collect();
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
        // Managers search customers
        if ($user->hasRole('manager')) {
            $this->customers = User::role('customer')
                ->where('name', 'like', '%' . $this->searchTerm . '%')
                ->get();
        }
    }

    public static function getNavigationLabel(): string
    {
        return 'Chat';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'EXTRA';
    }
}
