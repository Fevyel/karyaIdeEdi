<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dokumentasi — {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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

        if ($dokumentasiHero['media_type'] === 'video_upload' && $dokumentasiVideoUploadUrl) {
            $dokumentasiVideo = ['provider' => 'direct', 'embed_url' => $dokumentasiVideoUploadUrl];
        } elseif ($dokumentasiHero['media_type'] === 'video_url' && $dokumentasiHero['video_url']) {
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
         @php di atas & App\Models\HomeSection::classifyVideoUrl().
         Kalau admin belum pernah mengisi video, kolom kanan tetap
         kosong (section tampil seperti sebelumnya, teks kiri saja).
         ========================================================= --}}
    <section class="relative overflow-hidden bg-[#F9F7F2]">

        @if ($dokumentasiVideo)
            {{-- ============ BACKGROUND: video, memudar dari kiri ============ --}}
            <div
                class="pointer-events-none absolute inset-0 hidden overflow-hidden lg:block"
                style="-webkit-mask-image: linear-gradient(to right, transparent 0%, transparent 28%, black 55%); mask-image: linear-gradient(to right, transparent 0%, transparent 28%, black 55%);"
                aria-hidden="true"
            >
                @if ($dokumentasiVideo['provider'] === 'direct')
                    {{-- Video langsung (upload dari perangkat ATAU tautan file video) --
                         autoplay+suara begitu masuk viewport, suara meredup pelan saat
                         dilewati, tombol mute manual kiri-bawah. Sama komponen Alpine
                         dengan "Kenapa Pilih Kami" (dokumentasiVideoPlayer, resources/js/dokumentasi-video.js). --}}
                    <div x-data="dokumentasiVideoPlayer()" x-init="init()" class="absolute inset-0 h-full w-full">
                        <video
                            x-ref="video"
                            src="{{ $dokumentasiVideo['embed_url'] }}"
                            class="absolute inset-0 h-full w-full object-cover"
                            playsinline muted loop preload="auto"
                        ></video>

                        <button
                            type="button"
                            x-on:click="toggleMute()"
                            class="pointer-events-auto absolute bottom-4 right-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-black/50 text-white backdrop-blur transition hover:bg-black/70"
                            :aria-label="muted ? 'Aktifkan suara video' : 'Matikan suara video'"
                        >
                            <i class="fa-solid" :class="muted ? 'fa-volume-xmark' : 'fa-volume-high'"></i>
                        </button>
                    </div>
                @elseif ($dokumentasiVideo['provider'] === 'youtube')
                    {{-- YouTube: kontrol lewat postMessage API, komponen Alpine sama
                         dengan "Kenapa Pilih Kami" (dokumentasiYoutubePlayer, resources/js/dokumentasi-video.js). --}}
                    <div x-data="dokumentasiYoutubePlayer(@js($dokumentasiVideo['embed_url']))" x-init="init()" class="absolute inset-0 h-full w-full">
                        <iframe
                            x-ref="iframe"
                            title="{{ $dokumentasiHero['judul'] }}"
                            class="absolute inset-0 h-full w-full"
                            style="border:0;"
                            allow="autoplay; encrypted-media; picture-in-picture"
                            allowfullscreen
                        ></iframe>

                        <button
                            type="button"
                            x-on:click="toggleMute()"
                            class="pointer-events-auto absolute bottom-4 right-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-black/50 text-white backdrop-blur transition hover:bg-black/70"
                            :aria-label="muted ? 'Aktifkan suara video' : 'Matikan suara video'"
                        >
                            <i class="fa-solid" :class="muted ? 'fa-volume-xmark' : 'fa-volume-high'"></i>
                        </button>
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
        <div class="relative mx-auto max-w-7xl px-6 py-12 sm:px-8 lg:flex lg:min-h-[640px] lg:items-center lg:px-10 lg:py-24">
            <div class="max-w-xl animate-fade-in-up">
                <h1 class="font-display text-3xl font-semibold leading-[1.15] text-[#3D2B1F] sm:text-4xl lg:text-[2.5rem] xl:text-[2.75rem]">
                    {{ $dokumentasiHero['judul'] }}
                </h1>
                <p class="mt-3 font-display text-xl italic text-[#9B6E3E] sm:text-2xl">
                    {{ $dokumentasiHero['subjudul'] }}
                </p>
                <p class="mt-6 max-w-md text-sm leading-relaxed text-[#6B6E76]">
                    {{ $dokumentasiHero['deskripsi'] }}
                </p>
            </div>
        </div>
    </section>

    @include('partials.frontend.footer')
</body>
</html>