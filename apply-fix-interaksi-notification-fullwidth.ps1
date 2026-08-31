# ============================================================
# FIX: Notifikasi di halaman Interaksi masih melayang (fixed,
#      pojok kanan atas, lebar terbatas) — beda sama halaman
#      Produk/Kategori/Pesanan yang notifnya sudah fullwidth
#      dan menyatu di alur halaman (bukan floating).
#
# Penyebab: resources/views/pages/admin/interaksi.blade.php
# masih pakai style lama:
#   "fixed right-6 top-20 z-[9999] w-[min(28rem,...)] ... shadow-2xl pointer-events-none"
# sedangkan Produk/Kategori/Pesanan sudah dipatch (26 Aug) ke:
#   "mb-4 ... shadow-sm"  (block biasa, ikut lebar container, tidak fixed)
#
# Fix ini HANYA menyamakan style notifikasi "status" (hijau/sukses)
# dan "error" (merah) di interaksi.blade.php dengan pola yang sudah
# dipakai di 3 halaman admin lainnya. Tidak ada file lain yang disentuh.
#
# Cara pakai:
# 1. Jalankan dari folder project (C:\xampp\htdocs\karyaIdeEdi)
# 2. .\apply-fix-interaksi-notification-fullwidth.ps1
# ============================================================

$ErrorActionPreference = "Stop"
$targetFile = "resources\views\pages\admin\interaksi.blade.php"

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Fix: Notifikasi Interaksi jadi fullwidth" -ForegroundColor Cyan
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

$backupFile = "$targetFile.bak-before-notification-fullwidth-{0}" -f (Get-Date -Format "yyyyMMdd-HHmmss")
Copy-Item $targetFile $backupFile -Force
Write-Host "[1/3] Backup dibuat: $backupFile" -ForegroundColor Green

$content = Get-Content $targetFile -Raw -Encoding UTF8

# --- Blok "status" (sukses/hijau) ---
$oldStatus = @'
    @if (session('status'))
        <div class="fixed right-6 top-20 z-[9999] flex w-[min(28rem,calc(100vw-2rem))] items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 shadow-2xl pointer-events-none">
            <i class="fa-solid fa-circle-check"></i>
            {{ session('status') }}
        </div>
    @endif
'@

$newStatus = @'
    @if (session('status'))
        <div class="mb-4 flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 shadow-sm">
            <i class="fa-solid fa-circle-check"></i>
            {{ session('status') }}
        </div>
    @endif
'@

# --- Blok "error" (gagal/merah) ---
$oldError = @'
    @if (session('error'))
        <div class="fixed right-6 top-20 z-[9999] flex w-[min(28rem,calc(100vw-2rem))] items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600 shadow-2xl pointer-events-none">
            <i class="fa-solid fa-triangle-exclamation"></i>
            {{ session('error') }}
        </div>
    @endif
'@

$newError = @'
    @if (session('error'))
        <div class="mb-4 flex items-center gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600 shadow-sm">
            <i class="fa-solid fa-triangle-exclamation"></i>
            {{ session('error') }}
        </div>
    @endif
'@

if (-not $content.Contains($oldStatus)) {
    Write-Host "[ERROR] Blok notifikasi 'status' tidak ditemukan persis di file." -ForegroundColor Red
    Write-Host "Kemungkinan file sudah pernah diubah manual. Cek manual ya, ge." -ForegroundColor Yellow
    Write-Host "Backup tetap aman di: $backupFile" -ForegroundColor Yellow
    exit 1
}

if (-not $content.Contains($oldError)) {
    Write-Host "[ERROR] Blok notifikasi 'error' tidak ditemukan persis di file." -ForegroundColor Red
    Write-Host "Kemungkinan file sudah pernah diubah manual. Cek manual ya, ge." -ForegroundColor Yellow
    Write-Host "Backup tetap aman di: $backupFile" -ForegroundColor Yellow
    exit 1
}

Write-Host "[2/3] Mengganti style notifikasi (fixed -> fullwidth block)..." -ForegroundColor Green
$newContent = $content.Replace($oldStatus, $newStatus).Replace($oldError, $newError)

$utf8Bom = New-Object System.Text.UTF8Encoding $true
[System.IO.File]::WriteAllText((Resolve-Path $targetFile), $newContent, $utf8Bom)

Write-Host "[3/3] Selesai." -ForegroundColor Green
Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " FIX DITERAPKAN!" -ForegroundColor Cyan
Write-Host " Halaman Livewire, tinggal hard refresh browser" -ForegroundColor Cyan
Write-Host " (Ctrl+Shift+R) di /admin/interaksi, tidak perlu" -ForegroundColor Cyan
Write-Host " npm run build karena ini cuma ubah markup Blade." -ForegroundColor Cyan
Write-Host " File asli ada di: $backupFile" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
