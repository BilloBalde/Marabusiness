<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaiementResource\Pages;
use App\Models\Order;
use App\Models\Paiement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class PaiementResource extends Resource
{
    protected static ?string $model = Paiement::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function getNavigationGroup(): ?string
    {
        return __('filament.groups.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.paiements');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Auto-generated, non-editable transaction_id
                TextInput::make('transaction_id')
                    ->label('Transaction ID')
                    ->default(fn () => self::generateTransactionNumber())
                    ->disabled()
                    ->dehydrated()
                    ->required(),

                // Relationship with order
                Select::make('order_id')
                    ->label('Order')
                    ->relationship('order', 'order_number')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        $currencyCode = optional($record->vendor?->currency)->code ?? config('app.currency', 'USD');

                        return "{$record->order_number} | " .
                            optional($record->user)->name . " | " .
                            $record->created_at->format('Y-m-d') . " | " .
                            $record->grand_total . ' ' . $currencyCode;
                    }),

                TextInput::make('amount')
                    ->numeric()
                    ->required(),

                Select::make('payment_method')
                    ->options([
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'cod' => 'Cash on Delivery',
                        'om' => 'Orange Money',
                    ])
                    ->required(),

                Select::make('currency')
                    ->options([
                        'usd' => 'USD',
                        'cad' => 'CAD',
                        'gnf' => 'GNF',
                    ])
                    ->required(),

                // 'public', pas 'public_uploads' : c'est le disque que le
                // modal web (PaiementModal) et l'API mobile
                // (PaymentController::submitOfflinePayment) utilisent
                // réellement pour écrire une preuve de paiement, et celui que
                // PaiementsRelationManager lit déjà pour l'afficher sous une
                // commande. Avec 'public_uploads' ici, cette page ne montrait
                // jamais les preuves que les acheteurs envoient — seulement
                // celles créées depuis cette page elle-même.
                FileUpload::make('image')
                    ->disk('public')
                    ->directory('payments')
                    ->label('Payment Screenshot')
                    ->previewable(true),

                Select::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_id')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('order.order_number')->label('Order'),
                Tables\Columns\TextColumn::make('amount')->money(),
                Tables\Columns\TextColumn::make('currency'),
                Tables\Columns\TextColumn::make('payment_method'),
                Tables\Columns\TextColumn::make('payment_status'),
                ImageColumn::make('image')
                    ->label('Image')
                    ->disk('public')
                    ->circular(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function ($record) {
                        self::updateOrderTotals($record->order);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function ($record) {
                        self::updateOrderTotals($record->order);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->after(function ($records) {
                        foreach ($records as $record) {
                            self::updateOrderTotals($record->order);
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaiements::route('/'),
            'create' => Pages\CreatePaiement::route('/create'),
            'edit' => Pages\EditPaiement::route('/{record}/edit'),
        ];
    }

    /**
     * Delegates to the model so a single implementation stays in charge of payment
     * references (see Order::generateTransactionNumber()).
     */
    public static function generateTransactionNumber(): string
    {
        return \App\Models\Order::generateTransactionNumber();
    }

    /**
     * Was its own copy of the balance rules, summing every payment including ones
     * the shop has not received. Order::syncPaymentTotals() owns that logic now and
     * counts confirmed money only.
     */
    public static function updateOrderTotals(Order $order): void
    {
        $order->syncPaymentTotals();
    }
}
