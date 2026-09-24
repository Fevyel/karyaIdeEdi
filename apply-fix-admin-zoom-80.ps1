# ============================================================
# FIX: Admin Panel tampil kegedean di 100%, padahal ukuran yang
# pas itu yang kelihatan pas browser di-zoom ke 80%.
#
# Solusinya: kasih CSS `zoom: 80%` di <body> layout admin, supaya
# SELURUH Admin Panel (Dashboard, Produk, Kategori, Pesanan, dst)
# otomatis kelihatan sama seperti waktu kamu zoom manual ke 80%,
# tanpa perlu zoom manual lagi tiap buka.
#
# Kenapa pakai `zoom`, bukan `transform: scale()`:
#   - `zoom` scaling-nya sama persis kayak browser zoom (font,
#     padding, ikon ikut proporsional).
#   - `transform` bikin elemen `position: fixed` (garis aksen
#     emas di atas, sidebar overlay) jadi salah posisi -- ini
#     sudah ada catatannya sendiri di file admin-panel.blade.php.
#
# HANYA mengubah 1 file, 1 baris (nambah 1 atribut style di tag
# <body>): resources\views\layouts\admin-panel.blade.php
# Halaman frontend/toko TIDAK kesentuh (beda layout file).
#
# Cara pakai (dari terminal VS Code, di root project
# C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-fix-admin-zoom-80.ps1
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

$old = "    <body`n" +
    "        x-data=`"{ sidebarOpen: false }`"`n" +
    "        class=`"bg-admin-canvas font-sans text-admin-ink antialiased`"`n" +
    "    >"

$new = "    <body`n" +
    "        x-data=`"{ sidebarOpen: false }`"`n" +
    "        class=`"bg-admin-canvas font-sans text-admin-ink antialiased`"`n" +
    "        style=`"zoom: 80%;`"`n" +
    "    >"

if ($content.IndexOf($old) -lt 0) {
    Write-Host "[ERROR] Teks yang mau diganti tidak ketemu persis di $file." -ForegroundColor Red
    Write-Host "        Kemungkinan file ini sudah diubah lagi sejak versi yang dipakai untuk bikin patch ini." -ForegroundColor Red
    Write-Host "        Tidak ada perubahan yang dilakukan." -ForegroundColor Red
    exit 1
}

$occurrences = ([regex]::Matches($content, [regex]::Escape($old))).Count
if ($occurrences -ne 1) {
    Write-Host "[ERROR] Teks itu ketemu $occurrences kali (harusnya cuma 1) -- dibatalkan supaya aman." -ForegroundColor Red
    exit 1
}

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backup = "$file.bak-before-fix-admin-zoom-80-$timestamp"
Copy-Item $file $backup
Write-Host "Backup dibuat : $backup" -ForegroundColor DarkGray

$newContent = $content.Replace($old, $new)
[System.IO.File]::WriteAllText((Resolve-Path $file), $newContent, (New-Object System.Text.UTF8Encoding($false)))

Write-Host "[OK] Admin Panel dikunci ke skala 80% (style zoom: 80% di <body>)." -ForegroundColor Green
Write-Host ""
Write-Host "Refresh halaman admin manapun -- kalau browser kamu SEBELUMNYA" -ForegroundColor Cyan
Write-Host "di-zoom manual ke 80% (Ctrl -), set balik dulu ke 100% (Ctrl 0)" -ForegroundColor Cyan
Write-Host "supaya tidak numpuk jadi 64% (80% x 80%)." -ForegroundColor Cyan
