# ============================================================
# FIX: CartStatusController.php fatal error "Namespace
# declaration statement has to be the very first statement"
#
# ROOT CAUSE: script sebelumnya salah nulis file .php ini pakai
# UTF-8 BOM (3 byte tak kasat mata sebelum "<?php"). PHP gak
# terima ada apapun sebelum <?php di baris pertama -> fatal error.
# BOM itu seharusnya cuma dipakai buat file .ps1, BUKAN file .php.
#
# Script ini cuma buang 3 byte BOM di depan file itu kalau ada.
# Isi kode PHP-nya sendiri TIDAK diubah sama sekali.
#
# Cara pakai:
# 1. Jalankan dari folder project (C:\xampp\htdocs\karyaIdeEdi)
# 2. .\apply-fix-cartstatus-bom.ps1
# ============================================================

$ErrorActionPreference = "Stop"
$targetFile = "app\Http\Controllers\CartStatusController.php"

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Fix: Buang BOM dari CartStatusController.php" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

if (-not (Test-Path $targetFile)) {
    Write-Host "[ERROR] File tidak ditemukan: $targetFile" -ForegroundColor Red
    exit 1
}

$bytes = [System.IO.File]::ReadAllBytes((Resolve-Path $targetFile))

if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
    $cleanBytes = $bytes[3..($bytes.Length - 1)]
    [System.IO.File]::WriteAllBytes((Resolve-Path $targetFile), $cleanBytes)
    Write-Host "[OK] BOM ditemukan dan sudah dibuang. File sekarang murni UTF-8 tanpa BOM." -ForegroundColor Green
} else {
    Write-Host "[INFO] Tidak ada BOM di file ini -- kemungkinan masalahnya beda. Cek manual ya, ge." -ForegroundColor Yellow
    exit 1
}

Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " SELESAI! Coba lagi:" -ForegroundColor Cyan
Write-Host " php artisan route:list --name=testimonials" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
