# ============================================================
# FIX v2: Rectangle/celah gelap di bawah modal admin (Tambah/Edit
# Pesanan, dan modal admin lainnya) MASIH muncul walau sudah pakai
# fix v1 (nambah min-height di <body>).
#
# SEBAB SEBENARNYA: `zoom: 80%` dipasang di <body>. Modal-modal
# admin pakai `fixed inset-0` (nutup seluruh layar). Elemen
# `fixed` yang jadi anak dari <body> yang di-zoom IKUT ke-scale
# 80% juga -- jadi overlay modal cuma nutup 80% tinggi layar
# ASLI, sisa 20% di bawah gak ketutup apa-apa. Nambah min-height
# di body TIDAK membantu, karena posisi `fixed` dihitung dari
# viewport asli, bukan dari tinggi body.
#
# Bandingnya: di halaman FRONTEND, project ini sudah benar --
# `zoom: 80%` dipasang di <html> (root), BUKAN <body>. Zoom di
# <html> membuat browser menghitung ulang SELURUH viewport
# (termasuk semua elemen fixed) versi ter-zoom, persis kayak
# browser di-zoom manual -- jadi tidak ada gap.
#
# SOLUSI: pindahkan `zoom: 80%` dari <body> ke <html> di layout
# admin, dan buang `min-height` yang sekarang tidak relevan lagi.
# Ini benerin SEMUA modal di admin panel sekaligus (Produk,
# Kategori, Testimoni, Pesanan, dll), bukan cuma satu halaman.
#
# HANYA mengubah 1 file: resources\views\layouts\admin-panel.blade.php
# Halaman frontend/toko TIDAK kesentuh (beda layout file, beda
# mekanisme -- html[data-site='frontend'] di app.css).
#
# Cara pakai (dari terminal VS Code, di root project
# C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-fix-admin-zoom-gap-v2.ps1
# ============================================================

$ErrorActionPreference = "Stop"

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

$file = "resources\views\layouts\admin-panel.blade.php"
if (-not (Test-Path $file)) {
    Write-Host "[ERROR] File tidak ketemu: $file" -ForegroundColor Red
    exit 1
}

$content = [System.IO.File]::ReadAllText((Resolve-Path $file))

$oldHtml = "<html lang=`"id`" data-theme=`"{{ auth()->user()->theme ?? 'glow' }}`">"
$newHtml = "<html lang=`"id`" data-theme=`"{{ auth()->user()->theme ?? 'glow' }}`" style=`"zoom: 80%;`">"

$oldBody = "        class=`"bg-admin-canvas font-sans text-admin-ink antialiased`"`n" +
    "        style=`"zoom: 80%; min-height: 125vh;`"`n" +
    "    >"
$newBody = "        class=`"bg-admin-canvas font-sans text-admin-ink antialiased`"`n" +
    "    >"

if ($content.IndexOf($oldHtml) -lt 0) {
    Write-Host "[ERROR] Tag <html> yang dicari tidak ketemu persis di $file." -ForegroundColor Red
    Write-Host "        Kemungkinan file ini sudah diubah lagi sejak versi yang dipakai untuk bikin patch ini." -ForegroundColor Red
    Write-Host "        Tidak ada perubahan yang dilakukan." -ForegroundColor Red
    exit 1
}

if ($content.IndexOf($oldBody) -lt 0) {
    Write-Host "[ERROR] Blok <body style> (hasil fix v1) tidak ketemu persis di $file." -ForegroundColor Red
    Write-Host "        Kemungkinan fix v1 belum kepasang, atau file sudah diubah lagi." -ForegroundColor Red
    Write-Host "        Tidak ada perubahan yang dilakukan." -ForegroundColor Red
    exit 1
}

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backup = "$file.bak-before-fix-admin-zoom-gap-v2-$timestamp"
Copy-Item $file $backup
Write-Host "Backup dibuat : $backup" -ForegroundColor DarkGray

$newContent = $content.Replace($oldHtml, $newHtml).Replace($oldBody, $newBody)
[System.IO.File]::WriteAllText((Resolve-Path $file), $newContent, (New-Object System.Text.UTF8Encoding($false)))

Write-Host "[OK] zoom: 80% dipindah dari <body> ke <html>. Gap di bawah modal admin harusnya hilang total sekarang." -ForegroundColor Green
Write-Host ""
Write-Host "Refresh halaman admin manapun (hard refresh / Ctrl+Shift+R), coba buka beberapa modal berbeda." -ForegroundColor Cyan
