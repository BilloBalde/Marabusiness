<div class="max-w-3xl mx-auto p-4 sm:p-6 lg:p-8">
    @if(session()->has('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <h1 class="text-2xl font-bold mb-2">Vendor Application</h1>
    <p class="text-sm text-gray-600 mb-6">
        Please read and accept the conditions before submitting your vendor profile.
    </p>

    {{-- TERMS MODAL --}}
    @if($showTermsModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
            <div class="w-full max-w-2xl bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-5 py-4 border-b flex items-center justify-between">
                    <h2 class="font-semibold text-lg">Conditions & Commission Contract</h2>
                    <a href="{{ route('vendors.list') }}" class="text-gray-500 hover:text-gray-900">✕</a>
                </div>

                <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">
                    <div class="p-4 rounded-lg bg-gray-50 border text-sm text-gray-700 space-y-2">
                        <p class="font-semibold">Marketplace Rules</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>You must provide accurate store information.</li>
                            <li>Products must be authentic and legal to sell.</li>
                            <li>Orders must be fulfilled on time.</li>
                            <li>Repeated violations may lead to suspension.</li>
                        </ul>
                    </div>

                    <div class="p-4 rounded-lg bg-gray-50 border text-sm text-gray-700 space-y-2">
                        <p class="font-semibold">Commission Contract</p>
                        <p>
                            By joining, you agree that the platform may charge a commission per order
                            (percentage or fixed fees depending on your plan and payment method).
                        </p>
                        <p class="text-gray-500">
                            (Put your real commission text here. If you have a PDF contract, you can also link it.)
                        </p>
                    </div>

                    <label class="flex items-start gap-2 text-sm">
                        <input type="checkbox" class="mt-1"
                               wire:model="acceptedTerms">
                        <span>
                            I have read and accept the conditions and the commission contract.
                        </span>
                    </label>

                    @error('acceptedTerms')
                        <div class="text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <div class="px-5 py-4 border-t flex gap-2 justify-end">
                    <a href="{{ route('vendors.list') }}"
                       class="px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancel
                    </a>
                    <button wire:click="acceptTerms"
                            class="px-4 py-2 rounded-lg bg-[#D4AF37] text-white font-semibold hover:bg-[#c9a12f]">
                        I Accept — Continue
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- FORM --}}
    @if(!$showTermsModal)
        <div class="bg-white border rounded-xl p-5 space-y-6">

            {{-- If guest: create user first --}}
            @guest
                <div class="border rounded-lg p-4 bg-gray-50">
                    <h3 class="font-semibold mb-3">Create your account</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="text-sm text-gray-600">Full name</label>
                            <input type="text" wire:model="name"
                                   class="w-full border rounded-lg p-2">
                            @error('name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="text-sm text-gray-600">Email</label>
                            <input type="email" wire:model="email"
                                   class="w-full border rounded-lg p-2">
                            @error('email') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="text-sm text-gray-600">Password</label>
                            <input type="password" wire:model="password"
                                   class="w-full border rounded-lg p-2">
                            @error('password') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="text-sm text-gray-600">Confirm password</label>
                            <input type="password" wire:model="password_confirmation"
                                   class="w-full border rounded-lg p-2">
                        </div>
                    </div>
                </div>
            @endguest

            @php
                $inputClass = 'w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900
                            placeholder:text-gray-400 shadow-sm
                            focus:border-[#D4AF37] focus:ring-2 focus:ring-[#D4AF37]/40
                            transition';
                $labelClass = 'block text-sm font-medium text-gray-700 mb-1';
                $errorClass = 'mt-1 text-sm text-red-600';
            @endphp

            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 sm:p-8">
                <div class="flex items-start justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Vendor Profile</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Fill in your store information. This will appear on your vendor page.
                        </p>
                    </div>
                </div>

                {{-- 12-col grid gives better control on desktop --}}
                <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                    {{-- Store name --}}
                    <div class="md:col-span-6">
                        <label class="{{ $labelClass }}">Store name</label>
                        <input type="text" wire:model="store_name" class="{{ $inputClass }}" placeholder="e.g. Mara Electronics">
                        @error('store_name') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- Currency --}}
                    <div class="md:col-span-6">
                        <label class="{{ $labelClass }}">Currency</label>
                        <select wire:model="currency_id" class="{{ $inputClass }}">
                            <option value="">Select currency</option>
                            @foreach($currencies as $c)
                                <option value="{{ $c->id }}">{{ $c->code }}</option>
                            @endforeach
                        </select>
                        @error('currency_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- Description --}}
                    <div class="md:col-span-12">
                        <label class="{{ $labelClass }}">Description</label>
                        <textarea wire:model="description" rows="4" class="{{ $inputClass }}"
                                placeholder="Tell customers what you sell, shipping info, etc."></textarea>
                        @error('description') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- Country --}}
                    <div class="md:col-span-4">
                        <label class="{{ $labelClass }}">Country</label>
                        <input type="text" wire:model="country" class="{{ $inputClass }}" placeholder="Guinea">
                        @error('country') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- City --}}
                    <div class="md:col-span-4">
                        <label class="{{ $labelClass }}">City</label>
                        <input type="text" wire:model="city" class="{{ $inputClass }}" placeholder="Conakry">
                        @error('city') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- Zip --}}
                    <div class="md:col-span-4">
                        <label class="{{ $labelClass }}">Zip Code</label>
                        <input type="text" wire:model="zip_code" class="{{ $inputClass }}" placeholder="e.g. 00000">
                        @error('zip_code') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- Address --}}
                    <div class="md:col-span-8">
                        <label class="{{ $labelClass }}">Address</label>
                        <input type="text" wire:model="address" class="{{ $inputClass }}" placeholder="Street, building, area...">
                        @error('address') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- State --}}
                    <div class="md:col-span-4">
                        <label class="{{ $labelClass }}">State</label>
                        <input type="text" wire:model="state" class="{{ $inputClass }}" placeholder="Region / State">
                        @error('state') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- Store Logo --}}
                    <div class="md:col-span-12">
                        <label class="{{ $labelClass }}">Store Logo</label>

                        <div class="mt-2 flex flex-col sm:flex-row sm:items-center gap-4">
                            {{-- Preview --}}
                            <div class="flex items-center gap-3">
                                @if ($logo)
                                    <img src="{{ $logo->temporaryUrl() }}"
                                        class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl object-cover border border-gray-200 shadow-sm">
                                @else
                                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl border border-dashed border-gray-300
                                                flex items-center justify-center text-gray-400 text-xs sm:text-sm bg-gray-50">
                                        Logo
                                    </div>
                                @endif

                                <div>
                                    <p class="text-sm font-medium text-gray-700">Upload a logo</p>
                                    <p class="text-xs text-gray-500">PNG, JPG, WEBP — max 2MB</p>
                                </div>
                            </div>

                            {{-- File input (styled wrapper) --}}
                            <label class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-gray-300
                                        bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 cursor-pointer transition w-full sm:w-auto">
                                Choose file
                                <input type="file"
                                    wire:model="logo"
                                    accept="image/*"
                                    class="hidden">
                            </label>
                        </div>

                        @error('logo')
                            <p class="{{ $errorClass }}">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>


            <div class="flex justify-end gap-2">
                <a href="{{ route('vendors.list') }}" class="px-4 py-2 rounded-lg border hover:bg-gray-50">
                    Cancel
                </a>
                <button wire:click="submit"
                        class="px-4 py-2 rounded-lg bg-[#D4AF37] text-white font-semibold hover:bg-[#c9a12f]">
                    Submit Application
                </button>
            </div>
        </div>
    @endif
</div>
