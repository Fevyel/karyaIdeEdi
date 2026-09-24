# ============================================================
# RAPIKAN: Tailwind IntelliSense saran class arbitrary
#          `lg:w-[26rem]` ditulis pakai class canonical
#          `lg:w-104` (sama-sama 26rem, cuma lebih rapi/konsisten
#          dengan skala spacing Tailwind bawaan).
#
# Perubahan HANYA di:
# 1. resources/views/pages/frontend/booking.blade.php (baris 205)
#
# Murni ganti nama class, TIDAK mengubah tampilan sama sekali.
#
# Cara pakai (dari VS Code integrated terminal, di root project
#   C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-rapikan-w-26rem-canonical.ps1
#
# Tidak perlu migration/npm build.
# ============================================================

$ErrorActionPreference = "Stop"

function Backup-File {
    param([string]$Path)
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$Path.bak-before-w-26rem-canonical-$stamp"
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
Write-Host " Rapikan: lg:w-[26rem] -> lg:w-104 (booking.blade.php)" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

$path = "resources\views\pages\frontend\booking.blade.php"

Write-Host "[1/1] Ganti class arbitrary jadi canonical ..." -ForegroundColor Yellow
$old = @'
                            <div class="flex flex-col justify-center px-6 py-8 lg:w-[26rem] lg:flex-none lg:px-10 {{ $mediaDiKanan ? 'lg:order-1' : 'lg:order-2' }}">
'@

$new = @'
                            <div class="flex flex-col justify-center px-6 py-8 lg:w-104 lg:flex-none lg:px-10 {{ $mediaDiKanan ? 'lg:order-1' : 'lg:order-2' }}">
'@

Replace-ExactlyOnce -Path $path -Old $old -New $new -UseBom $false

Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Selesai. Tampilan tidak berubah, cuma class" -ForegroundColor Cyan
Write-Host " jadi lebih rapi (canonical)." -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
