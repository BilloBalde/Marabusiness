<?php

namespace App\Livewire;

use App\Models\Address;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MyAddresses extends Component
{
    public $editingId = null;

    public $first_name = '';
    public $last_name = '';
    public $phone = '';
    public $street_address = '';
    public $city = '';
    public $state = '';
    public $zip_code = '';
    public $country = 'Guinea';
    public $latitude = null;
    public $longitude = null;
    public $zone = null;
    public $is_default = false;

    protected function rules()
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'street_address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'zone' => ['nullable', 'string', 'max:255'],
            'is_default' => ['boolean'],
        ];
    }

    public function resetForm()
    {
        $this->editingId = null;

        $this->first_name = '';
        $this->last_name = '';
        $this->phone = '';
        $this->street_address = '';
        $this->city = '';
        $this->state = '';
        $this->zip_code = '';
        $this->country = 'Guinea';
        $this->latitude = null;
        $this->longitude = null;
        $this->zone = null;
        $this->is_default = false;

        $this->resetValidation();
    }

    public function edit(int $id)
    {
        $address = Address::where('user_id', Auth::id())
            ->whereNull('order_id')
            ->findOrFail($id);

        $this->editingId = $address->id;
        $this->first_name = $address->first_name;
        $this->last_name = $address->last_name;
        $this->phone = $address->phone;
        $this->street_address = $address->street_address;
        $this->city = $address->city;
        $this->state = $address->state;
        $this->zip_code = $address->zip_code;
        $this->country = $address->country ?? 'Guinea';
        $this->latitude = $address->latitude;
        $this->longitude = $address->longitude;
        $this->zone = $address->zone;
        $this->is_default = (bool) $address->is_default;

        $this->resetValidation();
    }

    public function save()
    {
        $data = $this->validate();
        $data['user_id'] = Auth::id();

        if ($data['is_default']) {
            Address::where('user_id', Auth::id())
                ->whereNull('order_id')
                ->update(['is_default' => false]);
        }

        if ($this->editingId) {
            Address::where('user_id', Auth::id())
                ->whereNull('order_id')
                ->where('id', $this->editingId)
                ->update($data);
        } else {
            Address::create($data);
        }

        session()->flash('success', 'Address saved successfully.');
        $this->resetForm();
    }

    public function delete(int $id)
    {
        Address::where('user_id', Auth::id())
            ->whereNull('order_id')
            ->where('id', $id)
            ->delete();
        session()->flash('success', 'Address removed.');
    }

    public function render()
    {
        $addresses = Address::where('user_id', Auth::id())
            ->whereNull('order_id')
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        return view('livewire.my-addresses', compact('addresses'));
    }
}
