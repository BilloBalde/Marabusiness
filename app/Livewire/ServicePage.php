<?php

namespace App\Livewire;

use App\Models\Service;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class ServicePage extends Component
{
    public $service;
    
    #[Title('Services - MARA BUSINESS')]
    
    public function mount($slug = null)
    {
        if ($slug) {
            $this->service = Service::where('slug', $slug)->firstOrFail();
        }
    }
    
    public function render()
    {
        if ($this->service) {
            // Single service page
            return view('livewire.service-page', [
                'service' => $this->service,
            ]);
        } else {
            // Services listing page
            $services = Service::all();
            return view('livewire.service-page', [
                'services' => $services,
                'isListing' => true,
            ]);
        }
    }
}