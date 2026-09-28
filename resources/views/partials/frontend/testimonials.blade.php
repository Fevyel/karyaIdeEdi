{{--
    ULASAN PELANGGAN KAMI
    Layout mengikuti sketsa user:
    kiri utama = profil, nama, bintang, komentar, 2 foto kecil
    kanan = alamat (provinsi + kabupaten/kota)
--}}

@php
    $testimonialList = \App\Models\Testimonial::query()
        ->approved()
        ->active()
        ->featuredHome()
        ->topLevel()
        ->with('product:id,nama')
        ->orderBy('urutan')
        ->latest()
        ->take(3)
        ->get();

    $testimoniSection = \App\Models\HomeSection::dataFor('testimoni', [
        'bg_color' => null,
        'card_colors' => [1 => null, 2 => null, 3 => null],
    ]);

    $testimoniFrame = \App\Support\FrameBackground::resolve(
        $testimoniSection['bg_color'] ?? null,
        $testimoniSection['bg_gradient'] ?? null,
        '#FAF8F4'
    );

    $testimoniBgColor = $testimoniFrame['base'];

    $contrastTestimoniHeading = function (string $hex): string {
        $hex = ltrim($hex, '#');

        if (strtoupper($hex) === 'FAF8F4') {
            return '#221A14';
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $linearize = fn (float $c): float => $c <= 0.03928
            ? $c / 12.92
            : (($c + 0.055) / 1.055) ** 2.4;

        $luminance =
            0.2126 * $linearize($r) +
            0.7152 * $linearize($g) +
            0.0722 * $linearize($b);

        return $luminance > 0.5 ? '#1A1208' : '#FFFFFF';
    };

    $testimoniHeadingColor = $contrastTestimoniHeading($testimoniBgColor);
    $testimoniCardColors = $testimoniSection['card_colors'] ?? [1 => null, 2 => null, 3 => null];
@endphp

<style>
    .kie-testi-wrap {
        width: min(100%, 1040px);
        margin-inline: auto;
        display: grid;
        gap: 16px;
    }

    .kie-testi-card {
        width: 100%;
        overflow: hidden;
        border-radius: 22px;
        border: 1px solid rgba(42, 33, 27, .08);
        box-shadow: 0 12px 30px rgba(25, 19, 15, .08);
    }

    .kie-testi-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 230px;
        min-height: 205px;
    }

    .kie-testi-main {
        min-width: 0;
        padding: 20px 22px 18px;
        display: flex;
        flex-direction: column;
    }

    .kie-testi-address {
        min-width: 0;
        padding: 20px;
        border-left: 1px solid rgba(255,255,255,.11);
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .kie-testi-card.is-light .kie-testi-address {
        border-left-color: rgba(42,33,27,.10);
    }

    .kie-testi-profile {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .kie-testi-avatar,
    .kie-testi-avatar-fallback {
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        border-radius: 999px;
    }

    .kie-testi-avatar {
        object-fit: cover;
    }

    .kie-testi-avatar-fallback {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
    }

    .kie-testi-name {
        min-width: 0;
        font-size: 14px;
        line-height: 1.2;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .kie-testi-stars {
        margin-top: 10px;
        display: flex;
        gap: 3px;
        font-size: 11px;
    }

    .kie-testi-comment {
        margin-top: 12px;
    }

    .kie-testi-comment i {
        font-size: 12px;
        opacity: .42;
    }

    .kie-testi-comment p {
        margin-top: 5px;
        font-size: 13px;
        line-height: 1.55;
        overflow-wrap: anywhere;
    }

    .kie-testi-photos {
        margin-top: auto;
        padding-top: 14px;
        display: flex;
        gap: 9px;
    }

    .kie-testi-photo {
        width: 74px;
        height: 56px;
        flex: 0 0 74px;
        border-radius: 11px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.12);
        background: rgba(255,255,255,.055);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .kie-testi-card.is-light .kie-testi-photo {
        border-color: rgba(42,33,27,.11);
        background: rgba(255,255,255,.68);
    }

    .kie-testi-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .kie-testi-photo i {
        font-size: 14px;
        opacity: .30;
    }

    .kie-testi-address-title {
        font-size: 10px;
        line-height: 1;
        font-weight: 800;
        letter-spacing: .18em;
        text-transform: uppercase;
        opacity: .50;
    }

    .kie-testi-address-list {
        margin-top: 14px;
        display: grid;
        gap: 13px;
    }

    .kie-testi-address-label {
        font-size: 9px;
        line-height: 1.1;
        letter-spacing: .12em;
        text-transform: uppercase;
        opacity: .45;
    }

    .kie-testi-address-value {
        margin-top: 4px;
        font-size: 12px;
        line-height: 1.35;
        font-weight: 650;
        overflow-wrap: anywhere;
    }

    .kie-testi-address-value.is-empty {
        font-weight: 500;
        opacity: .50;
        font-style: italic;
    }

    @media (max-width: 767.98px) {
        .kie-testi-wrap {
            gap: 12px;
        }

        .kie-testi-card {
            border-radius: 17px;
        }

        .kie-testi-layout {
            grid-template-columns: 1fr;
            min-height: 0;
        }

        .kie-testi-main {
            padding: 15px;
        }

        .kie-testi-address {
            padding: 12px 15px 14px;
            border-left: 0;
            border-top: 1px solid rgba(255,255,255,.11);
        }

        .kie-testi-card.is-light .kie-testi-address {
            border-top-color: rgba(42,33,27,.10);
        }

        .kie-testi-address-list {
            margin-top: 9px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .kie-testi-avatar,
        .kie-testi-avatar-fallback {
            width: 36px;
            height: 36px;
            flex-basis: 36px;
        }

        .kie-testi-comment p {
            font-size: 12px;
        }

        .kie-testi-photos {
            padding-top: 11px;
            gap: 7px;
        }

        .kie-testi-photo {
            width: 64px;
            height: 48px;
            flex-basis: 64px;
        }
    }
</style>

<section
    class="relative overflow-hidden"
    style="background: {{ $testimoniFrame['css'] }};"
>
    <div class="relative mx-auto max-w-7xl px-6 py-10 sm:px-8 lg:px-10 lg:py-14">
        <h2
            class="font-display text-2xl sm:text-3xl"
            style="color: {{ $testimoniHeadingColor }};"
        >
            Ulasan Pelanggan Kami
        </h2>

        @if ($testimonialList->isEmpty())
            <div class="mt-7 flex flex-col items-center justify-center rounded-3xl border border-dashed border-admin-border bg-admin-surface px-6 py-10 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-admin-cream">
                    <i class="fa-solid fa-quote-left text-lg text-admin-accent"></i>
                </span>
                <p class="mt-4 text-sm font-semibold text-admin-ink">Belum ada ulasan</p>
            </div>
        @else
            <div class="kie-testi-wrap mt-7">
                @foreach ($testimonialList as $index => $testimonial)
                    @php
                        $rank = $index + 1;
                        $customCardColor = $testimoniCardColors[$rank] ?? null;

                        if ($customCardColor) {
                            $hex = ltrim($customCardColor, '#');
                            $r = hexdec(substr($hex, 0, 2)) / 255;
                            $g = hexdec(substr($hex, 2, 2)) / 255;
                            $b = hexdec(substr($hex, 4, 2)) / 255;

                            $linearize = fn (float $c): float => $c <= 0.03928
                                ? $c / 12.92
                                : (($c + 0.055) / 1.055) ** 2.4;

                            $luminance =
                                0.2126 * $linearize($r) +
                                0.7152 * $linearize($g) +
                                0.0722 * $linearize($b);

                            $isDark = $luminance <= 0.5;
                        } else {
                            $isDark = $index % 3 !== 1;
                        }

                        $testimonialPhotos = collect($testimonial->photos ?? [])
                            ->filter()
                            ->take(5)
                            ->values();

                        $cardBackground = $customCardColor
                            ?: ($isDark ? '#2E1B10' : '#F4EEE5');

                        $cardText = $isDark ? '#FFFFFF' : '#2A211B';
                    @endphp

                    <article
                        class="kie-testi-card {{ $isDark ? 'is-dark' : 'is-light' }}"
                        style="background: {{ $cardBackground }}; color: {{ $cardText }};"
                    >
                        <div class="kie-testi-layout">
                            <div class="kie-testi-main">
                                <div class="kie-testi-profile">
                                    @if ($testimonial->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($testimonial->foto))
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($testimonial->foto) }}"
                                            alt="{{ $testimonial->displayName() }}"
                                            class="kie-testi-avatar"
                                        >
                                    @else
                                        <span
                                            class="kie-testi-avatar-fallback"
                                            style="background: {{ $isDark ? 'rgba(255,255,255,.10)' : '#FFFFFF' }};"
                                        >
                                            {{ strtoupper(mb_substr($testimonial->displayName(), 0, 1)) }}
                                        </span>
                                    @endif

                                    <p class="kie-testi-name">
                                        {{ $testimonial->displayName() }}
                                    </p>
                                </div>

                                <div class="kie-testi-stars text-admin-gold">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i
                                            class="fa-solid fa-star"
                                            style="opacity: {{ $i <= ($testimonial->rating ?? 0) ? '1' : '.22' }};"
                                        ></i>
                                    @endfor
                                </div>

                                <div class="kie-testi-comment">
                                    <i class="fa-solid fa-quote-left"></i>
                                    <p>"{{ $testimonial->comment }}"</p>
                                </div>

                                @php
                                    $photoCount = $testimonialPhotos->count();
                                    $remainingPhotoSlots = max(5 - $photoCount, 0);
                                    $blurSeed = $testimonialPhotos->last();
                                @endphp

                                @if ($photoCount > 0)
                                    <div class="kie-testi-photos">
                                        @foreach ($testimonialPhotos as $photo)
                                            <a
                                                href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo) }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="kie-testi-photo"
                                                aria-label="Lihat foto testimoni"
                                            >
                                                <img
                                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo) }}"
                                                    alt="Foto dari {{ $testimonial->displayName() }}"
                                                >
                                            </a>
                                        @endforeach

                                        @if ($remainingPhotoSlots > 0)
                                            <div class="kie-testi-photo kie-testi-photo-more" aria-label="{{ $remainingPhotoSlots }} slot foto lagi">
                                                @if ($blurSeed)
                                                    <img
                                                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($blurSeed) }}"
                                                        alt=""
                                                        aria-hidden="true"
                                                        class="kie-testi-photo-more-bg"
                                                    >
                                                @endif
                                                <span class="kie-testi-photo-more-shade" aria-hidden="true"></span>
                                                <span class="kie-testi-photo-more-count">{{ $remainingPhotoSlots }}+</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <aside class="kie-testi-address">
                                <p class="kie-testi-address-title">Alamat</p>

                                <div class="kie-testi-address-list">
                                    <div>
                                        <p class="kie-testi-address-label">Provinsi</p>

                                        @if (filled($testimonial->provinsi))
                                            <p class="kie-testi-address-value">
                                                {{ $testimonial->provinsi }}
                                            </p>
                                        @else
                                            <p class="kie-testi-address-value is-empty">
                                                Belum diisi
                                            </p>
                                        @endif
                                    </div>

                                    <div>
                                        <p class="kie-testi-address-label">Kabupaten / Kota</p>

                                        @if (filled($testimonial->kabupaten))
                                            <p class="kie-testi-address-value">
                                                {{ $testimonial->kabupaten }}
                                            </p>
                                        @else
                                            <p class="kie-testi-address-value is-empty">
                                                Belum diisi
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </aside>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- KIE-TESTIMONIAL-3-COLUMNS:START --}}
<style>
    /*
     * Desktop: 3 testimoni dalam 1 baris.
     * Card dibuat lebih compact agar proporsional.
     */
    .kie-testi-wrap {
        width: 100% !important;
        max-width: none !important;
        margin-inline: 0 !important;

        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        gap: 16px !important;
    }

    .kie-testi-card {
        min-width: 0 !important;
        height: 100% !important;
        border-radius: 20px !important;
    }

    .kie-testi-layout {
        display: grid !important;
        grid-template-columns: 1fr !important;
        min-height: 0 !important;
        height: 100% !important;
    }

    /*
     * Isi utama di atas.
     */
    .kie-testi-main {
        min-width: 0 !important;
        padding: 18px 18px 14px !important;
    }

    /*
     * Alamat pindah ke bagian bawah kartu agar card tidak terlalu lebar.
     */
    .kie-testi-address {
        min-width: 0 !important;
        padding: 12px 18px 16px !important;

        border-left: 0 !important;
        border-top: 1px solid rgba(255,255,255,.11) !important;
    }

    .kie-testi-card.is-light .kie-testi-address {
        border-top-color: rgba(42,33,27,.10) !important;
    }

    .kie-testi-profile {
        gap: 9px !important;
    }

    .kie-testi-avatar,
    .kie-testi-avatar-fallback {
        width: 36px !important;
        height: 36px !important;
        flex-basis: 36px !important;
    }

    .kie-testi-name {
        font-size: 13px !important;
    }

    .kie-testi-stars {
        margin-top: 9px !important;
        font-size: 10px !important;
    }

    .kie-testi-comment {
        margin-top: 10px !important;
    }

    .kie-testi-comment p {
        margin-top: 4px !important;
        font-size: 12px !important;
        line-height: 1.5 !important;
    }

    .kie-testi-photos {
        margin-top: 12px !important;
        padding-top: 0 !important;
        gap: 7px !important;
    }

    .kie-testi-photo {
        width: 58px !important;
        height: 44px !important;
        flex-basis: 58px !important;
        border-radius: 9px !important;
    }

    .kie-testi-address-title {
        font-size: 9px !important;
        letter-spacing: .15em !important;
    }

    .kie-testi-address-list {
        margin-top: 9px !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 10px !important;
    }

    .kie-testi-address-label {
        font-size: 8px !important;
    }

    .kie-testi-address-value {
        margin-top: 3px !important;
        font-size: 10.5px !important;
        line-height: 1.3 !important;
    }

    /*
     * Tablet: 2 kolom.
     */
    @media (max-width: 1023.98px) {
        .kie-testi-wrap {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 14px !important;
        }
    }

    /*
     * Mobile: 1 kolom.
     */
    @media (max-width: 639.98px) {
        .kie-testi-wrap {
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 12px !important;
        }

        .kie-testi-main {
            padding: 15px 15px 12px !important;
        }

        .kie-testi-address {
            padding: 11px 15px 13px !important;
        }

        .kie-testi-photo {
            width: 54px !important;
            height: 41px !important;
            flex-basis: 54px !important;
        }
    }
</style>
{{-- KIE-TESTIMONIAL-3-COLUMNS:END --}}


{{-- KIE-TESTIMONIAL-REQUESTED-FIX:START --}}
<style>
    /*
     * Alamat harus DI SAMPING, bukan di bawah.
     * 3 kartu per baris yang sudah ada tetap dipertahankan.
     */
    @media (min-width: 640px) {
        .kie-testi-layout {
            grid-template-columns: minmax(0, 1fr) 112px !important;
            align-items: stretch !important;
        }

        .kie-testi-address {
            padding: 14px 11px !important;
            border-top: 0 !important;
            border-left: 1px solid rgba(255,255,255,.11) !important;
        }

        .kie-testi-card.is-light .kie-testi-address {
            border-top-color: transparent !important;
            border-left-color: rgba(42,33,27,.10) !important;
        }

        .kie-testi-address-list {
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 11px !important;
        }

        .kie-testi-address-title {
            font-size: 8px !important;
        }

        .kie-testi-address-label {
            font-size: 7px !important;
        }

        .kie-testi-address-value {
            font-size: 9.5px !important;
            line-height: 1.25 !important;
        }
    }

    /*
     * Tidak ada foto = .kie-testi-photos memang tidak dirender.
     * Jadi tidak ada placeholder kosong.
     */
    .kie-testi-photo-more {
        position: relative !important;
        isolation: isolate !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .kie-testi-photo-more-bg {
        position: absolute !important;
        inset: -10% !important;
        z-index: 0 !important;
        width: 120% !important;
        height: 120% !important;
        object-fit: cover !important;
        filter: blur(6px) !important;
        transform: scale(1.08) !important;
        opacity: .72 !important;
        pointer-events: none !important;
    }

    .kie-testi-photo-more-shade {
        position: absolute !important;
        inset: 0 !important;
        z-index: 1 !important;
        background: rgba(18, 13, 10, .42) !important;
        backdrop-filter: blur(2px) !important;
        pointer-events: none !important;
    }

    .kie-testi-photo-more-count {
        position: relative !important;
        z-index: 2 !important;
        color: #fff !important;
        font-size: 17px !important;
        line-height: 1 !important;
        font-weight: 800 !important;
        letter-spacing: .01em !important;
        text-shadow: 0 1px 6px rgba(0,0,0,.35) !important;
    }
</style>
{{-- KIE-TESTIMONIAL-REQUESTED-FIX:END --}}

{{-- KIE-TESTIMONIAL-MOBILE-HORIZONTAL-SLIDE:START --}}
<style>
    /*
     * HANYA MOBILE:
     * testimoni jadi carousel horizontal / swipe ke samping.
     * Desktop dan tablet besar tetap memakai layout existing.
     */
    @media (max-width: 639.98px) {
        .kie-testi-wrap {
            display: flex !important;
            grid-template-columns: none !important;

            width: 100% !important;
            max-width: 100% !important;

            gap: .8rem !important;

            overflow-x: auto !important;
            overflow-y: hidden !important;

            scroll-snap-type: x mandatory !important;
            scroll-padding-left: 0 !important;
            overscroll-behavior-x: contain !important;
            -webkit-overflow-scrolling: touch !important;

            padding-bottom: .55rem !important;

            scrollbar-width: none !important;
        }

        .kie-testi-wrap::-webkit-scrollbar {
            display: none !important;
        }

        .kie-testi-card {
            flex: 0 0 88% !important;
            width: 88% !important;
            min-width: 88% !important;
            max-width: 88% !important;

            scroll-snap-align: start !important;
            scroll-snap-stop: always !important;

            margin: 0 !important;
        }
    }

    /*
     * HP sangat kecil: sedikit lebih lebar supaya isi tidak sesak.
     */
    @media (max-width: 359.98px) {
        .kie-testi-card {
            flex-basis: 92% !important;
            width: 92% !important;
            min-width: 92% !important;
            max-width: 92% !important;
        }
    }
</style>
{{-- KIE-TESTIMONIAL-MOBILE-HORIZONTAL-SLIDE:END --}}

{{-- KIE-TESTIMONIAL-MOBILE-ADDRESS-SIDE:START --}}
<style>
    /*
     * MOBILE:
     * alamat HARUS tetap di SAMPING kanan,
     * bukan turun ke bawah.
     *
     * Slider horizontal existing tetap dipakai.
     */
    @media (max-width: 639.98px) {
        .kie-testi-layout {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) 104px !important;
            align-items: stretch !important;
            min-height: 0 !important;
        }

        .kie-testi-main {
            min-width: 0 !important;
            padding: 15px 13px 14px !important;
        }

        .kie-testi-address {
            min-width: 0 !important;
            width: auto !important;
            max-width: none !important;

            padding: 14px 10px !important;

            border-top: 0 !important;
            border-left: 1px solid rgba(255,255,255,.11) !important;

            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
        }

        .kie-testi-card.is-light .kie-testi-address {
            border-top-color: transparent !important;
            border-left-color: rgba(42,33,27,.10) !important;
        }

        .kie-testi-address-list {
            margin-top: 9px !important;
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 10px !important;
        }

        .kie-testi-address-title {
            font-size: 8px !important;
            letter-spacing: .13em !important;
        }

        .kie-testi-address-label {
            font-size: 7px !important;
            letter-spacing: .08em !important;
        }

        .kie-testi-address-value {
            margin-top: 2px !important;
            font-size: 9.5px !important;
            line-height: 1.25 !important;
            overflow-wrap: anywhere !important;
        }

        /*
         * Sedikit rapikan isi kiri supaya tetap muat
         * tanpa mengubah struktur atau fungsi.
         */
        .kie-testi-avatar,
        .kie-testi-avatar-fallback {
            width: 34px !important;
            height: 34px !important;
            flex-basis: 34px !important;
        }

        .kie-testi-name {
            font-size: 12.5px !important;
        }

        .kie-testi-comment p {
            font-size: 11.5px !important;
            line-height: 1.5 !important;
        }
    }

    @media (max-width: 359.98px) {
        .kie-testi-layout {
            grid-template-columns: minmax(0, 1fr) 94px !important;
        }

        .kie-testi-address {
            padding-left: 8px !important;
            padding-right: 8px !important;
        }

        .kie-testi-address-value {
            font-size: 9px !important;
        }
    }
</style>
{{-- KIE-TESTIMONIAL-MOBILE-ADDRESS-SIDE:END --}}
