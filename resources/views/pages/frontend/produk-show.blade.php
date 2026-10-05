<!DOCTYPE html>
<html lang="id" data-site="frontend">
    <head>
    @include('partials.favicon')
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
            ->topLevel()
            ->with('approvedUpdateComment')
            ->latest()
            ->get();

        // Satu pesanan = satu review utama. Komentar Update tidak dihitung
        // sebagai review/rating baru supaya rata-rata produk tidak dobel.
        $ratedTestimonials = $approvedTestimonials->whereNotNull('rating');
        $reviewCount = $approvedTestimonials->count();
        $ratingCount = $ratedTestimonials->count();
        $averageRating = $ratingCount > 0 ? (float) $ratedTestimonials->avg('rating') : 0;

        $siteSetting = \App\Models\Setting::current();
        $waNumber = $siteSetting->whatsappDigits();

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
                        <a href="{{ route('products.index') }}" class="transition hover:text-admin-accent">Produk</a>
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
                                        <i class="fa-solid fa-star text-[10px] {{ $ratingCount === 0 || $averageRating < $i ? 'text-[#D9D4CD]' : '' }}"></i>
                                    @endfor
                                </div>
                                <span class="text-[10px] font-semibold text-[#6F6258]">
                                    {{ $ratingCount > 0 ? number_format($averageRating, 1) : '0.0' }}
                                </span>
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
                            @php
                                $productData = [
                                    'id' => $product->id,
                                    'slug' => $product->slug,
                                    'name' => $product->nama,
                                    'price' => $displayPrice,
                                    'image' => $thumbnailUrl,
                                    'category' => $product->category?->name ?? 'Furniture',
                                    'stock' => max(0, (int) $product->stok),
                                ];
                            @endphp

                            <div class="mt-7" data-product-quantity data-max-stock="{{ max(0, (int) $product->stok) }}" data-wa-number="{{ $waNumber }}" data-product-name="{{ $product->nama }}">
                                <div data-kie-product-actions class="flex items-center gap-2.5">
                                    <div class="inline-flex h-10 shrink-0 items-center overflow-hidden rounded-md border border-[#E3DED7] bg-white">
                                        <button type="button" data-quantity-minus class="flex h-full w-8 items-center justify-center text-[#7A6E63] transition hover:bg-[#F8F4EF] disabled:cursor-not-allowed disabled:opacity-40" aria-label="Kurangi jumlah">
                                            <i class="fa-solid fa-minus text-[9px]"></i>
                                        </button>
                                        <span data-quantity-display class="flex w-7 items-center justify-center text-xs font-medium text-[#2A211B]">1</span>
                                        <button type="button" data-quantity-plus class="flex h-full w-8 items-center justify-center text-[#7A6E63] transition hover:bg-[#F8F4EF] disabled:cursor-not-allowed disabled:opacity-40" aria-label="Tambah jumlah">
                                            <i class="fa-solid fa-plus text-[9px]"></i>
                                        </button>
                                    </div>
                                    <button
                                        type="button"
                                        aria-label="Simpan ke Favorit"
                                        title="Simpan ke Favorit"
                                        data-favorite-product
                                        data-product-id="{{ $product->id }}"
                                        data-product-slug="{{ $product->slug }}"
                                        data-product-name="{{ $product->nama }}"
                                        data-product-price="{{ $displayPrice }}"
                                        data-product-image="{{ $thumbnailUrl }}"
                                        data-product-category="{{ $product->category?->name ?? 'Furniture' }}"
                                        data-product-stock="{{ max(0, (int) $product->stok) }}"
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-[#E3DED7] bg-white text-[#5C5147] transition hover:border-[#C7A16D] hover:text-admin-accent"
                                    >
                                        <i data-favorite-icon class="fa-regular fa-heart text-sm"></i>
                                    </button>
                                    <button
                                        type="button"
                                        aria-label="Masukkan ke Keranjang"
                                        title="Masukkan ke Keranjang"
                                        data-cart-product
                                        data-product-id="{{ $product->id }}"
                                        data-product-slug="{{ $product->slug }}"
                                        data-product-name="{{ $product->nama }}"
                                        data-product-price="{{ $displayPrice }}"
                                        data-product-image="{{ $thumbnailUrl }}"
                                        data-product-category="{{ $product->category?->name ?? 'Furniture' }}"
                                        data-product-stock="{{ max(0, (int) $product->stok) }}"
                                        data-cart-quantity-source="data-product-quantity"
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#2A211B] text-white transition hover:bg-[#403129] disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <i class="fa-solid fa-bag-shopping text-sm"></i>
                                    </button>
                                    <a
                                        href="{{ $waNumber ? 'https://wa.me/'.$waNumber.'?text='.urlencode('Halo, saya ingin memesan produk "'.$product->nama.'" sebanyak 1 pcs.') : '#' }}"
                                        data-booking-link
                                        @if ($waNumber) target="_blank" rel="noopener" @endif
                                        class="h-10 flex flex-1 items-center justify-center rounded-md bg-[#F28A22] px-5 text-[11px] font-semibold text-white transition hover:bg-[#DD7614]"
                                        @unless ($waNumber) title="Nomor WhatsApp belum diisi di Admin > Pengaturan" onclick="event.preventDefault()" @endunless
                                    >
                                        Pesan Sekarang
                                    </a>
                                </div>
                                <p class="mt-2 text-[10px] text-[#A1988E]">Jumlah yang dipilih akan disertakan saat menghubungi admin via WhatsApp.</p>
                            </div>

                            <script>
                                (() => {
                                    const box = document.querySelector('[data-product-quantity]');
                                    if (!box) return;

                                    const maxStock = Number.parseInt(box.dataset.maxStock || '0', 10);
                                    const waNumber = box.dataset.waNumber || '';
                                    const productName = box.dataset.productName || '';
                                    const display = box.querySelector('[data-quantity-display]');
                                    const minus = box.querySelector('[data-quantity-minus]');
                                    const plus = box.querySelector('[data-quantity-plus]');
                                    const link = box.querySelector('[data-booking-link]');

                                    if (!display || !minus || !plus || !link) return;

                                    let quantity = maxStock > 0 ? 1 : 0;

                                    const render = () => {
                                        display.textContent = String(quantity);
                                        minus.disabled = quantity <= 1;
                                        plus.disabled = maxStock <= 0 || quantity >= maxStock;
                                        if (maxStock > 0 && waNumber) {
                                            const waMessage = `Halo, saya ingin memesan produk "${productName}" sebanyak ${quantity} pcs.`;
                                            link.href = `https://wa.me/${waNumber}?text=${encodeURIComponent(waMessage)}`;
                                        }
                                    };

                                    minus.addEventListener('click', () => {
                                        if (quantity > 1) {
                                            quantity -= 1;
                                            render();
                                        }
                                    });

                                    plus.addEventListener('click', () => {
                                        if (quantity < maxStock) {
                                            quantity += 1;
                                            render();
                                        }
                                    });

                                    render();
                                })();
                            </script>
                            <a
                            href="{{ $waNumber ? 'https://wa.me/'.$waNumber.'?text='.$waText : '#' }}"
                                    @if ($waNumber) target="_blank" rel="noopener" @endif
                                    class="mt-3 flex h-10 w-full items-center justify-center gap-2 rounded-md bg-[#58B13F] px-5 text-[11px] font-semibold text-white transition hover:bg-[#489C32] {{ $waNumber ? '' : 'cursor-not-allowed opacity-90' }}"
                                    @unless ($waNumber) title="Nomor WhatsApp belum diisi di Admin > Pengaturan" onclick="event.preventDefault()" @endunless
                                >
                                    Konsultasi via WhatsApp
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
                 - Specification: berat, dimensi (p/l/t), kategori
                 - Reviews      : testimonial approved + aktif milik produk ini
            ====================================================== --}}
            <section class="bg-white" x-data="{ tab: 'description' }">
                <div class="mx-auto w-full max-w-[1600px] px-5 pb-8 sm:px-8 lg:px-12 xl:px-16">
                    <div class="border-t border-[#EEEAE5]"></div>

                    <div class="pt-5">
                        <div data-kie-product-tabs class="flex items-center gap-8 border-b border-[#F0ECE7]">
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
<div x-show="tab === 'reviews'" x-cloak class="pt-5">

    @if ($approvedTestimonials->isNotEmpty())

        {{-- Header ala marketplace --}}
        <div class="kie-product-review-heading">
            <div>
                <p class="kie-product-review-heading-title">
                    Ulasan Produk
                </p>

                <div class="kie-product-review-summary">
                    <span class="kie-product-review-summary-score">
                        {{ number_format($averageRating, 1) }}
                    </span>

                    <div class="kie-product-review-summary-stars">
                        @for ($i = 1; $i <= 5; $i++)
                            @php
                                $fullStar = $averageRating >= $i;
                                $halfStar = ! $fullStar
                                    && $averageRating >= ($i - 0.5);
                            @endphp

                            @if ($fullStar)
                                <i
                                    class="fa-solid fa-star"
                                    style="color:#F2A01C;"
                                ></i>
                            @elseif ($halfStar)
                                <i
                                    class="fa-solid fa-star-half-stroke"
                                    style="color:#F2A01C;"
                                ></i>
                            @else
                                <i
                                    class="fa-solid fa-star"
                                    style="color:#DDD6CE;"
                                ></i>
                            @endif
                        @endfor
                    </div>

                    <span class="kie-product-review-summary-count">
                        {{ $reviewCount }} ulasan
                    </span>
                </div>
            </div>

            @if ($reviewCount > 2)
    <a
        href="{{ route('products.reviews', $product) }}"
        class="kie-product-review-more kie-product-review-more-mobile"
    >
        Lihat Selengkapnya
        <i class="fa-solid fa-chevron-right"></i>
    </a>
@endif

@if ($reviewCount > 3)
    <a
        href="{{ route('products.reviews', $product) }}"
        class="kie-product-review-more kie-product-review-more-desktop"
    >
        Lihat Selengkapnya
        <i class="fa-solid fa-chevron-right"></i>
    </a>
@endif
        </div>

        {{-- HANYA 2 review terbaru di halaman produk --}}
        <div class="kie-product-review-grid">
            @foreach ($approvedTestimonials->take(3) as $testimonial)
                @php
                    $productReviewPhotos = collect($testimonial->photos ?? [])
                        ->filter()
                        ->take(2)
                        ->values();

                    $productReviewAddress = collect([
                        $testimonial->kabupaten,
                        $testimonial->provinsi,
                    ])->filter()->implode(', ');

                    $productReviewName = $testimonial->displayName();
                    $productReviewRating = (int) ($testimonial->rating ?? 0);
                @endphp

                <article class="kie-product-review-card">
                    <div class="kie-product-review-inner">

                        <div class="kie-product-review-top">
                            <div class="kie-product-review-profile">
                                <span class="kie-product-review-avatar">
                                    {{ strtoupper(mb_substr($productReviewName, 0, 1)) }}
                                </span>

                                <div class="kie-product-review-user">
                                    <p class="kie-product-review-name">
                                        {{ $productReviewName }}
                                    </p>

                                    <div class="kie-product-review-rating-row">
                                        <div class="kie-product-review-stars">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <i
                                                    class="fa-solid fa-star"
                                                    style="color: {{ $i <= $productReviewRating ? '#F2A01C' : '#DDD6CE' }};"
                                                ></i>
                                            @endfor
                                        </div>

                                        @if ($testimonial->rating)
                                            <span class="kie-product-review-rating-number">
                                                {{ number_format((float) $testimonial->rating, 1) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <span class="kie-product-review-date">
                                {{ $testimonial->created_at?->format('d/m/Y') }}
                            </span>
                        </div>

                        @if ($productReviewAddress !== '')
                            <div class="kie-product-review-location">
                                <i class="fa-solid fa-location-dot"></i>
                                {{ $productReviewAddress }}
                            </div>
                        @endif

                        @if ($testimonial->comment)
                            <p class="kie-product-review-comment">
                                {{ $testimonial->comment }}
                            </p>
                        @endif

                        @if ($productReviewPhotos->isNotEmpty())
                            <div class="kie-product-review-photos">
                                @foreach ($productReviewPhotos as $photo)
                                    <a
                                        href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="kie-product-review-photo"
                                    >
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo) }}"
                                            alt="Foto ulasan {{ $productReviewName }}"
                                        >
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if ($testimonial->approvedUpdateComment?->comment)
                            <div class="kie-product-review-update">
                                <p>Update dari pembeli</p>
                                <span>
                                    {{ $testimonial->approvedUpdateComment->comment }}
                                </span>
                            </div>
                        @endif

                    </div>
                </article>
            @endforeach
        </div>

    @else
        <div class="kie-product-review-empty">
            Belum ada ulasan untuk produk ini.
        </div>
    @endif
</div>

<style>
    .kie-product-review-heading{
        display:flex;
        align-items:flex-end;
        justify-content:space-between;
        gap:20px;
        margin-bottom:14px;
        max-width:1100px;
    }

    .kie-product-review-heading-title{
        margin:0;
        font-size:13px;
        font-weight:700;
        color:#2A211B;
    }

    .kie-product-review-summary{
        margin-top:5px;
        display:flex;
        align-items:center;
        gap:7px;
    }

    .kie-product-review-summary-score{
        font-size:18px;
        line-height:1;
        font-weight:700;
        color:#2A211B;
    }

    .kie-product-review-summary-stars{
        display:flex;
        gap:2px;
        font-size:10px;
    }

    .kie-product-review-summary-count{
        font-size:9px;
        color:#9A8E82;
    }

    .kie-product-review-more{
        display:inline-flex;
        align-items:center;
        gap:7px;
        padding:8px 12px;
        border:1px solid #EADFD3;
        border-radius:999px;
        background:#FFF;
        color:#A7672C;
        font-size:10px;
        font-weight:600;
        text-decoration:none;
        transition:.2s ease;
    }

    .kie-product-review-more:hover{
        background:#FBF5ED;
        border-color:#DDBF9D;
    }

    .kie-product-review-more i{
        font-size:8px;
    }

    .kie-product-review-grid{
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:14px;
        max-width:1100px;
    }

    .kie-product-review-card{
        overflow:hidden;
        min-width:0;
        border:1px solid #ECE4DA;
        border-radius:16px;
        background:#FFF;
        box-shadow:0 7px 24px rgba(42,33,27,.05);
    }

    .kie-product-review-inner{
        padding:15px 16px;
    }

    .kie-product-review-top{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:12px;
    }

    .kie-product-review-profile{
        min-width:0;
        display:flex;
        align-items:center;
        gap:10px;
    }

    .kie-product-review-avatar{
        width:36px;
        height:36px;
        flex:0 0 36px;
        display:flex;
        align-items:center;
        justify-content:center;
        border-radius:999px;
        background:#F6EFE7;
        border:1px solid #ECE0D3;
        color:#D98222;
        font-size:11px;
        font-weight:700;
    }

    .kie-product-review-user{
        min-width:0;
    }

    .kie-product-review-name{
        margin:0;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
        font-size:11px;
        font-weight:700;
        color:#2A211B;
    }

    .kie-product-review-rating-row{
        margin-top:4px;
        display:flex;
        align-items:center;
        gap:6px;
    }

    .kie-product-review-stars{
        display:flex;
        align-items:center;
        gap:2px;
        font-size:10px;
    }

    .kie-product-review-rating-number{
        font-size:9px;
        font-weight:600;
        color:#75695E;
    }

    .kie-product-review-date{
        flex:0 0 auto;
        font-size:8.5px;
        color:#AAA098;
    }

    .kie-product-review-location{
        width:max-content;
        max-width:100%;
        margin-top:10px;
        padding:4px 8px;
        border-radius:999px;
        background:#FAF6F1;
        color:#887A6D;
        font-size:8.5px;
        overflow-wrap:anywhere;
    }

    .kie-product-review-location i{
        margin-right:4px;
        color:#BC8350;
        font-size:7px;
    }

    .kie-product-review-comment{
        margin:11px 0 0;
        color:#554B43;
        font-size:10.5px;
        line-height:1.65;
    }

    .kie-product-review-photos{
        margin-top:11px;
        display:flex;
        flex-wrap:wrap;
        gap:7px;
    }

    .kie-product-review-photo{
        width:58px;
        height:58px;
        flex:0 0 58px;
        display:block;
        overflow:hidden;
        border:1px solid #E8DED3;
        border-radius:9px;
        background:#F6F1EB;
    }

    .kie-product-review-photo img{
        display:block;
        width:100%;
        height:100%;
        object-fit:cover;
    }

    .kie-product-review-update{
        margin-top:11px;
        padding:9px 10px;
        border-radius:9px;
        background:#FBF6EF;
    }

    .kie-product-review-update p{
        margin:0;
        color:#9B6E3F;
        font-size:7.5px;
        font-weight:700;
        letter-spacing:.1em;
        text-transform:uppercase;
    }

    .kie-product-review-update span{
        display:block;
        margin-top:4px;
        color:#675C52;
        font-size:9.5px;
        line-height:1.55;
    }

    .kie-product-review-empty{
        max-width:1100px;
        padding:18px;
        border:1px dashed #E8DED1;
        border-radius:14px;
        background:#FCFAF7;
        color:#75695E;
        font-size:10px;
    }

    @media(max-width:767.98px){
        .kie-product-review-heading{
            align-items:center;
        }

        .kie-product-review-grid{
            grid-template-columns:1fr;
        }

        .kie-product-review-card:nth-child(n+2){
            display:none;
        }

        .kie-product-review-more{
            padding:7px 9px;
            font-size:9px;
        }
    }
</style>
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

{{-- KIE-PRODUCT-REVIEW-AESTHETIC-V2 --}}
<style>
    .kie-product-review-grid {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 16px !important;
        align-items: start !important;
    }

    .kie-product-review-card {
        box-sizing: border-box !important;
        width: 100% !important;
        min-width: 0 !important;
        height: auto !important;
        min-height: 0 !important;

        border: 1px solid #ece4db !important;
        border-radius: 18px !important;

        background:
            linear-gradient(
                145deg,
                #ffffff 0%,
                #fdfbf8 100%
            ) !important;

        box-shadow:
            0 8px 28px rgba(46, 32, 22, .06) !important;

        overflow: hidden !important;
    }

    .kie-product-review-card:hover {
        transform: translateY(-2px);
        box-shadow:
            0 13px 34px rgba(46, 32, 22, .09) !important;
    }

    .kie-product-review-card > div:first-child {
        padding: 18px !important;
    }

    .kie-product-review-card .fa-star {
        font-size: 10px !important;
    }

    .kie-product-review-photo {
        display: block !important;

        width: 72px !important;
        height: 72px !important;
        min-width: 72px !important;
        min-height: 72px !important;
        flex: 0 0 72px !important;

        border: 1px solid #e8ded3 !important;
        border-radius: 12px !important;

        background: #f6f1eb !important;
        overflow: hidden !important;
    }

    .kie-product-review-photo-img {
        display: block !important;
        width: 100% !important;
        height: 100% !important;
        max-width: 100% !important;
        object-fit: cover !important;
    }

    /*
     * Komentar dibuat lebih elegan:
     * tidak memakai tanda kutip besar/aneh,
     * cukup quote icon kecil.
     */
    .kie-product-review-card .fa-quote-left {
        color: #c1b19f !important;
    }

    @media (max-width: 899.98px) {
        .kie-product-review-grid {
            grid-template-columns: 1fr !important;
        }
    }

    @media (max-width: 639.98px) {
        .kie-product-review-grid {
            gap: 12px !important;
        }

        .kie-product-review-card {
            border-radius: 15px !important;
        }

        .kie-product-review-card > div:first-child {
            padding: 14px !important;
        }

        .kie-product-review-photo {
            width: 58px !important;
            height: 58px !important;
            min-width: 58px !important;
            min-height: 58px !important;
            flex-basis: 58px !important;

            border-radius: 10px !important;
        }
    }
</style>

{{-- KIE-PRODUCT-REVIEW-COMPACT-V3 --}}
<style>
    .kie-product-review-grid {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 620px)) !important;
        justify-content: start !important;
        align-items: start !important;
        gap: 14px !important;
    }

    .kie-product-review-card {
        width: 100% !important;
        max-width: 620px !important;
        min-height: 0 !important;
        height: auto !important;

        border: 1px solid #ece4da !important;
        border-radius: 16px !important;

        background: #fff !important;

        box-shadow:
            0 7px 24px rgba(42, 33, 27, .055) !important;
    }

    .kie-product-review-card > div:first-child {
        padding: 15px 16px !important;
    }

    .kie-product-review-card:hover {
        transform: translateY(-1px) !important;
        box-shadow:
            0 10px 28px rgba(42, 33, 27, .075) !important;
    }

    .kie-product-review-card .h-10.w-10 {
        width: 36px !important;
        height: 36px !important;
        min-width: 36px !important;
        min-height: 36px !important;
    }

    .kie-product-review-card .mt-4 {
        margin-top: 10px !important;
    }

    .kie-product-review-card .mt-3 {
        margin-top: 8px !important;
    }

    .kie-product-review-card .mt-1\.5 {
        margin-top: 4px !important;
    }

    .kie-product-review-photo {
        width: 62px !important;
        height: 62px !important;
        min-width: 62px !important;
        min-height: 62px !important;
        flex-basis: 62px !important;
        border-radius: 10px !important;
    }

    .kie-product-review-photo-img {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
    }

    /* Kalau hanya ada satu review, jangan dibuat selebar section. */
    .kie-product-review-grid:has(> .kie-product-review-card:only-child) {
        grid-template-columns: minmax(0, 620px) !important;
    }

    @media (max-width: 767.98px) {
        .kie-product-review-grid,
        .kie-product-review-grid:has(> .kie-product-review-card:only-child) {
            grid-template-columns: 1fr !important;
        }

        .kie-product-review-card {
            max-width: none !important;
            border-radius: 14px !important;
        }

        .kie-product-review-card > div:first-child {
            padding: 13px !important;
        }

        .kie-product-review-photo {
            width: 56px !important;
            height: 56px !important;
            min-width: 56px !important;
            min-height: 56px !important;
            flex-basis: 56px !important;
        }
    }
</style>


<style>
/* KIE-REVIEW-FINAL-LOCK-V14 */


/* =========================================================
   DETAIL PRODUK
   ========================================================= */

.kie-product-review-heading{
    width:100% !important;
    max-width:none !important;

    display:flex !important;
    align-items:flex-end !important;
    justify-content:space-between !important;

    gap:20px !important;

    margin-bottom:18px !important;
}

.kie-product-review-heading-title{
    margin:0 !important;

    font-size:15px !important;
    font-weight:700 !important;

    color:#2A211B !important;
}

.kie-product-review-summary{
    margin-top:6px !important;
    padding:0 !important;

    display:flex !important;
    align-items:center !important;

    gap:8px !important;

    border:0 !important;
    background:transparent !important;
    box-shadow:none !important;
}

.kie-product-review-summary-score{
    font-size:22px !important;
    line-height:1 !important;
    font-weight:700 !important;
}

.kie-product-review-summary-stars{
    display:flex !important;
    gap:2px !important;

    font-size:12px !important;
}

.kie-product-review-summary-count{
    font-size:10px !important;
    color:#95887B !important;
}

.kie-product-review-more{
    margin-left:auto !important;

    display:inline-flex !important;
    align-items:center !important;

    gap:8px !important;

    padding:9px 14px !important;

    border:1px solid #E9D8C5 !important;
    border-radius:999px !important;

    background:#FFF !important;

    color:#A86629 !important;

    font-size:10px !important;
    font-weight:600 !important;

    white-space:nowrap !important;
}

.kie-product-review-more-mobile{
    display:none !important;
}

.kie-product-review-more-desktop{
    display:inline-flex !important;
}


/* GRID DESKTOP = 3 */
.kie-product-review-grid{
    width:100% !important;
    max-width:none !important;

    display:grid !important;

    grid-template-columns:repeat(3,minmax(0,1fr)) !important;

    gap:16px !important;

    align-items:start !important;
}


/* CARD */
.kie-product-review-card{
    box-sizing:border-box !important;

    width:100% !important;

    height:auto !important;
    min-height:0 !important;

    align-self:start !important;

    overflow:hidden !important;

    border:1px solid #EDE3D8 !important;
    border-radius:18px !important;

    background:#FFF !important;

    box-shadow:
        0 8px 26px rgba(42,33,27,.055) !important;
}

.kie-product-review-inner{
    box-sizing:border-box !important;

    width:100% !important;

    height:auto !important;
    min-height:0 !important;

    display:block !important;

    padding:18px !important;
}


/* =========================================================
   USER
   ========================================================= */

.kie-product-review-top{
    display:flex !important;
    flex-direction:column !important;

    align-items:flex-start !important;

    gap:0 !important;
}

.kie-product-review-profile{
    width:100% !important;
    min-width:0 !important;

    display:flex !important;
    align-items:center !important;

    gap:11px !important;
}

.kie-product-review-avatar{
    width:42px !important;
    height:42px !important;

    min-width:42px !important;
    min-height:42px !important;

    flex:0 0 42px !important;

    display:flex !important;
    align-items:center !important;
    justify-content:center !important;

    border:1px solid #E9DDCF !important;
    border-radius:999px !important;

    background:#F8F3ED !important;

    color:#C97A1C !important;

    font-size:12px !important;
    font-weight:700 !important;
}

.kie-product-review-user{
    min-width:0 !important;
}

.kie-product-review-name{
    margin:0 !important;

    font-size:14px !important;
    line-height:1.25 !important;
    font-weight:700 !important;

    color:#2A211B !important;
}

.kie-product-review-rating-row{
    margin-top:5px !important;

    display:flex !important;
    align-items:center !important;

    gap:7px !important;
}

.kie-product-review-stars{
    display:flex !important;
    align-items:center !important;

    gap:2px !important;

    font-size:11px !important;
}

.kie-product-review-rating-number{
    font-size:10px !important;
    font-weight:600 !important;

    color:#756A60 !important;
}


/* TANGGAL DI BAWAH NAMA/RATING, BUKAN UJUNG CARD */
.kie-product-review-date{
    position:static !important;

    display:block !important;

    margin:5px 0 0 53px !important;

    padding:0 !important;

    font-size:9.5px !important;
    line-height:1.2 !important;

    color:#A0958A !important;

    white-space:nowrap !important;
}


/* =========================================================
   ISI
   ========================================================= */

.kie-product-review-location{
    width:fit-content !important;
    max-width:100% !important;

    display:inline-flex !important;
    align-items:center !important;

    gap:5px !important;

    margin-top:12px !important;

    padding:5px 9px !important;

    border-radius:999px !important;

    background:#F8F5F1 !important;

    color:#84776B !important;

    font-size:10px !important;
}

.kie-product-review-comment{
    width:100% !important;
    max-width:none !important;

    margin:12px 0 0 !important;

    color:#50463E !important;

    font-size:13px !important;
    line-height:1.65 !important;

    word-break:break-word !important;
}


/* =========================================================
   FOTO DI KANAN DESKTOP
   ========================================================= */

@media (min-width:1200px){

    .kie-product-review-card:has(.kie-product-review-photos)
    .kie-product-review-inner{
        display:grid !important;

        grid-template-columns:minmax(0,1fr) auto !important;

        column-gap:16px !important;

        align-items:start !important;
    }

    .kie-product-review-card:has(.kie-product-review-photos)
    .kie-product-review-top,
    .kie-product-review-card:has(.kie-product-review-photos)
    .kie-product-review-location,
    .kie-product-review-card:has(.kie-product-review-photos)
    .kie-product-review-comment,
    .kie-product-review-card:has(.kie-product-review-photos)
    .kie-product-review-update{
        grid-column:1 !important;
    }

    .kie-product-review-photos{
        position:static !important;

        grid-column:2 !important;
        grid-row:1 / span 4 !important;

        align-self:end !important;

        width:auto !important;

        margin:0 !important;

        display:grid !important;

        grid-template-columns:repeat(2,68px) !important;

        gap:8px !important;
    }

    .kie-product-review-photo{
        width:68px !important;
        height:68px !important;

        min-width:68px !important;
        min-height:68px !important;

        flex:0 0 68px !important;

        overflow:hidden !important;

        border:1px solid #E8DED3 !important;
        border-radius:12px !important;
    }

    .kie-product-review-photo img{
        display:block !important;

        width:100% !important;
        height:100% !important;

        object-fit:cover !important;
    }

    .kie-product-review-photo:nth-child(n+3){
        display:none !important;
    }
}


/* =========================================================
   SEMUA ULASAN
   ========================================================= */

.kie-all-product-reviews{
    width:min(1400px,calc(100% - 64px)) !important;

    margin:0 auto !important;

    padding-top:42px !important;
}

.kie-all-product-review-head{
    width:100% !important;

    display:flex !important;
    align-items:center !important;
    justify-content:space-between !important;

    gap:24px !important;

    margin-bottom:26px !important;
}

.kie-all-product-review-title{
    font-size:30px !important;
}

.kie-all-product-review-product{
    margin-top:7px !important;

    font-size:12px !important;
}

.kie-all-product-review-score{
    min-width:105px !important;

    padding:13px 15px !important;

    border-radius:16px !important;
}

.kie-all-product-review-score strong{
    font-size:22px !important;
}


/* SEMUA REVIEW DESKTOP 3 KOLOM */
.kie-all-product-review-grid{
    width:100% !important;

    display:grid !important;

    grid-template-columns:repeat(3,minmax(0,1fr)) !important;

    gap:16px !important;

    align-items:start !important;
}

.kie-all-product-review-card{
    box-sizing:border-box !important;

    position:relative !important;

    width:100% !important;

    height:auto !important;
    min-height:0 !important;

    padding:18px !important;

    align-self:start !important;

    border:1px solid #EDE3D8 !important;
    border-radius:18px !important;

    background:#FFF !important;

    box-shadow:
        0 8px 26px rgba(42,33,27,.055) !important;
}


/* HEADER SEMUA ULASAN */
.kie-all-product-review-top{
    display:flex !important;
    flex-direction:column !important;

    align-items:flex-start !important;
}

.kie-all-product-review-profile{
    width:100% !important;

    display:flex !important;
    align-items:center !important;

    gap:11px !important;
}

.kie-all-product-review-avatar{
    width:42px !important;
    height:42px !important;

    flex:0 0 42px !important;

    font-size:12px !important;
}

.kie-all-product-review-name{
    margin:0 !important;

    font-size:14px !important;

    font-weight:700 !important;
}

.kie-all-product-review-stars{
    margin-top:5px !important;

    display:flex !important;

    gap:2px !important;

    font-size:11px !important;
}


/* TANGGAL SEMUA ULASAN */
.kie-all-product-review-date{
    position:static !important;

    margin:5px 0 0 53px !important;

    padding:0 !important;

    font-size:9.5px !important;

    color:#A0958A !important;

    white-space:nowrap !important;
}

.kie-all-product-review-location{
    margin-top:12px !important;

    padding:5px 9px !important;

    font-size:10px !important;
}

.kie-all-product-review-comment{
    margin:12px 0 0 !important;

    font-size:13px !important;
    line-height:1.65 !important;
}


/* FOTO SEMUA REVIEW DI KANAN */
@media (min-width:1200px){

    .kie-all-product-review-card:has(.kie-all-product-review-photos){
        display:grid !important;

        grid-template-columns:minmax(0,1fr) auto !important;

        column-gap:16px !important;
    }

    .kie-all-product-review-card:has(.kie-all-product-review-photos)
    .kie-all-product-review-top,
    .kie-all-product-review-card:has(.kie-all-product-review-photos)
    .kie-all-product-review-location,
    .kie-all-product-review-card:has(.kie-all-product-review-photos)
    .kie-all-product-review-comment,
    .kie-all-product-review-card:has(.kie-all-product-review-photos)
    .kie-all-product-review-update{
        grid-column:1 !important;
    }

    .kie-all-product-review-photos{
        position:static !important;

        grid-column:2 !important;
        grid-row:1 / span 4 !important;

        align-self:end !important;

        width:auto !important;

        margin:0 !important;

        display:grid !important;

        grid-template-columns:repeat(2,68px) !important;

        gap:8px !important;
    }

    .kie-all-product-review-photo{
        width:68px !important;
        height:68px !important;

        border-radius:12px !important;
    }
}


/* =========================================================
   TABLET / DEVICE SELAIN DESKTOP = 2
   ========================================================= */

@media (max-width:1199.98px){

    .kie-product-review-grid,
    .kie-all-product-review-grid{
        grid-template-columns:repeat(2,minmax(0,1fr)) !important;

        gap:12px !important;
    }

    .kie-product-review-more-desktop{
        display:none !important;
    }

    .kie-product-review-more-mobile{
        display:inline-flex !important;
    }

    .kie-product-review-photos,
    .kie-all-product-review-photos{
        position:static !important;

        width:auto !important;

        margin-top:10px !important;

        display:flex !important;
        flex-wrap:wrap !important;

        gap:7px !important;
    }
}


/* =========================================================
   HP = TETAP 2
   ========================================================= */

@media (max-width:639.98px){

    .kie-product-review-grid,
    .kie-all-product-review-grid{
        grid-template-columns:repeat(2,minmax(0,1fr)) !important;

        gap:8px !important;
    }

    .kie-product-review-inner,
    .kie-all-product-review-card{
        padding:11px !important;
    }

    .kie-product-review-avatar,
    .kie-all-product-review-avatar{
        width:30px !important;
        height:30px !important;

        min-width:30px !important;
        min-height:30px !important;

        flex-basis:30px !important;

        font-size:8px !important;
    }

    .kie-product-review-name,
    .kie-all-product-review-name{
        font-size:10px !important;
    }

    .kie-product-review-stars,
    .kie-all-product-review-stars{
        font-size:8px !important;
    }

    .kie-product-review-date,
    .kie-all-product-review-date{
        margin-left:41px !important;

        font-size:7px !important;
    }

    .kie-product-review-location,
    .kie-all-product-review-location{
        margin-top:7px !important;

        padding:3px 6px !important;

        font-size:7.5px !important;
    }

    .kie-product-review-comment,
    .kie-all-product-review-comment{
        margin-top:7px !important;

        font-size:9px !important;
        line-height:1.45 !important;
    }

    .kie-product-review-photo,
    .kie-all-product-review-photo{
        width:44px !important;
        height:44px !important;

        min-width:44px !important;
        min-height:44px !important;

        flex-basis:44px !important;

        border-radius:7px !important;
    }
}
</style>

<style>
/* KIE-REVIEW-NATURAL-HEIGHT-V15 */

/*
 * Jangan samakan tinggi seluruh kartu.
 * Setiap review mengikuti isi masing-masing.
 */
.kie-product-review-grid{
    align-items:start !important;
}

.kie-product-review-card{
    height:auto !important;
    min-height:0 !important;
    align-self:start !important;
}

.kie-product-review-inner{
    height:auto !important;
    min-height:0 !important;
}

/*
 * Review berfoto tetap memakai layout kanan,
 * tetapi tinggi kartunya ditentukan oleh isi/foto,
 * bukan memaksa kartu lain ikut tinggi.
 */
@media (min-width:1200px){

    .kie-product-review-card:has(.kie-product-review-photos){
        height:auto !important;
        min-height:0 !important;
    }

    .kie-product-review-card:has(.kie-product-review-photos)
    .kie-product-review-inner{
        min-height:108px !important;
    }
}

/* Tablet / HP tetap 2 kolom */
@media (max-width:1199.98px){

    .kie-product-review-grid{
        grid-template-columns:repeat(2,minmax(0,1fr)) !important;
        align-items:start !important;
    }

    .kie-product-review-card,
    .kie-product-review-inner{
        height:auto !important;
        min-height:0 !important;
    }
}

@media (max-width:639.98px){

    .kie-product-review-grid{
        grid-template-columns:repeat(2,minmax(0,1fr)) !important;
    }
}
</style>

<style>
/* KIE-REVIEW-MASONRY-FIX-V16 */

/* Jangan paksa kartu dalam satu row memiliki tinggi sama */
.kie-product-review-grid{
    align-items:start !important;
    grid-auto-rows:max-content !important;
}

.kie-product-review-card{
    height:auto !important;
    min-height:0 !important;
    align-self:start !important;
}

.kie-product-review-inner{
    height:auto !important;
    min-height:0 !important;
}

/* Card tanpa foto benar-benar compact */
.kie-product-review-card:not(:has(.kie-product-review-photos)){
    height:auto !important;
    min-height:0 !important;
}

.kie-product-review-card:not(:has(.kie-product-review-photos))
.kie-product-review-inner{
    height:auto !important;
    min-height:0 !important;
}

/* Card dengan foto hanya setinggi kebutuhan foto + konten */
@media (min-width:1200px){

    .kie-product-review-card:has(.kie-product-review-photos){
        height:auto !important;
        min-height:0 !important;
    }

    .kie-product-review-card:has(.kie-product-review-photos)
    .kie-product-review-inner{
        height:auto !important;
        min-height:106px !important;
    }
}

/* Tablet / HP tetap 2 per baris */
@media (max-width:1199.98px){

    .kie-product-review-grid{
        grid-template-columns:repeat(2,minmax(0,1fr)) !important;
        align-items:start !important;
        grid-auto-rows:max-content !important;
    }

    .kie-product-review-card,
    .kie-product-review-inner{
        height:auto !important;
        min-height:0 !important;
    }
}

@media (max-width:639.98px){

    .kie-product-review-grid{
        grid-template-columns:repeat(2,minmax(0,1fr)) !important;
    }
}
</style>

<style>
/* KIE-MOBILE-TWO-REVIEWS-FIX-V17 */

/* DESKTOP: tampilkan semua 3 preview */
@media (min-width:1200px){

    .kie-product-review-grid > .kie-product-review-card{
        display:block !important;
    }
}


/* TABLET + HP: 2 REVIEW PER BARIS */
@media (max-width:1199.98px){

    .kie-product-review-grid{
        display:grid !important;
        grid-template-columns:repeat(2,minmax(0,1fr)) !important;
        gap:10px !important;
    }

    /*
     * Batalkan rule lama yang menyembunyikan card ke-2.
     */
    .kie-product-review-grid > .kie-product-review-card{
        display:block !important;
        min-width:0 !important;
    }

    /*
     * Preview halaman produk hanya 2 review.
     * Review ke-3 dst dibuka lewat Lihat Selengkapnya.
     */
    .kie-product-review-grid > .kie-product-review-card:nth-child(n+3){
        display:none !important;
    }
}


/* HP tetap 2 kolom */
@media (max-width:639.98px){

    .kie-product-review-grid{
        grid-template-columns:repeat(2,minmax(0,1fr)) !important;
        gap:8px !important;
    }

    .kie-product-review-grid > .kie-product-review-card:nth-child(1),
    .kie-product-review-grid > .kie-product-review-card:nth-child(2){
        display:block !important;
    }

    .kie-product-review-grid > .kie-product-review-card:nth-child(n+3){
        display:none !important;
    }
}
</style>
</body>
</html>