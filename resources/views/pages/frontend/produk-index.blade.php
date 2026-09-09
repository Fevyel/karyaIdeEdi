<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Produk &mdash; {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="min-h-screen bg-[#FEEDD8] font-sans antialiased text-[#2A211B]">
    @include('partials.frontend.navbar')

    {{-- ================= SHOP HERO / BREADCRUMB ================= --}}
    <section class="bg-[#F6F9F6]">
        <div class="mx-auto flex min-h-43.75 max-w-295 items-center justify-center gap-6 px-5 py-12 sm:px-7 lg:px-8">
            <div class="hidden shrink-0 -translate-y-3 grid-cols-2 gap-1 sm:grid">
                <span class="h-1 w-1 rounded-full bg-[#C7B6A3]"></span>
                <span class="h-1 w-1 rounded-full bg-[#C7B6A3]"></span>
                <span class="h-1 w-1 rounded-full bg-[#C7B6A3]"></span>
            </div>
            <div class="flex flex-col items-center text-center">
                <h1 class="font-display text-4xl font-semibold tracking-tight text-[#171717] sm:text-5xl">Produk</h1>
                <div class="mt-3 flex items-center gap-2 text-[11px] text-[#A29587]">
                    <a href="{{ route('home') }}" class="transition-colors hover:text-[#2A211B]">Home</a>
                    <span>/</span>
                    <span class="font-medium text-[#2A211B]">Produk</span>
                </div>
            </div>
            <div class="hidden shrink-0 translate-y-3 grid-cols-2 gap-1 sm:grid">
                <span class="h-1 w-1 rounded-full bg-[#C7B6A3]"></span>
                <span class="h-1 w-1 rounded-full bg-[#C7B6A3]"></span>
                <span class="h-1 w-1 rounded-full bg-[#C7B6A3]"></span>
            </div>
        </div>
    </section>

    {{-- ================= SHOP CONTENT ================= --}}
    <section class="bg-[#FEEDD8]">
        <div class="mx-auto max-w-295 px-5 py-10 sm:px-7 sm:py-12 lg:px-8 lg:py-14">

            <form id="shop-filter-form" action="{{ route('products.index') }}" method="GET">
                <div class="grid grid-cols-1 gap-10 lg:grid-cols-[145px_minmax(0,1fr)] lg:gap-8">

                    {{-- ================= FILTER SIDEBAR ================= --}}
                    <aside class="lg:pt-1">
                        <div class="flex items-center justify-between lg:block">
                            <h2 class="text-sm font-semibold text-[#2A211B]">Filter Options</h2>
                        </div>

                        <div class="mt-8">
                            <p class="text-xs font-semibold text-[#2A211B]">Category</p>

                            <div class="mt-3 space-y-2.5">
                                @foreach ($categories as $category)
                                    <label class="flex cursor-pointer items-center gap-2 text-[11px] text-[#75685B] transition-colors hover:text-[#2A211B]">
                                        <input
                                            type="checkbox"
                                            name="categories[]"
                                            value="{{ $category->slug }}"
                                            @checked(in_array($category->slug, (array) request('categories', []), true) || request('category') === $category->slug)
                                            class="h-3 w-3 rounded-sm border-[#B9A995] text-[#2A211B] accent-[#2A211B] focus:ring-0"
                                        >
                                        <span>{{ $category->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
<button type="submit" class="mt-7 inline-flex w-full items-center justify-center rounded-md bg-[#2A211B] px-3 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-white transition hover:bg-[#403129]">
                            Terapkan Filter
                        </button>
                    </aside>

                    {{-- ================= PRODUCT AREA ================= --}}
                    <div class="min-w-0">

                        {{-- Top controls --}}
                        <div class="flex flex-col gap-5 border-b border-[#E7D9C8] pb-5 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-[10px] text-[#8D7E6F]">
                                Showing
                                <span class="font-medium text-[#2A211B]">{{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }}</span>
                                of
                                <span class="font-medium text-[#2A211B]">{{ $products->total() }}</span>
                                results
                            </p>

                            <label class="flex items-center gap-2 text-[10px] text-[#8D7E6F]">
                                <span>Sort by :</span>
                                <select name="sort" onchange="this.form.submit()" class="cursor-pointer border-0 bg-transparent py-1 pl-1 pr-5 text-[10px] font-medium text-[#2A211B] outline-none focus:ring-0">
                                    <option value="">Default Sorting</option>
                                    <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: Low to High</option>
                                    <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: High to Low</option>
                                    <option value="name_asc" @selected(request('sort') === 'name_asc')>Name: A-Z</option>
                                </select>
                            </label>
                        </div>

                        {{-- Active filters --}}
                        <div class="flex flex-wrap items-center gap-2 py-5">
                            <span class="text-[10px] font-medium text-[#75685B]">Active Filter</span>

                            @foreach ($categories as $category)
                                @if (in_array($category->slug, (array) request('categories', []), true) || request('category') === $category->slug)
                                    <span class="inline-flex items-center gap-2 rounded-sm bg-[#FBE4C5] px-2.5 py-1.5 text-[9px] font-medium text-[#8C6A45]">
                                        {{ $category->name }}
                                    </span>
                                @endif
                            @endforeach


                            @if (request()->filled('search'))
                                <span class="inline-flex items-center gap-2 rounded-sm bg-[#FBE4C5] px-2.5 py-1.5 text-[9px] font-medium text-[#8C6A45]">
                                    "{{ request('search') }}"
                                </span>
                            @endif

                            <a href="{{ route('products.index') }}" class="ml-auto text-[10px] font-semibold text-[#4D433A] underline decoration-[#C7B6A3] underline-offset-4 transition hover:text-admin-accent">
                                Clear All
                            </a>
                        </div>

                        @if ($products->isEmpty())
                            <div class="flex min-h-90 items-center justify-center rounded-xl border border-dashed border-[#DCCDBB] bg-[#FFF5E9]/60 px-6 text-center">
                                <div>
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#FBE4C5] text-[#8C6A45]">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </div>
                                    <p class="mt-4 text-sm font-semibold text-[#2A211B]">Produk tidak ditemukan</p>
                                    <p class="mt-1 text-xs text-[#8D7E6F]">Coba ubah filter atau kata pencarian Anda.</p>
                                    <a href="{{ route('products.index') }}" class="mt-5 inline-flex rounded-md bg-[#2A211B] px-4 py-2 text-xs font-medium text-white">Lihat Semua Produk</a>
                                </div>
                            </div>
                        @else
                            {{-- Product grid: 3 kolom seperti referensi Figma --}}
                            <div class="grid grid-cols-1 gap-x-6 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($products as $product)
                                    @php
                                        $hasDiscount = $product->harga_diskon && (float) $product->harga_diskon > 0 && (float) $product->harga_diskon < (float) $product->harga;
                                        $displayPrice = $hasDiscount ? (float) $product->harga_diskon : (float) $product->harga;
                                        $discountPercent = $hasDiscount
                                            ? (int) round(((float) $product->harga - (float) $product->harga_diskon) / (float) $product->harga * 100)
                                            : 0;

                                        $thumbnailUrl = ($product->thumbnail && \Illuminate\Support\Facades\Storage::disk('public')->exists($product->thumbnail))
                                            ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->thumbnail)
                                            : null;

                                        $averageRating = $product->average_rating !== null ? round((float) $product->average_rating, 1) : null;
                                        $reviewCount = (int) ($product->approved_testimonials_count ?? 0);
                                    @endphp

                                    <article class="group relative min-w-0">
                                        <a href="{{ route('products.show', $product) }}" class="block">
                                            {{-- Kotak foto -- disamakan dengan partials/frontend/product-card.blade.php
                                                 (aspect-square + object-contain + padding p-4/sm:p-5) supaya foto
                                                 produk tampil identik di Beranda, Detail Produk, maupun Katalog ini.
                                                 Sebelumnya pakai aspect-[1.02/1] (nyaris kotak tapi tidak persis 1:1)
                                                 sementara hasil crop thumbnail admin SELALU kotak 800x800 -- beda
                                                 rasio kotak inilah yang bikin foto kelihatan beda tiap pindah halaman. --}}
                                            <div class="relative aspect-square overflow-hidden bg-[#F7FAF7]">
                                                @if ($hasDiscount)
                                                    <span class="absolute left-2.5 top-2.5 z-10 rounded-full bg-[#203D2E] px-2 py-1 text-[9px] font-semibold text-white">
                                                        {{ $discountPercent }}% off
                                                    </span>
                                                @endif

                                                @if ($thumbnailUrl)
                                                    <img
                                                        src="{{ $thumbnailUrl }}"
                                                        alt="{{ $product->nama }}"
                                                        loading="lazy"
                                                        class="h-full w-full rounded-2xl object-contain object-center p-1.5 transition-transform duration-500 ease-out group-hover:scale-[1.035] sm:p-2"
                                                    >
                                                @else
                                                    <div class="flex h-full w-full items-center justify-center text-[#C8BAA9]">
                                                        <i class="fa-solid fa-couch text-5xl"></i>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="pt-3">
                                                <div class="flex items-center justify-between gap-3">
                                                    <p class="text-[9px] text-[#A09384]">{{ $product->category?->name ?? 'Furniture' }}</p>

                                                    @if ($reviewCount > 0)
                                                        <span class="inline-flex items-center gap-1 text-[9px] text-[#C39B52]">
                                                            <i class="fa-solid fa-star text-[8px]"></i>
                                                            {{ number_format($averageRating, 1) }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <h3 class="mt-1 text-sm font-semibold text-[#2A211B] transition-colors group-hover:text-[#8C6A45]">
                                                    {{ $product->nama }}
                                                </h3>

                                                <div class="mt-1.5 flex items-center gap-2">
                                                    <span class="text-sm font-semibold text-[#2A211B]">
                                                        Rp{{ number_format($displayPrice, 0, ',', '.') }}
                                                    </span>
                                                    @if ($hasDiscount)
                                                        <span class="text-[10px] text-[#A09384] line-through">
                                                            Rp{{ number_format((float) $product->harga, 0, ',', '.') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </a>

                                        <button
                                            type="button"
                                            aria-label="Tambahkan {{ $product->nama }} ke favorit"
                                            data-favorite-product
                                            data-product-id="{{ $product->id }}"
                                            data-product-slug="{{ $product->slug }}"
                                            data-product-name="{{ $product->nama }}"
                                            data-product-price="{{ $displayPrice }}"
                                            data-product-image="{{ $thumbnailUrl }}"
                                            data-product-category="{{ $product->category?->name ?? 'Furniture' }}"
                                            data-product-stock="{{ max(0, (int) $product->stok) }}"
                                            class="absolute right-2.5 top-2.5 z-20 flex h-9 w-9 items-center justify-center rounded-full bg-white/95 text-[#6E6257] shadow-sm transition hover:text-admin-accent"
                                        >
                                            <i data-favorite-icon class="fa-regular fa-heart text-xs"></i>
                                        </button>
                                        <button
                                            type="button"
                                            aria-label="Masukkan {{ $product->nama }} ke keranjang"
                                            data-cart-product
                                            data-product-id="{{ $product->id }}"
                                            data-product-slug="{{ $product->slug }}"
                                            data-product-name="{{ $product->nama }}"
                                            data-product-price="{{ $displayPrice }}"
                                            data-product-image="{{ $thumbnailUrl }}"
                                            data-product-category="{{ $product->category?->name ?? 'Furniture' }}"
                                            data-product-stock="{{ max(0, (int) $product->stok) }}"
                                            class="absolute bottom-[4.7rem] right-2.5 z-20 flex h-9 w-9 items-center justify-center rounded-full bg-[#2A211B]/95 text-white shadow-sm transition hover:bg-admin-accent"
                                        >
                                            <i class="fa-solid fa-bag-shopping text-xs"></i>
                                        </button>
                                    </article>
                                @endforeach
                            </div>

                            @if ($products->hasPages())
                                <div class="mt-12 border-t border-[#E7D9C8] pt-6">
                                    {{ $products->onEachSide(1)->links() }}
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </section>

    @include('partials.frontend.footer')

</body>
</html>