<?php

namespace App\Livewire;

use App\Models\Sourcing;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Sourcing - MARA BUSINESS')]
class SourcingDetailPage extends Component
{
    public $sourcing;

    public function mount(string $sourcing): void
    {
        $this->sourcing = Sourcing::where('slug', $sourcing)
            ->where('is_active', true)
            ->with(['sourceProducts' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->firstOrFail();
    }

    /**
     * "Sélectionner" on a product — not connected sends the visitor to log in
     * and back here; connected goes straight to the quote form. The interest
     * counter only moves on a real selection, not a page view.
     */
    public function selectProduct(int $sourceProductId): void
    {
        $product = $this->sourcing->sourceProducts->firstWhere('id', $sourceProductId);

        if (! $product) {
            return;
        }

        if (! Auth::check()) {
            $this->redirect(route('login', ['redirect' => route('sourcing.quote', [
                'sourcing' => $this->sourcing->slug,
                'sourceProduct' => $product->slug,
            ])]));

            return;
        }

        $product->registerInterest();

        $this->redirect(route('sourcing.quote', [
            'sourcing' => $this->sourcing->slug,
            'sourceProduct' => $product->slug,
        ]));
    }

    public function render()
    {
        return view('livewire.sourcing-detail-page');
    }
}
