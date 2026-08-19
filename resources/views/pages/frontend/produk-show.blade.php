<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $product->nama }} &mdash; {{ \App\Models\Setting::current()->site_name }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    </head>

    @php
        $hasDiscount = $product->harga_diskon
            && (float) $product->harga_diskon > 0
            && (float) $product->harga_diskon < (float) $product->harga;

        $displayPrice = $hasDiscount ? (float) $product->harga_diskon : (float) $product->harga;

        $thumbnailUrl = ($product->thumbnail && \Illuminate\Support\Facades\Storage::disk('public')->exists($product->thumbnail))
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->thumbnail)
            : null;

        // Gallery: foto utama (thumbnail existing) diikuti foto tambahan
        // (product_images, sudah terurut sort_order lewat relasi images()).
        // Maksimal 4 foto total, sesuai 4 slot thumbnail yang sudah ada di desain.
        $galleryImages = collect();

        if ($thumbnailUrl) {
            $galleryImages->push($thumbnailUrl);
        }

        foreach ($product->images as $additionalImage) {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($additionalImage->image_path)) {
                $galleryImages->push(\Illuminate\Support\Facades\Storage::disk('public')->url($additionalImage->image_path));
            }
        }

        $galleryImages = $galleryImages->take(4)->values();

        $approvedTestimonials = $product->testimonials()
            ->approved()
            ->active()
            ->latest()
            ->get();

        $reviewCount = $approvedTestimonials->count();
        $averageRating = $reviewCount > 0 ? (float) $approvedTestimonials->avg('rating') : 0;

        $siteSetting = \App\Models\Setting::current();
        $waNumber = $siteSetting->whatsapp
            ? preg_replace('/\D/', '', $siteSetting->whatsapp)
            : null;

        $waText = urlencode('Halo, saya tertarik dengan produk "'.$product->nama.'".');
    @endphp

    <body class="min-h-screen bg-white font-sans antialiased text-[#2A211B]">
        @include('partials.frontend.navbar')

        <main>
            {{-- =====================================================
                 BREADCRUMB — persis: Home / Shop / Kategori / Nama Produk
            ====================================================== --}}
            <section class="bg-white">
                <div class="mx-auto w-full max-w-[1600px] px-5 pt-8 sm:px-8 sm:pt-10 lg:px-12 xl:px-16">
                    <nav aria-label="Breadcrumb" class="text-[10px] text-[#9A8E82] sm:text-[11px]">
                        <a href="{{ route('home') }}" class="transition hover:text-admin-accent">Home</a>
                        <span class="mx-1.5">/</span>
                        <a href="{{ route('products.index') }}" class="transition hover:text-admin-accent">Shop</a>
                        @if ($product->category)
                            <span class="mx-1.5">/</span>
                            <span>{{ $product->category->name }}</span>
                        @endif
                        <span class="mx-1.5">/</span>
                        <span class="font-medium text-[#5C5147]">{{ $product->nama }}</span>
                    </nav>
                </div>
            </section>

            {{-- =====================================================
                 PRODUCT DETAIL: GALLERY + INFO
            ====================================================== --}}
            <section class="bg-white">
                <div class="mx-auto w-full max-w-[1600px] px-5 pb-12 pt-6 sm:px-8 sm:pb-14 sm:pt-8 lg:px-12 lg:pb-16 xl:px-16">
                    <div class="grid grid-cols-1 items-start gap-9 lg:grid-cols-[minmax(0,1.05fr)_minmax(360px,0.95fr)] lg:gap-12 xl:gap-16">

                        {{-- ================= GALLERY: gambar utama + 4 thumbnail ================= --}}
                        <div data-product-gallery data-gallery-images="{{ $galleryImages->toJson() }}" class="min-w-0">
                            <div
                                data-main-image-wrapper
                                class="aspect-square w-full overflow-hidden rounded-[22px] bg-[#F7F8F6] {{ $thumbnailUrl ? 'cursor-zoom-in' : '' }}"
                            >
                                @if ($thumbnailUrl)
                                    <img
                                        data-main-image
                                        src="{{ $thumbnailUrl }}"
                                        alt="{{ $product->nama }}"
                                        class="h-full w-full object-contain p-7 sm:p-10 transition-opacity duration-300"
                                    >
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-[10px] font-medium uppercase tracking-wide text-[#C8BAA9]">
                                        Main Product Image
                                    </div>
                                @endif
                            </div>

                            <div class="mt-3 grid grid-cols-4 gap-2.5 sm:gap-3">
                                @for ($slot = 0; $slot < 4; $slot++)
                                    @php $slotImageUrl = $galleryImages->get($slot); @endphp

                                    @if ($slot === 0)
                                        @if ($slotImageUrl)
                                            <button
                                                type="button"
                                                class="group aspect-square overflow-hidden rounded-md border border-[#F28A22] bg-[#F7F8F6] p-1.5"
                                                aria-label="Tampilkan foto utama"
                                                data-gallery-image="{{ $slotImageUrl }}"
                                                data-gallery-index="0"
                                            >
                                                <img src="{{ $slotImageUrl }}" alt="{{ $product->nama }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                                            </button>
                                        @else
                                            <div class="aspect-square rounded-md border border-[#F28A22] bg-[#F7F8F6]"></div>
                                        @endif
                                    @else
                                        {{-- Slot foto tambahan: tombol kalau produk punya foto di slot ini,
                                             kalau tidak tetap slot kosong sesuai layout (bukan gambar palsu). --}}
                                        @if ($slotImageUrl)
                                            <button
                                                type="button"
                                                class="group aspect-square overflow-hidden rounded-md border border-transparent bg-[#F7F8F6] p-1.5"
                                                aria-label="Tampilkan foto {{ $slot + 1 }}"
                                                data-gallery-image="{{ $slotImageUrl }}"
                                                data-gallery-index="{{ $slot }}"
                                            >
                                                <img src="{{ $slotImageUrl }}" alt="{{ $product->nama }} - foto {{ $slot + 1 }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                                            </button>
                                        @else
                                            <div class="aspect-square rounded-md bg-[#F7F8F6]"></div>
                                        @endif
                                    @endif
                                @endfor
                            </div>
                        </div>

                        {{-- ================= PRODUCT INFO ================= --}}
                        <div class="pt-0 lg:pt-1">
                            <h1 class="font-display text-2xl font-semibold leading-tight text-[#2A211B] sm:text-[28px]">
                                {{ $product->nama }}
                            </h1>

                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1">
                                <div class="flex items-center gap-0.5 text-[#F0A321]" aria-label="Rating {{ number_format($averageRating, 1) }} dari 5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="fa-solid fa-star text-[10px] {{ $reviewCount === 0 || $averageRating < $i ? 'text-[#D9D4CD]' : '' }}"></i>
                                    @endfor
                                </div>
                                <span class="text-[10px] text-[#A1988E]">
                                    ({{ $reviewCount }} Customer Reviews)
                                </span>
                            </div>

                            <div class="mt-5 flex flex-wrap items-baseline gap-2.5">
                                <span class="text-[22px] font-semibold tracking-tight text-[#F28A22] sm:text-[24px]">
                                    Rp{{ number_format($displayPrice, 0, ',', '.') }}
                                </span>
                                @if ($hasDiscount)
                                    <span class="text-[12px] text-[#AAA098] line-through">
                                        Rp{{ number_format((float) $product->harga, 0, ',', '.') }}
                                    </span>
                                @endif
                            </div>

                            @if ($product->deskripsi_pendek)
                                <p class="mt-5 max-w-xl text-[11px] leading-[1.75] text-[#74695F] sm:text-xs">
                                    {{ $product->deskripsi_pendek }}
                                </p>
                            @endif

                            <div class="mt-7">
                                <label class="mb-2 block text-[10px] font-semibold text-[#2A211B]">Quantity:</label>
                                <div class="flex items-center gap-3">
                                    <div class="inline-flex h-10 shrink-0 items-center overflow-hidden rounded-md border border-[#E3DED7] bg-white">
                                        <button type="button" data-qty-minus class="flex h-full w-8 items-center justify-center text-[#7A6E63] transition hover:bg-[#F8F4EF]" aria-label="Kurangi jumlah">
                                            &minus;
                                        </button>
                                        <span data-qty-value class="flex w-7 items-center justify-center text-xs font-medium text-[#2A211B]">1</span>
                                    </div>

                                    {{-- Backend keranjang belum ada di repo — tombol disiapkan sesuai
                                         desain, tanpa mengarang logic checkout. --}}
                                    <button
                                        type="button"
                                        class="h-10 flex-1 rounded-md bg-[#F28A22] px-5 text-[11px] font-semibold text-white transition hover:bg-[#D97612]"
                                        onclick="window.dispatchEvent(new CustomEvent('karya-ide-edi-cart-unavailable'))"
                                    >
                                        Add to Cart
                                    </button>
                                </div>

                                <a
                                    href="{{ $waNumber ? 'https://wa.me/'.$waNumber.'?text='.$waText : '#' }}"
                                    @if ($waNumber) target="_blank" rel="noopener" @endif
                                    class="mt-3 flex h-10 w-full items-center justify-center gap-2 rounded-md bg-[#58B13F] px-5 text-[11px] font-semibold text-white transition hover:bg-[#489C32] {{ $waNumber ? '' : 'cursor-not-allowed opacity-90' }}"
                                    @unless ($waNumber) title="Nomor WhatsApp belum diisi di Admin > Pengaturan" onclick="event.preventDefault()" @endunless
                                >
                                    Whatsapp
                                    <i class="fa-brands fa-whatsapp"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- =====================================================
                 DESCRIPTION / SPECIFICATION / REVIEWS — tab, sesuai
                 referensi Figma. Isi tiap tab murni dari data produk
                 yang memang ada di database (tidak mengarang data):
                 - Description  : deskripsi_lengkap / deskripsi_pendek
                 - Specification: berat, dimensi (p/l/t), kategori, stok
                 - Reviews      : testimonial approved + aktif milik produk ini
            ====================================================== --}}
            <section class="bg-white" x-data="{ tab: 'description' }">
                <div class="mx-auto w-full max-w-[1600px] px-5 pb-14 sm:px-8 lg:px-12 xl:px-16">
                    <div class="border-t border-[#EEEAE5]"></div>

                    <div class="pt-5">
                        <div class="flex items-center gap-8 border-b border-[#F0ECE7]">
                            <button
                                type="button"
                                @click="tab = 'description'"
                                :class="tab === 'description' ? 'border-[#F28A22] text-[#F28A22]' : 'border-transparent text-[#9A8E82] hover:text-[#5C5147]'"
                                class="border-b pb-3 text-[10px] font-medium transition-colors sm:text-[11px]"
                            >
                                Description
                            </button>
                            <button
                                type="button"
                                @click="tab = 'specification'"
                                :class="tab === 'specification' ? 'border-[#F28A22] text-[#F28A22]' : 'border-transparent text-[#9A8E82] hover:text-[#5C5147]'"
                                class="border-b pb-3 text-[10px] font-medium transition-colors sm:text-[11px]"
                            >
                                Specification
                            </button>
                            <button
                                type="button"
                                @click="tab = 'reviews'"
                                :class="tab === 'reviews' ? 'border-[#F28A22] text-[#F28A22]' : 'border-transparent text-[#9A8E82] hover:text-[#5C5147]'"
                                class="border-b pb-3 text-[10px] font-medium transition-colors sm:text-[11px]"
                            >
                                Reviews ({{ $reviewCount }})
                            </button>
                        </div>

                        {{-- Tab: Description --}}
                        <div x-show="tab === 'description'" class="pt-7 max-w-3xl text-[10px] leading-[1.8] text-[#7A7067] sm:text-[11px]">
                            @if ($product->deskripsi_lengkap)
                                {!! nl2br(e($product->deskripsi_lengkap)) !!}
                            @elseif ($product->deskripsi_pendek)
                                {!! nl2br(e($product->deskripsi_pendek)) !!}
                            @else
                                <p>Belum ada deskripsi lengkap untuk produk ini.</p>
                            @endif
                        </div>

                        {{-- Tab: Specification — hanya menampilkan field yang memang terisi --}}
                        <div x-show="tab === 'specification'" x-cloak class="pt-7 max-w-3xl text-[10px] leading-[1.8] text-[#7A7067] sm:text-[11px]">
                            @php
                                $specs = collect([
                                    'Kategori' => $product->category?->name,
                                    'Berat' => $product->berat ? number_format((float) $product->berat, 2, ',', '.').' kg' : null,
                                    'Dimensi (P x L x T)' => ($product->panjang || $product->lebar || $product->tinggi)
                                        ? number_format((float) $product->panjang, 0, ',', '.').' x '.number_format((float) $product->lebar, 0, ',', '.').' x '.number_format((float) $product->tinggi, 0, ',', '.').' cm'
                                        : null,
                                    'Stok' => $product->stok !== null ? $product->stok.' unit' : null,
                                ])->filter();
                            @endphp

                            @if ($specs->isNotEmpty())
                                <dl class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
                                    @foreach ($specs as $label => $value)
                                        <div class="flex items-center justify-between border-b border-[#F0ECE7] pb-2 sm:justify-start sm:gap-4">
                                            <dt class="font-medium text-[#5C5147]">{{ $label }}</dt>
                                            <dd>{{ $value }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            @else
                                <p>Spesifikasi belum tersedia untuk produk ini.</p>
                            @endif
                        </div>

                        {{-- Tab: Reviews --}}
                        <div x-show="tab === 'reviews'" x-cloak class="pt-7 max-w-3xl">
                            @forelse ($approvedTestimonials as $testimonial)
                                <div class="mb-5 border-b border-[#F0ECE7] pb-5 last:mb-0 last:border-b-0 last:pb-0">
                                    <div class="flex items-center gap-0.5 text-[#F0A321]">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="fa-solid fa-star text-[10px] {{ $testimonial->rating < $i ? 'text-[#D9D4CD]' : '' }}"></i>
                                        @endfor
                                    </div>
                                    <p class="mt-2 text-[10px] font-semibold text-[#2A211B] sm:text-[11px]">
                                        {{ $testimonial->customer_name }}
                                    </p>
                                    @if ($testimonial->comment)
                                        <p class="mt-1 text-[10px] leading-[1.8] text-[#7A7067] sm:text-[11px]">
                                            {{ $testimonial->comment }}
                                        </p>
                                    @endif
                                </div>
                            @empty
                                <p class="text-[10px] leading-[1.8] text-[#7A7067] sm:text-[11px]">
                                    Belum ada ulasan untuk produk ini.
                                </p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            {{-- =====================================================
                 RELATED PRODUCTS — 4 produk paling sering dilihat
                 (atau se-kategori sebagai fallback), dikirim dari
                 route('products.show') sebagai $relatedProducts.
                 Kartu dibuat MENGIKUTI PERSIS referensi Figma: foto,
                 nama, harga — TANPA rating/deskripsi (beda dengan
                 partial product-card yang dipakai di Beranda/Katalog,
                 sengaja tidak dipakai di sini supaya sama seperti
                 mockup).
            ====================================================== --}}
            @if ($relatedProducts->isNotEmpty())
                <section class="bg-white">
                    <div class="mx-auto w-full max-w-[1600px] px-5 pb-16 sm:px-8 lg:px-12 xl:px-16">
                        <div class="border-t border-[#EEEAE5] pt-10">
                            <h2 class="font-display text-lg font-semibold text-[#2A211B] sm:text-xl">
                                Related Products
                            </h2>

                            <div class="mt-6 grid grid-cols-2 gap-5 sm:gap-6 lg:grid-cols-4">
                                @foreach ($relatedProducts as $relatedProduct)
                                    @php
                                        $relatedThumbnailUrl = ($relatedProduct->thumbnail && \Illuminate\Support\Facades\Storage::disk('public')->exists($relatedProduct->thumbnail))
                                            ? \Illuminate\Support\Facades\Storage::disk('public')->url($relatedProduct->thumbnail)
                                            : null;

                                        $relatedHasDiscount = $relatedProduct->harga_diskon && (float) $relatedProduct->harga_diskon > 0;
                                        $relatedDisplayPrice = $relatedHasDiscount ? (float) $relatedProduct->harga_diskon : (float) $relatedProduct->harga;
                                    @endphp

                                    <a href="{{ route('products.show', $relatedProduct) }}" class="group block">
                                        <div class="aspect-square w-full overflow-hidden rounded-md bg-[#F7F8F6]">
                                            @if ($relatedThumbnailUrl)
                                                <img
                                                    src="{{ $relatedThumbnailUrl }}"
                                                    alt="{{ $relatedProduct->nama }}"
                                                    class="h-full w-full object-contain p-6 transition duration-300 group-hover:scale-105"
                                                >
                                            @endif
                                        </div>

                                        <p class="mt-3 text-[11px] font-semibold text-[#2A211B] sm:text-xs">
                                            {{ $relatedProduct->nama }}
                                        </p>
                                        <p class="mt-1 text-[11px] font-semibold text-[#F28A22] sm:text-xs">
                                            Rp{{ number_format($relatedDisplayPrice, 0, ',', '.') }}
                                        </p>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>
            @endif
        </main>

        @include('partials.frontend.footer')

        {{-- =====================================================
             LIGHTBOX FOTO PRODUK — overlay fixed di luar alur layout,
             tidak mempengaruhi ukuran/posisi section manapun di atas.
             Ditutup secara default (hidden), dibuka lewat JS saat foto
             utama diklik.
        ====================================================== --}}
        <div
            id="productLightbox"
            class="fixed inset-0 z-999 hidden items-center justify-center bg-black/90"
            role="dialog"
            aria-modal="true"
            aria-label="Lihat foto produk {{ $product->nama }}"
        >
            <button type="button" id="lightboxClose" class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20" aria-label="Tutup">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <button type="button" id="lightboxPrev" class="absolute left-3 top-1/2 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 sm:left-6" aria-label="Foto sebelumnya">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <button type="button" id="lightboxNext" class="absolute right-3 top-1/2 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 sm:right-6" aria-label="Foto berikutnya">
                <i class="fa-solid fa-chevron-right"></i>
            </button>

            <div id="lightboxViewport" class="relative h-[78vh] w-[92vw] max-w-3xl cursor-grab touch-none select-none overflow-hidden sm:h-[85vh]">
                <img
                    id="lightboxImage"
                    src=""
                    alt="{{ $product->nama }}"
                    draggable="false"
                    class="pointer-events-none absolute left-1/2 top-1/2 max-h-full max-w-full select-none object-contain"
                >
            </div>

            <div class="absolute bottom-5 left-1/2 flex -translate-x-1/2 items-center gap-2 rounded-full bg-white/10 px-3 py-2 backdrop-blur-sm">
                <button type="button" id="lightboxZoomOut" class="flex h-8 w-8 items-center justify-center rounded-full text-white transition hover:bg-white/20" aria-label="Perkecil">
                    <i class="fa-solid fa-magnifying-glass-minus text-xs"></i>
                </button>
                <span id="lightboxZoomLabel" class="w-9 text-center text-[11px] font-medium text-white">1x</span>
                <button type="button" id="lightboxZoomIn" class="flex h-8 w-8 items-center justify-center rounded-full text-white transition hover:bg-white/20" aria-label="Perbesar">
                    <i class="fa-solid fa-magnifying-glass-plus text-xs"></i>
                </button>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // =====================================================
                // PRODUCT GALLERY — thumbnail, slideshow otomatis, dan
                // lightbox (zoom/pan). Semua vanilla JS, tanpa library
                // eksternal, supaya ringan dan tidak menyentuh desain.
                // =====================================================
                const gallery = document.querySelector('[data-product-gallery]');

                if (gallery) {
                    const mainImageWrapper = document.querySelector('[data-main-image-wrapper]');
                    const mainImage = gallery.querySelector('[data-main-image]');
                    const thumbButtons = Array.from(gallery.querySelectorAll('[data-gallery-image]'));

                    let galleryImages = [];
                    try {
                        galleryImages = JSON.parse(gallery.dataset.galleryImages || '[]');
                    } catch (e) {
                        galleryImages = [];
                    }

                    let currentIndex = 0;
                    const SLIDESHOW_INTERVAL_MS = 4500; // sekitar 4-5 detik per foto
                    const PAUSE_DURATION_MS = 60000; // pause 1 menit setelah interaksi manual

                    // Hanya SATU timer slideshow & SATU timer resume yang boleh aktif
                    // di saat bersamaan — selalu di-clear sebelum membuat yang baru.
                    let slideshowTimer = null;
                    let resumeTimer = null;
                    let lightboxOpen = false;

                    function setActiveImage(index) {
                        if (!galleryImages[index]) return;
                        currentIndex = index;

                        if (mainImage) {
                            mainImage.style.opacity = '0';
                            window.setTimeout(() => {
                                mainImage.src = galleryImages[index];
                                mainImage.style.opacity = '1';
                            }, 120);
                        }

                        thumbButtons.forEach((item) => {
                            const isActive = Number(item.dataset.galleryIndex) === index;
                            item.classList.toggle('border-[#F28A22]', isActive);
                            item.classList.toggle('border-transparent', !isActive);
                        });

                        if (lightboxOpen) {
                            updateLightboxImage();
                        }
                    }

                    function stopSlideshow() {
                        if (slideshowTimer) {
                            clearInterval(slideshowTimer);
                            slideshowTimer = null;
                        }
                    }

                    function startSlideshow() {
                        stopSlideshow();
                        if (galleryImages.length <= 1 || lightboxOpen) return;

                        slideshowTimer = setInterval(() => {
                            setActiveImage((currentIndex + 1) % galleryImages.length);
                        }, SLIDESHOW_INTERVAL_MS);
                    }

                    // Dipanggil setiap ada interaksi manual (klik thumbnail, buka/tutup
                    // lightbox): hentikan slideshow, lalu jadwalkan ulang jalan lagi
                    // setelah 1 menit. Klik lagi selama masa pause akan reset timer ini.
                    function pauseThenResumeSlideshow() {
                        stopSlideshow();

                        if (resumeTimer) {
                            clearTimeout(resumeTimer);
                            resumeTimer = null;
                        }

                        if (lightboxOpen) return;

                        resumeTimer = setTimeout(() => {
                            resumeTimer = null;
                            startSlideshow();
                        }, PAUSE_DURATION_MS);
                    }

                    thumbButtons.forEach((button) => {
                        button.addEventListener('click', () => {
                            const index = Number(button.dataset.galleryIndex);
                            setActiveImage(index);
                            pauseThenResumeSlideshow();
                        });
                    });

                    // ================= LIGHTBOX =================
                    const lightbox = document.getElementById('productLightbox');
                    const lightboxImage = document.getElementById('lightboxImage');
                    const lightboxViewport = document.getElementById('lightboxViewport');
                    const lightboxClose = document.getElementById('lightboxClose');
                    const lightboxPrev = document.getElementById('lightboxPrev');
                    const lightboxNext = document.getElementById('lightboxNext');
                    const lightboxZoomIn = document.getElementById('lightboxZoomIn');
                    const lightboxZoomOut = document.getElementById('lightboxZoomOut');
                    const lightboxZoomLabel = document.getElementById('lightboxZoomLabel');

                    const ZOOM_LEVELS = [1, 1.25, 1.5, 2, 2.5, 3];
                    let zoomIndex = 0;
                    let panX = 0;
                    let panY = 0;
                    let isDragging = false;
                    let dragStartX = 0;
                    let dragStartY = 0;
                    let dragStartPanX = 0;
                    let dragStartPanY = 0;

                    function currentZoom() {
                        return ZOOM_LEVELS[zoomIndex];
                    }

                    function clampPan() {
                        if (!lightboxViewport) return;
                        const scale = currentZoom();
                        const maxOffsetX = (lightboxViewport.clientWidth * (scale - 1)) / 2;
                        const maxOffsetY = (lightboxViewport.clientHeight * (scale - 1)) / 2;
                        panX = Math.min(maxOffsetX, Math.max(-maxOffsetX, panX));
                        panY = Math.min(maxOffsetY, Math.max(-maxOffsetY, panY));
                    }

                    function applyTransform() {
                        if (!lightboxImage) return;
                        const scale = currentZoom();
                        lightboxImage.style.transform = `translate(-50%, -50%) translate(${panX}px, ${panY}px) scale(${scale})`;
                        if (lightboxZoomLabel) {
                            lightboxZoomLabel.textContent = (Math.round(scale * 100) / 100) + 'x';
                        }
                        if (lightboxViewport) {
                            lightboxViewport.style.cursor = scale > 1 ? 'grab' : 'default';
                        }
                    }

                    function resetZoom() {
                        zoomIndex = 0;
                        panX = 0;
                        panY = 0;
                        applyTransform();
                    }

                    function setZoomIndex(index) {
                        zoomIndex = Math.min(ZOOM_LEVELS.length - 1, Math.max(0, index));
                        if (currentZoom() === 1) {
                            panX = 0;
                            panY = 0;
                        } else {
                            clampPan();
                        }
                        applyTransform();
                    }

                    function updateLightboxImage() {
                        if (!lightboxImage || !galleryImages[currentIndex]) return;
                        lightboxImage.src = galleryImages[currentIndex];
                        resetZoom();

                        const showNav = galleryImages.length > 1;
                        if (lightboxPrev) lightboxPrev.classList.toggle('hidden', !showNav);
                        if (lightboxNext) lightboxNext.classList.toggle('hidden', !showNav);
                    }

                    function openLightbox(index) {
                        if (!lightbox || !galleryImages[index]) return;

                        lightboxOpen = true;
                        stopSlideshow();
                        if (resumeTimer) {
                            clearTimeout(resumeTimer);
                            resumeTimer = null;
                        }

                        setActiveImage(index);
                        updateLightboxImage();

                        lightbox.classList.remove('hidden');
                        lightbox.classList.add('flex');
                        document.body.style.overflow = 'hidden';
                    }

                    function closeLightbox() {
                        if (!lightbox) return;

                        lightboxOpen = false;
                        lightbox.classList.add('hidden');
                        lightbox.classList.remove('flex');
                        document.body.style.overflow = '';

                        // Jangan langsung jalankan slideshow — tetap ikuti aturan pause 1 menit.
                        pauseThenResumeSlideshow();
                    }

                    function showRelativeImage(step) {
                        if (galleryImages.length <= 1) return;
                        const nextIndex = (currentIndex + step + galleryImages.length) % galleryImages.length;
                        setActiveImage(nextIndex);
                        updateLightboxImage();
                    }

                    if (mainImageWrapper && galleryImages.length > 0) {
                        mainImageWrapper.addEventListener('click', () => openLightbox(currentIndex));
                    }

                    if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
                    if (lightboxPrev) lightboxPrev.addEventListener('click', () => showRelativeImage(-1));
                    if (lightboxNext) lightboxNext.addEventListener('click', () => showRelativeImage(1));
                    if (lightboxZoomIn) lightboxZoomIn.addEventListener('click', () => setZoomIndex(zoomIndex + 1));
                    if (lightboxZoomOut) lightboxZoomOut.addEventListener('click', () => setZoomIndex(zoomIndex - 1));

                    // Klik area gelap di luar foto (bukan di viewport) menutup lightbox.
                    if (lightbox) {
                        lightbox.addEventListener('click', (event) => {
                            if (event.target === lightbox) closeLightbox();
                        });
                    }

                    document.addEventListener('keydown', (event) => {
                        if (!lightboxOpen) return;
                        if (event.key === 'Escape') closeLightbox();
                        if (event.key === 'ArrowLeft') showRelativeImage(-1);
                        if (event.key === 'ArrowRight') showRelativeImage(1);
                    });

                    // Mouse wheel zoom — preventDefault HANYA saat kursor di area viewer,
                    // supaya halaman tidak ikut ter-scroll tanpa sengaja.
                    if (lightboxViewport) {
                        lightboxViewport.addEventListener('wheel', (event) => {
                            event.preventDefault();
                            setZoomIndex(zoomIndex + (event.deltaY < 0 ? 1 : -1));
                        }, { passive: false });

                        // Pan / geser dengan mouse.
                        lightboxViewport.addEventListener('mousedown', (event) => {
                            if (currentZoom() <= 1) return;
                            isDragging = true;
                            dragStartX = event.clientX;
                            dragStartY = event.clientY;
                            dragStartPanX = panX;
                            dragStartPanY = panY;
                            lightboxViewport.style.cursor = 'grabbing';
                        });

                        window.addEventListener('mousemove', (event) => {
                            if (!isDragging) return;
                            panX = dragStartPanX + (event.clientX - dragStartX);
                            panY = dragStartPanY + (event.clientY - dragStartY);
                            clampPan();
                            applyTransform();
                        });

                        window.addEventListener('mouseup', () => {
                            if (!isDragging) return;
                            isDragging = false;
                            lightboxViewport.style.cursor = currentZoom() > 1 ? 'grab' : 'default';
                        });

                        // Dukungan sentuh dasar: drag satu jari untuk pan, cubit dua jari
                        // untuk zoom. Prioritas tetap desktop — ini tidak mengubah perilaku desktop.
                        let touchStartDistance = null;
                        let touchStartZoomIndex = 0;

                        lightboxViewport.addEventListener('touchstart', (event) => {
                            if (event.touches.length === 1 && currentZoom() > 1) {
                                isDragging = true;
                                dragStartX = event.touches[0].clientX;
                                dragStartY = event.touches[0].clientY;
                                dragStartPanX = panX;
                                dragStartPanY = panY;
                            } else if (event.touches.length === 2) {
                                const dx = event.touches[0].clientX - event.touches[1].clientX;
                                const dy = event.touches[0].clientY - event.touches[1].clientY;
                                touchStartDistance = Math.hypot(dx, dy);
                                touchStartZoomIndex = zoomIndex;
                            }
                        }, { passive: true });

                        lightboxViewport.addEventListener('touchmove', (event) => {
                            if (event.touches.length === 1 && isDragging) {
                                panX = dragStartPanX + (event.touches[0].clientX - dragStartX);
                                panY = dragStartPanY + (event.touches[0].clientY - dragStartY);
                                clampPan();
                                applyTransform();
                            } else if (event.touches.length === 2 && touchStartDistance) {
                                const dx = event.touches[0].clientX - event.touches[1].clientX;
                                const dy = event.touches[0].clientY - event.touches[1].clientY;
                                const distance = Math.hypot(dx, dy);
                                const ratio = distance / touchStartDistance;
                                const approxIndex = Math.round(touchStartZoomIndex + (ratio - 1) * (ZOOM_LEVELS.length - 1));
                                setZoomIndex(approxIndex);
                            }
                        }, { passive: true });

                        lightboxViewport.addEventListener('touchend', () => {
                            isDragging = false;
                            touchStartDistance = null;
                        });
                    }

                    // Mulai slideshow otomatis kalau produk punya lebih dari 1 foto.
                    if (galleryImages.length > 1) {
                        setActiveImage(0);
                        startSlideshow();
                    }
                }

                // Quantity selector: sesuai desain cuma ada tombol minus + angka.
                const minus = document.querySelector('[data-qty-minus]');
                const value = document.querySelector('[data-qty-value]');

                if (minus && value) {
                    let quantity = 1;

                    minus.addEventListener('click', () => {
                        quantity = Math.max(1, quantity - 1);
                        value.textContent = quantity;
                    });
                }
            });
        </script>
    </body>
</html>