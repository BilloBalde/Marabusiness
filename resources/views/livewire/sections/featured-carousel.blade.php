<section class="py-12">
  <div class="mx-auto max-w-7xl">
    <div class="swiper mySwiper">
      <div class="swiper-wrapper">
        @foreach ($featuredProducts as $product)
          <div class="flex flex-col items-center p-6 bg-white rounded-lg shadow swiper-slide md:flex-row">
            <img src="{{ url('uploads', $product->images[0]) }}" alt="{{ $product->name }}" class="w-full rounded-lg md:w-1/3">
            <div class="mt-6 md:ml-6 md:mt-0">
              <h2 class="text-2xl font-bold text-gray-800">{{ $product->name }}</h2>
              <p class="mt-2 text-gray-600">{{ Number::currency($product->price, 'CAD') }}</p>
              <a href="/products/{{ $product->slug }}" class="inline-block px-4 py-2 mt-4 text-white bg-blue-600 rounded hover:bg-blue-700">View Product</a>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js" defer></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      new Swiper('.mySwiper', {
        loop: true,
        autoplay: { delay: 4000 },
        slidesPerView: 1,
        spaceBetween: 30,
      });
    });
  </script>
</section>
