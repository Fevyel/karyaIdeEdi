# ============================================================
# FIX: Celah warna body muncul di atas navbar (sticky "lepas")
#
# Penyebab: efek overscroll "rubber-band" bawaan browser (Chrome
# di Windows/trackpad) — saat scroll dikit melewati batas atas
# halaman, browser sesaat menampilkan warna latar <body> di balik
# header sticky, sebelum snap balik ke posisi normal. Ini BUKAN
# bug di kode navbar (posisi sticky top-0 sudah benar).
#
# Fix: menambahkan 1 aturan CSS (overscroll-behavior-y: none)
# di resources/css/app.css supaya efek rubber-band ini dimatikan
# di seluruh halaman.
#
# Script ini HANYA menambah 1 blok kecil di app.css, lalu
# menjalankan ulang build Vite supaya CSS baru ini aktif.
# Tidak ada file lain yang disentuh.
#
# Cara pakai:
# 1. Jalankan dari folder project (C:\xampp\htdocs\karyaIdeEdi)
# 2. .\apply-fix-navbar-overscroll-gap.ps1
# ============================================================

$ErrorActionPreference = "Stop"
$targetFile = "resources\css\app.css"

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Fix: Celah warna body di atas navbar (overscroll)" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    Write-Host "Contoh: C:\xampp\htdocs\karyaIdeEdi" -ForegroundColor Yellow
    exit 1
}

if (-not (Test-Path $targetFile)) {
    Write-Host "[ERROR] File tidak ditemukan: $targetFile" -ForegroundColor Red
    exit 1
}

$backupFile = "$targetFile.bak"
Copy-Item $targetFile $backupFile -Force
Write-Host "[1/4] Backup dibuat: $backupFile" -ForegroundColor Green

$content = Get-Content $targetFile -Raw -Encoding UTF8

$oldBlock = @'
@import 'tailwindcss';

/* Alpine.js: sembunyikan elemen [x-cloak] sebelum Alpine selesai inisialisasi,
'@

$newBlock = @'
@import 'tailwindcss';

/* Matikan efek "overscroll bounce" (rubber-band) saat scroll melewati batas
   atas/bawah halaman. Tanpa ini, sticky header bisa terlihat "lepas" sesaat
   dari posisi top-0 dan menampilkan warna latar body di baliknya. */
html, body {
    overscroll-behavior-y: none;
}

/* Alpine.js: sembunyikan elemen [x-cloak] sebelum Alpine selesai inisialisasi,
'@

if (-not $content.Contains($oldBlock)) {
    Write-Host "[ERROR] Blok kode yang mau diganti tidak ditemukan persis di file." -ForegroundColor Red
    Write-Host "Kemungkinan file sudah pernah diubah manual. Cek manual ya, ge." -ForegroundColor Yellow
    Write-Host "Backup tetap aman di: $backupFile" -ForegroundColor Yellow
    exit 1
}

Write-Host "[2/4] Menambahkan aturan overscroll-behavior..." -ForegroundColor Green
$newContent = $content.Replace($oldBlock, $newBlock)

$utf8Bom = New-Object System.Text.UTF8Encoding $true
[System.IO.File]::WriteAllText((Resolve-Path $targetFile), $newContent, $utf8Bom)

Write-Host "[3/4] Menjalankan 'npm run build' (wajib, ini nambah CSS baru)..." -ForegroundColor Green
npm run build

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "[ERROR] Build gagal (exit code $LASTEXITCODE). Cek pesan error di atas." -ForegroundColor Red
    exit 1
}

Write-Host "[4/4] Selesai." -ForegroundColor Green
Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " FIX DITERAPKAN! Hard refresh browser (Ctrl+Shift+R)" -ForegroundColor Cyan
Write-Host " lalu coba scroll dikit ke atas di halaman Beranda." -ForegroundColor Cyan
Write-Host " Kalau celahnya masih muncul, kirim video/screenshot" -ForegroundColor Cyan
Write-Host " lagi ya, ge, biar dicek lebih dalam." -ForegroundColor Cyan
Write-Host " File asli ada di: $backupFile" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
