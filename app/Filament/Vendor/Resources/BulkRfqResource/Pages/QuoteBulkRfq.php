<?php

namespace App\Filament\Vendor\Resources\BulkRfqResource\Pages;

use App\Filament\Vendor\Resources\BulkRfqResource;
use App\Mail\RfqQuoteReceived;
use App\Models\BulkRfqOffer;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class QuoteBulkRfq extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    /**
     * Without this trait a custom resource page cannot resolve the {record} route
     * parameter, so /vendor/bulk-rfqs/{id}/quote answered 404 before any of the code
     * below ever ran — which is why the platform has RFQs and chat messages but not a
     * single vendor quote. ViewBulkRfq works because ViewRecord pulls the trait in.
     * It declares its own public $record, so the page must not redeclare one.
     */
    use InteractsWithRecord;

    protected static string $resource = BulkRfqResource::class;
    protected static string $view = 'filament.vendor.resources.bulk-rfq-resource.pages.quote-bulk-rfq';

    public ?array $data = [];

    public function mount(int | string $record): void
    {
        // Resolves through BulkRfqResource::getEloquentQuery(), which is already
        // scoped to the signed-in vendor.
        $this->record = $this->resolveRecord($record);
        $this->record->load(['product', 'user']);

        // Kept as defence in depth: the scoped query above would already hide another
        // vendor's RFQ, but this states the rule explicitly and answers 403 rather
        // than 404 if that scoping ever changes.
        if ($this->record->vendor_id !== auth()->user()->vendor?->id) {
            abort(403, 'Unauthorized');
        }

        // Check if already quoted
        if ($this->record->status !== 'pending') {
            Notification::make()
                ->title('Already Quoted')
                ->body('This RFQ has already been quoted.')
                ->warning()
                ->send();

            $this->redirect(BulkRfqResource::getUrl('view', ['record' => $this->record]));
        }

        $this->form->fill([
            'moq' => $this->record->quantity,
            'unit_price' => $this->record->target_price,
            'currency' => $this->record->currency ?? 'USD',
            'lead_time_days' => 30,
            'shipping_terms' => 'FOB',
            'shipping_cost' => 0,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Quote Details')
                    ->description('Submit your quotation to the buyer')
                    ->schema([
                        Forms\Components\TextInput::make('moq')
                            ->label('Minimum Order Quantity (MOQ)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('units')
                            ->helperText('Minimum quantity you can supply'),
                            
                        Forms\Components\TextInput::make('unit_price')
                            ->label('Unit Price')
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->step(0.01),
                            
                        Forms\Components\Select::make('currency')
                            // Was a hardcoded USD/EUR/GNF list. EUR is not in the
                            // currencies table, so a quote priced in it could never be
                            // converted into an order — the buyer would hit a dead end
                            // at acceptance. Only currencies the platform can convert
                            // are offered.
                            ->options(fn () => \App\Models\Currency::orderBy('code')->pluck('code', 'code'))
                            ->required(),
                            
                        Forms\Components\TextInput::make('lead_time_days')
                            ->label('Lead Time')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('days')
                            ->helperText('Days to prepare and ship the order'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Shipping & Logistics')
                    ->schema([
                        Forms\Components\Select::make('shipping_terms')
                            ->options([
                                'EXW' => 'EXW (Ex Works)',
                                'FOB' => 'FOB (Free On Board)',
                                'CIF' => 'CIF (Cost, Insurance & Freight)',
                                'DDP' => 'DDP (Delivered Duty Paid)',
                            ])
                            ->required()
                            ->helperText('Incoterms 2020'),
                            
                        Forms\Components\TextInput::make('shipping_cost')
                            ->label('Shipping Cost')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->helperText('Estimated shipping cost'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Additional Notes')
                    ->schema([
                        Forms\Components\Textarea::make('vendor_notes')
                            ->label('Your Message to Buyer')
                            ->rows(4)
                            ->placeholder('Add any notes, terms, or conditions...')
                            ->helperText('This will be sent to the buyer with your quote'),
                    ]),
            ])
            ->statePath('data');
    }

    public function submitQuote(): void
    {
        $data = $this->form->getState();
        
        $offer = DB::transaction(function () use ($data) {
            // Create the quote/offer
            $offer = BulkRfqOffer::create([
                'bulk_rfq_id' => $this->record->id,
                'vendor_id' => auth()->user()->vendor->id,
                'moq' => $data['moq'],
                'unit_price' => $data['unit_price'],
                'currency' => $data['currency'],
                'lead_time_days' => $data['lead_time_days'],
                'shipping_terms' => $data['shipping_terms'],
                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'vendor_notes' => $data['vendor_notes'] ?? '',
                'status' => 'pending',
            ]);
            
            // Update RFQ status
            $this->record->update([
                'status' => 'quoted',
            ]);
            
            // Add message to chat
            $this->record->messages()->create([
                'sender_type' => \App\Models\Vendor::class,
                'sender_id' => auth()->user()->vendor->id,
                'message' => 'Submitted quotation: $' . number_format($data['unit_price'], 2) . 
                           ' per unit, MOQ: ' . number_format($data['moq']) . 
                           ' units, Lead time: ' . $data['lead_time_days'] . ' days',
            ]);

            return $offer;
        });

        $this->notifyBuyer($offer);

        Notification::make()
            ->title('Quote Submitted Successfully')
            ->body('Your quotation has been sent to the buyer.')
            ->success()
            ->send();
            
        $this->redirect(BulkRfqResource::getUrl('view', ['record' => $this->record]));
    }

    /**
     * Sent after the commit, never inside it: the quote is already recorded and visible
     * in the conversation, so a mail failure must not roll it back. Until now nothing
     * reached the buyer outside the site — they had to think to reopen the chat to
     * discover a quote had arrived.
     */
    private function notifyBuyer(BulkRfqOffer $offer): void
    {
        $email = $this->record->user?->email;

        if (! $email) {
            Log::warning("Devis #{$offer->id} : l'acheteur n'a pas d'e-mail, notification non envoyée.");

            return;
        }

        try {
            Mail::to($email)->send(new RfqQuoteReceived($offer->fresh(['rfq.product', 'rfq.user', 'vendor'])));
        } catch (\Throwable $e) {
            Log::error("Échec de la notification de devis à l'acheteur.", [
                'offer_id' => $offer->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to RFQ')
                ->icon('heroicon-o-arrow-left')
                ->url(BulkRfqResource::getUrl('view', ['record' => $this->record])),
        ];
    }

    public function getTitle(): string
    {
        return 'Submit Quote for RFQ #' . $this->record->id;
    }

    public function getFormActions(): array
    {
        return [
            Actions\Action::make('submit')
                ->label('Submit Quote')
                ->action('submitQuote')
                ->color('success')
                ->icon('heroicon-o-check'),
        ];
    }
}