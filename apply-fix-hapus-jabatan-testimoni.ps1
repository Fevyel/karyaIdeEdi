# ============================================================
# FIX: Hapus tulisan jabatan (mis. ARSITEK / INTERIOR DESIGNER)
#      yang tampil di bawah nama pada kartu testimoni frontend.
#
# Perubahan (cuma tampilan, data 'jabatan' di database & form
# admin TIDAK dihapus/diubah -- cuma disembunyikan dari kartu):
# 1. resources/views/partials/frontend/testimonials.blade.php
#    (dipakai di Beranda & bagian atas halaman /testimoni)
# 2. resources/views/pages/frontend/testimoni.blade.php
#    (daftar lengkap testimoni di halaman /testimoni)
#
# TIDAK menyentuh file lain di luar yang disebut di atas.
#
# Cara pakai (dari VS Code integrated terminal, di root project
#   C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-fix-hapus-jabatan-testimoni.ps1
#
# Tidak perlu migration/npm build, murni hapus 1 blok markup.
# ============================================================

$ErrorActionPreference = "Stop"

function Backup-File {
    param([string]$Path)
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$Path.bak-before-hapus-jabatan-$stamp"
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
Write-Host " Fix: Hapus Jabatan di Kartu Testimoni Frontend" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

Write-Host "[1/2] Hapus jabatan di kartu Top 3 Testimoni (Beranda) ..." -ForegroundColor Yellow
$path1 = "resources\views\partials\frontend\testimonials.blade.php"
$old1 = @'
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold {{ $isDark ? 'text-white' : 'text-admin-ink' }}">
                                    {{ $testimonial->displayName() }}
                                </p>
                                @if ($testimonial->jabatan)
                                    <p class="truncate text-[11px] uppercase tracking-wide {{ $isDark ? 'text-white/50' : 'text-admin-ink-soft' }}">
                                        {{ $testimonial->jabatan }}
                                    </p>
                                @endif
                            </div>
'@

$new1 = @'
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold {{ $isDark ? 'text-white' : 'text-admin-ink' }}">
                                    {{ $testimonial->displayName() }}
                                </p>
                            </div>
'@

Replace-ExactlyOnce -Path $path1 -Old $old1 -New $new1 -UseBom $false
Write-Host ""
Write-Host "[2/2] Hapus jabatan di daftar lengkap testimoni (halaman /testimoni) ..." -ForegroundColor Yellow
$path2 = "resources\views\pages\frontend\testimoni.blade.php"
$old2 = @'
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[11px] font-semibold text-[#2A211B] sm:text-xs">
                                        {{ $testimonial->displayName() }}
                                    </p>
                                    @if ($testimonial->jabatan)
                                        <p class="truncate text-[10px] text-[#A1988E]">
                                            {{ $testimonial->jabatan }}
                                        </p>
                                    @endif
                                </div>
'@

$new2 = @'
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[11px] font-semibold text-[#2A211B] sm:text-xs">
                                        {{ $testimonial->displayName() }}
                                    </p>
                                </div>
'@

Replace-ExactlyOnce -Path $path2 -Old $old2 -New $new2 -UseBom $false
Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Selesai. Refresh Beranda & halaman /testimoni --" -ForegroundColor Cyan
Write-Host " tulisan jabatan di bawah nama sudah hilang." -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
