# ============================================================
# FIX: Layout Privacy Policy berantakan (TOC numpuk ke atas)
# Penyebab: CSS hasil build (public/build) basi / stale,
# belum meng-include class Tailwind baru yang dipakai di
# resources/views/pages/frontend/privacy-policy.blade.php
# (khususnya grid-cols-[260px_1fr] untuk layout TOC + konten).
#
# Script ini TIDAK mengubah file kode apa pun.
# Cuma menjalankan ulang build Vite/Tailwind supaya CSS
# yang dipakai browser sinkron dengan Blade terbaru.
#
# Cara pakai:
# 1. Buka terminal VS Code, pastikan posisi di folder project
#    (C:\xampp\htdocs\karyaIdeEdi)
# 2. Jalankan: .\apply-rebuild-privacy-policy.ps1
# ============================================================

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Rebuild asset (Tailwind/Vite) - Fix Privacy Policy layout" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

# Pastikan kita di folder project Laravel (cek keberadaan artisan)
if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] File 'artisan' tidak ditemukan di folder ini." -ForegroundColor Red
    Write-Host "Pastikan kamu menjalankan script ini dari dalam folder:" -ForegroundColor Yellow
    Write-Host "  C:\xampp\htdocs\karyaIdeEdi" -ForegroundColor Yellow
    exit 1
}

Write-Host "[1/2] Menjalankan 'npm run build' ..." -ForegroundColor Green
npm run build

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "[ERROR] Build gagal (exit code $LASTEXITCODE)." -ForegroundColor Red
    Write-Host "Cek pesan error di atas. Kemungkinan umum:" -ForegroundColor Yellow
    Write-Host "  - node_modules belum lengkap -> coba jalankan: npm install" -ForegroundColor Yellow
    Write-Host "  - ada syntax error di file CSS/JS yang baru diedit" -ForegroundColor Yellow
    exit 1
}

Write-Host ""
Write-Host "[2/2] Build selesai. CSS terbaru sudah ada di public\build\assets\" -ForegroundColor Green
Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " SELESAI! Silakan refresh halaman Privacy Policy di browser." -ForegroundColor Cyan
Write-Host " (Kalau masih kelihatan sama, coba hard refresh: Ctrl+Shift+R)" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
