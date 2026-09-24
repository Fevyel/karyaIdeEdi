# ============================================================
# Mengganti gaya Hero di Beranda: dari 2-kolom (teks kiri, foto
# kartu bulat kanan) jadi 1-kolom center: judul+deskripsi+CTA+
# statistik semua rata tengah di atas, lalu foto besar full-width
# polos (tanpa kartu/rounded/shadow) di bawahnya yang memudar ke
# warna latar section -- terinspirasi referensi hero landing page
# modern yang dikasih user.
#
# SEMUA sumber data TIDAK berubah: tetap dari Admin > Edit Web >
# Header (kata pembuka, tagline, deskripsi, 3 statistik, warna
# latar, foto lewat cropper 10:9) dan Admin > Pengaturan (nama
# toko). Tombol CTA tetap hardcode sesuai aturan sebelumnya.
# Kontras warna teks otomatis tetap sama seperti sebelumnya.
#
# Yang berubah: HANYA resources/views/partials/frontend/hero.blade.php
# (ditulis ulang penuh -- bagian @php di atas persis sama, cuma
# markup HTML di bawahnya yang diganti). Tidak ada file lain yang
# disentuh.
#
# Cara pakai (dari VS Code integrated terminal, root project
# C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-ganti-layout-hero-center.ps1
#
# Setelah itu cek di browser (php artisan serve):
#   - Buka "/" (Beranda) -- Hero sekarang teks rata tengah di
#     atas, foto besar penuh di bawahnya yang memudar ke warna
#     latar. Coba juga cek dari Admin > Edit Web > Header kalau
#     warna latar/teks/foto pernah diganti, semua harus tetap
#     ikut seperti sebelumnya.
# ============================================================

$ErrorActionPreference = "Stop"

function Backup-File {
    param([string]$Path)
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$Path.bak-before-ganti-layout-hero-center-$stamp"
    Copy-Item -Path $Path -Destination $backupPath -Force
    Write-Host "  Backup dibuat: $backupPath" -ForegroundColor DarkGray
    return $backupPath
}

$heroPath = "resources\views\partials\frontend\hero.blade.php"

if (-not (Test-Path $heroPath)) {
    Write-Host "[ERROR] File tidak ditemukan: $heroPath" -ForegroundColor Red
    Write-Host "        SAYA BERHENTI, tidak ada yang diubah." -ForegroundColor Red
    exit 1
}

Backup-File -Path $heroPath | Out-Null

$newContent = @'
{{--
    ==========================================================
    HERO SECTION — Homepage Karya Ide Edi
    ==========================================================
    Ini section "Header" yang bisa diedit admin lewat
    Admin > Edit Web > Header (lihat pages/admin/edit-web.blade.php).
    Data tersimpan di tabel home_sections dengan section_key = 'header'
    (App\Models\HomeSection). Yang BISA diedit admin: warna latar, kata
    pembuka judul, tagline, deskripsi, 3 statistik, dan foto (posisi
    diatur lewat cropper drag+zoom, sama seperti Produk/Kategori/
    Pengaturan). Warna judul/deskripsi/statistik BUKAN field terpisah --
    otomatis dihitung dari kontras warna latar (lihat $contrastTextColors
    di bawah), supaya tidak pernah bertabrakan dengan warna latar apa pun
    yang dipilih admin.

    Yang TIDAK bisa diedit dari admin (sengaja hardcode, sesuai aturan
    "tombol tidak boleh diedit"):
    - Label & link tombol CTA ("Lihat Katalog" -> products.index,
      "Jelajahi Profil" -> profile.index).
    - Nama toko (ambil dari App\Models\Setting, diatur lewat menu
      Pengaturan, bukan di sini).

    ====== LAYOUT (versi ini) ======
    Tata letak diganti dari 2-kolom (teks kiri, foto kartu bulat kanan)
    jadi 1-kolom terpusat: judul + deskripsi + CTA + statistik semua
    center-align di atas, lalu foto besar full-width di bawahnya yang
    memudar (fade) ke warna latar section di bagian bawah -- terinspirasi
    layout hero landing page modern (teks besar center, foto produk besar
    di bawah, tanpa bingkai/kartu/shadow supaya lebih editorial & tidak
    terkesan "kotak" seperti sebelumnya). SEMUA data & sumbernya
    (Setting, HomeSection, cropper foto 10:9) tidak berubah -- hanya
    markup & class Tailwind di bawah @endphp yang diganti.

    Pemakaian:
        @include('partials.frontend.hero')
    ==========================================================
--}}
@php
    $heroSetting = \App\Models\Setting::current();

    $headerSection = \App\Models\HomeSection::dataFor('header', [
        'bg_color' => null,
        'headline_prefix' => 'Furnitur',
        'tagline' => 'Menghidupkan Setiap Sudut',
        'description' => 'Setiap karya dibuat dengan tangan menggunakan material pilihan berkualitas tinggi, dirancang secara teliti dan detail untuk mempercantik interior Anda.',
        'stats' => [
            ['value' => '500+', 'label' => 'Pelanggan Puas'],
            ['value' => '2.000+', 'label' => 'Karya Produk'],
            ['value' => '12th', 'label' => 'Pengalaman'],
        ],
        'image_path' => null,
    ]);

    $heroBgColor = $headerSection['bg_color'] ?: '#F9F7F2';

    /**
     * Warna judul/deskripsi/statistik & garis pembatas TIDAK diatur manual --
     * dihitung otomatis dari kontras warna latar ($heroBgColor), pakai rumus
     * relative luminance (WCAG), supaya teks selalu kebaca & tidak
     * "bertabrakan" dengan warna latar apa pun yang dipilih admin.
     * Warna latar bawaan (belum diganti admin) selalu menghasilkan palet
     * default toko (coklat #3D2B1F) persis seperti sebelumnya.
     */
    $contrastTextColors = function (string $hex): array {
        $hex = ltrim($hex, '#');

        if (strtoupper($hex) === 'F9F7F2') {
            return [
                'heading' => '#3D2B1F',
                'body' => '#6B6E76',
                'divider' => 'rgba(61, 43, 31, 0.1)',
                // Sama persis dengan border tombol "Jelajahi Profil" bawaan
                // sebelumnya (#DCDDD7) -- supaya tampilan default tidak berubah.
                'border' => '#DCDDD7',
            ];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $linearize = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $linearize($r) + 0.7152 * $linearize($g) + 0.0722 * $linearize($b);

        return $luminance > 0.5
            ? ['heading' => '#1A1208', 'body' => 'rgba(26, 18, 8, 0.72)', 'divider' => 'rgba(26, 18, 8, 0.12)', 'border' => 'rgba(26, 18, 8, 0.22)']
            : ['heading' => '#FFFFFF', 'body' => 'rgba(255, 255, 255, 0.78)', 'divider' => 'rgba(255, 255, 255, 0.18)', 'border' => 'rgba(255, 255, 255, 0.35)'];
    };

    $heroTextColors = $contrastTextColors($heroBgColor);
    $heroHeadingColor = $heroTextColors['heading'];
    $heroTextColor = $heroTextColors['body'];
    $heroDividerColor = $heroTextColors['divider'];
    // Border tombol outline "Jelajahi Profil" -- dulu hardcode #DCDDD7 sehingga
    // teks & garisnya "hilang" (nyaris tak terlihat) kalau admin pilih warna
    // latar gelap (mis. Coklat Kayu Tua / Navy Malam / Hitam Elegan), karena
    // teks tombol tetap coklat tua di atas latar gelap. Sekarang ikut kontras.
    $heroBorderColor = $heroTextColors['border'];

    $heroImageUrl = $headerSection['image_path']
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($headerSection['image_path'])
        : asset('images/admin-login/hero.png');
@endphp

<section
    class="relative overflow-hidden"
    style="background: linear-gradient(135deg, color-mix(in oklab, {{ $heroBgColor }} 100%, white 8%) 0%, {{ $heroBgColor }} 55%, color-mix(in oklab, {{ $heroBgColor }} 100%, black 12%) 100%);"
>
    {{-- ============ ATAS: Teks, CTA, Statistik — center-align ============ --}}
    <div class="relative mx-auto max-w-3xl px-6 pt-14 text-center sm:px-8 sm:pt-16 lg:px-10 lg:pt-20">
        <div class="animate-fade-in-up">
            <h1 class="font-display text-4xl leading-[1.1] sm:text-5xl lg:text-[3.25rem]" style="color: {{ $heroHeadingColor }};">
                <span class="font-semibold">{{ $headerSection['headline_prefix'] }}</span><br>
                <span class="font-semibold">{{ $heroSetting->site_name }}</span><br>
                <span class="font-normal">{{ $headerSection['tagline'] }}</span>
            </h1>

            <p class="mx-auto mt-6 max-w-lg text-sm leading-relaxed sm:text-base" style="color: {{ $heroTextColor }};">
                {{ $headerSection['description'] }}
            </p>

            {{-- CTA — TIDAK BISA DIEDIT ADMIN, sengaja hardcode. --}}
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a
                    href="{{ route('products.index') }}"
                    class="group inline-flex items-center gap-2 rounded-lg bg-[#1A1A1A] px-6 py-3 text-sm font-medium text-white shadow-sm transition-all duration-300 hover:bg-black hover:shadow-md"
                >
                    Lihat Katalog
                    <x-icon-arrow direction="right" class="transition-transform duration-300 group-hover:translate-x-1" />
                </a>
                <a
                    href="{{ route('profile.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg border px-6 py-3 text-sm font-medium transition-all duration-300 hover:border-admin-accent! hover:text-admin-accent!"
                    style="color: {{ $heroHeadingColor }}; border-color: {{ $heroBorderColor }};"
                >
                    Jelajahi Tentang Kami
                </a>
            </div>

            {{-- Statistik --}}
            <dl class="mx-auto mt-10 flex max-w-md flex-wrap items-start justify-center gap-x-10 gap-y-4 border-t pt-6" style="border-color: {{ $heroDividerColor }};">
                @foreach ($headerSection['stats'] as $stat)
                    <div>
                        <dt class="font-display text-xl sm:text-2xl" style="color: {{ $heroHeadingColor }};">{{ $stat['value'] }}</dt>
                        <dd class="mt-1 text-xs font-medium uppercase tracking-wide text-[#8A8880]">{{ $stat['label'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>

    {{-- ============ BAWAH: Foto besar, full-width, memudar ke latar ============ --}}
    <div class="relative mt-12 sm:mt-14 lg:mt-16">
        {{--
            Foto Header — diatur admin lewat Edit Web (cropper drag+zoom,
            rasio 10:9, sama seperti Produk/Kategori). Sebelum admin
            pernah mengganti foto, pakai foto bawaan
            public/images/admin-login/hero.png. Ditampilkan besar & polos
            (tanpa kartu/rounded/shadow) supaya kerasa lebih editorial,
            lalu memudar ke warna latar section di bagian bawah.
        --}}
        <div class="mx-auto w-full max-w-5xl px-6 sm:px-8 lg:px-10">
            <img
                src="{{ $heroImageUrl }}"
                alt="Furniture {{ $heroSetting->site_name }}"
                class="h-[280px] w-full object-cover object-top sm:h-[380px] lg:h-[480px]"
            >
        </div>

        {{-- Fade ke warna latar section (bukan hardcode putih, ikut $heroBgColor
             supaya tetap benar di warna latar apa pun yang dipilih admin). --}}
        <div
            class="pointer-events-none absolute inset-x-0 bottom-0 h-24 sm:h-32 lg:h-40"
            style="background: linear-gradient(to bottom, transparent, {{ $heroBgColor }});"
        ></div>
    </div>
</section>
'@

$encoding = New-Object System.Text.UTF8Encoding($true)
[System.IO.File]::WriteAllText((Join-Path (Get-Location) $heroPath), $newContent, $encoding)

Write-Host "  OK -- $heroPath ditulis ulang." -ForegroundColor Green
Write-Host ""
Write-Host "Selesai. Hero sekarang center-align dengan foto besar di bawah." -ForegroundColor Green
