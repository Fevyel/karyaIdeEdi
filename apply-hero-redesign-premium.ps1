# apply-hero-redesign-premium.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   powershell -ExecutionPolicy Bypass -File apply-hero-redesign-premium.ps1
#
# Yang dilakukan:
# 1) navbar.blade.php -> header diubah dari "fixed" jadi "sticky top-0".
#    Header "fixed" keluar dari alur normal halaman, jadi section
#    sesudahnya (hero) tidak pernah tahu harus kasih jarak berapa untuk
#    header itu -- ditambah `zoom: 80%` yang dipasang di <html> khusus
#    halaman frontend (lihat resources/css/app.css), kombinasi ini yang
#    bikin ada "space" tak terduga di bawah navbar. `sticky` menjaga
#    header tetap nempel di atas SAAT discroll (perilaku visualnya sama
#    seperti sebelumnya), tapi tetap ikut alur normal dokumen -- jadi
#    tidak akan pernah ada gap atau tumpang tindih lagi, berapa pun
#    tinggi navbar-nya, dan tidak perlu tambahan padding manual di
#    section manapun.
# 2) hero.blade.php -> desain ulang total secara VISUAL:
#    - Vignette tipis di tepi foto (efek sinematik)
#    - Cahaya (glow) jadi SATU lapisan besar & lembut di belakang
#      seluruh blok teks (bukan kotak blur per baris yang bikin
#      belang seperti sebelumnya)
#    - Badge sosial-proof di atas judul (otomatis dari statistik
#      pertama yang sudah ada)
#    - Hierarki tipografi: label kecil -> nama toko besar -> tagline
#      miring
#    - Statistik jadi kartu kaca (glass card) dengan ikon
#    - Animasi masuk bertahap & indikator scroll di bawah
#    SEMUA data admin (warna latar, teks, cropper foto, kontras warna
#    otomatis, CTA hardcode) TIDAK berubah sama sekali -- murni
#    tampilan.

$ErrorActionPreference = "Stop"

$navbarPath = "resources/views/partials/frontend/navbar.blade.php"
$heroPath   = "resources/views/partials/frontend/hero.blade.php"

if (-not (Test-Path $navbarPath)) { throw "Tidak ketemu: $navbarPath (jalankan script ini dari root project)" }
if (-not (Test-Path $heroPath))   { throw "Tidak ketemu: $heroPath (jalankan script ini dari root project)" }

# Backup dulu (sesuai kebiasaan project ini)
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
Copy-Item $navbarPath "$navbarPath.bak-before-hero-redesign-$stamp"
Copy-Item $heroPath   "$heroPath.bak-before-hero-redesign-$stamp"

# ---------- 1. navbar.blade.php: fixed -> sticky ----------
$navbar = Get-Content $navbarPath -Raw -Encoding UTF8

$oldHeader = '<header class="fixed inset-x-0 top-0 z-100 border-b border-admin-border/80 bg-admin-surface/95 backdrop-blur-md">'
$newHeader = '<header class="sticky top-0 z-100 border-b border-admin-border/80 bg-admin-surface/95 backdrop-blur-md">'

if ($navbar -notmatch [regex]::Escape($oldHeader)) {
    throw "Baris <header> di navbar.blade.php tidak cocok persis (mungkin sudah pernah diubah). Tidak ada yang ditimpa, cek manual."
}
$navbar = $navbar.Replace($oldHeader, $newHeader)
Set-Content $navbarPath -Value $navbar -Encoding UTF8 -NoNewline

# ---------- 2. hero.blade.php: replace penuh (redesign visual) ----------
$newHero = @'
{{--
    ==========================================================
    HERO SECTION — Homepage Karya Ide Edi
    ==========================================================
    Ini section "Header" yang bisa diedit admin lewat
    Admin > Edit Web > Header (lihat pages/admin/edit-web.blade.php).
    Data tersimpan di tabel home_sections dengan section_key = 'header'
    (App\Models\HomeSection). Yang BISA diedit admin: warna latar, kata
    pembuka judul, tagline, deskripsi, 3 statistik, cahaya (glow) teks,
    dan foto (posisi diatur lewat cropper drag+zoom, sama seperti
    Produk/Kategori/Pengaturan). Warna judul/deskripsi/statistik BUKAN
    field terpisah -- otomatis dihitung dari kontras warna latar (lihat
    $contrastTextColors di bawah), supaya tidak pernah bertabrakan
    dengan warna latar apa pun yang dipilih admin.

    Yang TIDAK bisa diedit dari admin (sengaja hardcode, sesuai aturan
    "tombol tidak boleh diedit"):
    - Label & link tombol CTA ("Lihat Katalog" -> products.index,
      "Jelajahi Profil" -> profile.index).
    - Nama toko (ambil dari App\Models\Setting, diatur lewat menu
      Pengaturan, bukan di sini).
    - Badge "Dipercaya ..." di atas judul -- teksnya diambil otomatis
      dari statistik PERTAMA yang sudah ada (tidak ada field baru).

    ====== VERSI REDESIGN (premium) ======
    Perubahan dari versi sebelumnya (murni visual, SEMUA sumber data,
    field admin, dan logika kontras/glow TIDAK berubah):
    1) Cahaya (glow) sekarang SATU lapisan cahaya besar & lembut di
       belakang seluruh blok teks (bukan kotak blur kecil per baris
       seperti sebelumnya yang bikin belang) -- posisinya "isolate"
       supaya dijamin selalu di belakang teks, seberapa pun warna
       foto latarnya.
    2) Vignette tipis di tepi foto supaya teks lebih menonjol (efek
       sinematik), lapisan warna latar admin ($heroBgColor) di bawahnya
       tidak diubah sama sekali.
    3) Hierarki tipografi: kata pembuka jadi label kecil "eyebrow",
       nama toko jadi fokus utama (besar), tagline jadi subjudul
       miring -- datanya tetap headline_prefix/site_name/tagline yang
       sama persis.
    4) Badge sosial-proof di atas judul, statistik jadi "kartu kaca"
       dengan ikon, indikator scroll di bawah -- semua elemen dekoratif
       murni, tidak menambah field admin baru.
    5) Animasi masuk bertahap (badge -> judul -> deskripsi -> tombol ->
       statistik), pakai animasi `fade-in-up` yang SUDAH ada di
       resources/css/app.css (tidak menambah keyframe baru).

    Pemakaian:
        @include('partials.frontend.hero')
    ==========================================================
--}}
@php
    $heroSetting = \App\Models\Setting::current();

    $headerSection = \App\Models\HomeSection::dataFor('header', [
        'bg_color' => null,
        'text_color' => null,
        'text_glow' => null,
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
     * Warna cahaya (glow) di belakang teks -- diatur admin lewat Admin >
     * Edit Web > Header ("Beri cahaya di belakang teks"). null kalau admin
     * tidak mengaktifkannya (tidak ada glow yang dirender sama sekali).
     * SATU lapisan cahaya besar & lembut untuk seluruh blok teks (lihat
     * span "isolate" di markup) -- bukan kotak per baris, supaya hasilnya
     * terasa seperti cahaya alami di belakang teks, bukan belang/smudge.
     */
    $heroGlowRgb = match ($headerSection['text_glow']) {
        'white' => '255,255,255',
        'black' => '0,0,0',
        default => null,
    };

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

    // Override manual dari Admin > Edit Web > Header ("Warna Teks"). Kalau
    // admin tidak mengaktifkannya, $headerSection['text_color'] tetap null
    // dan kontras otomatis di atas yang dipakai (tidak ada perubahan).
    if ($headerSection['text_color'] === 'black') {
        $heroTextColors = ['heading' => '#1A1208', 'body' => 'rgba(26, 18, 8, 0.72)', 'divider' => 'rgba(26, 18, 8, 0.12)', 'border' => 'rgba(26, 18, 8, 0.22)'];
    } elseif ($headerSection['text_color'] === 'white') {
        $heroTextColors = ['heading' => '#FFFFFF', 'body' => 'rgba(255, 255, 255, 0.78)', 'divider' => 'rgba(255, 255, 255, 0.18)', 'border' => 'rgba(255, 255, 255, 0.35)'];
    }

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

    // Badge sosial-proof di atas judul -- murni dekoratif, teksnya diambil
    // dari statistik PERTAMA yang sudah ada (tidak menambah field admin baru).
    $heroBadgeStat = $headerSection['stats'][0] ?? null;

    // Ikon kartu statistik -- urutan tetap (users, couch, award), item ke-4+
    // (kalau admin nambah lebih dari 3 statistik) jatuh ke bintang.
    $heroStatIcons = ['fa-users', 'fa-couch', 'fa-award'];
@endphp

<section class="relative isolate flex min-h-[600px] items-center overflow-hidden sm:min-h-[680px] lg:min-h-[760px]">
    {{-- ============ Foto Header — background satu section penuh ============ --}}
    <div class="absolute inset-0 -z-10">
        <img
            src="{{ $heroImageUrl }}"
            alt="Furniture {{ $heroSetting->site_name }}"
            class="h-full w-full object-cover object-center"
        >

        {{-- Vignette tipis di tepi foto (efek sinematik) supaya teks lebih
             menonjol -- murni dekoratif, TIDAK mengubah lapisan warna latar
             admin ($heroBgColor) di bawahnya sama sekali. --}}
        <div class="absolute inset-0" style="background: radial-gradient(120% 85% at 50% 15%, transparent 50%, rgba(0,0,0,0.32) 100%);"></div>

        {{-- Lapisan warna latar ($heroBgColor, diatur admin) di atas foto: TIPIS di
             bagian atas (foto tetap kelihatan jelas), lalu perlahan makin pekat ke
             bawah sampai menyatu penuh dengan warna latar section berikutnya. Angka
             persen sengaja dibikin kecil di atas supaya foto tidak "ketutup putih". --}}
        <div
            class="absolute inset-0"
            style="background: linear-gradient(180deg, transparent 0%, transparent 40%, color-mix(in oklab, {{ $heroBgColor }} 35%, transparent) 65%, color-mix(in oklab, {{ $heroBgColor }} 70%, transparent) 85%, {{ $heroBgColor }} 100%);"
        ></div>
    </div>

    {{-- ============ Teks, CTA, Statistik — overlay di atas foto, center-align ============ --}}
    <div class="relative isolate mx-auto w-full max-w-3xl px-6 py-16 text-center sm:px-8 sm:py-20 lg:px-10 lg:py-24">
        {{-- Cahaya (glow) -- SATU lapisan besar & lembut di belakang seluruh
             blok teks, "isolate" di wrapper di atas menjamin -z-10 ini selalu
             di belakang badge/judul/deskripsi/statistik, tidak peduli warna
             foto latarnya. Tidak dirender sama sekali kalau admin mematikan
             opsi "Beri cahaya di belakang teks". --}}
        @if ($heroGlowRgb)
            <div
                class="pointer-events-none absolute left-1/2 top-1/2 -z-10 h-[125%] w-[115%] -translate-x-1/2 -translate-y-1/2 animate-fade-in"
                style="background: radial-gradient(closest-side, rgba({{ $heroGlowRgb }},0.32) 0%, rgba({{ $heroGlowRgb }},0.14) 45%, transparent 75%); filter: blur(38px);"
            ></div>
        @endif

        {{-- Badge sosial-proof --}}
        @if ($heroBadgeStat)
            <div class="animate-fade-in-up mb-6 flex justify-center">
                <span
                    class="inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-xs font-medium tracking-wide backdrop-blur-md"
                    style="border-color: {{ $heroBorderColor }}; background: {{ $heroDividerColor }}; color: {{ $heroTextColor }};"
                >
                    <i class="fa-solid fa-star text-[10px]" style="color: {{ $heroHeadingColor }};"></i>
                    Dipercaya {{ $heroBadgeStat['value'] }} {{ strtolower($heroBadgeStat['label']) }}
                </span>
            </div>
        @endif

        {{-- Judul: eyebrow kecil (headline_prefix) -> nama toko (fokus utama)
             -> tagline (subjudul miring). Datanya persis sama seperti
             sebelumnya, cuma hierarki ukurannya dibedakan biar lebih "wah". --}}
        <p
            class="animate-fade-in-up text-xs font-semibold uppercase tracking-[0.45em] sm:text-sm"
            style="animation-delay: .08s; color: {{ $heroTextColor }}; text-shadow: 0 1px 6px rgba(0,0,0,0.3);"
        >
            {{ $headerSection['headline_prefix'] }}
        </p>

        <h1
            class="animate-fade-in-up font-display mt-3 text-5xl font-semibold leading-[0.95] tracking-tight sm:text-6xl lg:text-7xl"
            style="animation-delay: .16s; color: {{ $heroHeadingColor }}; text-shadow: 0 4px 18px rgba(0,0,0,0.4);"
        >
            {{ $heroSetting->site_name }}
        </h1>

        <p
            class="animate-fade-in-up font-display mt-4 text-2xl italic leading-snug sm:text-3xl lg:text-4xl"
            style="animation-delay: .24s; color: {{ $heroTextColor }}; text-shadow: 0 2px 12px rgba(0,0,0,0.3);"
        >
            {{ $headerSection['tagline'] }}
        </p>

        <p
            class="animate-fade-in-up mx-auto mt-6 max-w-lg text-sm leading-relaxed sm:text-base"
            style="animation-delay: .32s; color: {{ $heroTextColor }}; text-shadow: 0 1px 6px rgba(0,0,0,0.25);"
        >
            {{ $headerSection['description'] }}
        </p>

        {{-- CTA — TIDAK BISA DIEDIT ADMIN, sengaja hardcode (label & link). --}}
        <div class="animate-fade-in-up mt-9 flex flex-wrap items-center justify-center gap-3" style="animation-delay: .4s;">
            <a
                href="{{ route('products.index') }}"
                class="group inline-flex items-center gap-2 rounded-lg bg-[#1A1A1A] px-7 py-3.5 text-sm font-medium text-white shadow-lg shadow-black/20 transition-all duration-300 hover:-translate-y-0.5 hover:bg-black hover:shadow-xl hover:shadow-black/30"
            >
                Lihat Katalog
                <x-icon-arrow direction="right" class="transition-transform duration-300 group-hover:translate-x-1" />
            </a>
            <a
                href="{{ route('profile.index') }}"
                class="group inline-flex items-center gap-2 rounded-lg bg-[#7A4A23] px-7 py-3.5 text-sm font-medium text-white shadow-lg shadow-black/20 transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#5C3719] hover:shadow-xl hover:shadow-black/30"
            >
                Jelajahi Tentang Kami
                <x-icon-arrow direction="right" class="transition-transform duration-300 group-hover:translate-x-1" />
            </a>
        </div>

        {{-- Statistik — kartu kaca (glass card) dengan ikon, datanya persis
             sama (value/label dari admin), cuma tampilannya diperkaya. --}}
        <dl class="animate-fade-in-up mx-auto mt-11 flex max-w-2xl flex-wrap items-stretch justify-center gap-3" style="animation-delay: .48s;">
            @foreach ($headerSection['stats'] as $i => $stat)
                <div
                    class="flex min-w-[128px] flex-1 flex-col items-center gap-1.5 rounded-2xl border px-5 py-4 backdrop-blur-md"
                    style="border-color: {{ $heroBorderColor }}; background: {{ $heroDividerColor }};"
                >
                    <i class="fa-solid {{ $heroStatIcons[$i] ?? 'fa-star' }} text-sm" style="color: {{ $heroHeadingColor }};"></i>
                    <dt class="font-display text-xl sm:text-2xl" style="color: {{ $heroHeadingColor }}; text-shadow: 0 2px 10px rgba(0,0,0,0.35);">{{ $stat['value'] }}</dt>
                    <dd class="text-[11px] font-medium uppercase tracking-wide" style="color: {{ $heroTextColor }};">{{ $stat['label'] }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    {{-- Indikator scroll -- murni dekoratif, ikon panah/chevron WAJIB lewat
         <x-icon-arrow> sesuai aturan satu-satunya sumber icon arrow project. --}}
    <div class="pointer-events-none absolute inset-x-0 bottom-6 hidden justify-center sm:flex">
        <span class="flex animate-bounce flex-col items-center gap-1" style="color: {{ $heroTextColor }};">
            <x-icon-arrow direction="chevron-down" size="text-sm" />
        </span>
    </div>
</section>
'@

Set-Content $heroPath -Value $newHero -Encoding UTF8 -NoNewline

Write-Host ""
Write-Host "Selesai." -ForegroundColor Green
Write-Host "Diubah: $navbarPath"
Write-Host "Diubah: $heroPath"
Write-Host "Backup asli disimpan sebagai *.bak-before-hero-redesign-$stamp"
Write-Host ""
Write-Host "Kalau perubahan belum kelihatan di browser, jalankan: php artisan view:clear"
