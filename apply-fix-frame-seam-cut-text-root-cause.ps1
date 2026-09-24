# apply-fix-frame-seam-cut-text-root-cause.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   powershell -ExecutionPolicy Bypass -File apply-fix-frame-seam-cut-text-root-cause.ps1
#
# AKAR MASALAH (kenapa banyak teks/judul kepotong-ketutupan di hampir semua
# section, bukan cuma satu halaman): strip "frame seam" (partials/frontend/
# frame-seam.blade.php) SENGAJA dikasih z-index:1 supaya selalu tampil DI
# ATAS latar section tetangganya (lihat komentar panjang di file itu -- ini
# perlu supaya seam tidak ikut ke-clip oleh overflow-hidden section manapun).
# Konsekuensinya: seam ini menang di lapisan PALING DEPAN terhadap section
# manapun yang TIDAK dikasih position:relative sendiri (dan banyak section di
# project ini memang tidak, misal Tentang Kami 2, Sejarah, Nilai Kami, Why
# Choose Us di profil.blade.php) -- apa pun yang jaraknya ke tepi section
# lebih dekat dari jangkauan seam otomatis ketutupan.
#
# Sebelumnya tinggi seam ini sempat dinaikkan ke 220px lalu dikembalikan ke
# 180px (90px tiap sisi) lewat apply-fix-frame-seam-height.ps1 -- TAPI 180px
# masih lebih BESAR dari padding section paling kecil di project ini
# ("max-sm:py-12" = 48px di HP), jadi teks yang mepet ke tepi section di HP
# tetap ketutupan walau sudah "dikembalikan ke 180".
#
# Perbaikan sekarang: turunkan tinggi seam ke 56px (28px tiap sisi) --
# jelas di BAWAH 48px (padding terkecil), dengan jarak aman ~20px, jadi
# seam tidak akan pernah menjangkau sampai ke teks section manapun, sekecil
# apa pun paddingnya. Transisi warna (color-mix oklch + easing smoothstep)
# TIDAK diubah sama sekali -- cuma jangkauannya yang dipersempit. Hanya
# menyentuh SATU file, dipakai bersama oleh semua frame (Beranda, Tentang
# Kami, Our Craftsmen) -- jadi otomatis berlaku ke semua section sekaligus,
# tanpa perlu menyentuh file section manapun satu per satu.
#
# Aman dijalankan berulang. Backup otomatis dibuat sebelum menimpa.

$ErrorActionPreference = "Stop"

$target = "resources/views/partials/frontend/frame-seam.blade.php"

if (-not (Test-Path $target)) {
    throw "Tidak ketemu: $target (jalankan script ini dari root project)"
}

$fullPath = (Resolve-Path $target).Path
$content = [System.IO.File]::ReadAllText($fullPath)

if ($content -match '\$height \?\? 56') {
    Write-Host "Sudah dalam kondisi yang benar (`$height ?? 56) -- tidak ada yang diubah." -ForegroundColor Yellow
    exit 0
}

if ($content -notmatch '\$height \?\? 180') {
    Write-Host "Tidak ketemu '`$height ?? 180' di file -- kemungkinan file sudah beda dari yang diharapkan skrip ini." -ForegroundColor Red
    Write-Host "Tidak ada yang diubah. Cek manual dulu isi $target." -ForegroundColor Red
    exit 1
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupPath = "$target.bak-before-seam-cut-text-root-cause-$stamp"
Copy-Item $target $backupPath
Write-Host "Backup dibuat : $backupPath" -ForegroundColor Yellow

# 1) Baris kode yang benar-benar menentukan perilaku.
$content = $content -replace '\$height \?\? 180', '$height ?? 56'

# 2) Dokumentasi default di komentar props (kosmetik, tapi disamakan supaya
#    tidak menyesatkan pembaca berikutnya).
$content = $content -replace (
    [regex]::Escape('$height -- opsional, total tinggi strip dalam px (default') +
    '\s*\r?\n\s*' + [regex]::Escape('180px -- 90px nongol ke section atas, 90px ke') +
    '\s*\r?\n\s*' + [regex]::Escape('section bawah).')
), @'
$height -- opsional, total tinggi strip dalam px (default
                 56px -- 28px nongol ke section atas, 28px ke
                 section bawah).
'@.TrimEnd()

[System.IO.File]::WriteAllText($fullPath, $content)

Write-Host "Selesai. $target diperbarui: tinggi seam default 180px -> 56px." -ForegroundColor Green
Write-Host "Refresh browser (Ctrl+Shift+R) untuk lihat hasilnya -- tidak perlu npm run build/dev" -ForegroundColor Green
Write-Host "karena ini file Blade (PHP), bukan asset CSS/JS yang di-bundle Vite." -ForegroundColor Green
