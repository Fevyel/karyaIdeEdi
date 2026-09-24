# apply-fix-frame-seam-smooth.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   powershell -ExecutionPolicy Bypass -File apply-fix-frame-seam-smooth.ps1
#
# Masalah: strip "frame seam" (partials/frontend/frame-seam.blade.php) sudah
# nge-blend warna dua section yang bertetangga di ruang oklch (jadi titik
# tengahnya tidak jatuh ke abu-abu kotor), TAPI transisinya pure linear --
# slope/kecepatan perubahan warnanya konstan dari ujung ke ujung strip. Itu
# bikin ada patahan kecepatan PERSIS di kedua ujung strip: warna section flat
# di atasnya diam (slope 0), lalu begitu masuk strip langsung berubah dengan
# kecepatan konstan, lalu berhenti mendadak lagi begitu masuk section flat
# berikutnya. Mata manusia sensitif ke patahan KECEPATAN ini (bukan cuma ke
# warnanya) -- makanya kerasa "maksa"/dipotong walau warnanya sendiri sudah
# benar.
#
# Perbaikan: bikin gradasinya smoothstep (t' = t^2*(3-2t)) -- slope-nya 0
# persis di kedua ujung strip juga, jadi transisinya "berangkat" dan
# "berhenti" landai, nyambung mulus ke warna flat di atas & bawahnya. Warna
# tiap titik tetap di-mix pakai color-mix(in oklch, ...) supaya percampuran
# warnanya sendiri tetap benar secara persepsi (bukan RGB linear yang bisa
# jadi kotor).
#
# Cuma menyentuh SATU file (dipakai bersama oleh semua section Beranda,
# Tentang Kami, & Our Craftsmen -- jadi otomatis berlaku untuk semua
# frame di Edit Web). File lain tidak disentuh sama sekali.
#
# Aman dijalankan berulang. Backup otomatis dibuat sebelum menimpa.

$ErrorActionPreference = "Stop"

$target = "resources/views/partials/frontend/frame-seam.blade.php"

if (-not (Test-Path $target)) {
    throw "Tidak ketemu: $target (jalankan script ini dari root project)"
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupPath = "$target.bak-before-smooth-seam-$stamp"
Copy-Item $target $backupPath
Write-Host "Backup dibuat : $backupPath" -ForegroundColor Yellow

$newContent = @'
{{--
    ==========================================================
    FRAME SEAM — transisi halus warna antar frame Admin > Edit Web
    ==========================================================
    Tiap frame yang bisa diedit di Edit Web (Header, Sejak Berdiri,
    Produk Unggulan, Kategori, Testimoni, Lokasi, Tentang Kami,
    Sejarah, Nilai Kami, Kenapa Pilih Kami, Our Craftsmen Hero, dst)
    adalah <section> sendiri dengan background-nya sendiri (warna
    polos ATAU gradasi -- lihat App\Support\FrameBackground). Kalau
    dua frame bertetangga warnanya beda, sambungannya jadi garis
    tegas/patah.

    Partial ini ditaruh PERSIS DI ANTARA dua @include section (bukan
    DI DALAM salah satunya) dan me-render satu strip tipis berisi
    gradasi (warna section ATAS -> warna section BAWAH), yang
    menutupi separuh bawah section atas + separuh atas section bawah.
    Warna & gradasi section itu sendiri SAMA SEKALI TIDAK diubah --
    ini murni lapisan penghalus di sambungannya saja.

    Kenapa position:relative + z-index:1 (bukan negative margin
    polos taruh di dalam salah satu section): hampir semua section
    frame di project ini punya class "overflow-hidden" (buat
    membungkus dekorasi glow/blur internal). Kalau seam ini jadi
    ANAK dari salah satu section, bagian yang nongol keluar kotak
    section (lewat negative margin) bakal kepotong overflow-hidden
    itu. Makanya seam ini sengaja jadi SAUDARA (sibling) dari kedua
    section -- z-index:1 memastikan dia tetap tampil DI ATAS kedua
    section (yang z-index-nya auto/default) walau posisinya di HTML
    ada DI ANTARA keduanya, dan overflow-hidden section manapun
    tidak akan memotongnya lagi karena dia bukan anak dari section
    itu. margin-top & margin-bottom yang sama besar (negatif) bikin
    total tinggi yang ditambahkan ke alur halaman = 0, jadi seam ini
    TIDAK menambah jarak/gap baru antar section.

    Kenapa color-mix(in oklch, ...) per titik stop (bukan cuma
    linear-gradient RGB biasa): dua warna yang jauh beda -- misal
    coklat tua ke putih -- kalau dicampur linear di ruang warna RGB,
    titik tengahnya jatuh ke abu-abu/coklat pudar yang "kotor" dan
    keliatan kayak sambungan dipaksa/patah, bukan gradasi mulus.
    Mixing di ruang oklch mengikuti persepsi mata (lightness bergerak
    rata dari ujung ke ujung), jadi warnanya sendiri sudah benar.

    Kenapa easing SMOOTHSTEP juga dipakai (bukan cuma warnanya di-mix
    oklch lalu ditaruh di gradient linear polos): linear-gradient
    biasa punya KECEPATAN perubahan warna yang konstan dari ujung ke
    ujung strip. Section flat di atas & bawah strip ini kecepatan
    perubahannya 0 (warnanya diam). Jadi persis di kedua ujung strip
    ada PATAHAN KECEPATAN -- dari diam, tiba-tiba berubah dengan
    kecepatan konstan, lalu berhenti mendadak lagi. Mata manusia
    sensitif ke patahan kecepatan ini (bukan cuma ke warnanya sendiri
    yang sudah benar), makanya transisinya kerasa "maksa"/dipotong
    walau sudah pakai oklch. Kurva smoothstep (t' = t^2*(3-2t)) bikin
    kecepatan perubahannya 0 juga persis di kedua ujung strip, jadi
    transisinya "berangkat" dan "berhenti" landai, nyambung mulus ke
    section flat di atas & bawahnya -- baru di tengah strip warnanya
    berubah lebih cepat. Caranya: strip dipecah jadi beberapa titik
    stop (bukan cuma 2), tiap titik warnanya di-mix oklch dengan
    proporsi yang sudah lewat kurva smoothstep, bukan proporsi linear.

    Props:
      $from   -- hex warna dasar section DI ATAS seam ini (pakai
                 ['base'] dari App\Support\FrameBackground::resolve(),
                 supaya benar baik section itu lagi pakai gradasi
                 maupun warna polos/bawaan).
      $to     -- hex warna dasar section DI BAWAH seam ini.
      $height -- opsional, total tinggi strip dalam px (default
                 220px -- 110px nongol ke section atas, 110px ke
                 section bawah).

    Pemakaian -- taruh PERSIS di antara dua section, TIDAK di dalam
    keduanya (lihat home-placeholder.blade.php / profil.blade.php /
    pengrajin.blade.php untuk contoh lengkap):

        @include('partials.frontend.mission')
        @include('partials.frontend.frame-seam', ['from' => $seamMission, 'to' => $seamProduk])
        @include('partials.frontend.products')
    ==========================================================
--}}
@php
    $seamHeight = $height ?? 220;
    $seamHalf = $seamHeight / 2;

    // 10 segmen = 11 titik stop. Cukup rapat supaya pendekatan smoothstep-nya
    // halus, tanpa bikin panjang string CSS-nya berlebihan.
    $segments = 10;
    $stops = [];

    for ($i = 0; $i <= $segments; $i++) {
        $p = $i / $segments;
        $t = $p * $p * (3 - 2 * $p); // smoothstep(p): slope 0 di p=0 dan p=1
        $toPercent = round($t * 100, 2);
        $fromPercent = round(100 - $toPercent, 2);
        $position = round($p * 100, 2);

        $stops[] = "color-mix(in oklch, {$from} {$fromPercent}%, {$to} {$toPercent}%) {$position}%";
    }

    $gradient = 'linear-gradient(to bottom in oklch, '.implode(', ', $stops).')';
@endphp
<div
    class="pointer-events-none"
    style="position: relative; z-index: 1; height: {{ $seamHeight }}px; margin-top: -{{ $seamHalf }}px; margin-bottom: -{{ $seamHalf }}px; background: {{ $gradient }};"
    aria-hidden="true"
></div>
'@

# Tulis sebagai UTF-8 TANPA BOM (BOM dari Set-Content/Out-File PowerShell
# pernah bikin masalah tersendiri di project ini -- lihat
# apply-fix-strip-bom-navbar-hero.ps1 / apply-fix-bom-testimoni-files.ps1).
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText((Resolve-Path $target).Path, $newContent, $utf8NoBom)

Write-Host "File diperbarui : $target" -ForegroundColor Green
Write-Host ""
Write-Host "Selesai. Lanjutkan dengan: php artisan view:clear" -ForegroundColor Green
