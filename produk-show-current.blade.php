<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $product->nama }} â€” {{ \App\Models\Setting::current()->site_name }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    </head>

    @php
        $siteSetting = \App\Models\Setting::current();

        $hasDiscount = $product->harga_diskon
            && (float) $product->harga_diskon > 0
            && (float) $product->harga_diskon < (float) $product->harga;

        $displayPrice = $hasDiscount ? (float) $product->harga_diskon : (float) $product->harga;

        $thumbnailUrl = ($product->thumbnail && \Illuminate\Support\Facades\Storage::disk('public')->exists($product->thumbnail))
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->thumbnail)
            : null;

        $approvedTestimonials = $product->testimonials()
            ->approved()
            ->active()
            ->latest()
            ->get();

        $reviewCount = $approvedTestimonials->count();
        $averageRating = $reviewCount > 0 ? (float) $approvedTestimonials->avg('rating') : 0;

        // Produk terkait: prioritaskan kategori yang sama, lalu produk aktif lainnya.
        $relatedProducts = \App\Models\Product::query()
            ->where('status', 'aktif')
            ->where('id', '!=', $product->id)
            ->with('category:id,name,slug')
            ->when($product->category_id, function ($query) use ($product) {
                $query->orderByRaw('CASE WHEN category_id = ? THEN 0 ELSE 1 END', [$product->category_id]);
            })
            ->latest()
            ->take(4)
            ->get();

        $waNumber = $siteSetting->whatsapp
            ? preg_replace('/\D/', '', $siteSetting->whatsapp)
            : null;

        $waText = urlencode('Halo, saya tertarik dengan produk "'.$product->nama.'".');

        $specifications = collect([
            'Kategori' => $product->category?->name,
            'Berat' => (float) $product->berat > 0 ? number_format((float) $product->berat, 2, ',', '.').' kg' : null,
            'Panjang' => (float) $product->panjang > 0 ? number_format((float) $product->panjang, 0, ',', '.').' cm' : null,
            'Lebar' => (float) $product->lebar > 0 ? number_format((float) $product->lebar, 0, ',', '.').' cm' : null,
            'Tinggi' => (float) $product->tinggi > 0 ? number_format((float) $product->tinggi, 0, ',', '.').' cm' : null,
            'Stok' => $product->stok >= 0 ? number_format((int) $product->stok, 0, ',', '.').' unit' : null,
        ])->filter(fn ($value) => filled($value));
    @endphp

    <body class="min-h-screen bg-white font-sans antialiased text-[#2A211B]">
        @include('partials.frontend.navbar')

        <main>
            {{-- =====================================================
                 BREADCRUMB
            ====================================================== --}}
            <section class="bg-white">
                <div class="mx-auto max-w-6xl px-5 pt-8 sm:px-8 sm:pt-10 lg:px-10">
                    <nav aria-label="Breadcrumb" class="text-[10px] text-[#9A8E82] sm:text-[11px]">
                        <a href="{{ route('home') }}" class="transition hover:text-admin-accent">Beranda</a>
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
                <div class="mx-auto max-w-6xl px-5 pb-12 pt-6 sm:px-8 sm:pb-14 sm:pt-8 lg:px-10 lg:pb-16">
                    <div class="grid grid-cols-1 items-start gap-9 lg:grid-cols-[minmax(0,1.05fr)_minmax(360px,0.95fr)] lg:gap-12 xl:gap-16">

                        {{-- ================= GALLERY ================= --}}
                        <div data-product-gallery class="min-w-0">
                            <div class="aspect-square w-full overflow-hidden rounded-[22px] bg-[#F7F8F6]">
                                @if ($thumbnailUrl)
                                    <img
                                        data-main-image
                                        src="{{ $thumbnailUrl }}"
                                        alt="{{ $product->nama }}"
                                        class="h-full w-full object-contain p-7 sm:p-10"
                                    >
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-[#C8BAA9]">
                                        <i class="fa-solid fa-couch text-6xl"></i>
                                    </div>
                                @endif
                            </div>

                            <div class="mt-3 grid grid-cols-4 gap-2.5 sm:gap-3">
                                @if ($thumbnailUrl)
                                    <button
                                        type="button"
                                        class="group aspect-square overflow-hidden rounded-md border border-[#F28A22] bg-[#F7F8F6] p-1.5"
                                        aria-label="Tampilkan foto utama"
                                        data-gallery-image="{{ $thumbnailUrl }}"
                                    >
                                        <img src="{{ $thumbnailUrl }}" alt="{{ $product->nama }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                                    </button>
                                @else
                                    <div class="aspect-square rounded-md border border-[#F28A22] bg-[#F7F8F6]"></div>
                                @endif

                                {{-- Database project saat ini hanya mempunyai satu field thumbnail.
                                     Tiga slot berikut menjaga komposisi gallery sesuai desain tanpa
                                     memalsukan gambar tambahan. --}}
                                <div class="aspect-square rounded-md bg-[#F7F8F6]"></div>
                                <div class="aspect-square rounded-md bg-[#F7F8F6]"></div>
                                <div class="aspect-square rounded-md bg-[#F7F8F6]"></div>
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
                                    {{ $reviewCount > 0 ? $reviewCount.' Customer Reviews' : 'Belum ada ulasan' }}
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

                            <div class="mt-5 flex items-center gap-2 text-[11px] text-admin-ink-soft">
                                <i class="fa-solid fa-box text-[10px] text-admin-ink-soft"></i>
                                <span>{{ $product->stok > 0 ? number_format((int) $product->stok, 0, ',', '.').' stok tersedia' : 'Stok habis' }}</span>
                            </div>

                            <div class="mt-7">
                                <label class="mb-2 block text-[10px] font-semibold text-[#2A211B]">Quantity:</label>
                                <div class="flex flex-wrap items-center gap-3">
                                    <div class="inline-flex h-10 items-center overflow-hidden rounded-md border border-[#E3DED7] bg-white">
                                        <button type="button" data-qty-minus class="flex h-full w-9 items-center justify-center text-[#7A6E63] transition hover:bg-[#F8F4EF]" aria-label="Kurangi jumlah">
                                            âˆ’
                                        </button>
                                        <span data-qty-value class="flex w-9 items-center justify-center text-xs font-medium text-[#2A211B]">1</span>
                                        <button type="button" data-qty-plus class="flex h-full w-9 items-center justify-center text-[#7A6E63] transition hover:bg-[#F8F4EF]" aria-label="Tambah jumlah">
                                            +
                                        </button>
                                    </div>

                                    {{-- Cart backend belum ada di repository. Tombol ini sudah disiapkan
                                         sebagai UI agar layout sama dengan desain, tanpa mengarang logic. --}}
                                    <button
                                        type="button"
                                        class="h-10 min-w-36.25 rounded-md bg-[#F28A22] px-5 text-[11px] font-semibold text-white transition hover:bg-[#D97612]"
                                        onclick="window.dispatchEvent(new CustomEvent('karya-ide-edi-cart-unavailable'))"
                                    >
                                        Add to Cart
                                    </button>
                                </div>
                            </div>

                            @if ($waNumber)
                                <a
                                    href="https://wa.me/{{ $waNumber }}?text={{ $waText }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="mt-3 flex h-10 w-full items-center justify-center gap-2 rounded-md bg-[#58B13F] px-5 text-[11px] font-semibold text-white transition hover:bg-[#489C32]"
                                >
                                    <i class="fa-brands fa-whatsapp"></i>
                                    WhatsApp
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            {{-- =====================================================
                 DESCRIPTION / SPECIFICATION / REVIEWS
            ====================================================== --}}
            <section class="bg-white">
                <div class="mx-auto max-w-6xl px-5 pb-14 sm:px-8 lg:px-10">
                    <div class="border-t border-[#EEEAE5]"></div>

                    <div data-product-tabs class="pt-5">
                        <div class="flex items-center gap-7 border-b border-[#F0ECE7] sm:gap-10" role="tablist" aria-label="Informasi produk">
                            <button
                                type="button"
                                data-tab="description"
                                data-tab-button="description"
                                class="border-b pb-3 text-[10px] font-medium transition sm:text-[11px]"
                            >
                                Description
                            </button>
                            <button
                                type="button"
                                data-tab="specification"
                                data-tab-button="specification"
                                class="border-b pb-3 text-[10px] font-medium transition sm:text-[11px]"
                            >
                                Specification
                            </button>
                            <button
                                type="button"
                                data-tab="reviews"
                                data-tab-button="reviews"
                                class="border-b pb-3 text-[10px] font-medium transition sm:text-[11px]"
                            >
                                Reviews ({{ $reviewCount }})
                            </button>
                        </div>

                        <div class="pt-7">
                            <div data-tab-panel="description">
                                <div class="max-w-3xl text-[10px] leading-[1.8] text-[#7A7067] sm:text-[11px]">
                                    @if ($product->deskripsi_lengkap)
                                        {!! nl2br(e($product->deskripsi_lengkap)) !!}
                                    @elseif ($product->deskripsi_pendek)
                                        {!! nl2br(e($product->deskripsi_pendek)) !!}
                                    @else
                                        <p>Belum ada deskripsi lengkap untuk produk ini.</p>
                                    @endif
                                </div>
                            </div>

                            <div data-tab-panel="specification" hidden>
                                @if ($specifications->isNotEmpty())
                                    <div class="grid max-w-2xl grid-cols-1 divide-y divide-[#F0ECE7] text-[10px] sm:grid-cols-2 sm:gap-x-10 sm:divide-y-0">
                                        @foreach ($specifications as $label => $value)
                                            <div class="flex items-center justify-between gap-5 border-b border-[#F0ECE7] py-3">
                                                <span class="text-[#9A8F85]">{{ $label }}</span>
                                                <span class="text-right font-medium text-[#3A3028]">{{ $value }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-[11px] text-[#9A8F85]">Belum ada spesifikasi untuk produk ini.</p>
                                @endif
                            </div>

                            <div data-tab-panel="reviews" hidden>
                                @if ($approvedTestimonials->isNotEmpty())
                                    <div class="space-y-4">
                                        @foreach ($approvedTestimonials as $review)
                                            <article class="rounded-lg border border-[#EEEAE5] p-4">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <div>
                                                        <p class="text-xs font-semibold text-[#3A3028]">{{ $review->customer_name ?? 'Pelanggan' }}</p>
                                                        <p class="mt-0.5 text-[9px] text-[#A29A92]">{{ optional($review->created_at)->format('d M Y') }}</p>
                                                    </div>
                                                    <div class="flex items-center gap-0.5 text-[#F0A321]">
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            <i class="fa-solid fa-star text-[9px] {{ (int) $review->rating < $i ? 'text-[#D9D4CD]' : '' }}"></i>
                                                        @endfor
                                                    </div>
                                                </div>
                                                <p class="mt-3 text-[10px] leading-6 text-admin-ink-soft">{{ $review->comment ?? '' }}</p>
                                            </article>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="rounded-lg border border-dashed border-[#E3DED7] px-5 py-8 text-center">
                                        <i class="fa-regular fa-star text-lg text-[#C8BAA9]"></i>
                                        <p class="mt-2 text-[11px] font-medium text-[#5E5349]">Belum ada ulasan</p>
                                        <p class="mt-1 text-[10px] text-[#A29A92]">Produk ini belum memiliki review yang disetujui.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- =====================================================
                 RELATED PRODUCTS
            ====================================================== --}}
            @if ($relatedProducts->isNotEmpty())
                <section class="bg-white">
                    <div class="mx-auto max-w-6xl px-5 pb-16 sm:px-8 lg:px-10 lg:pb-20">
                        <h2 class="text-base font-semibold text-[#2A211B] sm:text-lg">Related Products</h2>

                        <div class="mt-5 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-4 sm:gap-x-5">
                            @foreach ($relatedProducts as $related)
                                @php
                                    $relatedHasDiscount = $related->harga_diskon
                                        && (float) $related->harga_diskon > 0
                                        && (float) $related->harga_diskon < (float) $related->harga;

                                    $relatedPrice = $relatedHasDiscount ? (float) $related->harga_diskon : (float) $related->harga;

                                    $relatedImage = ($related->thumbnail && \Illuminate\Support\Facades\Storage::disk('public')->exists($related->thumbnail))
                                        ? \Illuminate\Support\Facades\Storage::disk('public')->url($related->thumbnail)
                                        : null;
                                @endphp

                                <a href="{{ route('products.show', $related) }}" class="group block min-w-0">
                                    <div class="aspect-square overflow-hidden rounded-md bg-[#F7F8F6]">
                                        @if ($relatedImage)
                                            <img
                                                src="{{ $relatedImage }}"
                                                alt="{{ $related->nama }}"
                                                loading="lazy"
                                                class="h-full w-full object-contain p-4 transition duration-500 group-hover:scale-[1.035]"
                                            >
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-[#C8BAA9]">
                                                <i class="fa-solid fa-couch text-3xl"></i>
                                            </div>
                                        @endif
                                    </div>

                                    <p class="mt-2 text-[8px] text-[#9A8F85]">{{ $related->category?->name ?? 'Furniture' }}</p>
                                    <h3 class="mt-0.5 line-clamp-2 text-[10px] font-semibold leading-4 text-[#2A211B] transition group-hover:text-admin-accent">
                                        {{ $related->nama }}
                                    </h3>
                                    <p class="mt-1 text-[10px] font-semibold text-[#F28A22]">
                                        Rp{{ number_format($relatedPrice, 0, ',', '.') }}
                                    </p>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        </main>

        @include('partials.frontend.footer')

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Product gallery
                const gallery = document.querySelector('[data-product-gallery]');
                if (gallery) {
                    const mainImage = gallery.querySelector('[data-main-image]');
                    gallery.querySelectorAll('[data-gallery-image]').forEach((button) => {
                        button.addEventListener('click', () => {
                            if (mainImage) {
                                mainImage.src = button.dataset.galleryImage;
                            }

                            gallery.querySelectorAll('[data-gallery-image]').forEach((item) => {
                                item.classList.remove('border-[#F28A22]');
                                item.classList.add('border-transparent');
                            });

                            button.classList.add('border-[#F28A22]');
                            button.classList.remove('border-transparent');
                        });
                    });
                }

                // Product tabs
                const tabs = document.querySelector('[data-product-tabs]');
                if (tabs) {
                    const buttons = tabs.querySelectorAll('[data-tab-button]');
                    const panels = tabs.querySelectorAll('[data-tab-panel]');

                    const activateTab = (name) => {
                        buttons.forEach((button) => {
                            const active = button.dataset.tabButton === name;
                            button.classList.toggle('border-[#F28A22]', active);
                            button.classList.toggle('text-[#F28A22]', active);
                            button.classList.toggle('border-transparent', !active);
                            button.classList.toggle('text-[#A29A92]', !active);
                        });

                        panels.forEach((panel) => {
                            panel.hidden = panel.dataset.tabPanel !== name;
                        });
                    };

                    buttons.forEach((button) => {
                        button.addEventListener('click', () => activateTab(button.dataset.tabButton));
                    });

                    activateTab('description');
                }

                // Quantity selector
                const minus = document.querySelector('[data-qty-minus]');
                const plus = document.querySelector('[data-qty-plus]');
                const value = document.querySelector('[data-qty-value]');

                if (minus && plus && value) {
                    let quantity = 1;
                    const stock = {{ max((int) $product->stok, 1) }};

                    minus.addEventListener('click', () => {
                        quantity = Math.max(1, quantity - 1);
                        value.textContent = quantity;
                    });

                    plus.addEventListener('click', () => {
                        quantity = Math.min(stock, quantity + 1);
                        value.textContent = quantity;
                    });
                }
            });
        </script>
    </body>
</html>


