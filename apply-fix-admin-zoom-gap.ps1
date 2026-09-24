# ============================================================
# FIX: Rectangle/celah kosong di bagian bawah halaman Admin
# (muncul di bawah sidebar & modal, warnanya beda dari admin
# canvas).
#
# SEBAB: <body> admin dipasang `zoom: 80%` (lihat
# apply-fix-admin-zoom-80.ps1). Zoom mengecilkan tinggi body jadi
# 80% dari tinggi aslinya, jadi sisa 20% di bawah nongol warna
# <html> (beda dari admin canvas) -- itu yang kelihatan sebagai
# "rectangle" aneh.
#
# SOLUSI: tambah `min-height: 125vh` di style yang sama
# (125 = 100 / 0.8), supaya SETELAH di-zoom 80% tingginya balik
# pas 100% tinggi viewport. Gap-nya hilang, bukan cuma disamarkan
# warnanya.
#
# HANYA mengubah 1 file, 1 baris (nambah 1 properti CSS di style
# body yang sudah ada): resources\views\layouts\admin-panel.blade.php
# Halaman frontend/toko TIDAK kesentuh (beda layout file).
#
# Cara pakai (dari terminal VS Code, di root project
# C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-fix-admin-zoom-gap.ps1
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

$old = "        class=`"bg-admin-canvas font-sans text-admin-ink antialiased`"`n" +
    "        style=`"zoom: 80%;`"`n" +
    "    >"

$new = "        class=`"bg-admin-canvas font-sans text-admin-ink antialiased`"`n" +
    "        style=`"zoom: 80%; min-height: 125vh;`"`n" +
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
$backup = "$file.bak-before-fix-admin-zoom-gap-$timestamp"
Copy-Item $file $backup
Write-Host "Backup dibuat : $backup" -ForegroundColor DarkGray

$newContent = $content.Replace($old, $new)
[System.IO.File]::WriteAllText((Resolve-Path $file), $newContent, (New-Object System.Text.UTF8Encoding($false)))

Write-Host "[OK] Gap/rectangle di bawah halaman Admin sudah dihilangkan (min-height: 125vh)." -ForegroundColor Green
Write-Host ""
Write-Host "Refresh halaman admin manapun (hard refresh / Ctrl+Shift+R) untuk lihat hasilnya." -ForegroundColor Cyan
