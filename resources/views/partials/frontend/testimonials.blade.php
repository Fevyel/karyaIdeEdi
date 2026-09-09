{{--
    ==========================================================
    ULASAN PELANGGAN KAMI — Homepage Karya Ide Edi
    ==========================================================
    Menampilkan MAKSIMAL 3 komentar yang DIPILIH SENDIRI oleh admin
    (is_featured_home = true) lewat menu "Testimoni" — bukan otomatis
    yang terbaru. Tetap disaring approval_status = 'approved' DAN
    is_active = true (jaga-jaga kalau ada testimoni yang ditarik/
    dinonaktifkan setelah dipilih tampil di beranda).

    Tabel yang sama diisi dari 2 jalur: komentar (untuk sekarang:
    dummy — lihat TestimonialSeeder) yang disetujui admin lewat
    menu "Interaksi", ATAU testimoni yang dibuat langsung admin
    lewat menu "Testimoni" (approval_status otomatis 'approved').
    Section ini tidak peduli asalnya, cukup baca yang dipilih admin
    untuk tampil. Rating bintang dibaca dari kolom `rating` — tidak
    ada yang di-hardcode 5 bintang.

    Semua warna pakai token admin-* (ikut tema Glow/Dark yang
    sudah ada), TIDAK ada warna hardcode.

    Pemakaian:
        @include('partials.frontend.testimonials')
    ==========================================================
--}}

@php
    $testimonialList = \App\Models\Testimonial::query()
        ->approved()
        ->active()
        ->featuredHome()
        ->with('product:id,nama')
        ->orderBy('urutan')
        ->latest()
        ->take(3)
        ->get();

    // Warna latar section & warna kartu "Top 3 Komentar" bisa diatur admin
    // lewat Edit Web > Testimoni Pelanggan (lihat pages/admin/edit-web.blade.php)
    // -- data testimoninya sendiri TIDAK diedit dari sana, tetap dipilih
    // lewat menu Testimoni seperti biasa. Data warna tersimpan di
    // home_sections, section_key 'testimoni'. Pola sama dengan
    // partials/frontend/products.blade.php & categories.blade.php.
    $testimoniSection = \App\Models\HomeSection::dataFor('testimoni', [
        'bg_color' => null,
        'card_colors' => [1 => null, 2 => null, 3 => null],
    ]);
    $testimoniBgColor = $testimoniSection['bg_color'] ?: '#FAF8F4';

    // Warna kartu per posisi (Top 1/2/3), masing-masing independen & opsional.
    // null di posisi tertentu = admin belum pilih warna khusus utk kartu itu
    // -> kartu itu pakai desain bawaan (bergantian gelap/krem berdasarkan
    // posisi, seperti sebelumnya). Posisi lain yang sudah dipilih tetap
    // pakai warnanya masing-masing, tidak saling mempengaruhi.
    $testimoniCardColors = $testimoniSection['card_colors'] ?? [1 => null, 2 => null, 3 => null];
@endphp

<section
    class="relative overflow-hidden"
    style="background: linear-gradient(135deg, color-mix(in oklab, {{ $testimoniBgColor }} 100%, white 10%) 0%, {{ $testimoniBgColor }} 55%, color-mix(in oklab, {{ $testimoniBgColor }} 100%, black 14%) 100%);"
>
    {{-- Glow & tekstur tipis, pola sama dengan section Produk Unggulan & Kategori Produk. --}}
    <div
        class="pointer-events-none absolute -right-24 -top-32 h-105 w-105 rounded-full blur-3xl"
        style="background: color-mix(in oklab, {{ $testimoniBgColor }} 100%, white 60%); opacity: 0.4;"
    ></div>
    <div
        class="pointer-events-none absolute inset-0 opacity-[0.05] mix-blend-overlay"
        style="background-image: url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22140%22 height=%22140%22%3E%3Cfilter id=%22n%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.85%22 numOctaves=%222%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22140%22 height=%22140%22 filter=%22url(%23n)%22/%3E%3C/svg%3E');"
    ></div>

    <div class="relative mx-auto max-w-7xl px-6 py-14 sm:px-8 lg:px-10 lg:py-20">

        <h2 class="font-display text-2xl text-admin-ink sm:text-3xl">Ulasan Pelanggan Kami</h2>

        @if ($testimonialList->isEmpty())
            {{-- Empty state — tetap rapi selagi belum ada testimoni --}}
            <div class="mt-10 flex flex-col items-center justify-center rounded-3xl border border-dashed border-admin-border bg-admin-surface px-6 py-16 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-admin-cream">
                    <i class="fa-solid fa-quote-left text-xl text-admin-accent"></i>
                </span>
                <p class="mt-5 text-sm font-semibold text-admin-ink">Belum ada ulasan</p>
                <p class="mt-1.5 max-w-sm text-xs leading-relaxed text-admin-ink-soft">
                    Testimoni dari pelanggan akan tampil di sini begitu tersedia.
                </p>
            </div>
        @else
            <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($testimonialList as $index => $testimonial)
                    @php
                        // Rank 1 = kartu pertama (Top 1), dst -- cocok dengan urutan
                        // Top 1/2/3 di form Edit Web.
                        $rank = $index + 1;
                        $customCardColor = $testimoniCardColors[$rank] ?? null;

                        if ($customCardColor) {
                            $hex = ltrim($customCardColor, '#');
                            $r = hexdec(substr($hex, 0, 2)) / 255;
                            $g = hexdec(substr($hex, 2, 2)) / 255;
                            $b = hexdec(substr($hex, 4, 2)) / 255;
                            $linearize = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
                            $luminance = 0.2126 * $linearize($r) + 0.7152 * $linearize($g) + 0.0722 * $linearize($b);
                            $isDark = $luminance <= 0.5;
                        } else {
                            // Fallback: pola bawaan bergantian gelap/krem berdasarkan posisi.
                            $isDark = $index % 3 !== 1;
                        }
                    @endphp
                    <div
                        x-data="{ shown: false }"
                        x-init="new IntersectionObserver((entries) => { if (entries[0].isIntersecting) { setTimeout(() => shown = true, {{ ($index % 3) * 100 }}); } }, { threshold: 0.15 }).observe($el)"
                        :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        class="flex flex-col rounded-3xl p-6 shadow-sm transition-all duration-500 ease-out hover:-translate-y-1.5 hover:shadow-xl
                            {{ $customCardColor
                                ? ($isDark ? 'text-white' : 'text-admin-ink')
                                : ($isDark ? 'bg-admin-panel text-white' : 'bg-admin-cream text-admin-ink') }}"
                        @if ($customCardColor) style="background: {{ $customCardColor }};" @endif
                    >
                        {{-- 1. Nama pembeli (+ foto profil & rating di baris yang sama) --}}
                        <div class="flex items-center gap-3">
                            @if ($testimonial->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($testimonial->foto))
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($testimonial->foto) }}"
                                    alt="{{ $testimonial->displayName() }}"
                                    class="h-11 w-11 shrink-0 rounded-full object-cover"
                                >
                            @else
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-semibold
                                    {{ $isDark ? 'bg-white/10 text-white' : 'bg-white text-admin-accent' }}">
                                    {{ strtoupper(substr($testimonial->displayName(), 0, 1)) }}
                                </span>
                            @endif

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold {{ $isDark ? 'text-white' : 'text-admin-ink' }}">
                                    {{ $testimonial->displayName() }}
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-0.5 text-admin-gold">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="fa-solid fa-star text-[11px] {{ $i > ($testimonial->rating ?? 0) ? ($isDark ? 'text-white/20' : 'text-admin-border') : '' }}"></i>
                                @endfor
                            </div>
                        </div>

                        {{-- 2. Produk yang dibeli --}}
                        @if ($testimonial->product)
                            <div class="mt-3 inline-flex w-fit items-center gap-1.5 rounded-full px-3 py-1 text-[10px] font-medium
                                {{ $isDark ? 'bg-white/10 text-white/70' : 'bg-white text-admin-ink-soft' }}">
                                <i class="fa-solid fa-box text-[9px]"></i>
                                {{ $testimonial->product->nama }}
                            </div>
                        @endif

                        {{-- 3. Foto yang dikirim pembeli (kalau ada) — kalau tidak ada, langsung ke komentar --}}
                        @if (! empty($testimonial->photos))
                            <div class="mt-3 flex gap-2 overflow-x-auto">
                                @foreach ($testimonial->photos as $photo)
                                    <img
                                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo) }}"
                                        alt="Foto dari {{ $testimonial->displayName() }}"
                                        class="h-14 w-14 shrink-0 rounded-lg object-cover"
                                    >
                                @endforeach
                            </div>
                        @endif

                        {{-- 4. Komentar --}}
                        <div class="mt-4 flex-1">
                            <i class="fa-solid fa-quote-left text-lg {{ $isDark ? 'text-white/40' : 'text-admin-accent/50' }}"></i>
                            <p class="mt-2 text-sm leading-relaxed {{ $isDark ? 'text-white/85' : 'text-admin-ink-soft' }}">
                                "{{ $testimonial->comment }}"
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>