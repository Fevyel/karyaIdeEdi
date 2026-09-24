# ============================================================
# FIX AKAR MASALAH: kotak foto galeri Dokumentasi (/booking)
#      punya `aspect-ratio` (aspect-4/5 / sm:aspect-16/10) DAN
#      `lg:max-h-96` sekaligus, TANPA lebar eksplisit. Browser
#      menghitung ulang lebarnya sendiri dari max-height x aspect
#      ratio (384px x 16/10 = 614px) dan MENGABAIKAN lebar
#      kolom/flex dari parent -- makanya 3 percobaan restrukturisasi
#      grid/flex sebelumnya (full width, kolom fixed, flexbox)
#      semuanya kelihatan tidak berpengaruh: itu sudah aktif dan
#      benar, cuma "dimentahkan" oleh baris ini.
#
# Perubahan HANYA di:
# 1. resources/views/pages/frontend/booking.blade.php
#    - tambah `w-full` di kotak foto, supaya lebar SELALU
#      ngikut parent (flex-1), baru tinggi & crop dihitung dari situ.
#
# TIDAK menyentuh file lain di luar yang disebut di atas.
#
# Cara pakai (dari VS Code integrated terminal, di root project
#   C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-fix-galeri-dokumentasi-width-akar.ps1
#
# Setelah ini WAJIB jalankan lagi:  npm run build
# (soalnya class baru `w-full` di posisi ini belum pernah dipakai
#  gabungan sama class lain di file ini -- aman kalau `w-full`
#  sendiri sudah pernah dipakai di file lain, Tailwind v4 tetap
#  scan ulang tiap build).
# ============================================================

$ErrorActionPreference = "Stop"

function Backup-File {
    param([string]$Path)
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$Path.bak-before-galeri-width-akar-$stamp"
    Copy-Item -Path $Path -Destination $backupPath -Force
    Write-Host "  Backup dibuat: $backupPath" -ForegroundColor DarkGray
    return $backupPath
}

function Replace-ExactlyOnce {
    param(
        [string]$Path,
        [string]$Old,
        [string]$New,
        [bool]$UseBom
    )

    $content = [System.IO.File]::ReadAllText((Join-Path (Get-Location) $Path))

    if ($content -match "`r`n") {
        $Old = $Old -replace "`r?`n", "`r`n"
        $New = $New -replace "`r?`n", "`r`n"
    } else {
        $Old = $Old -replace "`r`n", "`n"
        $New = $New -replace "`r`n", "`n"
    }

    $occurrences = ([regex]::Matches($content, [regex]::Escape($Old))).Count
    if ($occurrences -eq 0) {
        Write-Host "[ERROR] Teks yang mau diganti tidak ditemukan di $Path." -ForegroundColor Red
        Write-Host "        Kemungkinan file ini sudah diubah -- SAYA BERHENTI, tidak ada yang diubah di file ini." -ForegroundColor Red
        exit 1
    }
    if ($occurrences -gt 1) {
        Write-Host "[ERROR] Teks yang mau diganti muncul $occurrences kali di $Path (harusnya cuma 1)." -ForegroundColor Red
        Write-Host "        SAYA BERHENTI supaya tidak salah ganti bagian yang lain." -ForegroundColor Red
        exit 1
    }

    Backup-File -Path $Path | Out-Null

    $newContent = $content.Replace($Old, $New)
    $encoding = New-Object System.Text.UTF8Encoding($UseBom)
    [System.IO.File]::WriteAllText((Join-Path (Get-Location) $Path), $newContent, $encoding)

    Write-Host "  OK -- $Path sudah di-patch." -ForegroundColor Green
}

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Fix akar: w-full di kotak foto galeri (/booking)" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

$path = "resources\views\pages\frontend\booking.blade.php"

Write-Host "[1/1] Tambah w-full di kotak foto ..." -ForegroundColor Yellow
$old = @'
                                    class="relative aspect-4/5 overflow-hidden transition-all duration-1000 ease-out sm:aspect-16/10 lg:max-h-96"
'@

$new = @'
                                    class="relative w-full aspect-4/5 overflow-hidden transition-all duration-1000 ease-out sm:aspect-16/10 lg:max-h-96"
'@

Replace-ExactlyOnce -Path $path -Old $old -New $new -UseBom $false

Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Selesai. SEKARANG WAJIB jalankan:" -ForegroundColor Cyan
Write-Host "   npm run build" -ForegroundColor White
Write-Host " lalu hard refresh (Ctrl+Shift+R) di /booking." -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
