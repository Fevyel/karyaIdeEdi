# ============================================================
# FIX: 2 percobaan sebelumnya (full-width + kolom teks fixed)
#      dipakai lewat CSS Grid + custom property (--dokgal-cols)
#      -- hasilnya malah berantakan (foto tidak mengisi ruang
#      yang tersisa, ada celah kosong ganda kiri-kanan).
#
#      Fix kali ini GANTI PENDEKATAN: baris grid diganti jadi
#      flexbox biasa -- kolom teks lebar tetap (26rem, TIDAK
#      ikut membesar/mengecil), kolom media flex-1 (otomatis
#      mengisi SEMUA sisa ruang, full-bleed). Ini pola yang jauh
#      lebih predictable daripada grid-template-columns dinamis.
#
# Perubahan HANYA di:
# 1. resources/views/pages/frontend/booking.blade.php
#    - baris pembuka grid tiap item galeri -> jadi flex
#    - class wrapper MEDIA -> tambah lg:flex-1 lg:min-w-0
#    - class wrapper TEKS -> tambah lg:w-[26rem] lg:flex-none
#
# Variabel PHP $kolomGaleri (dari fix sebelumnya) SENGAJA
# dibiarkan apa adanya di file (jadi tidak terpakai, tapi aman
# -- tidak dihapus supaya patch ini tetap bisa jalan walau fix
# kolom-teks sebelumnya sempat gagal/berhasil, tidak masalah).
#
# TIDAK menyentuh file lain di luar yang disebut di atas.
#
# Cara pakai (dari VS Code integrated terminal, di root project
#   C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-fix-galeri-dokumentasi-flexbox.ps1
#
# Tidak perlu migration/npm build, murni ubah struktur <div>.
# ============================================================

$ErrorActionPreference = "Stop"

function Backup-File {
    param([string]$Path)
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$Path.bak-before-galeri-flexbox-$stamp"
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
Write-Host " Fix: Galeri Dokumentasi -> Flexbox (/booking)" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

$path = "resources\views\pages\frontend\booking.blade.php"

Write-Host "[1/3] Ganti baris grid jadi flex ..." -ForegroundColor Yellow
$old1 = @'
                        <div class="grid items-center gap-0 lg:grid-cols-(--dokgal-cols)" style="--dokgal-cols: {{ $kolomGaleri }};">
'@

$new1 = @'
                        <div class="flex flex-col items-stretch lg:flex-row lg:items-center">
'@

Replace-ExactlyOnce -Path $path -Old $old1 -New $new1 -UseBom $false

Write-Host ""
Write-Host "[2/3] Kolom MEDIA -> flex-1 (isi semua sisa ruang) ..." -ForegroundColor Yellow
$old2 = @'
                            <div
                                x-data="dokumentasiGaleriItem()" x-init="init()"
                                class="relative {{ $mediaDiKanan ? 'lg:order-2' : 'lg:order-1' }}"
                            >
'@

$new2 = @'
                            <div
                                x-data="dokumentasiGaleriItem()" x-init="init()"
                                class="relative lg:min-w-0 lg:flex-1 {{ $mediaDiKanan ? 'lg:order-2' : 'lg:order-1' }}"
                            >
'@

Replace-ExactlyOnce -Path $path -Old $old2 -New $new2 -UseBom $false

Write-Host ""
Write-Host "[3/3] Kolom TEKS -> lebar tetap 26rem, tidak ikut melar ..." -ForegroundColor Yellow
$old3 = @'
                            <div class="flex flex-col justify-center px-6 py-8 lg:px-10 {{ $mediaDiKanan ? 'lg:order-1' : 'lg:order-2' }}">
'@

$new3 = @'
                            <div class="flex flex-col justify-center px-6 py-8 lg:w-[26rem] lg:flex-none lg:px-10 {{ $mediaDiKanan ? 'lg:order-1' : 'lg:order-2' }}">
'@

Replace-ExactlyOnce -Path $path -Old $old3 -New $new3 -UseBom $false

Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Selesai. Refresh halaman /booking -- foto full-" -ForegroundColor Cyan
Write-Host " bleed mengisi semua sisa ruang, teks tetap" -ForegroundColor Cyan
Write-Host " lebar 26rem nempel dekat foto." -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
