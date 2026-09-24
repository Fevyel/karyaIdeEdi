# ============================================================
# Menghapus overlay dekoratif (tag "Workshop Kami" + lingkaran
# berdenyut ikon panah) di atas peta Lokasi Beranda.
#
# Alasan: peta kanan sekarang Google Maps asli, yang otomatis
# sudah kasih pin merah + label nama toko sendiri (dari alamat).
# Overlay dekoratif lama posisinya dipatok manual (persenan),
# jadi tidak pernah pas dengan posisi pin asli dari Google --
# dan ikon panahnya bikin bingung karena terkesan seperti
# penanda lain yang terpisah dari lokasi utama.
#
# Setelah ini: peta kanan cuma menampilkan Google Maps apa
# adanya (dengan filter warna coklat/krem yang sudah dipasang
# sebelumnya) -- pin & label lokasi 100% dari Google, akurat,
# tidak ada elemen dekoratif buatan lagi.
#
# Yang berubah: HANYA resources/views/partials/frontend/lokasi.blade.php
# (2 bagian kecil dihapus: definisi variabel label yang sudah
# tidak dipakai, dan blok HTML tag+marker dekoratifnya).
#
# Cara pakai (dari VS Code integrated terminal, root project
# C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-hapus-overlay-marker-lokasi.ps1
# ============================================================

$ErrorActionPreference = "Stop"

function Backup-File {
    param([string]$Path)
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$Path.bak-before-hapus-overlay-marker-lokasi-$stamp"
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

    $occurrences = ([regex]::Matches($content, [regex]::Escape($Old))).Count
    if ($occurrences -eq 0) {
        Write-Host "[ERROR] Teks yang mau diganti tidak ditemukan di $Path." -ForegroundColor Red
        Write-Host "        Kemungkinan file ini sudah beda dari yang saya kira -- SAYA BERHENTI, tidak ada yang diubah di file ini." -ForegroundColor Red
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

$lokasiPath = "resources\views\partials\frontend\lokasi.blade.php"

# ------------------------------------------------------------
# 1) Hapus definisi variabel $lokasiMapTagLabel (sudah tidak dipakai)
# ------------------------------------------------------------
$old1 = @'
    // Tag label dekoratif ala "DESTINATION" di atas peta -- tetap/hardcode,
    // murni aksen visual, tidak diedit dari mana pun.
    $lokasiMapTagLabel = 'Workshop Kami';

'@

$new1 = ""

Replace-ExactlyOnce -Path $lokasiPath -Old $old1 -New $new1 -UseBom $true

# ------------------------------------------------------------
# 2) Hapus blok HTML tag label + marker berdenyut dekoratif
# ------------------------------------------------------------
$old2 = @'
        {{-- tag label ala "DESTINATION" -- aksen dekoratif, teks tetap/hardcode --}}
        <span class="absolute -translate-y-1/2 rounded-md bg-white px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-admin-accent shadow-sm" style="left:56%; top:33%;">
            {{ $lokasiMapTagLabel }}
        </span>

        {{-- marker lokasi (berdenyut, ikon panah ke atas ala live-location) --}}
        <span class="absolute -translate-x-1/2 -translate-y-1/2" style="left:56%; top:44%;">
            <span class="absolute -inset-3 animate-ping rounded-full bg-admin-accent/25"></span>
            <span class="relative flex h-9 w-9 items-center justify-center rounded-full bg-admin-accent text-white shadow-lg">
                <i class="fa-solid fa-caret-up text-base"></i>
            </span>
        </span>
'@

$new2 = ""

Replace-ExactlyOnce -Path $lokasiPath -Old $old2 -New $new2 -UseBom $true

Write-Host ""
Write-Host "Selesai. Overlay dekoratif sudah dihapus -- peta kanan sekarang cuma pin asli dari Google." -ForegroundColor Green
