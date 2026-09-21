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

    @php
        // Galeri "Dokumentasi" (foto & video jejak karya) -- diisi admin lewat
        // Admin > Edit Web > Dokumentasi, kartu "Galeri Dokumentasi" (section_key
        // 'dokumentasi' yang SAMA dengan hero di atas, cuma key 'galeri'). Array
        // kosong kalau admin belum pernah mengisi galeri sama sekali -- section
        // di bawah ini otomatis tidak tampil (lihat @if di bawah).
        $dokumentasiGaleri = $dokumentasiHero['galeri'] ?? [];
    @endphp

    @if (count($dokumentasiGaleri) > 0)
        {{-- =========================================================
             GALERI DOKUMENTASI -- gaya "galeri museum digital": tiap
             foto/video muncul menyamping (kiri/kanan bergantian), tepi yang
             dekat teks memudar ke putih (mask-image, pola sama dengan hero
             di atas & "Kunjungi Kami" Beranda), dengan 2 kartu polos di
             belakangnya untuk kesan "tumpukan foto". Teks dibuat seminim
             mungkin: cuma nomor urut besar + 1 baris keterangan singkat.
             Animasi masuknya lewat Alpine, lihat resources/js/dokumentasi-galeri.js.
             ========================================================= --}}
        <section class="relative overflow-hidden bg-[#F9F7F2] py-16 sm:py-24">
            <div class="mx-auto max-w-6xl px-6 sm:px-8 lg:px-10">
                <p class="mb-12 text-center text-xs font-semibold uppercase tracking-[0.3em] text-[#9B6E3E] sm:mb-20">
                    Galeri
                </p>

                <div class="space-y-20 sm:space-y-28">
                    @foreach ($dokumentasiGaleri as $i => $item)
                        @php
                            // Genap = media di kanan (teks di kiri), ganjil = media di kiri (teks di kanan).
                            $mediaDiKanan = $i % 2 === 0;
                            // Tepi media yang memudar putih = tepi yang dekat dengan teks.
                            $maskKe = $mediaDiKanan ? 'left' : 'right';
                            // Media masuk dari sisi LUAR (menjauhi teks), bukan dari sisi teks.
                            $translasiAwal = $mediaDiKanan ? 'translate-x-10' : '-translate-x-10';
                        @endphp

                        <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                            {{-- MEDIA + 2 kartu polos di belakangnya (kesan tumpukan foto) --}}
                            <div
                                x-data="dokumentasiGaleriItem()" x-init="init()"
                                class="relative {{ $mediaDiKanan ? 'lg:order-2' : 'lg:order-1' }}"
                            >
                                <div class="absolute inset-4 -z-10 rotate-3 rounded-2xl border-8 border-white bg-[#EFE7D8] shadow-md" aria-hidden="true"></div>
                                <div class="absolute inset-4 -z-20 -rotate-6 rounded-2xl border-8 border-white bg-[#E4D8C2] shadow-md" aria-hidden="true"></div>

                                <div
                                    class="relative aspect-4/5 overflow-hidden rounded-2xl border-8 border-white shadow-2xl transition-all duration-1000 ease-out sm:aspect-16/10"
                                    :class="masuk ? 'opacity-100 translate-x-0' : 'opacity-0 {{ $translasiAwal }}'"
                                    style="-webkit-mask-image: linear-gradient(to {{ $maskKe }}, black 78%, transparent 100%); mask-image: linear-gradient(to {{ $maskKe }}, black 78%, transparent 100%);"
                                >
                                    @if (($item['tipe'] ?? 'foto') === 'video')
                                        <video
                                            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item['path']) }}"
                                            class="h-full w-full object-cover"
                                            autoplay muted loop playsinline preload="metadata"
                                            x-data x-init="$el.muted = true; $el.volume = 0; $el.addEventListener('volumechange', () => { $el.muted = true; $el.volume = 0; })"
                                        ></video>
                                    @else
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item['path']) }}"
                                            alt="{{ $item['keterangan'] ?: 'Dokumentasi '.\App\Models\Setting::current()->site_name }}"
                                            class="h-full w-full object-cover"
                                            loading="lazy"
                                        >
                                    @endif
                                </div>
                            </div>

                            {{-- TEKS: seminim mungkin -- nomor urut + 1 baris keterangan (opsional) --}}
                            <div class="{{ $mediaDiKanan ? 'lg:order-1' : 'lg:order-2' }}">
                                <span class="font-display text-5xl font-semibold text-[#E4D8C2] sm:text-6xl">
                                    {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                                </span>
                                @if (! empty($item['keterangan']))
                                    <p class="mt-2 max-w-xs text-sm text-[#6B6E76]">
                                        {{ $item['keterangan'] }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{--
        ==========================================================
        SECTION TAMBAHAN: DOKUMENTASI 1, 2, 3
        ==========================================================
        3 section TAMBAHAN yang ditaruh DI BAWAH hero + galeri di atas.
        Hero dan galeri di atas TIDAK diubah sama sekali oleh fitur ini.

          1. DOKUMENTASI 1 — Pita Berjalan (marquee)
             Teks di KANAN, media (foto + video) berjalan terus dari
             kanan ke kiri di KIRI. Pola loop-nya sama persis dengan
             pita "FURNITUR TOKO MEBEL • KARYA IDE-EDI" di atas footer
             (partials/frontend/marquee-brand.blade.php), bedanya isi
             pita ini foto & video, bukan teks.
             Sumber data: section_key 'dokumentasi-1'.

          2. DOKUMENTASI 2 — Video Wall Mosaic
             Dinding media gelap ala ruang pamer: ubin besar-kecil,
             video/foto looping, klik ubin untuk zoom (lightbox).
             Sumber data: section_key 'dokumentasi-2'.

          3. DOKUMENTASI 3 — 3D Tilt Card Stack
             Kartu foto/video bertumpuk yang "mengipas" miring 3D saat
             masuk layar, dan menegak + terangkat saat disentuh kursor.
             Sumber data: section_key 'dokumentasi-3'.

        Semuanya diisi dari Admin > Edit Web > Dokumentasi, tab
        "Dokumentasi 1/2/3".

        CATATAN — SUARA VIDEO:
        SEMUA video di halaman ini MUTE PERMANEN (atribut `muted` +
        dikunci ulang lewat listener `volumechange`). Ini galeri, bukan
        pemutar video — jadi tidak ada tombol volume sama sekali.

        CATATAN CSS/JS:
        Style & script ketiga section SENGAJA self-contained (inline
        <style> + x-data literal), persis pola marquee-brand.blade.php,
        supaya langsung jalan tanpa perlu `npm run build` dan tidak
        menambah class Tailwind baru. Semua nama class diawali `kie-dok`.
        ==========================================================
    --}}

    @php
        // ============ Ambil data tiap section tambahan ============
        $dok1 = \App\Models\HomeSection::dataFor('dokumentasi-1', [
            'judul' => 'Jejak Proses',
            'subjudul' => 'Pita Karya',
            'deskripsi' => 'Cuplikan foto dan video yang berjalan terus, dari pemilihan bahan sampai furnitur siap dipakai di rumah pelanggan.',
            'marquee_speed' => 45,
            'items' => [],
        ]);

        $dok2 = \App\Models\HomeSection::dataFor('dokumentasi-2', [
            'judul' => 'Dinding Karya',
            'subjudul' => 'Video Wall',
            'deskripsi' => 'Potongan proses, detail sambungan, dan hasil akhir yang kami rekam langsung dari bengkel. Ketuk salah satu bidang untuk melihatnya lebih besar.',
            'items' => [],
        ]);

        $dok3 = \App\Models\HomeSection::dataFor('dokumentasi-3', [
            'judul' => 'Arsip Pilihan',
            'subjudul' => 'Kartu Karya',
            'deskripsi' => 'Beberapa karya yang paling sering ditanyakan pelanggan. Arahkan kursor ke salah satu kartu untuk melihatnya lebih dekat.',
            'items' => [],
        ]);

        // ============ Isi dummy sementara (dari internet) ============
        // Dipakai HANYA kalau slot di admin masih kosong semua. Begitu admin
        // mengunggah foto/video sendiri, isi dummy ini tidak dipakai lagi.
        $dokDummyFoto = fn (string $seed) => 'https://picsum.photos/seed/'.$seed.'/900/1200';
        $dokDummyVideo = [
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerMeltdowns.mp4',
        ];

        // Menyeragamkan 1 baris data admin ATAU 1 baris dummy jadi:
        // ['tipe' => 'foto|video', 'src' => URL, 'keterangan' => '...'].
        $dokBentukMedia = function (array $item) {
            $src = isset($item['path']) && $item['path']
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($item['path'])
                : ($item['url'] ?? null);

            if (! $src) {
                return null;
            }

            return [
                'tipe' => ($item['tipe'] ?? 'foto') === 'video' ? 'video' : 'foto',
                'src' => $src,
                'keterangan' => trim((string) ($item['keterangan'] ?? '')),
            ];
        };

        $dokPilihMedia = function ($tersimpan, array $dummy) use ($dokBentukMedia) {
            $tersimpan = is_array($tersimpan) ? array_values(array_filter($tersimpan, 'is_array')) : [];
            $sumber = count($tersimpan) > 0 ? $tersimpan : $dummy;

            return array_values(array_filter(array_map($dokBentukMedia, $sumber)));
        };

        $dok1Media = $dokPilihMedia($dok1['items'] ?? [], [
            ['tipe' => 'foto', 'url' => $dokDummyFoto('kie-dok1-a'), 'keterangan' => 'Pemilihan bahan kayu'],
            ['tipe' => 'video', 'url' => $dokDummyVideo[0], 'keterangan' => 'Proses pengamplasan'],
            ['tipe' => 'foto', 'url' => $dokDummyFoto('kie-dok1-b'), 'keterangan' => 'Detail sambungan'],
            ['tipe' => 'foto', 'url' => $dokDummyFoto('kie-dok1-c'), 'keterangan' => 'Finishing natural'],
            ['tipe' => 'video', 'url' => $dokDummyVideo[1], 'keterangan' => 'Perakitan lemari'],
            ['tipe' => 'foto', 'url' => $dokDummyFoto('kie-dok1-d'), 'keterangan' => 'Hasil akhir di ruang tamu'],
        ]);

        $dok2Media = $dokPilihMedia($dok2['items'] ?? [], [
            ['tipe' => 'video', 'url' => $dokDummyVideo[0], 'keterangan' => 'Bengkel pagi hari'],
            ['tipe' => 'foto', 'url' => $dokDummyFoto('kie-dok2-a'), 'keterangan' => 'Kursi jati'],
            ['tipe' => 'foto', 'url' => $dokDummyFoto('kie-dok2-b'), 'keterangan' => 'Meja makan'],
            ['tipe' => 'video', 'url' => $dokDummyVideo[2], 'keterangan' => 'Pahat tangan'],
            ['tipe' => 'foto', 'url' => $dokDummyFoto('kie-dok2-c'), 'keterangan' => 'Rak dinding'],
            ['tipe' => 'foto', 'url' => $dokDummyFoto('kie-dok2-d'), 'keterangan' => 'Kabinet dapur'],
            ['tipe' => 'foto', 'url' => $dokDummyFoto('kie-dok2-e'), 'keterangan' => 'Interior kamar'],
        ]);

        $dok3Media = $dokPilihMedia($dok3['items'] ?? [], [
            ['tipe' => 'foto', 'url' => $dokDummyFoto('kie-dok3-a'), 'keterangan' => 'Lemari pakaian custom'],
            ['tipe' => 'video', 'url' => $dokDummyVideo[3], 'keterangan' => 'Proses finishing'],
            ['tipe' => 'foto', 'url' => $dokDummyFoto('kie-dok3-b'), 'keterangan' => 'Set ruang tamu'],
            ['tipe' => 'foto', 'url' => $dokDummyFoto('kie-dok3-c'), 'keterangan' => 'Meja kerja minimalis'],
            ['tipe' => 'foto', 'url' => $dokDummyFoto('kie-dok3-d'), 'keterangan' => 'Kitchen set'],
        ]);

        // Kecepatan pita berjalan (detik per putaran). Makin besar = makin pelan.
        $dok1Speed = max(15, min(180, (int) ($dok1['marquee_speed'] ?? 45)));
    @endphp

    {{-- =========================================================
         DOKUMENTASI 1 — pita media berjalan (kiri) + teks (kanan)
         ========================================================= --}}
    <section class="kie-dok1" id="dokumentasi-1">
        <div class="kie-dok1__inner">

            {{-- KIRI: pita foto & video berjalan kanan -> kiri, loop mulus.
                 Track berisi 2 grup ISINYA IDENTIK lalu digeser 0 -> -50%,
                 sama persis dengan marquee-brand.blade.php. --}}
            <div class="kie-dok1__rail" style="--kie-dok1-speed: {{ $dok1Speed }}s;" aria-hidden="true">
                <div class="kie-dok1__track">
                    @foreach ([1, 2] as $grup)
                        <div class="kie-dok1__group">
                            @foreach ($dok1Media as $media)
                                <figure class="kie-dok1__card">
                                    @if ($media['tipe'] === 'video')
                                        {{-- MUTE PERMANEN. --}}
                                        <video
                                            src="{{ $media['src'] }}"
                                            autoplay muted loop playsinline preload="metadata"
                                            x-data x-init="$el.muted = true; $el.volume = 0; $el.addEventListener('volumechange', () => { $el.muted = true; $el.volume = 0; })"
                                        ></video>
                                        <span class="kie-dok1__badge"><i class="fa-solid fa-play"></i></span>
                                    @else
                                        <img src="{{ $media['src'] }}" alt="{{ $media['keterangan'] ?: 'Dokumentasi' }}" loading="lazy">
                                    @endif

                                    @if ($media['keterangan'] !== '')
                                        <figcaption class="kie-dok1__cap">{{ $media['keterangan'] }}</figcaption>
                                    @endif
                                </figure>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- KANAN: teks --}}
            <div class="kie-dok1__text">
                <p class="kie-dok1__eyebrow">{{ $dok1['subjudul'] }}</p>
                <h2 class="kie-dok1__judul">{{ $dok1['judul'] }}</h2>
                <p class="kie-dok1__desc">{{ $dok1['deskripsi'] }}</p>
            </div>
        </div>
    </section>

    <style>
        .kie-dok1 {
            position: relative;
            overflow: hidden;
            background: #F9F7F2;
            padding: 3.5rem 0;
        }

        .kie-dok1__inner {
            margin: 0 auto;
            display: grid;
            max-width: 80rem;
            gap: 2.5rem;
            align-items: center;
            padding: 0 1.5rem;
        }

        @media (min-width: 1024px) {
            .kie-dok1 { padding: 5.5rem 0; }
            .kie-dok1__inner {
                grid-template-columns: 1.05fr 0.95fr;
                gap: 3.5rem;
                padding: 0 2.5rem;
            }
        }

        .kie-dok1__rail {
            position: relative;
            overflow: hidden;
            padding: 0.5rem 0;
            -webkit-mask-image: linear-gradient(to right, transparent 0%, black 9%, black 91%, transparent 100%);
            mask-image: linear-gradient(to right, transparent 0%, black 9%, black 91%, transparent 100%);
        }

        .kie-dok1__track {
            display: flex;
            width: max-content;
            gap: 1rem;
            animation: kie-dok1-scroll var(--kie-dok1-speed, 45s) linear infinite;
            will-change: transform;
        }

        .kie-dok1__group {
            display: flex;
            flex-shrink: 0;
            gap: 1rem;
        }

        .kie-dok1__card {
            position: relative;
            margin: 0;
            flex: none;
            width: clamp(148px, 19vw, 212px);
            aspect-ratio: 3 / 4;
            overflow: hidden;
            border-radius: 1rem;
            background: #EFE7D8;
            box-shadow: 0 18px 35px -18px rgba(42, 27, 18, 0.55);
        }

        .kie-dok1__card img,
        .kie-dok1__card video {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .kie-dok1__badge {
            position: absolute;
            top: 0.6rem;
            right: 0.6rem;
            display: flex;
            height: 1.75rem;
            width: 1.75rem;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background: rgba(0, 0, 0, 0.45);
            color: #fff;
            font-size: 0.6rem;
            backdrop-filter: blur(4px);
        }

        .kie-dok1__cap {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 1.5rem 0.75rem 0.6rem;
            font-size: 0.7rem;
            line-height: 1.3;
            color: #fff;
            background: linear-gradient(to top, rgba(20, 13, 9, 0.8), transparent);
        }

        .kie-dok1__eyebrow {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: #9B6E3E;
        }

        .kie-dok1__judul {
            margin-top: 0.75rem;
            font-family: var(--font-display, Georgia, serif);
            font-size: clamp(1.75rem, 3vw, 2.5rem);
            font-weight: 600;
            line-height: 1.15;
            color: #3D2B1F;
        }

        .kie-dok1__desc {
            margin-top: 1.2rem;
            max-width: 32rem;
            font-size: 0.9rem;
            line-height: 1.75;
            color: #6B6E76;
        }

        @keyframes kie-dok1-scroll {
            from { transform: translateX(0); }
            to   { transform: translateX(calc(-50% - 0.5rem)); }
        }

        @media (prefers-reduced-motion: reduce) {
            .kie-dok1__track { animation: none; }
        }
    </style>

    {{-- =========================================================
         DOKUMENTASI 2 — Video Wall Mosaic (klik ubin untuk zoom)
         ========================================================= --}}
    <section
        class="kie-dok2"
        id="dokumentasi-2"
        x-data="{
            buka: false,
            aktif: null,
            tampilkan(item) { this.aktif = item; this.buka = true; document.body.style.overflow = 'hidden'; },
            tutup() { this.buka = false; this.aktif = null; document.body.style.overflow = ''; }
        }"
        x-on:keydown.escape.window="tutup()"
    >
        <div class="kie-dok2__head">
            <p class="kie-dok2__eyebrow">{{ $dok2['subjudul'] }}</p>
            <h2 class="kie-dok2__judul">{{ $dok2['judul'] }}</h2>
            <p class="kie-dok2__desc">{{ $dok2['deskripsi'] }}</p>
        </div>

        <div class="kie-dok2__grid">
            @foreach ($dok2Media as $i => $media)
                <button
                    type="button"
                    class="kie-dok2__tile kie-dok2__tile--{{ ($i % 7) + 1 }}"
                    x-on:click="tampilkan(@js($media))"
                    aria-label="Perbesar {{ $media['keterangan'] ?: 'dokumentasi '.($i + 1) }}"
                >
                    @if ($media['tipe'] === 'video')
                        {{-- MUTE PERMANEN. --}}
                        <video
                            src="{{ $media['src'] }}"
                            autoplay muted loop playsinline preload="metadata"
                            x-data x-init="$el.muted = true; $el.volume = 0; $el.addEventListener('volumechange', () => { $el.muted = true; $el.volume = 0; })"
                        ></video>
                        <span class="kie-dok2__badge"><i class="fa-solid fa-play"></i></span>
                    @else
                        <img src="{{ $media['src'] }}" alt="{{ $media['keterangan'] ?: 'Dokumentasi' }}" loading="lazy">
                    @endif

                    <span class="kie-dok2__veil">
                        <span class="kie-dok2__cap">{{ $media['keterangan'] ?: 'Lihat lebih besar' }}</span>
                        <span class="kie-dok2__zoom"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></span>
                    </span>
                </button>
            @endforeach
        </div>

        {{-- Lightbox / zoom --}}
        <div class="kie-dok2__lightbox" x-show="buka" x-cloak x-transition.opacity x-on:click.self="tutup()">
            <button type="button" class="kie-dok2__close" x-on:click="tutup()" aria-label="Tutup">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <template x-if="aktif && aktif.tipe === 'video'">
                <div class="kie-dok2__stageItem">
                    <video
                        :src="aktif.src"
                        autoplay muted loop playsinline
                        x-init="$el.muted = true; $el.volume = 0; $el.addEventListener('volumechange', () => { $el.muted = true; $el.volume = 0; })"
                    ></video>
                    <p class="kie-dok2__lightcap" x-text="aktif.keterangan"></p>
                </div>
            </template>

            <template x-if="aktif && aktif.tipe !== 'video'">
                <div class="kie-dok2__stageItem">
                    <img :src="aktif.src" :alt="aktif.keterangan || 'Dokumentasi'">
                    <p class="kie-dok2__lightcap" x-text="aktif.keterangan"></p>
                </div>
            </template>
        </div>
    </section>

    <style>
        [x-cloak] { display: none !important; }

        .kie-dok2 {
            position: relative;
            background: #1C1512;
            color: #F3EBE1;
            padding: 4rem 0;
        }

        @media (min-width: 1024px) {
            .kie-dok2 { padding: 6rem 0; }
        }

        .kie-dok2__head {
            margin: 0 auto 2.75rem;
            max-width: 46rem;
            padding: 0 1.5rem;
            text-align: center;
        }

        .kie-dok2__eyebrow {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: #C9A566;
        }

        .kie-dok2__judul {
            margin-top: 0.75rem;
            font-family: var(--font-display, Georgia, serif);
            font-size: clamp(1.75rem, 3vw, 2.5rem);
            font-weight: 600;
            color: #F7F1E7;
        }

        .kie-dok2__desc {
            margin: 1rem auto 0;
            max-width: 36rem;
            font-size: 0.875rem;
            line-height: 1.75;
            color: rgba(243, 235, 225, 0.65);
        }

        .kie-dok2__grid {
            margin: 0 auto;
            display: grid;
            max-width: 82rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            grid-auto-rows: 118px;
            gap: 0.6rem;
            padding: 0 1.5rem;
        }

        @media (min-width: 640px) {
            .kie-dok2__grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                grid-auto-rows: 150px;
                gap: 0.8rem;
            }
        }

        @media (min-width: 1024px) {
            .kie-dok2__grid {
                grid-template-columns: repeat(6, minmax(0, 1fr));
                grid-auto-rows: 168px;
                gap: 1rem;
                padding: 0 2.5rem;
            }
        }

        .kie-dok2__tile {
            position: relative;
            display: block;
            height: 100%;
            width: 100%;
            overflow: hidden;
            padding: 0;
            border: 1px solid rgba(201, 165, 102, 0.18);
            border-radius: 0.9rem;
            background: #120D0B;
            cursor: zoom-in;
        }

        .kie-dok2__tile img,
        .kie-dok2__tile video {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.7s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .kie-dok2__tile:hover img,
        .kie-dok2__tile:hover video { transform: scale(1.07); }

        /* Ubin pertama & keempat dibuat besar supaya mosaiknya tidak monoton. */
        .kie-dok2__tile--1 { grid-column: span 2; grid-row: span 2; }
        .kie-dok2__tile--4 { grid-column: span 2; grid-row: span 2; }

        @media (min-width: 1024px) {
            .kie-dok2__tile--1 { grid-column: span 3; grid-row: span 2; }
            .kie-dok2__tile--2 { grid-column: span 3; grid-row: span 1; }
            .kie-dok2__tile--3 { grid-column: span 3; grid-row: span 1; }
            .kie-dok2__tile--4 { grid-column: span 2; grid-row: span 2; }
            .kie-dok2__tile--5 { grid-column: span 2; grid-row: span 1; }
            .kie-dok2__tile--6 { grid-column: span 2; grid-row: span 1; }
            .kie-dok2__tile--7 { grid-column: span 4; grid-row: span 1; }
        }

        .kie-dok2__badge {
            position: absolute;
            top: 0.6rem;
            right: 0.6rem;
            display: flex;
            height: 1.75rem;
            width: 1.75rem;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background: rgba(0, 0, 0, 0.45);
            color: #fff;
            font-size: 0.6rem;
            backdrop-filter: blur(4px);
        }

        .kie-dok2__veil {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.75rem;
            background: linear-gradient(to top, rgba(18, 13, 11, 0.85), rgba(18, 13, 11, 0) 55%);
            opacity: 0;
            transition: opacity 0.4s ease;
            text-align: left;
        }

        .kie-dok2__tile:hover .kie-dok2__veil,
        .kie-dok2__tile:focus-visible .kie-dok2__veil { opacity: 1; }

        .kie-dok2__cap {
            font-size: 0.72rem;
            line-height: 1.35;
            color: #F7F1E7;
        }

        .kie-dok2__zoom {
            flex-shrink: 0;
            color: #C9A566;
            font-size: 0.7rem;
        }

        .kie-dok2__lightbox {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: rgba(12, 8, 6, 0.92);
            backdrop-filter: blur(6px);
        }

        .kie-dok2__stageItem {
            max-width: min(92vw, 68rem);
            max-height: 86vh;
            text-align: center;
        }

        .kie-dok2__stageItem img,
        .kie-dok2__stageItem video {
            max-width: min(92vw, 68rem);
            max-height: 78vh;
            border-radius: 0.9rem;
            box-shadow: 0 40px 80px -30px rgba(0, 0, 0, 0.9);
        }

        .kie-dok2__lightcap {
            margin-top: 1rem;
            font-size: 0.8rem;
            color: rgba(243, 235, 225, 0.75);
        }

        .kie-dok2__close {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            display: flex;
            height: 2.75rem;
            width: 2.75rem;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            border: 1px solid rgba(201, 165, 102, 0.35);
            background: rgba(255, 255, 255, 0.06);
            color: #F7F1E7;
            transition: background 0.25s ease;
        }

        .kie-dok2__close:hover { background: rgba(255, 255, 255, 0.16); }
    </style>

    {{-- =========================================================
         DOKUMENTASI 3 — 3D Tilt Card Stack
         ========================================================= --}}
    <section class="kie-dok3" id="dokumentasi-3">
        <div class="kie-dok3__head">
            <p class="kie-dok3__eyebrow">{{ $dok3['subjudul'] }}</p>
            <h2 class="kie-dok3__judul">{{ $dok3['judul'] }}</h2>
            <p class="kie-dok3__desc">{{ $dok3['deskripsi'] }}</p>
        </div>

        {{-- Kartu "mengipas" begitu section masuk layar (IntersectionObserver),
             lalu ikut miring halus mengikuti posisi kursor di atas panggung. --}}
        <div
            class="kie-dok3__stage"
            x-data="{
                masuk: false,
                ry: 0,
                rx: 0,
                init() {
                    new IntersectionObserver((e) => { if (e[0].isIntersecting) { this.masuk = true; } }, { threshold: 0.25 }).observe(this.$el);
                },
                gerak(ev) {
                    const r = this.$el.getBoundingClientRect();
                    this.ry = (((ev.clientX - r.left) / r.width) - 0.5) * 10;
                    this.rx = (0.5 - ((ev.clientY - r.top) / r.height)) * 6;
                },
                reset() { this.ry = 0; this.rx = 0; }
            }"
            x-on:mousemove="gerak($event)"
            x-on:mouseleave="reset()"
        >
            <div class="kie-dok3__deck" :style="`transform: rotateX(${rx}deg) rotateY(${ry}deg)`">
                @foreach ($dok3Media as $i => $media)
                    @php
                        $dok3Total = max(count($dok3Media) - 1, 1);
                        // Sudut & ketinggian kartu dihitung simetris dari tengah tumpukan.
                        $dok3Posisi = $i - ($dok3Total / 2);
                        $dok3Putar = round($dok3Posisi * -9, 2);
                        $dok3Naik = round(abs($dok3Posisi) * 14, 2);
                    @endphp
                    <figure
                        class="kie-dok3__card"
                        style="--kie-dok3-rot: {{ $dok3Putar }}deg; --kie-dok3-lift: {{ $dok3Naik }}px; --kie-dok3-delay: {{ $i * 90 }}ms; z-index: {{ 20 - $i }};"
                        :class="masuk ? 'is-masuk' : ''"
                    >
                        @if ($media['tipe'] === 'video')
                            {{-- MUTE PERMANEN. --}}
                            <video
                                src="{{ $media['src'] }}"
                                autoplay muted loop playsinline preload="metadata"
                                x-data x-init="$el.muted = true; $el.volume = 0; $el.addEventListener('volumechange', () => { $el.muted = true; $el.volume = 0; })"
                            ></video>
                            <span class="kie-dok3__badge"><i class="fa-solid fa-play"></i></span>
                        @else
                            <img src="{{ $media['src'] }}" alt="{{ $media['keterangan'] ?: 'Dokumentasi' }}" loading="lazy">
                        @endif

                        <figcaption class="kie-dok3__cap">
                            <span class="kie-dok3__num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <span>{{ $media['keterangan'] ?: 'Karya Ide Edi' }}</span>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>

    <style>
        .kie-dok3 {
            position: relative;
            overflow: hidden;
            background: #F7F4EF;
            padding: 4rem 0 5rem;
        }

        @media (min-width: 1024px) {
            .kie-dok3 { padding: 6rem 0 7rem; }
        }

        .kie-dok3__head {
            margin: 0 auto 1rem;
            max-width: 46rem;
            padding: 0 1.5rem;
            text-align: center;
        }

        .kie-dok3__eyebrow {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: #9B6E3E;
        }

        .kie-dok3__judul {
            margin-top: 0.75rem;
            font-family: var(--font-display, Georgia, serif);
            font-size: clamp(1.75rem, 3vw, 2.5rem);
            font-weight: 600;
            color: #3D2B1F;
        }

        .kie-dok3__desc {
            margin: 1rem auto 0;
            max-width: 36rem;
            font-size: 0.875rem;
            line-height: 1.75;
            color: #6B6E76;
        }

        .kie-dok3__stage {
            perspective: 1400px;
            padding: 3.5rem 1rem 1rem;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .kie-dok3__stage::-webkit-scrollbar { display: none; }

        .kie-dok3__deck {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            width: max-content;
            min-width: 100%;
            padding: 0 1.5rem 2rem;
            transform-style: preserve-3d;
            transition: transform 0.5s ease-out;
        }

        .kie-dok3__card {
            position: relative;
            margin: 0 0 0 -2.75rem;
            width: clamp(158px, 19vw, 248px);
            aspect-ratio: 3 / 4;
            flex-shrink: 0;
            overflow: hidden;
            border: 7px solid #fff;
            border-radius: 1.1rem;
            background: #EFE7D8;
            box-shadow: 0 28px 55px -22px rgba(42, 27, 18, 0.65);
            transform-style: preserve-3d;

            /* Posisi awal: tumpukan rata & rebah, sebelum masuk layar. */
            opacity: 0;
            transform: translateY(2.5rem) rotate(0deg) rotateY(0deg);
            transition:
                transform 0.85s cubic-bezier(0.22, 1, 0.36, 1) var(--kie-dok3-delay, 0ms),
                opacity 0.7s ease var(--kie-dok3-delay, 0ms),
                box-shadow 0.45s ease;
        }

        .kie-dok3__card:first-child { margin-left: 0; }

        /* Posisi akhir: mengipas miring 3D. */
        .kie-dok3__card.is-masuk {
            opacity: 1;
            transform:
                translateY(calc(var(--kie-dok3-lift, 0px) * -1))
                rotate(var(--kie-dok3-rot, 0deg))
                rotateY(calc(var(--kie-dok3-rot, 0deg) * -0.9));
        }

        /* Disentuh kursor: menegak, maju ke depan, dan naik. */
        .kie-dok3__deck:hover .kie-dok3__card.is-masuk { filter: saturate(0.85) brightness(0.95); }

        .kie-dok3__card.is-masuk:hover {
            z-index: 30 !important;
            filter: none;
            transform: translateY(-2.75rem) rotate(0deg) rotateY(0deg) translateZ(90px) scale(1.04);
            box-shadow: 0 45px 80px -25px rgba(42, 27, 18, 0.7);
            transition-delay: 0ms;
        }

        .kie-dok3__card img,
        .kie-dok3__card video {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .kie-dok3__badge {
            position: absolute;
            top: 0.6rem;
            right: 0.6rem;
            display: flex;
            height: 1.75rem;
            width: 1.75rem;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background: rgba(0, 0, 0, 0.45);
            color: #fff;
            font-size: 0.6rem;
            backdrop-filter: blur(4px);
        }

        .kie-dok3__cap {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
            padding: 2rem 0.8rem 0.75rem;
            font-size: 0.72rem;
            line-height: 1.35;
            color: #fff;
            background: linear-gradient(to top, rgba(20, 13, 9, 0.85), transparent);
        }

        .kie-dok3__num {
            font-family: var(--font-display, Georgia, serif);
            font-size: 1.05rem;
            font-weight: 600;
            color: #C9A566;
        }

        @media (prefers-reduced-motion: reduce) {
            .kie-dok3__card,
            .kie-dok3__deck { transition: none; }
        }
    </style>

    @include('partials.frontend.footer')
</body>
</html>