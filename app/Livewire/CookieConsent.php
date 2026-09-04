<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\CookieConsentService;

class CookieConsent extends Component
{
    public array $preferences = [];
    public bool $showDetails = false;
    public array $categories = [];

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

    public function acceptEssential()
    {
        $this->preferences = [
            'essential' => true,
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
        ];

        $this->save();
    }

    public function save()
    {
        $this->validate([
            'preferences.functional' => 'boolean',
            'preferences.analytics' => 'boolean',
            'preferences.marketing' => 'boolean',
        ]);

        $this->cookieService->saveConsent($this->preferences);

        $this->dispatch('cookie-consent-saved');
        $this->dispatch('close-banner');
        
        // Flash a success message
        session()->flash('message', 'Vos préférences de cookies ont été enregistrées.');
    }

    public function toggleDetails()
    {
        $this->showDetails = !$this->showDetails;
    }

    public function render()
    {
        return view('livewire.cookie-consent');
    }
}