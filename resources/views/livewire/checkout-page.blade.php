<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
	<h1 class="mb-4 text-2xl font-bold text-gray-800 dark:text-black">
		Checkout
	</h1>
	<form wire:submit.prevent='placeOrder'>
        <div class="grid grid-cols-12 gap-4">
		<div class="col-span-12 md:col-span-12 lg:col-span-8">
			<!-- Card -->
			<div class="p-4 bg-white shadow rounded-xl sm:p-7 dark:bg-slate-900">
				<!-- Shipping Address -->
				<div class="mb-6">
					<h2 class="mb-2 text-xl font-bold text-gray-700 underline dark:text-white">
						Shipping Address
					</h2>
					<div class="grid grid-cols-2 gap-4">
						<div>
							<label class="block mb-1 text-gray-700 dark:text-white" for="first_name">
								First Name
							</label>
							<input class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-none @error('first_name') border-red-500 @enderror" wire:model='first_name' id="first_name" type="text">
							</input>
                            @error('first_name')
                                <div class="text-sm text-red-500">{{ $message }}</div>
                            @enderror
						</div>
						<div>
							<label class="block mb-1 text-gray-700 dark:text-white" for="last_name">
								Last Name
							</label>
							<input class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-none @error('last_name') border-red-500 @enderror" wire:model='last_name' id="last_name" type="text">
							</input>
                            @error('last_name')
                                <div class="text-sm text-red-500">{{ $message }}</div>
                            @enderror
						</div>
					</div>
					<div class="mt-4">
						<label class="block mb-1 text-gray-700 dark:text-white" for="phone">
							Phone
						</label>
						<input class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-none @error('phone') border-red-500 @enderror" wire:model='phone' id="phone" type="text">
						</input>
                        @error('phone')
                                <div class="text-sm text-red-500">{{ $message }}</div>
                            @enderror
					</div>
					<div class="mt-4">
						<label class="block mb-1 text-gray-700 dark:text-white" for="address">
							Address
						</label>
						<input class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-none @error('street_address') border-red-500 @enderror" wire:model='street_address' id="address" type="text">
						</input>
                        @error('street_address')
                                <div class="text-sm text-red-500">{{ $message }}</div>
                            @enderror
					</div>
					<div class="mt-4">
						<label class="block mb-1 text-gray-700 dark:text-white" for="city">
							City
						</label>
						<input class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-none @error('city') border-red-500 @enderror" wire:model='city' id="city" type="text">
						</input>
                        @error('city')
                                <div class="text-sm text-red-500">{{ $message }}</div>
                            @enderror
					</div>
					<div class="grid grid-cols-2 gap-4 mt-4">
						<div>
							<label class="block mb-1 text-gray-700 dark:text-white" for="state">
								State
							</label>
							<input class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-none @error('state') border-red-500 @enderror" wire:model='state' id="state" type="text">
							</input>
                            @error('state')
                                <div class="text-sm text-red-500">{{ $message }}</div>
                            @enderror
						</div>
						<div>
							<label class="block mb-1 text-gray-700 dark:text-white" for="zip">
								ZIP Code
							</label>
							<input class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-none @error('zip_code') border-red-500 @enderror" wire:model='zip_code' id="zip" type="text">
							</input>
                            @error('zip_code')
                                <div class="text-sm text-red-500">{{ $message }}</div>
                            @enderror
						</div>
					</div>
				</div>
				<div class="mb-4 text-lg font-semibold text-gray-700 underline dark:text-white">
					Select Payment Method
				</div>
				<ul class="grid w-full gap-6 md:grid-cols-3">
					<li>
						<input wire:change="$set('payment_method', 'cod')" name="payment_method" value="cod" class="hidden peer" id="hosting-small" type="radio"/>
						<label class="inline-flex items-center justify-between w-full p-5 text-gray-500 bg-white border border-gray-200 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-700 dark:peer-checked:text-blue-500 peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700" for="hosting-small">
							<div class="block">
								<div class="w-full text-lg font-semibold @error('payment_method') border-red-500 @enderror">
									Cash on Delivery
								</div>
							</div>
							<svg aria-hidden="true" class="w-5 h-5 ms-3 rtl:rotate-180" fill="none" viewbox="0 0 14 10" xmlns="http://www.w3.org/2000/svg">
								<path d="M1 5h12m0 0L9 1m4 4L9 9" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
								</path>
							</svg>
						</label>
					</li>
					<li>
						<input wire:change="$set('payment_method', 'stripe')" name="payment_method" value="stripe" class="hidden peer" id="hosting-middle" type="radio"/>
						<label class="inline-flex items-center justify-between w-full p-5 text-gray-500 bg-white border border-gray-200 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-700 dark:peer-checked:text-blue-500 peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700" for="hosting-middle">
							<div class="block">
								<div class="w-full text-lg font-semibold @error('payment_method') border-red-500 @enderror">
									Stripe
								</div>
							</div>
							<svg aria-hidden="true" class="w-5 h-5 ms-3 rtl:rotate-180" fill="none" viewbox="0 0 14 10" xmlns="http://www.w3.org/2000/svg">
								<path d="M1 5h12m0 0L9 1m4 4L9 9" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
								</path>
							</svg>
						</label>
					</li>
					<li>
						<input wire:change="$set('payment_method', 'om')" name="payment_method" value="om" class="hidden peer" id="hosting-big" type="radio"/>
						<label class="inline-flex items-center justify-between w-full p-5 text-gray-500 bg-white border border-gray-200 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-700 dark:peer-checked:text-blue-500 peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700" for="hosting-big">
							<div class="block">
								<div class="w-full text-lg font-semibold @error('payment_method') border-red-500 @enderror">
									Orange Money
								</div>
							</div>
							<svg aria-hidden="true" class="w-5 h-5 ms-3 rtl:rotate-180" fill="none" viewbox="0 0 14 10" xmlns="http://www.w3.org/2000/svg">
								<path d="M1 5h12m0 0L9 1m4 4L9 9" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
								</path>
							</svg>
						</label>
					</li>

				</ul>
                @error('payment_method')
                    <div class="text-sm text-red-500">{{ $message }}</div>
                @enderror
                @if ($payment_method === 'om')
                    <div class="mt-4 p-4 bg-yellow-50 border border-yellow-300 rounded-lg text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                        <p class="font-semibold mb-2">
                            Faites un dépôt sur ce numéro <strong>625170257</strong>,
                            entrez le montant déposé et la capture d'écran dans les champs ci-dessous.
                        </p>

                        <div class="mt-4">
                            <label class="block mb-1 text-gray-700 dark:text-white" for="amount">
                                Montant Déposé (Orange Money)
                            </label>
                            <input type="number" id="amount" wire:model="amount"
                                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-none @error('amount') border-red-500 @enderror">
                            @error('amount')
                                <div class="text-sm text-red-500">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <label class="block mb-1 text-gray-700 dark:text-white" for="image">
                                Capture d'écran du paiement
                            </label>
                            <input type="file" id="image" wire:model="image"
                                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-none @error('image') border-red-500 @enderror">
                            @error('image')
                                <div class="text-sm text-red-500">{{ $message }}</div>
                            @enderror

                            <div wire:loading wire:target="image" class="text-sm text-gray-500 mt-1">
                                Téléchargement...
                            </div>
                            @if ($image)
                                <div class="mt-3">
                                    <span class="block mb-1 text-gray-700 dark:text-white font-medium">Aperçu de l'image :</span>
                                    <img src="{{ $image->temporaryUrl() }}" class="w-64 h-auto rounded border border-gray-300 dark:border-gray-600">
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

			</div>
			<!-- End Card -->
		</div>
		<div class="col-span-12 md:col-span-12 lg:col-span-4">
			<div class="p-4 bg-white shadow rounded-xl sm:p-7 dark:bg-slate-900">
				<div class="mb-2 text-xl font-bold text-gray-700 underline dark:text-white">
					COMMANDE SOMMAIRE
				</div>
				<div class="flex justify-between mb-2 font-bold text-gray-500">
					<span>
						Subtotal
					</span>
					<span>
						{{ Number::currency($grand_total, 'CAD') }}
					</span>
				</div>
				<div class="flex justify-between mb-2 font-bold text-gray-500">
					<span>
						Taxes
					</span>
					<span>
						{{ Number::currency(0.00, 'CAD') }}
					</span>
				</div>
				<div class="flex justify-between mb-2 font-bold text-gray-500">
					<span>
						Shipping Cost
					</span>
					<span>
						{{ Number::currency(0.00, 'CAD') }}
					</span>
				</div>
				<hr class="h-1 my-4 rounded bg-slate-100">
				<div class="flex justify-between mb-2 font-bold text-gray-500">
					<span>
						Grand Total
					</span>
					<span>
						{{ Number::currency($grand_total, 'CAD') }}
					</span>
				</div>
				</hr>
			</div>
			<button type="submit" class="w-full p-3 mt-4 text-lg text-white bg-green-500 rounded-lg hover:bg-green-600">
				<span wire:loading.remove>Placer la Commande</span>
                <span wire:loading>Processing...</span>
			</button>
			<div class="p-4 mt-4 bg-white shadow rounded-xl sm:p-7 dark:bg-slate-900">
				<div class="mb-2 text-xl font-bold text-gray-700 underline dark:text-white">
					BASKET SUMMARY
				</div>
				<ul class="divide-y divide-gray-200 dark:divide-gray-700" role="list">
					@foreach ($cart_items as $ci)
                    <li class="py-3 sm:py-4" wire:key="{{ $ci['product_id'] }}">
						<div class="flex items-center">
							<div class="flex-shrink-0">
								<img alt="Neil image" class="w-12 h-12 rounded-full" src="{{ url('uploads', $ci['image']) }}">
								</img>
							</div>
							<div class="flex-1 min-w-0 ms-4">
								<p class="text-sm font-medium text-gray-900 truncate dark:text-white">
									{{ $ci['name'] }}
								</p>
								<p class="text-sm text-gray-500 truncate dark:text-gray-400">
									Quantity: {{ $ci['quantity'] }}
								</p>
							</div>
							<div class="inline-flex items-center text-base font-semibold text-gray-900 dark:text-white">
								{{ Number::currency($ci['total_amount'], 'CAD') }}
							</div>
						</div>
					</li>
                    @endforeach
				</ul>
			</div>
		</div>
	</div>
    </form>
</div>
