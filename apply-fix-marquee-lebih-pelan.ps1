# ============================================================
# FIX: Tulisan berjalan "FURNITUR TOKO MEBEL - KARYA IDE-EDI"
#      di atas footer (marquee-brand) masih kecepatan, dibuat
#      sedikit lebih pelan.
#
# Perubahan HANYA di:
# 1. resources/views/partials/frontend/marquee-brand.blade.php
#    --kie-marquee-speed: 70s -> 90s (makin besar = makin pelan)
#
# TIDAK menyentuh file lain di luar yang disebut di atas.
#
# Cara pakai (dari VS Code integrated terminal, di root project
#   C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-fix-marquee-lebih-pelan.ps1
#
# CSS-nya inline di file ini (sengaja, biar tidak perlu build) --
# jadi TIDAK perlu npm run build, cukup refresh browser biasa.
# ============================================================

$ErrorActionPreference = "Stop"

function Backup-File {
    param([string]$Path)
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$Path.bak-before-marquee-pelan-$stamp"
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
Write-Host " Fix: Marquee Brand Lebih Pelan (70s -> 90s)" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

$path = "resources\views\partials\frontend\marquee-brand.blade.php"

Write-Host "[1/1] Ubah durasi animasi 70s -> 90s ..." -ForegroundColor Yellow
$old = @'
        --kie-marquee-speed: 70s;
'@

$new = @'
        --kie-marquee-speed: 90s;
'@

Replace-ExactlyOnce -Path $path -Old $old -New $new -UseBom $false

Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Selesai. Tinggal refresh browser biasa (TIDAK" -ForegroundColor Cyan
Write-Host " perlu npm run build, CSS-nya inline)." -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
