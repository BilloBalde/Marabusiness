<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Currency;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Vendor Application - MARA BUSINESS')]
class VendorApplyPage extends Component
{
    use WithFileUploads;
    public bool $showTermsModal = true;
    public bool $acceptedTerms = false;

    // If user is not logged in, we create the user first:
    public ?string $name = null;
    public ?string $email = null;
    public ?string $password = null;
    public ?string $password_confirmation = null;

    // Vendor fields
    public ?int $currency_id = null;
    public ?string $store_name = null;
    public ?string $description = null;
    public $logo; // temporary uploaded file

    public ?string $address = null;
    public ?string $city = null;
    public ?string $state = null;
    public ?string $country = 'Guinea';
    public ?string $zip_code = null;

    public function mount()
    {
        // If logged in and already has a vendor → redirect to vendor page (optional)
        if (Auth::check()) {
            $alreadyVendor = Vendor::where('user_id', Auth::id())->exists();
            if ($alreadyVendor) {
                // change this route to your vendor dashboard if you have one
                return redirect()->route('filament.vendor.pages.vendor-dashboard');
            }

            // Pre-fill from user
            $u = Auth::user();
            $this->name = $u->name;
            $this->email = $u->email;
        }

        // Default currency (optional)
        $this->currency_id = Currency::query()->value('id');
    }

    public function acceptTerms()
    {
        if (!$this->acceptedTerms) {
            $this->addError('acceptedTerms', 'You must accept the conditions to continue.');
            return;
        }

        $this->resetErrorBag('acceptedTerms');
        $this->showTermsModal = false;
    }

    public function submit()
    {
        if (!$this->acceptedTerms) {
            $this->addError('acceptedTerms', 'You must accept the conditions to continue.');
            $this->showTermsModal = true;
            return;
        }

        // 1) Validate user fields (only if guest)
        if (!Auth::check()) {
            $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
                'password' => ['required', 'string', 'min:6', 'confirmed'],
            ]);
        }

        // 2) Validate vendor fields
        $this->validate([
            'logo' => ['nullable', 'image', 'max:2048'],
            'store_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'currency_id' => ['required', 'integer', Rule::exists('currencies', 'id')],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:50'],
        ]);

        // 3) Create / get user
        $user = Auth::user();

        if (!$user) {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
            ]);

            Auth::login($user);
        }

        // 4) If vendor already exists (double safety)
        if (Vendor::where('user_id', $user->id)->exists()) {
            session()->flash('error', 'You already have a vendor profile.');
            return redirect()->route('vendors.list');
        }

        // 5) Assign vendor role (Spatie)
        // Make sure role "vendor" exists in DB
        if (!$user->hasRole('vendor')) {
            $user->assignRole('vendor');
        }

        $logoPath = null;

        if ($this->logo) {
            $logoPath = $this->logo->store('vendors', 'public_uploads');
        }

        // 6) Create vendor (pending approval)
        $vendor = Vendor::create([
            'user_id' => $user->id,
            'currency_id' => $this->currency_id,
            'store_name' => $this->store_name,
            'slug' => Str::slug($this->store_name) . '-' . Str::lower(Str::random(6)),
            'description' => $this->description,
            'is_active' => false,
            'approved_at' => null,
            'logo_path'  => $logoPath,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'zip_code' => $this->zip_code,
        ]);

        session()->flash('success', 'Application submitted! Your vendor account is pending approval.');

        // redirect wherever you want after apply
        return redirect()->route('vendors.list');
    }

    public function render()
    {
        return view('livewire.vendor-apply-page', [
            'currencies' => Currency::orderBy('code')->get(),
        ]);
    }
}
