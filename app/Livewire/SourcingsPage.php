<?php

namespace App\Livewire;

use App\Models\Setting;
use App\Models\Sourcing;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Sourcing - MARA BUSINESS')]
class SourcingsPage extends Component
{
    public function render()
    {
        $sourcings = Sourcing::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('livewire.sourcings-page', [
            'sourcings' => $sourcings,
            'intro' => Setting::text('sourcing_intro'),
        ]);
    }
}
