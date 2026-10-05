<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    @include('partials.favicon')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dokumentasi | {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

{{-- KIE-DOKUMENTASI-HERO-RESPONSIVE:START --}}
<style>
    @media (max-width: 1023.98px) {
        html[data-site='frontend'] [data-kie-dokumentasi-desktop-hero] {
            display: none !important;
        }

        html[data-site='frontend'] [data-kie-dokumentasi-mobile-hero] {
            display: block !important;
            position: relative !important;
            min-height: clamp(26rem, 62svh, 33rem) !important;
            overflow: hidden !important;
            isolation: isolate !important;
            margin-bottom: -2px !important;
            border: 0 !important;
            outline: 0 !important;
            box-shadow: none !important;
        }

        html[data-site='frontend'] [data-kie-dokumentasi-mobile-media] {
            position: absolute !important;
            inset: 0 !important;
            width: 100% !important;
            height: 100% !important;
            overflow: hidden !important;
        }

        html[data-site='frontend'] [data-kie-dokumentasi-mobile-media] video,
        html[data-site='frontend'] [data-kie-dokumentasi-mobile-media] iframe {
            display: block !important;
            width: 100% !important;
            height: 100% !important;
            min-width: 100% !important;
            min-height: 100% !important;

            object-fit: cover !important;
            object-position: center 45% !important;
            border: 0 !important;

            transform: scale(1.045) !important;

            filter:
                saturate(.80)
                contrast(1.08)
                brightness(.84)
                sepia(.035) !important;
        }

        /*
         * Color grade premium: warm, dalam, tapi objek kerja tetap terlihat.
         */
        html[data-site='frontend'] [data-kie-doc-grade] {
            background:
                linear-gradient(
                    180deg,
                    rgba(18,11,7,.26) 0%,
                    rgba(18,11,7,.04) 24%,
                    rgba(18,11,7,.02) 49%,
                    rgba(18,11,7,.14) 72%,
                    transparent 100%
                ),
                linear-gradient(
                    90deg,
                    rgba(18,11,7,.14) 0%,
                    transparent 24%,
                    transparent 76%,
                    rgba(18,11,7,.13) 100%
                ) !important;
        }

        /*
         * Spotlight hanya berupa cahaya/gelap lembut, bukan card.
         */
        html[data-site='frontend'] [data-kie-doc-spotlight] {
            background:
                radial-gradient(
                    ellipse at 50% 43%,
                    rgba(7,4,3,.36) 0%,
                    rgba(7,4,3,.20) 20%,
                    rgba(7,4,3,.06) 42%,
                    transparent 64%
                ) !important;
        }

        /*
         * Fade putih bawah panjang dan gradual.
         */
        html[data-site='frontend'] [data-kie-doc-bottom-fade] {
            background:
                linear-gradient(
                    180deg,
                    transparent 0%,
                    transparent 48%,
                    rgba(255,255,255,.025) 55%,
                    rgba(255,255,255,.08) 62%,
                    rgba(255,255,255,.18) 69%,
                    rgba(255,255,255,.35) 76%,
                    rgba(255,255,255,.56) 83%,
                    rgba(255,255,255,.75) 89%,
                    rgba(255,255,255,.90) 94%,
                    rgba(255,255,255,.97) 97%,
                    #FFFFFF 100%
                ) !important;
        }

        html[data-site='frontend'] [data-kie-doc-stage] {
            min-height: clamp(26rem, 62svh, 33rem) !important;
            padding-top: 1.5rem !important;
            padding-bottom: 5.75rem !important;
        }

        /*
         * TITLE: modern editorial, sans-serif, uppercase, tracking lebar.
         * Sengaja berbeda dari serif besar sebelumnya agar benar-benar terasa baru.
         */
        html[data-site='frontend'] [data-kie-doc-title] {
            transform: translateY(-1rem) !important;
        }

        html[data-site='frontend'] [data-kie-doc-kicker] {
            display: block !important;
            width: 3.25rem !important;
            height: 2px !important;
            margin: 0 auto .85rem !important;

            border-radius: 999px !important;
            background:
                linear-gradient(
                    90deg,
                    transparent,
                    rgba(226,183,127,.88) 25%,
                    #F2D2A8 50%,
                    rgba(226,183,127,.88) 75%,
                    transparent
                ) !important;
            box-shadow: 0 0 18px rgba(226,183,127,.24) !important;
        }

        html[data-site='frontend'] [data-kie-doc-title] h1 {
            margin: 0 !important;
            padding: 0 !important;

            font-family:
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif !important;

            font-size: clamp(2.05rem, 6.8vw, 3.4rem) !important;
            font-weight: 650 !important;
            line-height: 1 !important;

            text-transform: uppercase !important;
            letter-spacing: .16em !important;

            color: rgba(255,253,249,.98) !important;
            text-align: center !important;

            text-shadow:
                0 1px 2px rgba(0,0,0,.38),
                0 9px 30px rgba(0,0,0,.22) !important;
        }

        /*
         * Editorial corner marks pada area Hero, bukan kotak penuh.
         * Ini memberi rasa art-direction tanpa terasa template.
         */
        html[data-site='frontend'] [data-kie-doc-frame] span {
            position: absolute !important;
            width: 2rem !important;
            height: 2rem !important;
            opacity: .72 !important;
        }

        html[data-site='frontend'] [data-kie-doc-frame] span::before,
        html[data-site='frontend'] [data-kie-doc-frame] span::after {
            content: "" !important;
            position: absolute !important;
            background: rgba(239,210,174,.72) !important;
        }

        html[data-site='frontend'] [data-kie-doc-frame] span::before {
            width: 100% !important;
            height: 1px !important;
        }

        html[data-site='frontend'] [data-kie-doc-frame] span::after {
            width: 1px !important;
            height: 100% !important;
        }

        html[data-site='frontend'] [data-kie-doc-frame] [data-corner='tl'] {
            top: 1.35rem !important;
            left: 1.2rem !important;
        }

        html[data-site='frontend'] [data-kie-doc-frame] [data-corner='tr'] {
            top: 1.35rem !important;
            right: 1.2rem !important;
            transform: scaleX(-1) !important;
        }

        html[data-site='frontend'] [data-kie-doc-frame] [data-corner='bl'] {
            bottom: 4.35rem !important;
            left: 1.2rem !important;
            transform: scaleY(-1) !important;
        }

        html[data-site='frontend'] [data-kie-doc-frame] [data-corner='br'] {
            right: 1.2rem !important;
            bottom: 4.35rem !important;
            transform: scale(-1) !important;
        }

        @media (prefers-reduced-motion: no-preference) {
            html[data-site='frontend'] [data-kie-doc-title] {
                animation: kieDocTitleV5 .7s cubic-bezier(.2,.78,.2,1) both !important;
            }

            html[data-site='frontend'] [data-kie-doc-frame] {
                animation: kieDocFrameV5 .9s ease-out both !important;
            }

            @keyframes kieDocTitleV5 {
                from {
                    opacity: 0;
                    transform: translateY(.25rem);
                    letter-spacing: .22em;
                }
                to {
                    opacity: 1;
                    transform: translateY(-1rem);
                    letter-spacing: normal;
                }
            }

            @keyframes kieDocFrameV5 {
                from { opacity: 0; }
                to { opacity: 1; }
            }
        }
    }

    @media (max-width: 639.98px) {
        html[data-site='frontend'] [data-kie-dokumentasi-mobile-hero] {
            min-height: clamp(24rem, 56svh, 29rem) !important;
        }

        html[data-site='frontend'] [data-kie-doc-stage] {
            min-height: clamp(24rem, 56svh, 29rem) !important;
            padding-top: 1rem !important;
            padding-bottom: 5rem !important;
        }

        html[data-site='frontend'] [data-kie-doc-title] {
            transform: translateY(-.55rem) !important;
        }

        html[data-site='frontend'] [data-kie-doc-title] h1 {
            font-size: clamp(1.75rem, 7.6vw, 2.35rem) !important;
            letter-spacing: .13em !important;
        }

        html[data-site='frontend'] [data-kie-doc-kicker] {
            width: 2.7rem !important;
            margin-bottom: .7rem !important;
        }

        html[data-site='frontend'] [data-kie-doc-frame] span {
            width: 1.45rem !important;
            height: 1.45rem !important;
        }

        html[data-site='frontend'] [data-kie-doc-frame] [data-corner='tl'] {
            top: 1rem !important;
            left: .85rem !important;
        }

        html[data-site='frontend'] [data-kie-doc-frame] [data-corner='tr'] {
            top: 1rem !important;
            right: .85rem !important;
        }

        html[data-site='frontend'] [data-kie-doc-frame] [data-corner='bl'] {
            bottom: 3.9rem !important;
            left: .85rem !important;
        }

        html[data-site='frontend'] [data-kie-doc-frame] [data-corner='br'] {
            right: .85rem !important;
            bottom: 3.9rem !important;
        }

        @media (prefers-reduced-motion: no-preference) {
            @keyframes kieDocTitleV5 {
                from {
                    opacity: 0;
                    transform: translateY(.25rem);
                }
                to {
                    opacity: 1;
                    transform: translateY(-.55rem);
                }
            }
        }
    }

    @media (min-width: 1024px) {
        html[data-site='frontend'] [data-kie-dokumentasi-mobile-hero] {
            display: none !important;
        }
    }
</style>
{{-- KIE-DOKUMENTASI-HERO-RESPONSIVE:END --}}

{{-- KIE-PHOTO-MOBILE-EDITORIAL:START --}}
<style>
    /*
     * MOBILE ONLY:
     * - bukan lagi satu card landscape besar per baris
     * - jadi editorial 2 kolom
     * - foto pertama menjadi feature card full width
     * - foto berikutnya portrait compact
     * - setiap card ke-6 menjadi feature separator full width
     */
    @media (max-width: 639.98px) {
        html[data-site='frontend'] [data-kie-photo-grid] {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            grid-auto-flow: dense !important;
            grid-auto-rows: auto !important;

            gap: .7rem !important;
            margin-top: 1.35rem !important;
        }

        /*
         * Reset layout span dari desktop/tablet pada mobile.
         */
        html[data-site='frontend'] [data-kie-photo-card] {
            grid-column: auto !important;
            grid-row: auto !important;

            width: 100% !important;
            min-width: 0 !important;
            height: auto !important;
            min-height: 0 !important;

            aspect-ratio: 4 / 5 !important;

            border-radius: 1.15rem !important;
            border-width: 1px !important;

            box-shadow:
                0 16px 34px -26px rgba(61,43,31,.42) !important;
        }

        /*
         * Feature card pertama: lebar penuh, tetapi tidak setinggi layout lama.
         */
        html[data-site='frontend'] [data-kie-photo-card]:first-child {
            grid-column: 1 / -1 !important;
            aspect-ratio: 16 / 9 !important;
            border-radius: 1.3rem !important;
        }

        /*
         * Setiap 6 card, satu full-width lagi untuk ritme editorial.
         */
        html[data-site='frontend'] [data-kie-photo-card]:nth-child(6n) {
            grid-column: 1 / -1 !important;
            aspect-ratio: 16 / 9 !important;
            border-radius: 1.3rem !important;
        }

        html[data-site='frontend'] [data-kie-photo-card] > img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            object-position: center !important;
        }

        /*
         * Overlay dibuat lebih halus supaya foto tetap jadi fokus.
         */
        html[data-site='frontend'] [data-kie-photo-card] > div:nth-of-type(1) {
            background:
                linear-gradient(
                    180deg,
                    rgba(18,12,8,.08) 0%,
                    transparent 38%,
                    rgba(18,12,8,.08) 60%,
                    rgba(18,12,8,.82) 100%
                ) !important;
        }

        /*
         * Nomor dan badge FOTO dibuat mini.
         */
        html[data-site='frontend'] [data-kie-photo-card] > .absolute.left-4.right-4.top-4 {
            left: .65rem !important;
            right: .65rem !important;
            top: .65rem !important;
        }

        html[data-site='frontend'] [data-kie-photo-card] > .absolute.left-4.right-4.top-4 > span {
            min-width: 0 !important;
            padding: .38rem .52rem !important;
            border-radius: 999px !important;

            font-size: .52rem !important;
            line-height: 1 !important;
            letter-spacing: .08em !important;
        }

        html[data-site='frontend'] [data-kie-photo-card] > .absolute.left-4.right-4.top-4 > span:last-child {
            gap: .25rem !important;
            letter-spacing: .08em !important;
        }

        html[data-site='frontend'] [data-kie-photo-card] > .absolute.left-4.right-4.top-4 i {
            margin-right: .1rem !important;
            font-size: .5rem !important;
        }

        /*
         * Caption compact di bagian bawah.
         */
        html[data-site='frontend'] [data-kie-photo-card] [data-kie-video-caption] {
            padding: .7rem .72rem .75rem !important;
        }

        html[data-site='frontend'] [data-kie-photo-card] [data-kie-video-caption] p {
            font-size: .48rem !important;
            line-height: 1.2 !important;
            letter-spacing: .18em !important;
        }

        html[data-site='frontend'] [data-kie-photo-card] [data-kie-video-caption] h3 {
            margin-top: .28rem !important;

            display: -webkit-box !important;
            -webkit-box-orient: vertical !important;
            -webkit-line-clamp: 2 !important;
            overflow: hidden !important;

            font-size: .78rem !important;
            line-height: 1.25 !important;
            font-weight: 650 !important;
        }

        /*
         * Feature card boleh punya judul sedikit lebih besar.
         */
        html[data-site='frontend'] [data-kie-photo-card]:first-child [data-kie-video-caption],
        html[data-site='frontend'] [data-kie-photo-card]:nth-child(6n) [data-kie-video-caption] {
            padding: .85rem .9rem .9rem !important;
        }

        html[data-site='frontend'] [data-kie-photo-card]:first-child [data-kie-video-caption] h3,
        html[data-site='frontend'] [data-kie-photo-card]:nth-child(6n) [data-kie-video-caption] h3 {
            font-size: 1rem !important;
        }

        /*
         * Teks bantuan bawah dipadatkan agar tidak membuang ruang.
         */
        html[data-site='frontend'] [data-kie-photo-grid] + .mt-8 {
            margin-top: 1.15rem !important;
            font-size: .65rem !important;
        }
    }

    /*
     * HP sangat kecil tetap 2 kolom tetapi lebih rapat.
     */
    @media (max-width: 359.98px) {
        html[data-site='frontend'] [data-kie-photo-grid] {
            gap: .55rem !important;
        }

        html[data-site='frontend'] [data-kie-photo-card] {
            border-radius: .95rem !important;
        }

        html[data-site='frontend'] [data-kie-photo-card] [data-kie-video-caption] h3 {
            font-size: .7rem !important;
        }
    }
</style>
{{-- KIE-PHOTO-MOBILE-EDITORIAL:END --}}
</head>

<body class="min-h-screen bg-[#F7F4EF] font-sans antialiased text-[#2A211B]">
    @include('partials.frontend.navbar')

    @php
        // Section Hero -- bisa diedit admin lewat Admin > Edit Web >
        // Dokumentasi. Lihat App\Models\HomeSection, section_key
        // 'dokumentasi'.
        $dokumentasiHero = \App\Models\HomeSection::dataFor('dokumentasi', [
            'judul' => 'Dokumentasi',
            'subjudul' => 'Jejak karya dan proses kerja Karya Ide Edi',
            'deskripsi' => 'Kumpulan foto dan video hasil pekerjaan serta proses pembuatan furnitur Karya Ide Edi, sebagai gambaran kualitas dan ketelitian kami di setiap karya.',
            'media_type' => 'video_url',
            'video_url' => null,
            'video_path' => null,
        ]);

        // ============ Video kolom kanan: tautan ATAU upload perangkat ============
        // Pola & helper sama persis dengan partials/frontend/expertise.blade.php
        // ("Kenapa Pilih Kami") -- lihat App\Models\HomeSection::classifyVideoUrl().
        $dokumentasiVideoUploadUrl = $dokumentasiHero['video_path']
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($dokumentasiHero['video_path'])
            : null;

        $dokumentasiVideo = null;

        // Normalisasi data lama supaya video milik Dokumentasi tetap membaca
        // field Dokumentasi sendiri. Tidak ada fallback ke media section lain.
        $dokumentasiMediaType = in_array($dokumentasiHero['media_type'] ?? null, ['video_url', 'video_upload'], true)
            ? $dokumentasiHero['media_type']
            : ($dokumentasiVideoUploadUrl ? 'video_upload' : (($dokumentasiHero['video_url'] ?? null) ? 'video_url' : null));

        if ($dokumentasiMediaType === 'video_upload' && $dokumentasiVideoUploadUrl) {
            $dokumentasiVideo = ['provider' => 'direct', 'embed_url' => $dokumentasiVideoUploadUrl];
        } elseif ($dokumentasiMediaType === 'video_url' && ($dokumentasiHero['video_url'] ?? null)) {
            $dokumentasiVideo = \App\Models\HomeSection::classifyVideoUrl($dokumentasiHero['video_url']);
        }
    @endphp

    {{-- =========================================================
         HERO DOKUMENTASI - kolom kiri: judul, subjudul, deskripsi.
         Kolom kanan: video full-bleed sampai tepi kanan, memudar
         (mask-image) ke arah kiri supaya teks di atasnya tetap
         gampang dibaca -- struktur & efeknya SAMA PERSIS dengan
         partials/frontend/lokasi.blade.php ("Kunjungi Kami" di
         Beranda), cuma peta digantikan video di sini.

         Video diisi admin lewat Admin > Edit Web > Dokumentasi
         (section_key 'dokumentasi' di tabel home_sections):
         tautan (YouTube/TikTok/Instagram/Facebook/Google Drive/
         link file langsung) ATAU upload dari perangkat -- lihat
         @@php di atas & App\Models\HomeSection::classifyVideoUrl().
         Kalau admin belum pernah mengisi video, kolom kanan tetap
         kosong (section tampil seperti sebelumnya, teks kiri saja).
         ========================================================= --}}
        {{-- KIE-DOKUMENTASI-MOBILE-HERO:START --}}
    <section data-kie-dokumentasi-mobile-hero class="relative isolate overflow-hidden bg-[#17110D] lg:hidden">
        @if ($dokumentasiVideo)
            <div data-kie-dokumentasi-mobile-media class="absolute inset-0 -z-40 overflow-hidden">
                @if ($dokumentasiVideo['provider'] === 'direct')
                    <video
                        src="{{ $dokumentasiVideo['embed_url'] }}"
                        autoplay
                        muted
                        loop
                        playsinline
                        preload="metadata"
                        class="h-full w-full object-cover"
                        onloadeddata="this.play().catch(() => {})"
                    ></video>
                @elseif ($dokumentasiVideo['provider'] === 'youtube')
                    <iframe
                        src="{{ $dokumentasiVideo['embed_url'] }}&autoplay=1&mute=1&controls=0&loop=1&playlist={{ $dokumentasiVideo['video_id'] ?? '' }}"
                        title="Video Dokumentasi {{ \App\Models\Setting::current()->site_name }}"
                        allow="autoplay; encrypted-media"
                        class="pointer-events-none h-full w-full"
                    ></iframe>
                @elseif ($dokumentasiVideo['provider'] === 'vimeo')
                    <iframe
                        src="{{ $dokumentasiVideo['embed_url'] }}?autoplay=1&muted=1&background=1&loop=1"
                        title="Video Dokumentasi {{ \App\Models\Setting::current()->site_name }}"
                        allow="autoplay; fullscreen"
                        class="pointer-events-none h-full w-full"
                    ></iframe>
                @endif
            </div>
        @endif

        <div data-kie-doc-grade class="pointer-events-none absolute inset-0 -z-30"></div>
        <div data-kie-doc-spotlight class="pointer-events-none absolute inset-0 -z-20"></div>
        <div data-kie-doc-bottom-fade class="pointer-events-none absolute inset-0 -z-10"></div>

        {{-- Editorial corner frame: bukan box judul --}}
        <div data-kie-doc-frame aria-hidden="true" class="pointer-events-none absolute inset-0 z-10">
            <span data-corner="tl"></span>
            <span data-corner="tr"></span>
            <span data-corner="bl"></span>
            <span data-corner="br"></span>
        </div>

        <div data-kie-doc-stage class="relative mx-auto flex max-w-360 items-center justify-center px-5 sm:px-8">
            <div data-kie-doc-title class="relative z-20 text-center">
                <span data-kie-doc-kicker aria-hidden="true"></span>
                <h1>Dokumentasi</h1>
            </div>
        </div>
    </section>
{{-- KIE-DOKUMENTASI-MOBILE-HERO:END --}}
<section class="relative overflow-hidden bg-[#F9F7F2]" data-kie-dokumentasi-desktop-hero>

        @if ($dokumentasiVideo)
            {{-- ============ BACKGROUND: video, memudar dari kiri ============ --}}
            <div
                class="pointer-events-none absolute inset-0 hidden overflow-hidden lg:block"
                style="-webkit-mask-image: linear-gradient(to right, transparent 0%, transparent 28%, black 55%); mask-image: linear-gradient(to right, transparent 0%, transparent 28%, black 55%);"
                aria-hidden="true"
            >
                @if ($dokumentasiVideo['provider'] === 'direct')
                    {{-- Video langsung (upload dari perangkat ATAU tautan file video).
                         MUTE PERMANEN: atribut `muted` + dikunci ulang lewat listener
                         `volumechange`, jadi tidak bisa balik bersuara. Tombol volume
                         yang dulu ada di sini SENGAJA dihapus -- halaman Dokumentasi
                         ini galeri, bukan pemutar video. --}}
                    <div class="absolute inset-0 h-full w-full">
                        <video
                            src="{{ $dokumentasiVideo['embed_url'] }}"
                            class="absolute inset-0 h-full w-full object-cover"
                            autoplay muted loop playsinline preload="auto"
                            x-data x-init="$el.muted = true; $el.volume = 0; $el.addEventListener('volumechange', () => { $el.muted = true; $el.volume = 0; })"
                        ></video>
                    </div>
                @elseif ($dokumentasiVideo['provider'] === 'youtube')
                    {{-- YouTube: MUTE PERMANEN lewat parameter `mute=1` di URL embed
                         (tanpa postMessage API & tanpa tombol volume). Iframe baru
                         dimuat saat section masuk layar. --}}
                    <div x-data="{ loaded: false }" x-init="new IntersectionObserver((entries) => { if (entries[0].isIntersecting) { loaded = true; } }, { threshold: 0.3 }).observe($el)" class="absolute inset-0 h-full w-full">
                        <template x-if="loaded">
                            <iframe
                                src="{{ $dokumentasiVideo['embed_url'] }}&autoplay=1&mute=1&controls=0"
                                title="{{ $dokumentasiHero['judul'] }}"
                                class="absolute inset-0 h-full w-full"
                                style="border:0;"
                                allow="autoplay; encrypted-media; picture-in-picture"
                                allowfullscreen
                            ></iframe>
                        </template>
                    </div>
                @else
                    {{-- Facebook / TikTok / Instagram / Google Drive: embed resmi platform,
                         baru dimuat saat section masuk layar. --}}
                    <div x-data="{ loaded: false }" x-init="new IntersectionObserver((entries) => { if (entries[0].isIntersecting) { loaded = true; } }, { threshold: 0.3 }).observe($el)" class="absolute inset-0 h-full w-full">
                        <template x-if="loaded">
                            <iframe
                                src="{{ $dokumentasiVideo['embed_url'] }}"
                                title="{{ $dokumentasiHero['judul'] }}"
                                class="absolute inset-0 h-full w-full"
                                style="border:0;"
                                allow="autoplay; encrypted-media; picture-in-picture"
                                allowfullscreen
                            ></iframe>
                        </template>
                    </div>
                @endif
            </div>
        @endif

        {{-- ============ KONTEN: Judul, subjudul, deskripsi ============ --}}
        <div class="relative mx-auto max-w-7xl px-6 py-12 sm:px-8 lg:flex lg:min-h-160 lg:items-center lg:px-10 lg:py-24">
            <div class="max-w-xl animate-fade-in-up">
                <h1 class="font-display text-4xl font-semibold leading-[1.08] text-[#3D2B1F] sm:text-5xl lg:text-6xl">
                    {{ $dokumentasiHero['judul'] }}
                </h1>
                <p class="mt-4 font-display text-2xl italic leading-snug text-[#9B6E3E] sm:text-3xl lg:text-4xl">
                    {{ $dokumentasiHero['subjudul'] }}
                </p>
                <p class="mt-7 max-w-2xl text-base leading-8 text-[#6B6E76] sm:text-lg lg:text-xl">
                    {{ $dokumentasiHero['deskripsi'] }}
                </p>
            </div>
        </div>
    </section>

        @include('partials.frontend.dokumentasi-video-section', ['showAllVideos' => false])

    @include("partials.frontend.dokumentasi-foto-section", ["showPhotoMoreButton" => true])

@include('partials.frontend.footer')
</body>
</html>








