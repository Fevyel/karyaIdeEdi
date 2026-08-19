<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $product->nama ?? 'Detail Produk' }} - Karya Ide Edi</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="bg-white font-sans text-gray-800 antialiased">

    @include('partials.frontend.navbar')

    <!-- Content Container -->
    <main class="max-w-6xl mx-auto px-4 py-8">
        
        <!-- Breadcrumb -->
        <nav class="text-xs text-gray-400 mb-8 flex items-center space-x-2">
            <a href="{{ route('home') }}" class="hover:text-gray-600">Home</a>
            <span>/</span>
            <a href="{{ route('products.index') }}" class="hover:text-gray-600">Shop</a>
            @if($product->category)
                <span>/</span>
                <a href="{{ route('products.index', ['category' => $product->category->slug]) }}" class="hover:text-gray-600">{{ $product->category->name }}</a>
            @endif
            <span>/</span>
            <span class="text-gray-900 font-semibold">{{ $product->nama }}</span>
        </nav>

        <!-- Product Grid (Layout Persis Desain Figma) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-start">
            
            <!-- Side Kiri: Foto Utama + 4 Thumbnail Horizontal di Bawahnya -->
            <div class="space-y-4">
                @php
                    $mainImage = $product->foto_utama ?? $product->gambar ?? $product->image ?? $product->foto ?? null;
                    $gallery = is_array($product->galeri) ? $product->galeri : json_decode($product->galeri ?? '[]', true);
                @endphp

                <!-- Box Foto Utama -->
                <div class="w-full h-95 bg-[#F4F4F4] rounded-xl flex items-center justify-center text-xs font-semibold text-gray-400 tracking-wider overflow-hidden">
                    @if($mainImage)
                        <img src="{{ asset('storage/' . $mainImage) }}" alt="{{ $product->nama }}" class="w-full h-full object-cover">
                    @else
                        <span>MAIN PRODUCT IMAGE</span>
                    @endif
                </div>

                <!-- 4 Box Thumbnail di Bawah Foto Utama (Horizontal Row) -->
                <div class="grid grid-cols-4 gap-4">
                    @if(!empty($gallery) && count($gallery) > 0)
                        @foreach(array_slice($gallery, 0, 4) as $index => $img)
                            <div class="h-24 bg-[#F4F4F4] rounded-lg border {{ $index === 0 ? 'border-2 border-[#FF6600]' : 'border-transparent' }} overflow-hidden cursor-pointer">
                                <img src="{{ asset('storage/' . $img) }}" class="w-full h-full object-cover">
                            </div>
                        @endforeach
                    @else
                        <div class="h-24 bg-[#F4F4F4] rounded-lg border-2 border-[#FF6600] overflow-hidden cursor-pointer flex items-center justify-center">
                            @if($mainImage)
                                <img src="{{ asset('storage/' . $mainImage) }}" class="w-full h-full object-cover">
                            @endif
                        </div>
                        <div class="h-24 bg-[#F4F4F4] rounded-lg border border-transparent"></div>
                        <div class="h-24 bg-[#F4F4F4] rounded-lg border border-transparent"></div>
                        <div class="h-24 bg-[#F4F4F4] rounded-lg border border-transparent"></div>
                    @endif
                </div>
            </div>

            <!-- Side Kanan: Detail & Tombol Aksi -->
            <div class="flex flex-col justify-start">
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $product->nama }}</h1>
                
                <!-- Rating -->
                <div class="flex items-center space-x-2 mt-2">
                    <div class="flex text-amber-400 text-xs gap-0.5">
                        ★ ★ ★ ★ ★
                    </div>
                    <span class="text-xs text-gray-400 font-medium">(12 Customer Reviews)</span>
                </div>

                <!-- Harga -->
                <div class="flex items-baseline space-x-3 mt-4">
                    @if($product->harga_diskon && $product->harga_diskon > 0)
                        <span class="text-2xl font-extrabold text-[#FF6600]">Rp {{ number_format($product->harga_diskon, 0, ',', '.') }}</span>
                        <span class="text-sm font-semibold text-gray-400 line-through">Rp {{ number_format($product->harga, 0, ',', '.') }}</span>
                    @else
                        <span class="text-2xl font-extrabold text-[#FF6600]">Rp {{ number_format($product->harga, 0, ',', '.') }}</span>
                    @endif
                </div>

                <!-- Deskripsi Singkat -->
                <p class="text-xs text-gray-500 leading-relaxed mt-4">
                    {{ $product->deskripsi_pendek ?? $product->deskripsi ?? 'High-quality product crafted with care.' }}
                </p>

                <!-- Container Quantity & Add to Cart (SEJAJAR / INLINE PERSIS FIGMA) -->
                <div class="mt-8 max-w-md">
                    <label class="block text-xs font-bold text-gray-800 mb-2">Quantity:</label>
                    
                    <div class="flex items-center space-x-3">
                        <!-- Input Counter (- 1 +) -->
                        <div class="inline-flex items-center bg-[#F8F9FA] border border-gray-200 rounded-md px-3 py-3 text-xs font-semibold">
                            <button type="button" class="text-gray-500 hover:text-black px-2">-</button>
                            <span class="px-3 text-gray-800 font-bold">1</span>
                            <button type="button" class="text-gray-500 hover:text-black px-2">+</button>
                        </div>

                        <!-- Tombol Add to Cart (Orange, Sejajar) -->
                        <button type="button" class="flex-1 bg-[#FF6600] hover:bg-orange-600 text-white font-bold text-xs py-3.5 px-6 rounded-md transition duration-200 shadow-sm">
                            Add to Cart
                        </button>
                    </div>

                    <!-- Tombol Whatsapp Full Width di Bawahnya -->
                    <a href="https://wa.me/?text=Halo,%20saya%20tertarik%20dengan%20produk%20{{ urlencode($product->nama) }}" target="_blank" class="w-full mt-3 bg-[#52B747] hover:bg-green-600 text-white font-bold text-xs py-3.5 px-6 rounded-md transition duration-200 flex items-center justify-center gap-2 shadow-sm">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                        </svg>
                        Whatsapp <span class="text-xs font-normal">💬</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Section Tabs -->
        <div class="mt-16">
            <div class="border-b border-gray-100 flex space-x-8 text-sm">
                <button class="pb-3 text-[#FF6600] border-b-2 border-[#FF6600] font-bold">Description</button>
                <button class="pb-3 text-gray-400 font-semibold hover:text-gray-600">Specification</button>
                <button class="pb-3 text-gray-400 font-semibold hover:text-gray-600">Reviews (12)</button>
            </div>

            <div class="py-6">
                <p class="text-xs text-gray-500 leading-relaxed max-w-3xl">
                    {{ $product->deskripsi_lengkap ?? $product->deskripsi_pendek ?? $product->deskripsi ?? 'Deskripsi detail produk.' }}
                </p>
            </div>
        </div>

        <!-- Section: Related Products (Judul Sesuai Figma, Algoritma Frekuensi Buka) -->
        <div class="mt-12 pt-6 border-t border-gray-100">
            <h2 class="text-lg font-bold text-gray-900 mb-6">Related Products</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                @forelse($relatedProducts ?? [] as $related)
                    <a href="{{ route('products.show', $related->slug) }}" class="group block">
                        <div class="w-full h-56 bg-[#F4F4F4] rounded-lg mb-3 overflow-hidden">
                            @php
                                $relImage = $related->foto_utama ?? $related->gambar ?? $related->image ?? $related->foto ?? null;
                            @endphp
                            @if($relImage)
                                <img src="{{ asset('storage/' . $relImage) }}" alt="{{ $related->nama }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-xs text-gray-400 font-semibold">NO IMAGE</div>
                            @endif
                        </div>
                        <h3 class="text-xs font-bold text-gray-900 group-hover:text-[#FF6600] transition line-clamp-1">{{ $related->nama }}</h3>
                        <p class="text-xs font-bold text-[#FF6600] mt-1">
                            @if($related->harga_diskon && $related->harga_diskon > 0)
                                Rp {{ number_format($related->harga_diskon, 0, ',', '.') }}
                            @else
                                Rp {{ number_format($related->harga, 0, ',', '.') }}
                            @endif
                        </p>
                    </a>
                @empty
                    <!-- Fallback jika database masih kosong -->
                    <div>
                        <div class="w-full h-56 bg-[#F4F4F4] rounded-lg mb-3"></div>
                        <h3 class="text-xs font-bold text-gray-900">Minimalist Table</h3>
                        <p class="text-xs font-bold text-[#FF6600] mt-1">Rp 250.000</p>
                    </div>
                    <div>
                        <div class="w-full h-56 bg-[#F4F4F4] rounded-lg mb-3"></div>
                        <h3 class="text-xs font-bold text-gray-900">Modern Sofa</h3>
                        <p class="text-xs font-bold text-[#FF6600] mt-1">Rp 450.000</p>
                    </div>
                    <div>
                        <div class="w-full h-56 bg-[#F4F4F4] rounded-lg mb-3"></div>
                        <h3 class="text-xs font-bold text-gray-900">Cabinet Luxe</h3>
                        <p class="text-xs font-bold text-[#FF6600] mt-1">Rp 180.000</p>
                    </div>
                    <div>
                        <div class="w-full h-56 bg-[#F4F4F4] rounded-lg mb-3"></div>
                        <h3 class="text-xs font-bold text-gray-900">Office Desk</h3>
                        <p class="text-xs font-bold text-[#FF6600] mt-1">Rp 320.000</p>
                    </div>
                @endforelse
            </div>
        </div>

    </main>

    @include('partials.frontend.footer')

</body>
</html>