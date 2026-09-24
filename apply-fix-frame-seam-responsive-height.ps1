# apply-fix-frame-seam-responsive-height.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   powershell -ExecutionPolicy Bypass -File apply-fix-frame-seam-responsive-height.ps1
#
# Lanjutan dari apply-fix-frame-seam-cut-text-root-cause.ps1 (fix #1):
# setelah tinggi strip "frame seam" diturunkan ke 56px supaya tidak
# menutupi teks di HP, ternyata di layar besar (desktop) 56px jadi
# KEPENDEKAN untuk membaurkan dua warna yang beda jauh (mis. putih <->
# krem) secara halus -- hasilnya malah kelihatan sebagai garis/batas
# tegas ("batas putih" jadi kelihatan), bukan gradasi lembut lagi.
#
# Perbaikan: tinggi strip sekarang RESPONSIF lewat CSS murni (bukan lagi
# satu angka tetap dari PHP) -- tetap 56px (aman) di HP, naik jadi 96px
# di >=640px, dan 140px di >=1024px (padding section di layar besar jauh
# lebih lega, jadi aman dipanjangkan lagi supaya transisinya mulus).
# Warna gradasinya (smoothstep + oklch) sama sekali tidak berubah -- itu
# dihitung dalam PERSEN, bukan px, jadi otomatis tetap pas di tinggi
# berapa pun.
#
# Script ini MENIMPA SELURUH ISI file frame-seam.blade.php dengan versi
# final (sudah termasuk fix #1 + fix #2 sekaligus) -- kamu TIDAK perlu
# jalankan apply-fix-frame-seam-cut-text-root-cause.ps1 lagi kalau pakai
# script ini. Hanya menyentuh SATU file, dipakai bersama oleh 16 section
# (Beranda, Tentang Kami, Our Craftsmen) -- otomatis berlaku ke semuanya.
#
# Aman dijalankan berulang. Backup otomatis dibuat sebelum menimpa.

$ErrorActionPreference = "Stop"

$target = "resources/views/partials/frontend/frame-seam.blade.php"

if (-not (Test-Path $target)) {
    throw "Tidak ketemu: $target (jalankan script ini dari root project)"
}

$fullPath = (Resolve-Path $target).Path
$existing = [System.IO.File]::ReadAllText($fullPath)

if ($existing -match [regex]::Escape("frame-seam-strip")) {
    Write-Host "Sudah dalam kondisi yang benar (class .frame-seam-strip sudah ada) -- tidak ada yang diubah." -ForegroundColor Yellow
    exit 0
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupPath = "$target.bak-before-seam-responsive-height-$stamp"
Copy-Item $target $backupPath
Write-Host "Backup dibuat : $backupPath" -ForegroundColor Yellow

$newContent = @'
{{--
    ==========================================================
    FRAME SEAM â€” transisi halus warna antar frame Admin > Edit Web
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
      ($height -- SUDAH TIDAK DIPAKAI, lihat CATATAN FIX #2. Tinggi
                 strip sekarang diatur lewat CSS class
                 .frame-seam-strip di bawah, responsif per breakpoint,
                 bukan lewat parameter ini lagi.)

    CATATAN FIX #1 (teks/judul kepotong -- diperbaiki tanpa menyentuh
    section manapun): seam ini SENGAJA z-index:1 (lihat penjelasan di
    atas) supaya selalu tampil DI ATAS latar section manapun. Efek
    sampingnya: kalau tingginya (jadi jangkauannya ke section atas/
    bawah) lebih jauh dari padding section itu, seam ikut menutupi
    judul/teks yang mepet ke tepi section -- padding section paling
    kecil di project ini "max-sm:py-12" = 48px di HP. Tinggi di HP
    diturunkan dari 180px (90px tiap sisi, LEBIH BESAR dari 48px itu,
    makanya kepotong) jadi 56px (28px tiap sisi, di bawah 48px dengan
    jarak aman).

    CATATAN FIX #2 (batas/garis putih jadi KELIHATAN setelah fix #1):
    56px cukup aman dari teks di HP, tapi di layar besar jadi
    KEPENDEKAN untuk blend dua warna yang beda jauh (mis. putih <->
    krem) secara halus -- hasilnya malah kelihatan seperti garis/kotak
    tegas, bukan gradasi lembut lagi. Solusinya BUKAN naikkan tinggi
    global lagi (nanti fix #1 rusak balik), tapi bikin tingginya
    RESPONSIF lewat CSS murni (@once <style> di bawah, dipakai sama
    semua 16 pemakaian seam ini) -- kecil & aman di HP (56px, padding
    terkecil di sana 48px), lebih panjang & mulus di layar besar
    (96px di >=640px, 140px di >=1024px -- padding di sana jauh lebih
    lega, 64-96px). Titik-titik warna gradasinya (smoothstep + oklch
    di atas) sengaja dalam PERSEN bukan px, jadi otomatis tetap pas &
    mulus di tinggi berapa pun -- tidak perlu dihitung ulang per
    breakpoint, cukup ganti tinggi CSS-nya saja.

    Pemakaian -- taruh PERSIS di antara dua section, TIDAK di dalam
    keduanya (lihat home-placeholder.blade.php / profil.blade.php /
    pengrajin.blade.php untuk contoh lengkap):

        @include('partials.frontend.mission')
        @include('partials.frontend.frame-seam', ['from' => $seamMission, 'to' => $seamProduk])
        @include('partials.frontend.products')
    ==========================================================
--}}
@once
    <style>
        .frame-seam-strip {
            height: 56px;
            margin-top: -28px;
            margin-bottom: -28px;
        }
        @media (min-width: 640px) {
            .frame-seam-strip {
                height: 96px;
                margin-top: -48px;
                margin-bottom: -48px;
            }
        }
        @media (min-width: 1024px) {
            .frame-seam-strip {
                height: 140px;
                margin-top: -70px;
                margin-bottom: -70px;
            }
        }
    </style>
@endonce
@php
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
    class="frame-seam-strip pointer-events-none"
    style="position: relative; z-index: 1; background: {{ $gradient }};"
    aria-hidden="true"
></div>
'@

[System.IO.File]::WriteAllText($fullPath, $newContent)

Write-Host "Selesai. $target diperbarui: tinggi seam sekarang responsif (56px HP / 96px tablet / 140px desktop)." -ForegroundColor Green
Write-Host "Refresh browser (Ctrl+Shift+R) untuk lihat hasilnya -- tidak perlu npm run build/dev" -ForegroundColor Green
Write-Host "karena ini file Blade (PHP), bukan asset CSS/JS yang di-bundle Vite." -ForegroundColor Green
