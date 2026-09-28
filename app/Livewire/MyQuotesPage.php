<?php

namespace App\Livewire;

use App\Models\Quote;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Mes devis - MARA BUSINESS')]
class MyQuotesPage extends Component
{
    public function render()
    {
        $quotes = Quote::where('client_id', Auth::id())
            ->with('sourceProduct.sourcing')
            ->latest()
            ->get();

        return view('livewire.my-quotes-page', ['quotes' => $quotes]);
    }
}
