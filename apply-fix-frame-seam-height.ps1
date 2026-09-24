# apply-fix-frame-seam-height.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   powershell -ExecutionPolicy Bypass -File apply-fix-frame-seam-height.ps1
#
# Penyebab teks kepotong/ketutupan: di patch sebelumnya (smooth seam), tinggi
# strip seam dinaikkan dari 180px jadi 220px (110px nongol ke section atas,
# 110px ke section bawah) supaya transisinya kerasa lebih landai. Ternyata
# beberapa section padding atas/bawahnya di mobile cuma ~48-56px -- jauh lebih
# kecil dari 110px, jadi strip seam-nya (yang sengaja ditaruh DI ATAS section
# lewat z-index:1, lihat komentar di frame-seam.blade.php) nutupin/motong
# judul & paragraf yang letaknya deket ke tepi section.
#
# Perbaikan: turunkan tinggi strip balik ke 180px (90px tiap sisi) -- ukuran
# yang sebelumnya sudah terbukti tidak menutupi teks section manapun. Kurva
# easing smoothstep dari patch sebelumnya TETAP dipakai (itu yang benerin
# transisi "maksa"-nya) -- yang diubah cuma ukurannya, bukan cara blend-nya.
#
# Cuma menyentuh SATU file, sama seperti patch sebelumnya. Aman dijalankan
# berulang. Backup otomatis dibuat sebelum menimpa.

$ErrorActionPreference = "Stop"

$target = "resources/views/partials/frontend/frame-seam.blade.php"

if (-not (Test-Path $target)) {
    throw "Tidak ketemu: $target (jalankan script ini dari root project)"
}

$fullPath = (Resolve-Path $target).Path
$content = [System.IO.File]::ReadAllText($fullPath)

if ($content -notmatch '\$height \?\? 220') {
    Write-Host "Tidak ketemu '`$height ?? 220' di file -- kemungkinan file sudah beda dari yang diharapkan skrip ini." -ForegroundColor Red
    Write-Host "Tidak ada yang diubah. Cek manual dulu isi $target." -ForegroundColor Red
    exit 1
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupPath = "$target.bak-before-seam-height-fix-$stamp"
Copy-Item $target $backupPath
Write-Host "Backup dibuat : $backupPath" -ForegroundColor Yellow

$updated = $content `
    -replace '\$height \?\? 220', '$height ?? 180' `
    -replace '220px -- 110px nongol ke section atas, 110px ke', '180px -- 90px nongol ke section atas, 90px ke'

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($fullPath, $updated, $utf8NoBom)

Write-Host "File diperbarui : $target (tinggi strip: 220px -> 180px)" -ForegroundColor Green
Write-Host ""
Write-Host "Selesai. Lanjutkan dengan: php artisan view:clear" -ForegroundColor Green
