<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil — {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Reveal-on-scroll khusus halaman ini — tidak menyentuh app.css global
           supaya tidak berdampak ke halaman lain. Animasi ringan (fade + slide
           kecil), bukan bounce/blink/parallax. */
        [data-reveal] {
            opacity: 0;
            transform: translateY(18px);
            transition: opacity .7s cubic-bezier(.16,1,.3,1), transform .7s cubic-bezier(.16,1,.3,1);
        }
        [data-reveal].is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        /* HP: kartu Nilai Kami digeser ke samping, jadi tidak memakai reveal
           (kartu di luar layar tidak akan pernah "terlihat" oleh observer). */
        @media (max-width: 639px) {
            .value-scroller [data-reveal] {
                opacity: 1;
                transform: none;
                transition: none;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-white font-sans text-[#2A211B] antialiased">
    @include('partials.frontend.navbar')

    @php
        $profileSetting = \App\Models\Setting::current();
        $profileWaNumber = $profileSetting->whatsappDigits();

        // Section A (Hero) -- bisa diedit admin lewat Admin > Edit Web >
        // Tentang Kami > (tab) Tentang Kami. Lihat App\Models\HomeSection,
        // section_key 'profil-toko'. Tombol "Lihat Produk" & "Hubungi Kami"
        // TIDAK diedit di sini (link & tulisan tetap, sama seperti tombol
        // CTA Header Beranda).
        $profilHero = \App\Models\HomeSection::dataFor('profil-toko', [
            'eyebrow' => 'Tentang Kami',
            'heading_line1' => 'Mewujudkan Ruang',
            'heading_line2' => 'yang Punya Cerita.',
            'description' => $profileSetting->site_name.' menghadirkan furnitur yang dibuat dengan teliti untuk melengkapi ruang Anda \u2014 bukan sekadar mengisinya. Setiap karya dirancang untuk nyaman digunakan sekaligus enak dipandang, untuk rumah maupun ruang kerja.',
            'image_path' => null,
        ]);

        $profilHeroImageUrl = $profilHero['image_path']
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($profilHero['image_path'])
            : asset('images/admin-login/kursi.png');
    @endphp

    {{-- =====================================================
         A. HERO PROFILE
         REVISI KE-3 (per masukan): SATU-SATUNYA hal yang boleh
         "niru" Hero Beranda adalah teknik gradasi hilang di bawah
         (gradient sederhana transparan -> putih, PERSIS pola yang
         dipakai Beranda). Semua yang lain (posisi teks, alignment,
         gaya eyebrow, komposisi) sengaja DIBUAT BEDA dari Beranda,
         supaya halaman ini tetap punya identitas sendiri:
         - Teks rata KIRI di area ATAS foto (Beranda: rata tengah,
           vertikal di tengah section).
         - Eyebrow gaya garis-tunggal khas halaman ini dari awal
           (Beranda pakai badge pill "Dipercaya ...").
         - Scrim gelap cuma di SISI KIRI (menyorot area teks saja),
           bukan vignette simetris penuh macam Beranda.
         - Tidak ada blur-duplikat/backdrop-filter lagi (percobaan
           sebelumnya gagal render di Chrome/Edge) -- gradasi bawah
           sekarang gradient linear biasa saja, sama sesederhana
           punya Beranda.
         Foto tetap $profilHeroImageUrl (foto asli upload admin via
         Edit Web > Tentang Kami), fallback kursi.png.
    ====================================================== --}}
    <section class="relative isolate flex min-h-[560px] items-center overflow-hidden bg-white sm:min-h-[620px] lg:min-h-[700px]">
        {{-- Foto latar — satu section penuh (FULL, edge-to-edge) --}}
        <div class="absolute inset-0 -z-10">
            <img
                src="{{ $profilHeroImageUrl }}"
                alt="Furniture {{ $profileSetting->site_name }}"
                class="absolute inset-0 h-full w-full object-cover object-center"
            >

            {{-- Scrim terang di sisi KIRI -- ini yang gantiin kartu mengambang.
                 Fotonya memudar HALUS jadi terang di area teks lalu balik jelas
                 jadi foto ke arah kanan, jadi teks menyatu langsung dengan
                 visual (bukan kotak yang "ditempel" di atasnya). Di mobile area
                 terangnya dibikin lebih lebar (karena section-nya sempit),
                 di desktop cuma nutup ±55% kiri. --}}
            <div class="absolute inset-0 lg:hidden" style="background: linear-gradient(180deg, rgba(255,255,255,.97) 0%, rgba(255,255,255,.9) 38%, rgba(255,255,255,.55) 60%, transparent 78%);"></div>
            <div class="absolute inset-0 hidden lg:block" style="background: linear-gradient(100deg, rgba(255,255,255,.96) 0%, rgba(255,255,255,.9) 30%, rgba(255,255,255,.55) 48%, transparent 66%);"></div>

            {{-- Gradasi hilang di BAWAH — SATU-SATUNYA hal yang sengaja disamakan
                 dengan pola Hero Beranda (linear-gradient transparan di atas,
                 perlahan jadi solid di paling bawah), warna akhirnya putih
                 (bukan krem $heroBgColor punya Beranda). --}}
            <div class="absolute inset-0" style="background: linear-gradient(180deg, transparent 0%, transparent 55%, rgba(255,255,255,.35) 78%, rgba(255,255,255,.78) 92%, #FFFFFF 100%);"></div>
        </div>

        {{-- Teks & CTA — langsung di atas scrim (BUKAN kartu/kotak), rata KIRI.
             Warna gelap, rata kiri, tombol solid hitam + outline abu seperti
             desain awal -- ini yang bikin halaman ini kelihatan "Tentang Kami",
             bukan Beranda (yang teksnya putih & center di atas vignette gelap). --}}
        <div class="animate-fade-in-up relative z-10 w-full px-6 py-16 sm:px-8 sm:py-20 lg:px-10 lg:py-24" data-reveal>
            <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-admin-accent">
                <span class="h-px w-8 bg-admin-accent"></span>
                Tentang Kami
            </div>

            <h1 class="mt-5 max-w-lg font-display text-4xl leading-[1.12] text-[#3D2B1F] sm:text-5xl lg:text-[3.25rem]">
                Mewujudkan Ruang
                <br>
                yang Punya Cerita.
            </h1>

            <p class="mt-6 max-w-md text-sm leading-relaxed text-[#6B6E76] sm:text-base">
                {{ $profileSetting->site_name }} menghadirkan furnitur yang dibuat dengan
                teliti untuk melengkapi ruang Anda — bukan sekadar mengisinya. Setiap
                karya dirancang untuk nyaman digunakan sekaligus enak dipandang, untuk
                rumah maupun ruang kerja.
            </p>

            <div class="mt-8 flex flex-wrap items-center gap-3">
                <a
                    href="{{ route('products.index') }}"
                    class="group inline-flex items-center gap-2 rounded-lg bg-[#1A1A1A] px-6 py-3 text-sm font-medium text-white shadow-sm transition-all duration-300 hover:bg-black hover:shadow-md"
                >
                    Lihat Produk
                    <x-icon-arrow direction="right" class="transition-transform duration-300 group-hover:translate-x-1" />
                </a>
                <a
                    href="{{ $profileWaNumber ? 'https://wa.me/'.$profileWaNumber : '#' }}"
                    @if ($profileWaNumber) target="_blank" rel="noopener" @endif
                    class="inline-flex items-center gap-2 rounded-lg border border-[#DCDDD7] px-6 py-3 text-sm font-medium text-[#3D2B1F] transition-all duration-300 hover:border-admin-accent hover:text-admin-accent"
                >
                    <i class="fa-brands fa-whatsapp"></i>
                    Hubungi Kami
                </a>
            </div>
        </div>
    </section>

    {{-- =====================================================
         B. TENTANG KARYA IDE EDI
    ====================================================== --}}
    @php
        // Bagian B bisa diedit admin lewat Admin > Edit Web > Tentang Kami > Tentang Kami 2
        // (section_key 'tentang-kami-2' di home_sections, lihat pages/admin/edit-web.blade.php
        // method saveTentangKami2()): warna frame (polos + gradasi opsional), foto, dan isi teks.
        // Belum pernah diedit = tampilan PERSIS seperti sebelumnya (latar krem lembut, foto
        // hero.png, teks bawaan). Nilai bawaan di bawah HARUS sama dengan tentangKami2Defaults()
        // di edit-web.
        $profilTk2 = \App\Models\HomeSection::dataFor('tentang-kami-2', [
            'bg_color' => null,
            'eyebrow' => 'Tentang Kami',
            'heading' => 'Lebih dari sekadar furniture.',
            'description' => $profileSetting->site_name." adalah toko furniture yang menghadirkan produk untuk membantu Anda menciptakan ruang yang nyaman, fungsional, dan punya karakter. Kami percaya furnitur yang baik bukan cuma soal bentuk \u{2014} tapi juga soal bagaimana ia membuat ruang terasa lebih hidup untuk dipakai sehari-hari.\n\nDari kebutuhan rumah tangga sampai ruang kerja, setiap produk kami pilih dan siapkan dengan memperhatikan kualitas bahan, kenyamanan pemakaian, dan kejelasan informasi \u{2014} supaya Anda bisa memutuskan dengan tenang.",
            'image_path' => null,
        ]);

        $tk2ImageUrl = $profilTk2['image_path']
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($profilTk2['image_path'])
            : null;

        // Paragraf dipisah baris kosong; baris tunggal di dalam paragraf tetap jadi <br>.
        $tk2Paragraf = array_values(array_filter(
            array_map('trim', preg_split('/\R{2,}/', trim((string) $profilTk2['description'])) ?: []),
            fn ($paragraf) => $paragraf !== '',
        ));

        // Latar: bawaan class Tailwind lama (krem lembut). Warna khusus/gradasi hanya kalau admin memilihnya.
        $tk2Frame = \App\Support\FrameBackground::resolve($profilTk2['bg_color'] ?? null, $profilTk2['bg_gradient'] ?? null, '#FBF8F3');
        $tk2PakaiWarnaKhusus = $tk2Frame['is_gradient'] || \App\Support\FrameBackground::hex($profilTk2['bg_color'] ?? null) !== null;

        $tk2HexTerang = function (string $hex): bool {
            $hex = ltrim($hex, '#');

            $r = hexdec(substr($hex, 0, 2)) / 255;
            $g = hexdec(substr($hex, 2, 2)) / 255;
            $b = hexdec(substr($hex, 4, 2)) / 255;

            $linearize = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;

            return (0.2126 * $linearize($r) + 0.7152 * $linearize($g) + 0.0722 * $linearize($b)) > 0.5;
        };

        $tk2Colors = $tk2PakaiWarnaKhusus
            ? ($tk2HexTerang($tk2Frame['base'])
                ? ['ink' => '#1A1208', 'soft' => 'rgba(26, 18, 8, 0.72)', 'accent' => '#7A4F26']
                : ['ink' => '#FFFFFF', 'soft' => 'rgba(255, 255, 255, 0.75)', 'accent' => '#E3C58B'])
            : ['ink' => '', 'soft' => '', 'accent' => ''];

        // Atribut style hanya dihasilkan kalau warna khusus dipakai.
        $tk2Style = fn (string $key): string => $tk2PakaiWarnaKhusus ? 'color: '.$tk2Colors[$key].';' : '';
    @endphp

    {{-- Frame seam: Hero Profil (bg-white, tidak punya opsi warna) -> Tentang Kami 2 --}}
    @include('partials.frontend.frame-seam', ['from' => '#FFFFFF', 'to' => $tk2Frame['base']])

    <section
        class="{{ $tk2PakaiWarnaKhusus ? '' : 'bg-admin-cream/40' }}"
        @if ($tk2PakaiWarnaKhusus) style="background: {{ $tk2Frame['css'] }};" @endif
    >
        <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 py-16 max-sm:gap-8 max-sm:py-12 sm:px-8 lg:grid-cols-2 lg:gap-16 lg:px-10 lg:py-24">
            <div class="order-2 lg:order-1" data-reveal>
                {{-- Foto bawaan (hero.png) tetap berbingkai 4:3 di HP seperti sebelumnya; foto upload admin
                     dipotong 4:5 dari cropper, jadi bingkainya 4:5 di semua ukuran layar. --}}
                <span class="relative flex aspect-4/5 w-full items-center justify-center overflow-hidden rounded-3xl bg-white shadow-lg {{ $tk2ImageUrl ? '' : 'max-sm:aspect-4/3' }}">
                    @if ($tk2ImageUrl)
                        <img
                            src="{{ $tk2ImageUrl }}"
                            alt="Tentang {{ $profileSetting->site_name }}"
                            loading="lazy"
                            class="h-full w-full object-cover"
                        >
                    @else
                        <img
                            src="{{ asset('images/admin-login/hero.png') }}"
                            alt="Furniture {{ $profileSetting->site_name }}"
                            class="h-[82%] w-auto object-contain"
                        >
                    @endif
                </span>
            </div>

            <div class="order-1 lg:order-2" data-reveal style="transition-delay:.1s">
                <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-admin-accent" style="{{ $tk2Style('accent') }}">
                    <span class="h-px w-8 bg-admin-accent" @if ($tk2PakaiWarnaKhusus) style="background: {{ $tk2Colors['accent'] }};" @endif></span>
                    {{ $profilTk2['eyebrow'] }}
                </div>

                <h2 class="mt-5 font-display text-3xl leading-tight text-[#4B3A26] sm:text-4xl" style="{{ $tk2Style('ink') }}">
                    {{ $profilTk2['heading'] }}
                </h2>

                @foreach ($tk2Paragraf as $paragraf)
                    <p class="{{ $loop->first ? 'mt-6' : 'mt-4' }} max-w-lg text-sm leading-relaxed text-admin-ink-soft sm:text-base" style="{{ $tk2Style('soft') }}">
                        {!! nl2br(e($paragraf)) !!}
                    </p>
                @endforeach
            </div>
        </div>
    </section>

    {{-- =====================================================
         B2. SEJARAH
         Bisa diedit admin lewat Admin > Edit Web > Tentang Kami > Sejarah
         (section_key 'sejarah' di tabel home_sections, lihat
         pages/admin/edit-web.blade.php method saveSejarah()). Yang bisa
         diedit: warna frame (warna polos + gradasi opsional), foto, dan isi
         teks (label kecil, judul 2 baris, paragraf).

         Bawaan: latar putih polos. Kalau admin memilih warna/gradasi khusus,
         warna teks otomatis menyesuaikan terang/gelapnya latar (pola yang sama
         dengan partials/frontend/lokasi.blade.php) supaya tetap terbaca.
         Nilai bawaan di bawah HARUS sama dengan sejarahDefaults() di edit-web.
    ====================================================== --}}
    @php
        $profilSejarah = \App\Models\HomeSection::dataFor('sejarah', [
            'bg_color' => null,
            'eyebrow' => 'Sejarah Kami',
            'heading_line1' => 'Perjalanan Kami',
            'heading_line2' => 'dari Awal Hingga Kini.',
            'description' => $profileSetting->site_name." terus tumbuh dari satu karya ke karya berikutnya, dibuat dengan tangan dan dengan perhatian pada setiap detail.\n\nSetiap pesanan menjadi kesempatan untuk belajar, menjaga mutu, dan membuat furnitur yang benar-benar dipakai dan disukai pelanggan.",
            'image_path' => null,
        ]);

        $sejarahImageUrl = $profilSejarah['image_path']
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($profilSejarah['image_path'])
            : null;

        // Paragraf dipisah baris kosong; baris tunggal di dalam paragraf tetap jadi <br>.
        $sejarahParagraf = array_values(array_filter(
            array_map('trim', preg_split('/\R{2,}/', trim((string) $profilSejarah['description'])) ?: []),
            fn ($paragraf) => $paragraf !== '',
        ));

        // Latar: polos secara bawaan, gradasi hanya kalau dinyalakan admin.
        $sejarahFrame = \App\Support\FrameBackground::resolve($profilSejarah['bg_color'] ?? null, $profilSejarah['bg_gradient'] ?? null, '#FFFFFF');
        $sejarahBase = $sejarahFrame['base'];
        $sejarahPakaiWarnaKhusus = $sejarahFrame['is_gradient'] || strtoupper(ltrim($sejarahBase, '#')) !== 'FFFFFF';

        $sejarahHexTerang = function (string $hex): bool {
            $hex = ltrim($hex, '#');

            $r = hexdec(substr($hex, 0, 2)) / 255;
            $g = hexdec(substr($hex, 2, 2)) / 255;
            $b = hexdec(substr($hex, 4, 2)) / 255;

            $linearize = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;

            return (0.2126 * $linearize($r) + 0.7152 * $linearize($g) + 0.0722 * $linearize($b)) > 0.5;
        };

        $sejarahColors = $sejarahPakaiWarnaKhusus
            ? ($sejarahHexTerang($sejarahBase)
                ? ['ink' => '#1A1208', 'soft' => 'rgba(26, 18, 8, 0.72)', 'accent' => '#7A4F26']
                : ['ink' => '#FFFFFF', 'soft' => 'rgba(255, 255, 255, 0.75)', 'accent' => '#E3C58B'])
            : ['ink' => '', 'soft' => '', 'accent' => ''];

        // Atribut style hanya dihasilkan kalau warna khusus dipakai.
        $sejarahStyle = fn (string $key): string => $sejarahPakaiWarnaKhusus ? 'color: '.$sejarahColors[$key].';' : '';
    @endphp

    {{-- Frame seam: Tentang Kami 2 -> Sejarah --}}
    @include('partials.frontend.frame-seam', ['from' => $tk2Frame['base'], 'to' => $sejarahBase])

    <section
        id="sejarah"
        class="{{ $sejarahPakaiWarnaKhusus ? '' : 'bg-white' }}"
        @if ($sejarahPakaiWarnaKhusus) style="background: {{ $sejarahFrame['css'] }};" @endif
    >
        <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 py-16 max-sm:gap-8 max-sm:py-12 sm:px-8 lg:grid-cols-2 lg:gap-16 lg:px-10 lg:py-24">
            <div data-reveal>
                <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-admin-accent" style="{{ $sejarahStyle('accent') }}">
                    <span class="h-px w-8 bg-admin-accent" @if ($sejarahPakaiWarnaKhusus) style="background: {{ $sejarahColors['accent'] }};" @endif></span>
                    {{ $profilSejarah['eyebrow'] }}
                </div>

                <h2 class="mt-5 font-display text-3xl leading-tight text-[#4B3A26] sm:text-4xl" style="{{ $sejarahStyle('ink') }}">
                    {{ $profilSejarah['heading_line1'] }}<br>{{ $profilSejarah['heading_line2'] }}
                </h2>

                @foreach ($sejarahParagraf as $paragraf)
                    <p class="{{ $loop->first ? 'mt-6' : 'mt-4' }} max-w-lg text-sm leading-relaxed text-admin-ink-soft sm:text-base" style="{{ $sejarahStyle('soft') }}">
                        {!! nl2br(e($paragraf)) !!}
                    </p>
                @endforeach
            </div>

            <div data-reveal style="transition-delay:.1s">
                <span class="relative flex aspect-4/5 w-full items-center justify-center overflow-hidden rounded-3xl bg-admin-cream shadow-lg">
                    @if ($sejarahImageUrl)
                        <img
                            src="{{ $sejarahImageUrl }}"
                            alt="Sejarah {{ $profileSetting->site_name }}"
                            loading="lazy"
                            class="h-full w-full object-cover"
                        >
                    @else
                        <img
                            src="{{ asset('images/admin-login/kursi.png') }}"
                            alt="Furniture {{ $profileSetting->site_name }}"
                            class="h-[82%] w-auto object-contain"
                        >
                    @endif
                </span>
            </div>
        </div>
    </section>

    {{-- =====================================================
         C. NILAI / KEUNGGULAN ("Nilai Kami")
         Bisa diedit admin lewat Admin > Edit Web > Tentang Kami > Nilai Kami
         (section_key 'nilai-kami' di tabel home_sections, lihat
         pages/admin/edit-web.blade.php method saveNilaiKami()). Yang bisa
         diedit: warna frame (warna polos + gradasi opsional), foto tiap
         kartu, dan isi teks (label kecil, judul, judul + deskripsi kartu).
         Nomor & ikon kartu tetap.

         Belum pernah diedit = tampilan PERSIS seperti sebelumnya (latar krem
         bg-admin-cream, teks bawaan, foto kartu diambil dari produk aktif).
         Nilai bawaan di bawah HARUS sama dengan nilaiKamiDefaults() di edit-web.
    ====================================================== --}}
    @php
        $profilNilai = \App\Models\HomeSection::dataFor('nilai-kami', [
            'bg_color' => null,
            'eyebrow' => 'Nilai Kami',
            'heading' => 'Yang kami utamakan di setiap karya.',
            'items' => [
                ['title' => 'Kualitas Terpilih', 'desc' => 'Produk dipilih dengan mempertimbangkan kualitas dan fungsi.', 'image_path' => null],
                ['title' => 'Desain Berkarakter', 'desc' => 'Furniture yang dirancang untuk melengkapi berbagai gaya ruang.', 'image_path' => null],
                ['title' => 'Pelayanan Terpercaya', 'desc' => 'Memberikan pengalaman belanja yang nyaman dan jelas.', 'image_path' => null],
                ['title' => 'Untuk Setiap Ruang', 'desc' => 'Pilihan furniture untuk kebutuhan rumah maupun ruang kerja.', 'image_path' => null],
            ],
        ]);

        // Latar: bawaan class Tailwind lama (krem). Warna khusus/gradasi hanya kalau admin memilihnya.
        $nilaiFrame = \App\Support\FrameBackground::resolve($profilNilai['bg_color'] ?? null, $profilNilai['bg_gradient'] ?? null, '#F4EDE0');
        $nilaiPakaiWarnaKhusus = $nilaiFrame['is_gradient'] || \App\Support\FrameBackground::hex($profilNilai['bg_color'] ?? null) !== null;

        $nilaiHexTerang = function (string $hex): bool {
            $hex = ltrim($hex, '#');

            $r = hexdec(substr($hex, 0, 2)) / 255;
            $g = hexdec(substr($hex, 2, 2)) / 255;
            $b = hexdec(substr($hex, 4, 2)) / 255;

            $linearize = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;

            return (0.2126 * $linearize($r) + 0.7152 * $linearize($g) + 0.0722 * $linearize($b)) > 0.5;
        };

        // Hanya label kecil & judul yang berada di atas latar section; kartu tetap putih.
        $nilaiColors = $nilaiPakaiWarnaKhusus
            ? ($nilaiHexTerang($nilaiFrame['base'])
                ? ['ink' => '#1A1208', 'accent' => '#7A4F26']
                : ['ink' => '#FFFFFF', 'accent' => '#E3C58B'])
            : ['ink' => '', 'accent' => ''];

        // Atribut style hanya dihasilkan kalau warna khusus dipakai.
        $nilaiStyle = fn (string $key): string => $nilaiPakaiWarnaKhusus ? 'color: '.$nilaiColors[$key].';' : '';
    @endphp

    {{-- Frame seam: Sejarah -> Nilai Kami --}}
    @include('partials.frontend.frame-seam', ['from' => $sejarahBase, 'to' => $nilaiFrame['base']])

    <section
        class="{{ $nilaiPakaiWarnaKhusus ? '' : 'bg-admin-cream' }}"
        @if ($nilaiPakaiWarnaKhusus) style="background: {{ $nilaiFrame['css'] }};" @endif
    >
        <div class="mx-auto max-w-7xl px-6 py-16 max-sm:py-12 sm:px-8 lg:px-10 lg:py-24">
            <div class="mx-auto max-w-xl text-center" data-reveal>
                <div class="mx-auto flex items-center justify-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-admin-accent" style="{{ $nilaiStyle('accent') }}">
                    <span class="h-px w-8 bg-admin-accent" @if ($nilaiPakaiWarnaKhusus) style="background: {{ $nilaiColors['accent'] }};" @endif></span>
                    {{ $profilNilai['eyebrow'] }}
                    <span class="h-px w-8 bg-admin-accent" @if ($nilaiPakaiWarnaKhusus) style="background: {{ $nilaiColors['accent'] }};" @endif></span>
                </div>
                <h2 class="mt-5 font-display text-3xl leading-tight text-[#4B3A26] sm:text-4xl" style="{{ $nilaiStyle('ink') }}">
                    {{ $profilNilai['heading'] }}
                </h2>
            </div>

            @php
                // Nomor & ikon kartu tetap; judul, deskripsi, dan foto bisa diedit admin.
                $valueFixed = [
                    ['no' => '01', 'icon' => 'fa-gem'],
                    ['no' => '02', 'icon' => 'fa-drafting-compass'],
                    ['no' => '03', 'icon' => 'fa-shield-halved'],
                    ['no' => '04', 'icon' => 'fa-house'],
                ];

                // Visual kartu: foto upload admin; kalau belum ada, diambil dari foto
                // produk ASLI (thumbnail) seperti sebelumnya, supaya "Nilai Kami"
                // kelihatan konkret. Kalau produk berfoto belum sampai 4, sisanya
                // otomatis fallback ke tampilan ikon polos (tanpa foto rusak).
                $valueShowcaseProducts = \App\Models\Product::query()
                    ->where('status', 'aktif')
                    ->whereNotNull('thumbnail')
                    ->orderByDesc('featured')
                    ->orderByDesc('created_at')
                    ->take(4)
                    ->get()
                    ->values();

                $valueDefs = [];

                foreach ($valueFixed as $i => $fixed) {
                    $valueItem = $profilNilai['items'][$i] ?? [];
                    $valueCustomImage = $valueItem['image_path'] ?? null;
                    $valueProduct = $valueShowcaseProducts->get($i);

                    if ($valueCustomImage && \Illuminate\Support\Facades\Storage::disk('public')->exists($valueCustomImage)) {
                        $valueImage = \Illuminate\Support\Facades\Storage::disk('public')->url($valueCustomImage);
                    } elseif ($valueProduct && \Illuminate\Support\Facades\Storage::disk('public')->exists($valueProduct->thumbnail)) {
                        $valueImage = \Illuminate\Support\Facades\Storage::disk('public')->url($valueProduct->thumbnail);
                    } else {
                        $valueImage = null;
                    }

                    $valueDefs[] = [
                        'no' => $fixed['no'],
                        'icon' => $fixed['icon'],
                        'title' => $valueItem['title'] ?? '',
                        'desc' => $valueItem['desc'] ?? '',
                        'image' => $valueImage,
                    ];
                }
            @endphp

            <div class="value-scroller mt-12 grid grid-cols-1 gap-6 max-sm:-mx-6 max-sm:mt-8 max-sm:flex max-sm:snap-x max-sm:snap-mandatory max-sm:scroll-pl-6 max-sm:gap-4 max-sm:overflow-x-auto max-sm:px-6 max-sm:pb-3 max-sm:[scrollbar-width:none] max-sm:[&::-webkit-scrollbar]:hidden sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($valueDefs as $i => $value)
                    <div
                        data-reveal
                        style="transition-delay:{{ $i * .08 }}s"
                        class="group overflow-hidden rounded-2xl border border-admin-border bg-white transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/5 max-sm:w-[78%] max-sm:shrink-0 max-sm:snap-start"
                    >
                        <div class="relative aspect-4/3 w-full overflow-hidden bg-admin-cream">
                            @if ($value['image'])
                                <img
                                    src="{{ $value['image'] }}"
                                    alt="{{ $value['title'] }}"
                                    loading="lazy"
                                    class="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105"
                                >
                            @else
                                <div class="flex h-full w-full items-center justify-center text-admin-ink-soft/30">
                                    <i class="fa-solid {{ $value['icon'] }} text-3xl"></i>
                                </div>
                            @endif
                            <span class="absolute left-3 top-3 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-admin-accent shadow-sm backdrop-blur-sm">
                                <i class="fa-solid {{ $value['icon'] }}"></i>
                            </span>
                        </div>
                        <div class="p-6">
                            <p class="font-display text-xl text-admin-gold">{{ $value['no'] }}</p>
                            <p class="mt-2 text-base font-semibold text-[#3D2B1F]">{{ $value['title'] }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-admin-ink-soft">{{ $value['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- =====================================================
         D. WHY CHOOSE US
         Bisa diedit admin lewat Admin > Edit Web > Tentang Kami > Why Choose Us
         (section_key 'why-choose-us-profil' di tabel home_sections, lihat
         pages/admin/edit-web.blade.php method saveWhyChooseUs()). Yang bisa
         diedit: warna frame (warna polos + gradasi opsional), isi teks (label
         kecil, judul, paragraf, teks 5 kartu), dan ikon tiap kartu.

         Belum pernah diedit = tampilan PERSIS seperti sebelumnya (latar cokelat
         gelap bg-[#221B14], teks bawaan). Nilai bawaan di bawah HARUS sama
         dengan whyChooseUsDefaults() di edit-web.
    ====================================================== --}}
    @php
        $whyProfil = \App\Models\HomeSection::dataFor('why-choose-us-profil', [
            'bg_color' => null,
            'eyebrow' => 'Why Choose Us',
            'heading' => 'Kenapa memilih '.$profileSetting->site_name.'?',
            'description' => "Kami ingin proses memilih furniture terasa mudah dan tenang \u{2014} dari melihat produk sampai memutuskan yang paling cocok untuk ruang Anda.",
            'items' => [
                ['icon' => 'fa-layer-group', 'text' => 'Produk pilihan'],
                ['icon' => 'fa-circle-info', 'text' => 'Informasi produk yang jelas'],
                ['icon' => 'fa-cart-shopping', 'text' => 'Proses pemesanan mudah'],
                ['icon' => 'fa-headset', 'text' => 'Dukungan pelanggan'],
                ['icon' => 'fa-couch', 'text' => 'Pengalaman belanja yang nyaman'],
            ],
        ]);

        // 5 kartu poin. Nama ikon dijaga hanya berbentuk "fa-xxx".
        $whyProfilPoints = collect($whyProfil['items'])->take(5)->map(fn ($item) => [
            'icon' => is_string($item['icon'] ?? null) && preg_match('/^fa-[a-z0-9-]+$/', $item['icon']) === 1 ? $item['icon'] : 'fa-circle-check',
            'text' => (string) ($item['text'] ?? ''),
        ])->all();

        // Latar: bawaan class Tailwind lama (cokelat gelap). Warna khusus/gradasi hanya kalau admin memilihnya.
        $whyProfilFrame = \App\Support\FrameBackground::resolve($whyProfil['bg_color'] ?? null, $whyProfil['bg_gradient'] ?? null, '#221B14');
        $whyProfilPakaiWarnaKhusus = $whyProfilFrame['is_gradient'] || \App\Support\FrameBackground::hex($whyProfil['bg_color'] ?? null) !== null;

        $whyProfilTerang = function (string $hex): bool {
            $hex = ltrim($hex, '#');

            $r = hexdec(substr($hex, 0, 2)) / 255;
            $g = hexdec(substr($hex, 2, 2)) / 255;
            $b = hexdec(substr($hex, 4, 2)) / 255;

            $linearize = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;

            return (0.2126 * $linearize($r) + 0.7152 * $linearize($g) + 0.0722 * $linearize($b)) > 0.5;
        };

        // Teks bawaan section ini terang (untuk latar gelap). Hanya kalau admin memilih
        // frame TERANG, teks & kartu diganti gelap supaya tetap kebaca.
        $whyProfilTerangMode = $whyProfilPakaiWarnaKhusus && $whyProfilTerang($whyProfilFrame['base']);
        $whyProfilWarna = [
            'ink' => '#1A1208',
            'muted' => 'rgba(26, 18, 8, 0.68)',
            'accent' => '#7A4F26',
            'line' => 'rgba(26, 18, 8, 0.12)',
            'card' => 'rgba(255, 255, 255, 0.7)',
            'chip' => 'rgba(122, 79, 38, 0.14)',
        ];

        // Atribut style hanya dihasilkan kalau perlu (selain itu tampilan bawaan tidak disentuh).
        $whyProfilGaya = fn (string $css): string => $whyProfilTerangMode ? ' style="'.e($css).'"' : '';
    @endphp

    {{-- Frame seam: Nilai Kami -> Why Choose Us --}}
    @include('partials.frontend.frame-seam', ['from' => $nilaiFrame['base'], 'to' => $whyProfilFrame['base']])

    <section class="{{ $whyProfilPakaiWarnaKhusus ? '' : 'bg-[#221B14]' }}"@if ($whyProfilPakaiWarnaKhusus) style="background: {{ $whyProfilFrame['css'] }};" @endif>
        <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 py-16 max-sm:gap-8 max-sm:py-12 sm:px-8 lg:grid-cols-2 lg:gap-16 lg:px-10 lg:py-24">
            <div data-reveal>
                <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-admin-gold"{!! $whyProfilGaya('color: '.$whyProfilWarna['accent'].';') !!}>
                    <span class="h-px w-8 bg-admin-gold"{!! $whyProfilGaya('background: '.$whyProfilWarna['accent'].';') !!}></span>
                    {{ $whyProfil['eyebrow'] }}
                </div>
                <h2 class="mt-5 font-display text-3xl leading-tight text-white sm:text-4xl"{!! $whyProfilGaya('color: '.$whyProfilWarna['ink'].';') !!}>
                    {{ $whyProfil['heading'] }}
                </h2>
                <p class="mt-5 max-w-md text-sm leading-relaxed text-white/60 sm:text-base"{!! $whyProfilGaya('color: '.$whyProfilWarna['muted'].';') !!}>
                    {{ $whyProfil['description'] }}
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" data-reveal style="transition-delay:.1s">
                @foreach ($whyProfilPoints as $point)
                    <div class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/4 px-4 py-3.5"{!! $whyProfilGaya('border-color: '.$whyProfilWarna['line'].'; background: '.$whyProfilWarna['card'].';') !!}>
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-admin-gold/15 text-admin-gold"{!! $whyProfilGaya('background: '.$whyProfilWarna['chip'].'; color: '.$whyProfilWarna['accent'].';') !!}>
                            <i class="fa-solid {{ $point['icon'] }} text-sm"></i>
                        </span>
                        <p class="text-sm font-medium text-white"{!! $whyProfilGaya('color: '.$whyProfilWarna['ink'].';') !!}>{{ $point['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @include('partials.frontend.footer')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const revealTargets = document.querySelectorAll('[data-reveal]');

            if (!('IntersectionObserver' in window) || revealTargets.length === 0) {
                revealTargets.forEach((el) => el.classList.add('is-visible'));
                return;
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

            revealTargets.forEach((el) => observer.observe(el));
        });
    </script>
</body>
</html>