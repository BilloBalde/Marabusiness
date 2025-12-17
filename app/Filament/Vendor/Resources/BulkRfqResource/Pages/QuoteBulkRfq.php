<?php

namespace App\Filament\Vendor\Resources\BulkRfqResource\Pages;

use App\Filament\Vendor\Resources\BulkRfqResource;
use App\Models\BulkRfq;
use App\Models\BulkRfqOffer;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class QuoteBulkRfq extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static string $resource = BulkRfqResource::class;
    protected static string $view = 'filament.vendor.resources.bulk-rfq-resource.pages.quote-bulk-rfq';

    public BulkRfq $record;
    public ?array $data = [];

    public function mount($record): void
    {
        $this->record = BulkRfq::with(['product', 'user'])->findOrFail($record);

        // Check if vendor is authorized
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
                            ->options([
                                'USD' => 'USD',
                                'EUR' => 'EUR',
                                'GNF' => 'GNF',
                            ])
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
        
        DB::transaction(function () use ($data) {
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
        });
        
        Notification::make()
            ->title('Quote Submitted Successfully')
            ->body('Your quotation has been sent to the buyer.')
            ->success()
            ->send();
            
        $this->redirect(BulkRfqResource::getUrl('view', ['record' => $this->record]));
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