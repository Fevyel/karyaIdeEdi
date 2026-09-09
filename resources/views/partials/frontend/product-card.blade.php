{{--
    ==========================================================
    KARTU PRODUK (Frontend) — Partial reusable
    ==========================================================
    Dipakai di semua tempat frontend yang menampilkan produk
    (Beranda "Semua Produk", nanti halaman Katalog & per-kategori),
    supaya thumbnail & rating konsisten di satu tempat saja.

    Variabel yang WAJIB dikirim saat @include:
        'product' => instance App\Models\Product

    Agar rating akurat & tidak N+1 query, query produk di halaman
    pemanggil SEBAIKNYA sudah memakai withCount/withAvg seperti di
    partials/frontend/products.blade.php:
        ->withCount(['testimonials as approved_testimonials_count' => fn ($q) => $q->approved()])
        ->withAvg(['testimonials as average_rating' => fn ($q) => $q->approved()], 'rating')
    Kalau kolom itu tidak ada (mis. dipanggil dari tempat lain yang
    belum sempat pakai withCount/withAvg), partial ini tetap aman:
    otomatis fallback query on-the-fly per produk.

    Pemakaian:
        @include('partials.frontend.product-card', ['product' => $product])
    ==========================================================
--}}

@php
    // Warna teks kartu (judul, deskripsi/rating, harga) bisa dikirim dari
    // halaman pemanggil lewat $cardTextColors -- dipakai di Beranda > Semua
    // Produk (partials/frontend/products.blade.php) supaya ikut kontras
    // warna latar section "Produk Unggulan" yang diatur admin di Edit Web.
    // Kalau tidak dikirim (dipakai di tempat lain nanti), fallback ke warna
    // asli kartu ini -- sama persis seperti sebelum bisa diedit.
    $cardTextColors = $cardTextColors ?? [];
    $cardTitleColor = $cardTextColors['title'] ?? '#4B3A26';
    $cardBodyColor = $cardTextColors['body'] ?? '#756A5D';
    $cardPriceColor = $cardTextColors['price'] ?? '#1A1A1A';

    $hasDiscount = $product->harga_diskon && (float) $product->harga_diskon > 0;

    $thumbnailUrl = ($product->thumbnail && \Illuminate\Support\Facades\Storage::disk('public')->exists($product->thumbnail))
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->thumbnail)
        : null;

    // Rating MURNI dari database (rata-rata testimonial yang sudah di-approve admin).
    // Tidak pernah hardcode. Kalau produk belum punya testimonial approved,
    // average_rating akan null & reviewCount akan 0 -> tampil "Belum ada ulasan".
    $averageRating = $product->average_rating ?? $product->testimonials()->approved()->avg('rating');
    $reviewCount = $product->approved_testimonials_count ?? $product->testimonials()->approved()->count();
    $averageRating = $reviewCount > 0 ? round((float) $averageRating, 1) : null;
@endphp

@php
    $cardDisplayPrice = ($product->harga_diskon && (float) $product->harga_diskon > 0)
        ? (float) $product->harga_diskon
        : (float) $product->harga;
@endphp

<article class="group relative block">
    {{-- Kotak foto — mengikuti referensi terbaru: foto produk studio (bg putih)
         ditampilkan utuh (object-contain) di atas card berwarna admin-cream,
         dengan padding rapat (bukan p-8 lama) supaya tidak ada ruang kosong berlebih. --}}
    <div class="relative aspect-square w-full overflow-hidden rounded-3xl bg-admin-cream p-1.5 shadow-sm transition-all duration-300 ease-out group-hover:-translate-y-1.5 group-hover:shadow-xl sm:p-2">
        @if ($thumbnailUrl)
            <img
                src="{{ $thumbnailUrl }}"
                alt="{{ $product->nama }}"
                class="h-full w-full rounded-2xl object-contain object-center transition-transform duration-500 ease-out group-hover:scale-105"
            >
        @else
            <div class="flex h-full w-full items-center justify-center text-admin-ink-soft/40">
                <i class="fa-solid fa-couch text-4xl"></i>
            </div>
        @endif
    </div>

    <div class="absolute right-3 top-3 z-10 flex flex-col gap-2">
        <button
            type="button"
            aria-label="Tambahkan {{ $product->nama }} ke favorit"
            data-favorite-product
            data-product-id="{{ $product->id }}"
            data-product-slug="{{ $product->slug }}"
            data-product-name="{{ $product->nama }}"
            data-product-price="{{ $cardDisplayPrice }}"
            data-product-image="{{ $thumbnailUrl }}"
            data-product-category="{{ $product->category?->name ?? 'Furniture' }}"
            data-product-stock="{{ max(0, (int) $product->stok) }}"
            onclick="event.preventDefault(); event.stopPropagation();"
            class="flex h-9 w-9 items-center justify-center rounded-full bg-white/95 text-[#6E6257] shadow-sm transition hover:text-admin-accent"
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
            data-product-price="{{ $cardDisplayPrice }}"
            data-product-image="{{ $thumbnailUrl }}"
            data-product-category="{{ $product->category?->name ?? 'Furniture' }}"
            data-product-stock="{{ max(0, (int) $product->stok) }}"
            onclick="event.preventDefault(); event.stopPropagation();"
            class="flex h-9 w-9 items-center justify-center rounded-full bg-[#2A211B] text-white shadow-sm transition hover:bg-admin-accent disabled:cursor-not-allowed disabled:opacity-50"
        >
            <i class="fa-solid fa-bag-shopping text-xs"></i>
        </button>
    </div>

    <a href="{{ route('products.show', $product) }}" class="block">
        {{-- Info produk --}}
    <div class="mt-4">
        <p class="text-base font-semibold" style="color: {{ $cardTitleColor }};">{{ $product->nama }}</p>
        <p class="mt-0.5 text-sm" style="color: {{ $cardBodyColor }};">{{ $product->deskripsi_pendek }}</p>

        {{-- Rating — hanya tampil kalau sudah ada testimonial approved --}}
        <div class="mt-2 flex items-center gap-1.5">
            @if ($reviewCount > 0)
                <div class="flex items-center gap-0.5 text-admin-gold">
                    @for ($i = 1; $i <= 5; $i++)
                        @if ($averageRating >= $i)
                            <i class="fa-solid fa-star text-xs"></i>
                        @elseif ($averageRating >= $i - 0.5)
                            <i class="fa-solid fa-star-half-stroke text-xs"></i>
                        @else
                            <i class="fa-regular fa-star text-xs"></i>
                        @endif
                    @endfor
                </div>
                <span class="text-xs" style="color: {{ $cardBodyColor }};">
                    {{ number_format($averageRating, 1) }} ({{ $reviewCount }})
                </span>
            @else
                <div class="flex items-center gap-0.5 text-admin-ink-soft/25">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="fa-regular fa-star text-xs"></i>
                    @endfor
                </div>
                <span class="text-xs" style="color: {{ $cardBodyColor }}; opacity: 0.7;">Belum ada ulasan</span>
            @endif
        </div>

        {{-- Harga --}}
        <div class="mt-2 flex items-center gap-2">
            @if ($hasDiscount)
                <span class="text-base font-semibold text-red-600">
                    Rp{{ number_format((float) $product->harga_diskon, 0, ',', '.') }}
                </span>
                <span class="text-sm line-through" style="color: {{ $cardBodyColor }}; opacity: 0.7;">
                    Rp{{ number_format((float) $product->harga, 0, ',', '.') }}
                </span>
            @else
                <span class="text-base font-semibold" style="color: {{ $cardPriceColor }};">
                    Rp{{ number_format((float) $product->harga, 0, ',', '.') }}
                </span>
            @endif
        </div>
    </div>
</a>
</article>