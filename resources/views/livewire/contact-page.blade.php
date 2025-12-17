<div class="w-full max-w-[90rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">

    <!-- PAGE TITLE -->
    <h1 class="mb-10 text-3xl font-bold text-gray-800 tracking-tight">
        Contact Us
    </h1>

    <div class="flex flex-col gap-8 lg:flex-row">

        <!-- LEFT: FORM -->
        <div class="lg:w-3/4">
            <div class="p-8 bg-white rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] border border-gray-100">

                @if (session()->has('success'))
                    <div class="p-4 mb-6 rounded-xl bg-green-50 text-green-700 font-medium border border-green-200">
                        {{ session('success') }}
                    </div>
                @endif

                <form wire:submit.prevent="submit" class="space-y-6">

                    <!-- NAME -->
                    <div class="space-y-1">
                        <label class="block text-gray-700 font-semibold">Name</label>
                        <input type="text"
                               wire:model.defer="name"
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-[#D4AF37] focus:ring-[#D4AF37] transition">
                        @error('name') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <!-- EMAIL -->
                    <div class="space-y-1">
                        <label class="block text-gray-700 font-semibold">Email</label>
                        <input type="email"
                               wire:model.defer="email"
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-[#D4AF37] focus:ring-[#D4AF37] transition">
                        @error('email') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <!-- SUBJECT -->
                    <div class="space-y-1">
                        <label class="block text-gray-700 font-semibold">Subject</label>
                        <input type="text"
                               wire:model.defer="subject"
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-[#D4AF37] focus:ring-[#D4AF37] transition">
                        @error('subject') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <!-- MESSAGE -->
                    <div class="space-y-1">
                        <label class="block text-gray-700 font-semibold">Message</label>
                        <textarea wire:model.defer="message" rows="5"
                                  class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-[#D4AF37] focus:ring-[#D4AF37] transition"></textarea>
                        @error('message') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <!-- SUBMIT BUTTON (TEMU STYLE) -->
                    <div>
                        <button type="submit"
                                class="w-full py-3 flex items-center justify-center gap-2 rounded-xl text-lg font-semibold text-white bg-[#D4AF37] hover:bg-[#c9a12f] shadow-md transition">
                            <i class="fa-solid fa-paper-plane"></i>
                            Send Message
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <!-- RIGHT: CONTACT INFO -->
        <div class="lg:w-1/4">
            <div class="p-8 bg-white rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] border border-gray-100">

                <h2 class="mb-6 text-xl font-bold text-gray-800">Our Contact Info</h2>

                <div class="space-y-4 text-gray-700">

                    <p>
                        <span class="font-semibold text-gray-900">Email:</span><br>
                        <a href="mailto:contact@yourcompany.com" class="text-[#D4AF37] font-medium">
                            contact@yourcompany.com
                        </a>
                    </p>

                    <p>
                        <span class="font-semibold text-gray-900">Phone:</span><br>
                        +123 456 7890
                    </p>

                    <p>
                        <span class="font-semibold text-gray-900">Address:</span><br>
                        123 Main Street<br>
                        City, Country
                    </p>
                </div>

                <p class="mt-6 text-sm text-gray-500">
                    We'll respond within 24 hours.
                </p>
            </div>
        </div>

    </div>

</div>
