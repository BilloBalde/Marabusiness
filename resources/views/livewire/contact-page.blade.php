<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
  <h1 class="mb-6 text-2xl font-semibold">Contact Us</h1>

  <div class="flex flex-col gap-6 md:flex-row">

    <!-- Left Column: Contact Form -->
    <div class="md:w-3/4">
      <div class="p-6 bg-white rounded-lg shadow-md">

        @if (session()->has('success'))
          <div class="p-4 mb-4 text-green-800 bg-green-100 rounded-lg">
            {{ session('success') }}
          </div>
        @endif

        <form wire:submit.prevent="submit" class="space-y-5">
          <div>
            <label class="block mb-1 font-medium text-gray-700">Name</label>
            <input type="text" wire:model.defer="name" class="w-full px-4 py-2 border rounded-md focus:ring focus:border-blue-500">
            @error('name') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
          </div>

          <div>
            <label class="block mb-1 font-medium text-gray-700">Email</label>
            <input type="email" wire:model.defer="email" class="w-full px-4 py-2 border rounded-md focus:ring focus:border-blue-500">
            @error('email') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
          </div>

          <div>
            <label class="block mb-1 font-medium text-gray-700">Subject</label>
            <input type="text" wire:model.defer="subject" class="w-full px-4 py-2 border rounded-md focus:ring focus:border-blue-500">
            @error('subject') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
          </div>

          <div>
            <label class="block mb-1 font-medium text-gray-700">Message</label>
            <textarea wire:model.defer="message" rows="5" class="w-full px-4 py-2 border rounded-md focus:ring focus:border-blue-500"></textarea>
            @error('message') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
          </div>

          <div>
            <button type="submit" class="px-6 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700">
              Send Message
            </button>
          </div>
        </form>

      </div>
    </div>

    <!-- Right Column: Contact Info -->
    <div class="md:w-1/4">
      <div class="p-6 bg-white rounded-lg shadow-md">
        <h2 class="mb-4 text-lg font-semibold">Our Contact Info</h2>
        <p class="mb-2"><strong>Email:</strong> contact@yourcompany.com</p>
        <p class="mb-2"><strong>Phone:</strong> +123 456 7890</p>
        <p class="mb-2"><strong>Address:</strong><br>123 Main Street,<br>City, Country</p>
        <p class="mt-4 text-sm text-gray-500">We'll get back to you as soon as possible.</p>
      </div>
    </div>

  </div>
</div>
