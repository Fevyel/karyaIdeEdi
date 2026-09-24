<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dokumentasi | {{ \App\Models\Setting::current()->site_name }}</title>
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
    <section class="relative overflow-hidden bg-[#F9F7F2]">

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

    @php
        // =========================================================
        // GALERI VIDEO PREMIUM â€” isi utama halaman Dokumentasi.
        // HERO di atas SENGAJA tidak disentuh.
        // Sumber data utama: section_key 'dokumentasi-3' dari Edit Web.
        // Kalau belum ada item di sana, fallback ke galeri lama (khusus
        // item bertipe video). Kalau masih kosong juga, section video tidak ditampilkan.
        // =========================================================
        $dokVideoSection = \App\Models\HomeSection::dataFor('dokumentasi-3', [
            'judul' => 'Galeri Video',
            'subjudul' => 'Dokumentasi Proses & Hasil',
            'deskripsi' => 'Lihat lebih dekat proses pengerjaan, detail finishing, hingga hasil akhir furnitur yang kami kerjakan.',
            'items' => [],
        ]);


        $dokVideoBentukItem = function (array $item) {
            $src = isset($item['path']) && $item['path']
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($item['path'])
                : ($item['url'] ?? null);

            if (! $src || ($item['tipe'] ?? null) !== 'video') {
                return null;
            }

            return [
                'src' => $src,
                'keterangan' => trim((string) ($item['keterangan'] ?? '')),
            ];
        };

        $dokVideoFromDok3 = array_values(array_filter(array_map(
            $dokVideoBentukItem,
            is_array($dokVideoSection['items'] ?? null) ? $dokVideoSection['items'] : []
        )));

        $dokVideoFromLegacyGallery = array_values(array_filter(array_map(
            $dokVideoBentukItem,
            is_array($dokumentasiHero['galeri'] ?? null) ? $dokumentasiHero['galeri'] : []
        )));

        $dokVideoItems = count($dokVideoFromDok3) > 0
            ? $dokVideoFromDok3
            : $dokVideoFromLegacyGallery;

        $dokVideoCount = count($dokVideoItems);
    @endphp

    @if ($dokVideoCount > 0)
        <section class="relative overflow-hidden bg-[#F7F4EF] py-18 sm:py-20 lg:py-24">
            <div class="pointer-events-none absolute -left-32 top-16 h-48 w-48 rounded-full bg-[#E7D7C2]/45 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-24 bottom-8 h-44 w-44 rounded-full bg-[#D7C2A8]/30 blur-3xl"></div>

            <div
                x-data="{
                    open: false,
                    activeSrc: '',
                    activeTitle: '',
                    activeNumber: '',
                    show(src, title, number) {
                        this.activeSrc = src;
                        this.activeTitle = title;
                        this.activeNumber = number;
                        this.open = true;
                        document.body.classList.add('overflow-hidden');
                    },
                    close() {
                        this.open = false;
                        this.activeSrc = '';
                        this.activeTitle = '';
                        this.activeNumber = '';
                        document.body.classList.remove('overflow-hidden');
                    }
                }"
                x-on:keydown.escape.window="close()"
                class="relative mx-auto max-w-7xl px-6 sm:px-8 lg:px-10"
            >
                <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <div class="mb-4 flex items-center gap-3">
                            <span class="h-px w-10 bg-[#B68A5B]"></span>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.42em] text-[#B07A43] sm:text-xs">
                                {{ $dokVideoSection['subjudul'] }}
                            </p>
                        </div>

                        <h2 class="font-display text-4xl font-semibold leading-none text-[#3D2B1F] sm:text-5xl lg:text-[4rem]">
                            {{ $dokVideoSection['judul'] }}
                        </h2>

                        <p class="mt-6 max-w-2xl text-base leading-9 text-[#6E6357] sm:text-lg">
                            {{ $dokVideoSection['deskripsi'] }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3 lg:justify-end">
                        <div class="inline-flex items-center gap-3 rounded-full border border-[#D7C4AD] bg-white/85 px-5 py-3 shadow-[0_14px_40px_-32px_rgba(61,43,31,0.45)] backdrop-blur">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#4B2F1F] text-white">
                                <i class="fa-solid fa-film text-sm"></i>
                            </span>
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-[0.28em] text-[#9F8B74]">Koleksi</p>
                                <p class="text-lg font-semibold text-[#3D2B1F]">{{ $dokVideoCount }} Video</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-10 h-px w-full bg-linear-to-r from-[#DCCEBB] via-[#CDB89D] to-transparent"></div>

                <div class="mt-9 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($dokVideoItems as $i => $video)
                        @php
                            $nomorVideo = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
                            $judulVideo = $video['keterangan'] ?: 'Video Dokumentasi '.$nomorVideo;
                        @endphp

                        <button
                            type="button"
                            x-on:click="show(@js($video['src']), @js($judulVideo), @js($nomorVideo))"
                            class="group relative overflow-hidden rounded-4xl border border-[#4E3324]/18 bg-[#EADCCB] text-left shadow-[0_26px_70px_-48px_rgba(61,43,31,0.62)] transition duration-300 hover:-translate-y-1.5 hover:shadow-[0_30px_80px_-42px_rgba(61,43,31,0.72)] focus:outline-none focus:ring-2 focus:ring-[#9B6E3E]/30"
                        >
                            <div class="relative aspect-video overflow-hidden bg-[#D8C0A6]">
                                <video
                                    src="{{ $video['src'] }}"
                                    class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
                                    autoplay muted loop playsinline preload="metadata"
                                    x-data x-init="$el.muted = true; $el.volume = 0; $el.addEventListener('volumechange', () => { $el.muted = true; $el.volume = 0; })"
                                ></video>

                                <div class="absolute inset-0 bg-linear-to-t from-[#160E09]/90 via-[#160E09]/25 to-[#160E09]/10"></div>
                                <div class="absolute inset-0 bg-linear-to-br from-[#6D4426]/24 via-transparent to-[#0F0906]/16"></div>

                                <div class="absolute left-4 right-4 top-4 flex items-start justify-between gap-3">
                                    <span class="inline-flex min-w-12 items-center justify-center rounded-full border border-white/20 bg-black/28 px-3 py-2 text-xs font-semibold tracking-[0.16em] text-white backdrop-blur">
                                        {{ $nomorVideo }}
                                    </span>

                                    <span class="inline-flex items-center gap-2 rounded-full border border-white/16 bg-black/28 px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-white backdrop-blur">
                                        <span class="h-1.5 w-1.5 rounded-full bg-[#E8BE84]"></span>
                                        Video
                                    </span>
                                </div>

                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="flex h-14 w-14 items-center justify-center rounded-full border border-white/28 bg-white/12 text-white shadow-lg backdrop-blur-md transition duration-300 group-hover:scale-110 group-hover:bg-white/20">
                                        <i class="fa-solid fa-play text-sm"></i>
                                    </span>
                                </div>

                                <div class="absolute inset-x-0 bottom-0 p-5 sm:p-6">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.34em] text-[#E7C89A]">
                                        Dokumentasi Karya
                                    </p>
                                    <h3 class="mt-2 line-clamp-2 text-xl font-semibold leading-snug text-white">
                                        {{ $judulVideo }}
                                    </h3>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>

                <div class="mt-8 flex items-center justify-center gap-4 text-center text-sm text-[#9A7E61]">
                    <span class="hidden h-px w-14 bg-[#D9C9B4] sm:block"></span>
                    <p>Klik video untuk melihat dalam ukuran lebih besar</p>
                    <span class="hidden h-px w-14 bg-[#D9C9B4] sm:block"></span>
                </div>

                <div
                    x-show="open"
                    x-transition.opacity
                    x-cloak
                    class="fixed inset-0 z-100 flex items-center justify-center bg-[#120C08]/78 px-4 py-6 backdrop-blur-sm"
                >
                    <div class="absolute inset-0" x-on:click="close()"></div>

                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-6 scale-[0.98]"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 scale-[0.98]"
                        class="relative z-10 w-full max-w-5xl overflow-hidden rounded-4xl border border-white/12 bg-[#17100D] shadow-[0_30px_100px_-36px_rgba(0,0,0,0.7)]"
                    >
                        <div class="flex items-center justify-between gap-4 border-b border-white/10 px-5 py-4 sm:px-6">
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-[#D4B083]" x-text="'Video ' + activeNumber"></p>
                                <h3 class="mt-1 truncate text-lg font-semibold text-white sm:text-xl" x-text="activeTitle"></h3>
                            </div>

                            <button
                                type="button"
                                x-on:click="close()"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/12 bg-white/6 text-white transition hover:bg-white/12"
                            >
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>

                        <div class="bg-black p-3 sm:p-4">
                            <div class="overflow-hidden rounded-3xl bg-black">
                                <video
                                    x-bind:src="activeSrc"
                                    class="aspect-video w-full bg-black"
                                    controls autoplay playsinline
                                ></video>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @php
        // GALERI FOTO PREMIUM â€” data baru section_key 'dokumentasi-foto'.
        // Fallback ke galeri lama hanya untuk menjaga foto existing sebelum
        // admin pertama kali menekan Simpan di tab Galeri Foto yang baru.
        $dokFotoSection = \App\Models\HomeSection::dataFor('dokumentasi-foto', [
            'judul' => 'Momen Karya dalam Bingkai',
            'subjudul' => 'Galeri Foto',
            'deskripsi' => 'Dokumentasi visual yang menampilkan proses, detail pengerjaan, hingga hasil akhir furnitur secara lebih dekat, bersih, dan profesional.',
            'items' => [],
        ]);
        $dokPhotoBentukItem = function (array $item) {
            $src = isset($item['path']) && $item['path'] ? \Illuminate\Support\Facades\Storage::disk('public')->url($item['path']) : ($item['url'] ?? null);
            if (! $src || ($item['tipe'] ?? null) !== 'foto') return null;
            return ['src' => $src, 'keterangan' => trim((string) ($item['keterangan'] ?? ''))];
        };
        $dokPhotoItems = array_values(array_filter(array_map($dokPhotoBentukItem, is_array($dokFotoSection['items'] ?? null) ? $dokFotoSection['items'] : [])));
        if ($dokPhotoItems === []) {
            $dokPhotoItems = array_values(array_filter(array_map($dokPhotoBentukItem, is_array($dokumentasiHero['galeri'] ?? null) ? $dokumentasiHero['galeri'] : [])));
        }
        $dokPhotoCount = count($dokPhotoItems);
    @endphp

    @if ($dokPhotoCount > 0)
        <section class="relative overflow-hidden border-t border-[#E7DCCF] bg-white py-18 sm:py-20 lg:py-24">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-20 bg-linear-to-b from-[#F6EFE6]/85 to-transparent"></div>
            <div x-data="{open:false,activeSrc:'',activeTitle:'',activeNumber:'',show(src,title,n){this.activeSrc=src;this.activeTitle=title;this.activeNumber=n;this.open=true;document.body.classList.add('overflow-hidden')},close(){this.open=false;document.body.classList.remove('overflow-hidden')}}" x-on:keydown.escape.window="close()" class="relative mx-auto max-w-7xl px-6 sm:px-8 lg:px-10">
                <div class="grid gap-8 lg:grid-cols-[minmax(0,1.05fr)_21rem] lg:items-end">
                    <div><div class="mb-4 flex items-center gap-3"><span class="h-px w-10 bg-[#C39058]"></span><p class="text-[11px] font-semibold uppercase tracking-[0.42em] text-[#BC8651] sm:text-xs">{{ $dokFotoSection['subjudul'] }}</p></div><h2 class="font-display text-4xl font-semibold leading-none text-[#3D2B1F] sm:text-5xl lg:text-[3.8rem]">{{ $dokFotoSection['judul'] }}</h2><p class="mt-6 max-w-2xl text-base leading-9 text-[#6E6357] sm:text-lg">{{ $dokFotoSection['deskripsi'] }}</p></div>
                    <div class="rounded-4xl border border-[#E2D2BF] bg-[#FCFAF7] p-5 shadow-[0_18px_50px_-38px_rgba(61,43,31,0.35)]"><div class="flex items-center gap-4"><span class="flex h-12 w-12 items-center justify-center rounded-full bg-[#4B2F1F] text-white"><i class="fa-solid fa-camera-retro"></i></span><div><p class="text-[10px] font-semibold uppercase tracking-[0.28em] text-[#AA8E71]">Portofolio</p><p class="text-lg font-semibold text-[#3D2B1F]">{{ $dokPhotoCount }} Foto Pilihan</p></div></div><div class="mt-4 h-px bg-linear-to-r from-[#E1D2BF] to-transparent"></div><p class="mt-4 text-sm leading-7 text-[#7A6B5E]">Disusun seperti editorial gallery agar dokumentasi terasa estetik, mewah, dan profesional.</p></div>
                </div>
                <div class="mt-10 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4" style="grid-auto-rows:220px;">
                    @foreach ($dokPhotoItems as $i => $photo)
                        @php
                            $n = str_pad($i + 1, 2, '0', STR_PAD_LEFT); $title = $photo['keterangan'] ?: 'Foto Dokumentasi '.$n; $sisa = $dokPhotoCount % 4;
                            $isLast = $i === $dokPhotoCount - 1;
                            $layout = match ($i % 6) {0 => 'md:col-span-2 md:row-span-2',1 => 'xl:row-span-2',2 => '',3 => '',4 => 'md:col-span-2',default => ''};
                            if ($isLast && $sisa === 1) $layout = 'md:col-span-2 xl:col-span-4 md:row-span-2';
                        @endphp
                        <button type="button" x-on:click="show(@js($photo['src']),@js($title),@js($n))" class="group relative {{ $layout }} overflow-hidden rounded-4xl border border-[#E7DACB] bg-[#F3E6D5] text-left shadow-[0_22px_60px_-40px_rgba(61,43,31,0.4)] transition duration-300 hover:-translate-y-1">
                            <img src="{{ $photo['src'] }}" alt="{{ $title }}" class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-[1.05]" loading="lazy"><div class="absolute inset-0 bg-linear-to-t from-[#140D08]/88 via-[#140D08]/22 to-transparent"></div>
                            <div class="absolute left-4 right-4 top-4 flex justify-between"><span class="rounded-full border border-white/18 bg-black/24 px-3 py-2 text-xs font-semibold text-white backdrop-blur">{{ $n }}</span><span class="rounded-full border border-white/15 bg-white/10 px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-white backdrop-blur"><i class="fa-solid fa-image mr-1"></i> Foto</span></div>
                            <div class="absolute inset-x-0 bottom-0 p-5 sm:p-6"><p class="text-[11px] font-semibold uppercase tracking-[0.34em] text-[#E8C692]">Dokumentasi Visual</p><h3 class="mt-2 line-clamp-2 text-xl font-semibold text-white">{{ $title }}</h3></div>
                        </button>
                    @endforeach
                </div>
                <div class="mt-8 flex items-center justify-center gap-4 text-sm text-[#9A7E61]"><span class="hidden h-px w-14 bg-[#D9C9B4] sm:block"></span><p>Klik foto untuk melihat dalam ukuran lebih besar</p><span class="hidden h-px w-14 bg-[#D9C9B4] sm:block"></span></div>
                <div x-show="open" x-transition.opacity x-cloak class="fixed inset-0 z-100 flex items-center justify-center bg-[#120C08]/80 px-4 py-6 backdrop-blur-sm"><div class="absolute inset-0" x-on:click="close()"></div><div class="relative z-10 w-full max-w-6xl overflow-hidden rounded-4xl bg-[#17110E]"><div class="flex items-center justify-between border-b border-white/10 px-6 py-4"><div><p class="text-[11px] uppercase tracking-[0.3em] text-[#D4B083]" x-text="'Foto '+activeNumber"></p><h3 class="mt-1 text-xl font-semibold text-white" x-text="activeTitle"></h3></div><button type="button" x-on:click="close()" class="h-11 w-11 rounded-full border border-white/12 text-white"><i class="fa-solid fa-xmark"></i></button></div><div class="bg-[#120C08] p-4"><img x-bind:src="activeSrc" x-bind:alt="activeTitle" class="max-h-[78vh] w-full rounded-3xl object-contain"></div></div></div>
            </div>
        </section>
    @endif

@include('partials.frontend.footer')
</body>
</html>








