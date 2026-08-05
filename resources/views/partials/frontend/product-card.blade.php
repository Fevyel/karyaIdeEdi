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

<div class="group">
    {{-- Kotak foto — mengikuti referensi terbaru: foto produk studio (bg putih)
         ditampilkan utuh (object-contain) di atas card berwarna admin-cream,
         dengan padding rapat (bukan p-8 lama) supaya tidak ada ruang kosong berlebih. --}}
    <div class="relative aspect-square w-full overflow-hidden rounded-3xl bg-admin-cream p-4 shadow-sm transition-all duration-300 ease-out group-hover:-translate-y-1.5 group-hover:shadow-xl sm:p-5">
        @if ($thumbnailUrl)
            <img
                src="{{ $thumbnailUrl }}"
                alt="{{ $product->nama }}"
                class="h-full w-full object-contain object-center transition-transform duration-500 ease-out group-hover:scale-105"
            >
        @else
            <div class="flex h-full w-full items-center justify-center text-admin-ink-soft/40">
                <i class="fa-solid fa-couch text-4xl"></i>
            </div>
        @endif
    </div>

    {{-- Info produk --}}
    <div class="mt-4">
        <p class="text-base font-semibold text-[#4B3A26]">{{ $product->nama }}</p>
        <p class="mt-0.5 text-sm text-admin-ink-soft">{{ $product->deskripsi_pendek }}</p>

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
                <span class="text-xs text-admin-ink-soft">
                    {{ number_format($averageRating, 1) }} ({{ $reviewCount }})
                </span>
            @else
                <div class="flex items-center gap-0.5 text-admin-ink-soft/25">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="fa-regular fa-star text-xs"></i>
                    @endfor
                </div>
                <span class="text-xs text-admin-ink-soft/70">Belum ada ulasan</span>
            @endif
        </div>

        {{-- Harga --}}
        <div class="mt-2 flex items-center gap-2">
            @if ($hasDiscount)
                <span class="text-base font-semibold text-red-600">
                    Rp{{ number_format((float) $product->harga_diskon, 0, ',', '.') }}
                </span>
                <span class="text-sm text-admin-ink-soft/70 line-through">
                    Rp{{ number_format((float) $product->harga, 0, ',', '.') }}
                </span>
            @else
                <span class="text-base font-semibold text-[#1A1A1A]">
                    Rp{{ number_format((float) $product->harga, 0, ',', '.') }}
                </span>
            @endif
        </div>
    </div>
</div>
