# ============================================================
# FIX: Galeri "Dokumentasi" (halaman /booking) kepotong di
#      tengah layar (max-w-7xl), padahal foto/videonya dibuat
#      full-bleed. Sekarang seluruh section galeri dibuat
#      full width; cuma label "Galeri" di atasnya yang tetap
#      dibatasi lebar (max-w-7xl) biar rapi.
#
# Perubahan HANYA di:
# 1. resources/views/pages/frontend/booking.blade.php
#    (buka wrapper <div class="mx-auto max-w-7xl ..."> tepat
#     setelah label "Galeri", supaya baris foto/teks di
#     bawahnya tidak lagi dibatasi lebar 1280px)
#
# TIDAK menyentuh file lain di luar yang disebut di atas.
#
# Cara pakai (dari VS Code integrated terminal, di root project
#   C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-fix-galeri-dokumentasi-full-width.ps1
#
# Tidak perlu migration/npm build, murni ubah struktur <div>.
# ============================================================

$ErrorActionPreference = "Stop"

function Backup-File {
    param([string]$Path)
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$Path.bak-before-galeri-full-width-$stamp"
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
Write-Host " Fix: Galeri Dokumentasi Full Width (/booking)" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

$path = "resources\views\pages\frontend\booking.blade.php"

Write-Host "[1/2] Tutup wrapper max-w-7xl tepat setelah label 'Galeri' ..." -ForegroundColor Yellow
$old1 = @'
            <div class="mx-auto max-w-7xl px-6 sm:px-8 lg:px-10">
                <p class="mb-12 text-center text-xs font-semibold uppercase tracking-[0.3em] text-[#9B6E3E] sm:mb-20">
                    Galeri
                </p>

                <div class="divide-y divide-[#E4D8C2]/70">
'@

$new1 = @'
            <div class="mx-auto max-w-7xl px-6 sm:px-8 lg:px-10">
                <p class="mb-12 text-center text-xs font-semibold uppercase tracking-[0.3em] text-[#9B6E3E] sm:mb-20">
                    Galeri
                </p>
            </div>

            <div class="divide-y divide-[#E4D8C2]/70">
'@

Replace-ExactlyOnce -Path $path -Old $old1 -New $new1 -UseBom $false

Write-Host ""
Write-Host "[2/2] Hapus penutup </div> ganda di akhir section galeri ..." -ForegroundColor Yellow
$old2 = @'
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
'@

$new2 = @'
                        </div>
                    @endforeach
                </div>
        </section>
    @endif
'@

Replace-ExactlyOnce -Path $path -Old $old2 -New $new2 -UseBom $false

Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Selesai. Refresh halaman /booking -- galeri" -ForegroundColor Cyan
Write-Host " Dokumentasi sekarang full width, cuma label" -ForegroundColor Cyan
Write-Host " 'Galeri' di atasnya yang tetap ke tengah." -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
