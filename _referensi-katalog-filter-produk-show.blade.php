<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $product->nama }} — {{ \App\Models\Setting::current()->site_name }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    </head>
    <body class="min-h-screen bg-admin-canvas font-sans antialiased">

        @php
            $siteSetting = \App\Models\Setting::current();

            $hasDiscount = $product->harga_diskon && (float) $product->harga_diskon > 0;

            $thumbnailUrl = ($product->thumbnail && \Illuminate\Support\Facades\Storage::disk('public')->exists($product->thumbnail))
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->thumbnail)
                : null;

            $approvedTestimonials = $product->testimonials()->approved()->latest()->get();
            $averageRating = $approvedTestimonials->avg('rating');
            $reviewCount = $approvedTestimonials->count();
        @endphp

        @include('partials.frontend.navbar')

        <section class="bg-white">
            <div class="mx-auto max-w-7xl px-6 py-12 sm:px-8 lg:px-10 lg:py-16">

                <p class="mb-8 text-xs text-admin-ink-soft">
                    <a href="{{ route('home') }}" class="hover:text-admin-accent">Beranda</a>
                    <span class="mx-1.5">/</span>
                    <a href="{{ route('products.index') }}" class="hover:text-admin-accent">Produk</a>
                    <span class="mx-1.5">/</span>
                    {{ $product->nama }}
                </p>

                <div class="grid grid-cols-1 gap-10 lg:grid-cols-2 lg:gap-16">

                    {{-- ============ Foto produk ============ --}}
                    <div class="aspect-square w-full overflow-hidden rounded-3xl bg-admin-cream p-8">
                        @if ($thumbnailUrl)
                            <img src="{{ $thumbnailUrl }}" alt="{{ $product->nama }}" class="h-full w-full object-contain">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-admin-ink-soft/40">
                                <i class="fa-solid fa-couch text-6xl"></i>
                            </div>
                        @endif
                    </div>

                    {{-- ============ Info produk ============ --}}
                    <div>
                        @if ($product->category)
                            <p class="text-xs font-semibold uppercase tracking-wide text-admin-accent">{{ $product->category->name }}</p>
                        @endif

                        <h1 class="mt-2 font-display text-2xl text-[#4B3A26] sm:text-3xl">{{ $product->nama }}</h1>

                        <div class="mt-3 flex items-center gap-1.5">
                            @if ($reviewCount > 0)
                                <div class="flex items-center gap-0.5 text-admin-gold">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="fa-solid fa-star text-xs {{ $averageRating >= $i ? '' : 'text-admin-ink-soft/25' }}"></i>
                                    @endfor
                                </div>
                                <span class="text-xs text-admin-ink-soft">{{ number_format($averageRating, 1) }} ({{ $reviewCount }} ulasan)</span>
                            @else
                                <span class="text-xs text-admin-ink-soft/70">Belum ada ulasan</span>
                            @endif
                        </div>

                        <div class="mt-4 flex items-center gap-3">
                            @if ($hasDiscount)
                                <span class="text-2xl font-semibold text-red-600">Rp{{ number_format((float) $product->harga_diskon, 0, ',', '.') }}</span>
                                <span class="text-base text-admin-ink-soft/70 line-through">Rp{{ number_format((float) $product->harga, 0, ',', '.') }}</span>
                            @else
                                <span class="text-2xl font-semibold text-[#1A1A1A]">Rp{{ number_format((float) $product->harga, 0, ',', '.') }}</span>
                            @endif
                        </div>

                        <p class="mt-5 text-sm leading-relaxed text-admin-ink-soft">{{ $product->deskripsi_pendek }}</p>

                        @if ($product->deskripsi_lengkap)
                            <div class="mt-6 border-t border-admin-border pt-6">
                                <p class="text-sm font-semibold text-[#1A1A1A]">Deskripsi</p>
                                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-admin-ink-soft">{{ $product->deskripsi_lengkap }}</p>
                            </div>
                        @endif

                        <div class="mt-6 flex items-center gap-2 text-sm text-admin-ink-soft">
                            <i class="fa-solid fa-box"></i>
                            {{ $product->stok > 0 ? $product->stok.' stok tersedia' : 'Stok habis' }}
                        </div>

                        @if ($siteSetting->whatsapp)
                            <a
                                href="https://wa.me/{{ preg_replace('/\D/', '', $siteSetting->whatsapp) }}?text={{ urlencode('Halo, saya tertarik dengan produk "'.$product->nama.'".') }}"
                                target="_blank"
                                rel="noopener"
                                class="mt-8 inline-flex items-center gap-2 rounded-lg bg-[#1A1A1A] px-5 py-3 text-sm font-medium text-white shadow-sm transition-all duration-300 hover:bg-black hover:shadow-md"
                            >
                                <i class="fa-brands fa-whatsapp"></i>
                                Tanya Produk Ini
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        @include('partials.frontend.footer')
    </body>
</html>
