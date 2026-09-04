<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\CookieConsentService;

class CookieSettings extends Component
{
    public array $preferences = [];
    public array $categories = [];
    public string $message = '';

    protected CookieConsentService $cookieService;

    public function boot(CookieConsentService $cookieService)
    {
        $this->cookieService = $cookieService;
    }

    public function mount()
    {
        $this->categories = CookieConsentService::getCategories();
        $this->preferences = $this->cookieService->getConsentSettings();
    }

    public function save()
    {
        $this->cookieService->saveConsent($this->preferences);
        $this->message = 'Vos préférences ont été enregistrées avec succès.';
        
        $this->dispatch('settings-saved');
    }

    public function acceptAll()
    {
        $this->preferences = [
            'essential' => true,
            'functional' => true,
            'analytics' => true,
            'marketing' => true,
        ];
        $this->save();
    }

    public function render()
    {
        return view('livewire.cookie-settings')->layout('components.layouts.app');
    }
}