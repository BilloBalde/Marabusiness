<div class="bg-gray-50 min-h-screen py-8">
    
    @if(isset($isListing) && $isListing)
        {{-- SERVICES LISTING PAGE --}}
        <div class="max-w-7xl mx-auto px-4">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Our Services</h1>
                <p class="text-gray-600">Discover our premium services designed to enhance your business experience</p>
            </div>
            
            @if($services->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($services as $service)
                        <a href="/service/{{ $service->slug }}" 
                           class="bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group border border-gray-200">
                            <div class="p-6">
                                <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-r from-[#D4AF37] to-[#c9a12f] flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                    @if($service->icon)
                                        <i class="{{ $service->icon }} text-white text-3xl"></i>
                                    @else
                                        <i class="fas fa-concierge-bell text-white text-3xl"></i>
                                    @endif
                                </div>
                                
                                <h3 class="text-xl font-bold text-gray-900 mb-3 text-center group-hover:text-[#D4AF37] transition-colors">
                                    {{ $service->name }}
                                </h3>
                                
                                <p class="text-gray-600 text-center line-clamp-3">
                                    {{ $service->description ?? 'Premium service offering designed to meet your business needs.' }}
                                </p>
                                
                                <div class="mt-6 text-center">
                                    <span class="inline-flex items-center text-[#D4AF37] font-medium">
                                        Learn More
                                        <i class="fas fa-arrow-right ml-2 group-hover:translate-x-2 transition-transform"></i>
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                    <i class="fas fa-concierge-bell text-gray-400 text-5xl mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">No Services Available</h3>
                    <p class="text-gray-600">Services will be added soon. Please check back later.</p>
                </div>
            @endif
        </div>
    @else
        {{-- SINGLE SERVICE DETAIL PAGE --}}
        <div class="max-w-7xl mx-auto px-4">
            <nav class="mb-6">
                <ol class="flex items-center space-x-2 text-sm text-gray-600">
                    <li><a href="/" class="hover:text-[#D4AF37]">Home</a></li>
                    <li><i class="fas fa-chevron-right text-xs"></i></li>
                    <li><a href="/services" class="hover:text-[#D4AF37]">Services</a></li>
                    <li><i class="fas fa-chevron-right text-xs"></i></li>
                    <li class="text-gray-900 font-medium">{{ $service->name }}</li>
                </ol>
            </nav>
            
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="md:flex">
                    {{-- Service Image/Icon --}}
                    <div class="md:w-1/3 bg-gradient-to-br from-[#D4AF37] to-[#c9a12f] p-8 flex items-center justify-center">
                        <div class="text-center">
                            @if($service->icon)
                                <i class="{{ $service->icon }} text-white text-6xl mb-4"></i>
                            @else
                                <i class="fas fa-concierge-bell text-white text-6xl mb-4"></i>
                            @endif
                            <h2 class="text-2xl font-bold text-white">{{ $service->name }}</h2>
                        </div>
                    </div>
                    
                    {{-- Service Details --}}
                    <div class="md:w-2/3 p-8">
                        <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $service->name }}</h1>
                        
                        <div class="prose max-w-none text-gray-700 mb-6">
                            <p class="text-lg">{!! \App\Support\HtmlSanitizer::clean($service->description) ?: 'Premium service offering from MARA BUSINESS.' !!}</p>
                        </div>
                        
                        {{-- Service Features --}}
                        <div class="bg-gray-50 rounded-lg p-6 mb-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-4">Service Features</h3>
                            <ul class="space-y-3">
                                @if(!empty($service->features))
                                    @foreach($service->features as $feature)
                                        <li class="flex items-start">
                                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                                            <span class="text-gray-700">{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                @else
                                    {{-- Default features if none are set --}}
                                    <li class="flex items-start">
                                        <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                                        <span class="text-gray-700">Professional and reliable service</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                                        <span class="text-gray-700">24/7 customer support</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                                        <span class="text-gray-700">Quality guaranteed</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                                        <span class="text-gray-700">Flexible service options</span>
                                    </li>
                                @endif
                            </ul>
                        </div>
                        
                        {{-- Contact/Inquiry Button --}}
                        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                            <div>
                                <p class="text-gray-600">Interested in this service?</p>
                                <p class="text-sm text-gray-500">Contact us for more information and pricing</p>
                            </div>
                            <a href="/contact" 
                               class="bg-[#D4AF37] hover:bg-[#c9a12f] text-white font-medium py-3 px-6 rounded-lg transition-colors inline-flex items-center">
                                <i class="fas fa-envelope mr-2"></i>
                                Contact Us
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Related Services --}}
            <div class="mt-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Other Services</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @php
                        $otherServices = \App\Models\Service::where('id', '!=', $service->id)->limit(3)->get();
                    @endphp
                    
                    @if($otherServices->count() > 0)
                        @foreach($otherServices as $otherService)
                            <a href="/service/{{ $otherService->slug }}" 
                               class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-6 border border-gray-200">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-r from-[#D4AF37] to-[#c9a12f] flex items-center justify-center mb-4">
                                    @if($otherService->icon)
                                        <i class="{{ $otherService->icon }} text-white text-xl"></i>
                                    @else
                                        <i class="fas fa-concierge-bell text-white text-xl"></i>
                                    @endif
                                </div>
                                <h3 class="font-bold text-gray-900 mb-2">{{ $otherService->name }}</h3>
                                <p class="text-sm text-gray-600 line-clamp-3">
                                    {{ $otherService->description ?? 'Premium service offering' }}
                                </p>
                                <span class="inline-flex items-center text-[#D4AF37] text-sm font-medium mt-3">
                                    Learn More
                                    <i class="fas fa-arrow-right ml-1"></i>
                                </span>
                            </a>
                        @endforeach
                    @else
                        <div class="col-span-3 text-center py-8 text-gray-500">
                            No other services available at the moment.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
    
</div>