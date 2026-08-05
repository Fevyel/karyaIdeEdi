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
        ->orderBy('urutan')
        ->latest()
        ->take(3)
        ->get();
@endphp

<section class="bg-admin-canvas">
    <div class="mx-auto max-w-7xl px-6 py-14 sm:px-8 lg:px-10 lg:py-20">

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
                    @php $isDark = $index % 3 !== 1; @endphp
                    <div
                        x-data="{ shown: false }"
                        x-init="new IntersectionObserver((entries) => { if (entries[0].isIntersecting) { setTimeout(() => shown = true, {{ ($index % 3) * 100 }}); } }, { threshold: 0.15 }).observe($el)"
                        :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        class="flex flex-col rounded-3xl p-6 shadow-sm transition-all duration-500 ease-out hover:-translate-y-1.5 hover:shadow-xl
                            {{ $isDark ? 'bg-admin-panel text-white' : 'bg-admin-cream text-admin-ink' }}"
                    >
                        <i class="fa-solid fa-quote-left text-lg {{ $isDark ? 'text-white/40' : 'text-admin-accent/50' }}"></i>

                        <p class="mt-4 flex-1 text-sm leading-relaxed {{ $isDark ? 'text-white/85' : 'text-admin-ink-soft' }}">
                            "{{ $testimonial->comment }}"
                        </p>

                        <div class="mt-5 border-t {{ $isDark ? 'border-white/15' : 'border-admin-border' }}"></div>

                        <div class="mt-4 flex items-center gap-3">
                            @if ($testimonial->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($testimonial->foto))
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($testimonial->foto) }}"
                                    alt="{{ $testimonial->customer_name }}"
                                    class="h-11 w-11 shrink-0 rounded-full object-cover"
                                >
                            @else
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-semibold
                                    {{ $isDark ? 'bg-white/10 text-white' : 'bg-white text-admin-accent' }}">
                                    {{ strtoupper(substr($testimonial->customer_name, 0, 1)) }}
                                </span>
                            @endif

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold {{ $isDark ? 'text-white' : 'text-admin-ink' }}">
                                    {{ $testimonial->customer_name }}
                                </p>
                                @if ($testimonial->jabatan)
                                    <p class="truncate text-[11px] uppercase tracking-wide {{ $isDark ? 'text-white/50' : 'text-admin-ink-soft' }}">
                                        {{ $testimonial->jabatan }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex shrink-0 items-center gap-0.5 text-admin-gold">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="fa-solid fa-star text-[11px] {{ $i > ($testimonial->rating ?? 0) ? ($isDark ? 'text-white/20' : 'text-admin-border') : '' }}"></i>
                                @endfor
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
