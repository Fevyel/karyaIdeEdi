# ============================================================
# Menghapus 5 section di halaman "Tentang Kami" (/profil):
# E. Toko & Produk dalam Angka, F. Cara Berbelanja, G. Garansi,
# H. Pengiriman & Pengembalian, I. CTA penutup ("Temukan
# furniture yang tepat untuk ruangmu.").
#
# Yang berubah: HANYA resources/views/pages/frontend/profil.blade.php
# Section A (Hero), B (Tentang Karya Ide Edi), C (Nilai/Keunggulan),
# D (Why Choose Us) TIDAK disentuh -- section D langsung disambung
# ke Footer.
#
# BEDA dari versi sebelumnya: script ini TIDAK mencocokkan seluruh
# 239 baris yang mau dihapus secara persis (rawan gagal kalau ada
# karakter spesial seperti tanda pisah "-" yang encoding-nya beda
# antara PowerShell dan file aslinya). Sekarang cukup mencari 2
# penanda pendek (awal section E, dan baris @include footer) lalu
# menghapus semua yang ada DI ANTARA keduanya -- jauh lebih tahan
# terhadap perbedaan kecil.
#
# Cara pakai (dari VS Code integrated terminal, root project
# C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-hapus-section-toko-sampai-cta-profil.ps1
#
# Setelah itu cek di browser: buka "/profil" (Tentang Kami), scroll
# dari "Why Choose Us" -- harus langsung ke Footer, tanpa section
# angka/cara-berbelanja/garansi/pengiriman/CTA lagi.
# ============================================================

$ErrorActionPreference = "Stop"

function Backup-File {
    param([string]$Path)
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$Path.bak-before-hapus-section-toko-sampai-cta-$stamp"
    Copy-Item -Path $Path -Destination $backupPath -Force
    Write-Host "  Backup dibuat: $backupPath" -ForegroundColor DarkGray
    return $backupPath
}

function Remove-Range {
    param(
        [string]$Path,
        [string]$StartAnchor,
        [string]$EndAnchor,
        [bool]$UseBom
    )

    $content = [System.IO.File]::ReadAllText((Join-Path (Get-Location) $Path))

    $startCount = ([regex]::Matches($content, [regex]::Escape($StartAnchor))).Count
    if ($startCount -eq 0) {
        Write-Host "[ERROR] Penanda awal (section E) tidak ditemukan di $Path." -ForegroundColor Red
        Write-Host "        SAYA BERHENTI, tidak ada yang diubah." -ForegroundColor Red
        exit 1
    }
    if ($startCount -gt 1) {
        Write-Host "[ERROR] Penanda awal muncul $startCount kali (harusnya cuma 1). SAYA BERHENTI." -ForegroundColor Red
        exit 1
    }

    $endCount = ([regex]::Matches($content, [regex]::Escape($EndAnchor))).Count
    if ($endCount -eq 0) {
        Write-Host "[ERROR] Penanda akhir (@include footer) tidak ditemukan di $Path." -ForegroundColor Red
        Write-Host "        SAYA BERHENTI, tidak ada yang diubah." -ForegroundColor Red
        exit 1
    }
    if ($endCount -gt 1) {
        Write-Host "[ERROR] Penanda akhir muncul $endCount kali (harusnya cuma 1). SAYA BERHENTI." -ForegroundColor Red
        exit 1
    }

    $startIdx = $content.IndexOf($StartAnchor)
    $endIdx = $content.IndexOf($EndAnchor)

    if ($endIdx -le $startIdx) {
        Write-Host "[ERROR] Urutan penanda tidak seperti yang diharapkan (akhir sebelum awal). SAYA BERHENTI." -ForegroundColor Red
        exit 1
    }

    Backup-File -Path $Path | Out-Null

    $before = $content.Substring(0, $startIdx).TrimEnd()
    $after = $content.Substring($endIdx)
    $newContent = $before + "`n`n" + $after

    $encoding = New-Object System.Text.UTF8Encoding($UseBom)
    [System.IO.File]::WriteAllText((Join-Path (Get-Location) $Path), $newContent, $encoding)

    Write-Host "  OK -- $Path sudah di-patch." -ForegroundColor Green
}

$profilPath = "resources\views\pages\frontend\profil.blade.php"

$startAnchor = "    {{-- =====================================================`n         E. TOKO"

$endAnchor = "    @include('partials.frontend.footer')"

Remove-Range -Path $profilPath -StartAnchor $startAnchor -EndAnchor $endAnchor -UseBom $true

Write-Host ""
Write-Host "Selesai. Section E-I (Toko dalam Angka s.d. CTA) sudah dihapus dari /profil." -ForegroundColor Green
