# ============================================================
# FIX: Galeri "Dokumentasi" (halaman /booking) sudah full width,
#      TAPI kolom teks (angka urut + keterangan) ikut melar
#      selebar kolom grid (karena lebarnya pakai fr, proporsi
#      dari TOTAL lebar layar) -- jadi angka & teks keliatan
#      "ngambang" jauh dari fotonya, banyak ruang kosong.
#
# Perubahan (media tetap full-bleed/flexible, cuma kolom TEKS
# dikunci ke lebar tetap 26rem (~416px) supaya nempel dekat ke
# foto, bukan ikut melar selebar layar):
# 1. resources/views/pages/frontend/booking.blade.php
#
# TIDAK menyentuh file lain di luar yang disebut di atas.
#
# Cara pakai (dari VS Code integrated terminal, di root project
#   C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-fix-galeri-dokumentasi-kolom-teks.ps1
#
# Tidak perlu migration/npm build, murni ubah 1 baris PHP (rasio
# lebar kolom grid).
# ============================================================

$ErrorActionPreference = "Stop"

function Backup-File {
    param([string]$Path)
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$Path.bak-before-galeri-kolom-teks-$stamp"
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
Write-Host " Fix: Kolom Teks Galeri Dokumentasi (/booking)" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

$path = "resources\views\pages\frontend\booking.blade.php"

Write-Host "[1/1] Kunci lebar kolom teks ke 26rem, media dapat sisa ruang ..." -ForegroundColor Yellow
$old = @'
                            // Kolom media dibuat lebih lebar (3fr) daripada kolom teks (2fr) supaya
                            // foto/video tampil besar -- urutan fr dibalik sesuai sisi media, jadi
                            // media SELALU dapat porsi lebih lebar di kedua arah (item genap/ganjil).
                            $kolomGaleri = $mediaDiKanan ? '2fr 3fr' : '3fr 2fr';
'@

$new = @'
                            // Kolom teks dikunci lebar TETAP (26rem) supaya nempel dekat ke foto --
                            // sisanya (media) fleksibel mengisi ruang yang tersisa (full-bleed).
                            // Urutan kolom dibalik sesuai sisi media (item genap/ganjil).
                            $kolomGaleri = $mediaDiKanan ? '26rem minmax(0,1fr)' : 'minmax(0,1fr) 26rem';
'@

Replace-ExactlyOnce -Path $path -Old $old -New $new -UseBom $false

Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Selesai. Refresh halaman /booking -- teks" -ForegroundColor Cyan
Write-Host " (angka + keterangan) sekarang nempel dekat" -ForegroundColor Cyan
Write-Host " foto, foto tetap full-bleed mengisi sisa layar." -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
