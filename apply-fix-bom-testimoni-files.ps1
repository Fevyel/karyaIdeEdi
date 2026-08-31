# ============================================================
# FIX: "Page layout may be unexpected due to Quirks Mode" +
# gap aneh di atas navbar -- disebabkan karakter BOM (3 byte
# EF BB BF, tak kasat mata) nyangkut SEBELUM "<!DOCTYPE html>".
# Browser gagal kenali DOCTYPE-nya -> render pakai Quirks Mode.
#
# ROOT CAUSE: Get-Content -Encoding UTF8 di PowerShell tidak
# membuang karakter BOM dari ISI TEKS kalau file sumbernya sudah
# lebih dulu kena BOM (dari patch/script sebelumnya) -- walau
# ditulis ulang pakai UTF8Encoding($false), BOM yang sudah
# "nempel" di teksnya tetap ikut ke-tulis. Ini beda dari bug BOM
# kemarin (itu soal PENULISAN baru), ini soal BOM yang ke-COPY
# dari file sumber yang sudah lebih dulu terkontaminasi.
#
# Script ini CUMA mengecek & membuang 3 byte BOM di awal file
# (kalau ada) dari daftar file yang kesenggol pekerjaan testimoni
# kemarin. Isi kode di dalamnya TIDAK diubah sama sekali.
#
# Cara pakai:
# 1. Jalankan dari folder project (C:\xampp\htdocs\karyaIdeEdi)
# 2. .\apply-fix-bom-testimoni-files.ps1
# ============================================================

$ErrorActionPreference = "Stop"

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Fix: Cek & buang BOM nyasar (file testimoni)" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

$filesToCheck = @(
    "resources\views\pages\frontend\testimoni.blade.php",
    "resources\views\pages\frontend\testimoni.blade.php.bak-20260830",
    "resources\views\pages\admin\testimoni.blade.php",
    "resources\views\pages\admin\testimoni-form.blade.php",
    "app\Http\Controllers\CartStatusController.php"
)

$foundAny = $false

foreach ($file in $filesToCheck) {
    if (-not (Test-Path $file)) {
        Write-Host "[SKIP] Tidak ditemukan: $file" -ForegroundColor DarkGray
        continue
    }

    $bytes = [System.IO.File]::ReadAllBytes((Resolve-Path $file))

    if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
        $cleanBytes = $bytes[3..($bytes.Length - 1)]
        [System.IO.File]::WriteAllBytes((Resolve-Path $file), $cleanBytes)
        Write-Host "[FIXED] BOM ditemukan & dibuang: $file" -ForegroundColor Green
        $foundAny = $true
    } else {
        Write-Host "[OK] Sudah bersih (tidak ada BOM): $file" -ForegroundColor DarkGreen
    }
}

Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
if ($foundAny) {
    Write-Host " SELESAI! Beberapa file dibersihkan dari BOM." -ForegroundColor Cyan
    Write-Host " Jalankan: php artisan view:clear" -ForegroundColor Cyan
    Write-Host " Lalu hard refresh browser (Ctrl+Shift+R) di /testimoni" -ForegroundColor Cyan
    Write-Host " dan /admin/testimoni." -ForegroundColor Cyan
} else {
    Write-Host " Tidak ada BOM ditemukan di file manapun." -ForegroundColor Cyan
    Write-Host " Kalau gap masih ada, kemungkinan penyebabnya beda -- kabarin gw." -ForegroundColor Cyan
}
Write-Host "==============================================" -ForegroundColor Cyan
