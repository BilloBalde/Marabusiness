<?php

namespace App\Filament\Vendor\Pages;

use App\Models\Currency;
use App\Models\Vendor;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Lets a vendor edit their own store record — the piece VendorApplyPage collects at
 * signup but that, until now, had no self-service way to correct afterward (a wrong
 * store name, a missing address, the placeholder currency assigned when a broken
 * vendor row was repaired by hand). Editing here always targets the signed-in user's
 * own vendor row; there is no route parameter and no way to target another vendor's.
 */
class VendorProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static string $view = 'filament.vendor.pages.vendor-profile';
    protected static ?string $navigationLabel = 'Ma boutique';
    protected static ?string $title = 'Profil de la boutique';
    protected static ?int $navigationSort = -1;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('vendor') ?? false;
    }

    public function mount(): void
    {
        $vendor = $this->vendor();

        $this->form->fill([
            'store_name'  => $vendor->store_name,
            'description' => $vendor->description,
            'logo_path'   => $vendor->logo_path,
            'currency_id' => $vendor->currency_id,
            'address'     => $vendor->address,
            'city'        => $vendor->city,
            'state'       => $vendor->state,
            'country'     => $vendor->country,
            'zip_code'    => $vendor->zip_code,
        ]);
    }

    protected function vendor(): Vendor
    {
        return Filament::auth()->user()->vendor;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Boutique')
                    ->description('Ces informations sont visibles par les acheteurs sur votre page boutique publique.')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->disk('public_uploads')
                            ->directory('vendors')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->avatar()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('store_name')
                            ->label('Nom de la boutique')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('currency_id')
                            ->label('Devise')
                            // Plain ->options() rather than ->relationship(): this form
                            // belongs to a standalone Page, not a Resource, so it has no
                            // bound Eloquent model for the relationship helper to
                            // introspect (it throws trying to call isRelation() on null).
                            ->options(fn () => Currency::orderBy('code')->pluck('code', 'id'))
                            ->required()
                            ->helperText('S\'applique aux prochaines commandes, sans changer celles déjà passées.'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Adresse')
                    ->description('Utilisée pour identifier votre boutique — la tarification par zone que vous configurez dans « Zones de livraison » reste séparée de cette adresse.')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Adresse')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('city')
                            ->label('Ville')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('state')
                            ->label('Région / État')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('country')
                            ->label('Pays')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('zip_code')
                            ->label('Code postal')
                            ->maxLength(50),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->vendor()->update($data);

        Notification::make()
            ->title('Boutique mise à jour')
            ->success()
            ->send();
    }
}
