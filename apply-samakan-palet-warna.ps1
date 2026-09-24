# apply-samakan-palet-warna.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
#   .\apply-samakan-palet-warna.ps1
#
# Tujuan: SEMUA pilihan warna di Admin > Edit Web memakai satu palet yang sama
# (palet Header sebagai acuan): Header, Sejak Berdiri, Produk Unggulan, Kategori
# Produk, Testimoni Pelanggan (frame + kartu Top 1/2/3), dan Lokasi. Sebelumnya
# tiap bagian punya daftar sendiri yang isinya sedikit beda-beda.
#
# Yang berubah:
#  - File BARU  : app/Support/ColorPalette.php  (satu-satunya tempat daftar warna)
#  - File DIUBAH: resources/views/pages/admin/edit-web.blade.php -- HANYA 7 deklarasi
#                 daftar preset (headerBgPresets, missionBgPresets, dst.) yang kini
#                 mengambil dari ColorPalette. Tampilan, tombol, dan logika lain tidak disentuh.
#
# Aman: dicek "tepat 1 kali cocok" dulu di memori. Kalau ada yang tidak cocok,
# script berhenti dan TIDAK ADA file yang ditulis. Backup: .backup-samakan-palet-<waktu>/

$ErrorActionPreference = "Stop"

$root = (Get-Location).Path
if (-not (Test-Path (Join-Path $root "artisan"))) { throw "Jalankan script ini dari root project (folder yang ada file artisan-nya)." }

function Read-Lf([string]$RelPath) {
    $full = Join-Path $root $RelPath
    if (-not (Test-Path $full)) { throw "Tidak ketemu: $RelPath" }
    $raw = [System.IO.File]::ReadAllText($full)
    return @{ Text = ($raw -replace "`r`n", "`n"); CrLf = $raw.Contains("`r`n") }
}

function Write-Lf([string]$RelPath, [string]$Text, [bool]$CrLf) {
    $full = Join-Path $root $RelPath
    if ($CrLf) { $Text = $Text -replace "`n", "`r`n" }
    $dir = Split-Path $full -Parent
    if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
    [System.IO.File]::WriteAllText($full, $Text, (New-Object System.Text.UTF8Encoding($false)))
}

function Replace-ExactlyOnce([string]$Text, [string]$Old, [string]$New, [string]$Label) {
    $Old = $Old -replace "`r`n", "`n"
    $New = $New -replace "`r`n", "`n"
    $first = $Text.IndexOf($Old, [System.StringComparison]::Ordinal)
    if ($first -lt 0) { throw "[$Label] tidak cocok persis (mungkin sudah pernah diubah). Tidak ada file yang ditulis." }
    $second = $Text.IndexOf($Old, $first + $Old.Length, [System.StringComparison]::Ordinal)
    if ($second -ge 0) { throw "[$Label] cocok lebih dari 1 kali. Tidak ada file yang ditulis." }
    return $Text.Substring(0, $first) + $New + $Text.Substring($first + $Old.Length)
}

if (Test-Path (Join-Path $root "app/Support/ColorPalette.php")) { throw "Sudah ada: app/Support/ColorPalette.php (script ini sepertinya sudah pernah dijalankan). Tidak ada yang diubah." }

$pending = @()

# ---------- resources/views/pages/admin/edit-web.blade.php ----------
$f1 = Read-Lf "resources/views/pages/admin/edit-web.blade.php"
$f1Text = $f1.Text
$f1Text = Replace-ExactlyOnce $f1Text @'
    /**
     * Palet warna latar yang direkomendasikan -- dipilih tangan supaya
     * hasilnya konsisten dengan gaya toko (bukan random) dan kontrasnya
     * sudah pasti aman lewat contrastTextColors() di hero.blade.php.
     * Admin tetap bisa pakai color picker di sampingnya untuk warna bebas.
     *
     * @var array<int, array{label: string, value: string}>
     */
    public array $headerBgPresets = [
        ['label' => 'Krem Hangat (Default)', 'value' => '#F9F7F2', 'check' => '#3D2B1F'],
        ['label' => 'Putih Gading', 'value' => '#FFFDF8', 'check' => '#3D2B1F'],
        ['label' => 'Beige Pasir', 'value' => '#EFE3D0', 'check' => '#3D2B1F'],
        ['label' => 'Sage Hijau Lembut', 'value' => '#DCE3CE', 'check' => '#3D2B1F'],
        ['label' => 'Terracotta', 'value' => '#C97B5A', 'check' => '#FFFFFF'],
        ['label' => 'Coklat Kayu Tua', 'value' => '#3D2B1F', 'check' => '#FFFFFF'],
        ['label' => 'Navy Malam', 'value' => '#22303F', 'check' => '#FFFFFF'],
        ['label' => 'Hitam Elegan', 'value' => '#1A1A1A', 'check' => '#FFFFFF'],
    ];

'@ @'
    /**
     * Palet warna rekomendasi untuk: Header (section paling atas Beranda).
     * SATU daftar yang sama untuk SEMUA pilihan warna di Edit Web -- ubah/tambah
     * warna di app/Support/ColorPalette.php, jangan bikin daftar sendiri di sini.
     *
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public array $headerBgPresets = \App\Support\ColorPalette::PRESETS;

'@ "headerBgPresets"
$f1Text = Replace-ExactlyOnce $f1Text @'
    /**
     * Palet warna latar rekomendasi untuk section ini -- sama semangatnya
     * dengan $headerBgPresets, cuma preset pertama disesuaikan jadi warna
     * bawaan section ini (krem persik #FEEDD8), bukan krem hangat Header.
     *
     * @var array<int, array{label: string, value: string}>
     */
    public array $missionBgPresets = [
        ['label' => 'Krem Persik (Default)', 'value' => '#FEEDD8', 'check' => '#4B3A26'],
        ['label' => 'Putih Gading', 'value' => '#FFFDF8', 'check' => '#3D2B1F'],
        ['label' => 'Beige Pasir', 'value' => '#EFE3D0', 'check' => '#3D2B1F'],
        ['label' => 'Sage Hijau Lembut', 'value' => '#DCE3CE', 'check' => '#3D2B1F'],
        ['label' => 'Terracotta', 'value' => '#C97B5A', 'check' => '#FFFFFF'],
        ['label' => 'Coklat Kayu Tua', 'value' => '#3D2B1F', 'check' => '#FFFFFF'],
        ['label' => 'Navy Malam', 'value' => '#22303F', 'check' => '#FFFFFF'],
        ['label' => 'Hitam Elegan', 'value' => '#1A1A1A', 'check' => '#FFFFFF'],
    ];

'@ @'
    /**
     * Palet warna rekomendasi untuk: Sejak Berdiri.
     * SATU daftar yang sama untuk SEMUA pilihan warna di Edit Web -- ubah/tambah
     * warna di app/Support/ColorPalette.php, jangan bikin daftar sendiri di sini.
     *
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public array $missionBgPresets = \App\Support\ColorPalette::PRESETS;

'@ "missionBgPresets"
$f1Text = Replace-ExactlyOnce $f1Text @'
    /**
     * Palet warna latar rekomendasi untuk section ini -- sama pola dengan
     * $headerBgPresets & $missionBgPresets, preset pertama disesuaikan
     * jadi putih (warna bawaan section ini).
     *
     * @var array<int, array{label: string, value: string}>
     */
    public array $produkUnggulanBgPresets = [
        ['label' => 'Putih (Default)', 'value' => '#FFFFFF', 'check' => '#3D2B1F'],
        ['label' => 'Krem Hangat', 'value' => '#F9F7F2', 'check' => '#3D2B1F'],
        ['label' => 'Beige Pasir', 'value' => '#EFE3D0', 'check' => '#3D2B1F'],
        ['label' => 'Sage Hijau Lembut', 'value' => '#DCE3CE', 'check' => '#3D2B1F'],
        ['label' => 'Terracotta', 'value' => '#C97B5A', 'check' => '#FFFFFF'],
        ['label' => 'Coklat Kayu Tua', 'value' => '#3D2B1F', 'check' => '#FFFFFF'],
        ['label' => 'Navy Malam', 'value' => '#22303F', 'check' => '#FFFFFF'],
        ['label' => 'Hitam Elegan', 'value' => '#1A1A1A', 'check' => '#FFFFFF'],
    ];

'@ @'
    /**
     * Palet warna rekomendasi untuk: Produk Unggulan.
     * SATU daftar yang sama untuk SEMUA pilihan warna di Edit Web -- ubah/tambah
     * warna di app/Support/ColorPalette.php, jangan bikin daftar sendiri di sini.
     *
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public array $produkUnggulanBgPresets = \App\Support\ColorPalette::PRESETS;

'@ "produkUnggulanBgPresets"
$f1Text = Replace-ExactlyOnce $f1Text @'
    /**
     * Palet warna latar rekomendasi untuk section ini -- sama pola dengan
     * $headerBgPresets, $missionBgPresets & $produkUnggulanBgPresets,
     * preset pertama disesuaikan jadi peach lembut (warna bawaan section
     * ini sebelum bisa diedit).
     *
     * @var array<int, array{label: string, value: string}>
     */
    public array $kategoriBgPresets = [
        ['label' => 'Peach Lembut (Default)', 'value' => '#FEEDD8', 'check' => '#3D2B1F'],
        ['label' => 'Krem Hangat', 'value' => '#F9F7F2', 'check' => '#3D2B1F'],
        ['label' => 'Putih', 'value' => '#FFFFFF', 'check' => '#3D2B1F'],
        ['label' => 'Beige Pasir', 'value' => '#EFE3D0', 'check' => '#3D2B1F'],
        ['label' => 'Sage Hijau Lembut', 'value' => '#DCE3CE', 'check' => '#3D2B1F'],
        ['label' => 'Terracotta', 'value' => '#C97B5A', 'check' => '#FFFFFF'],
        ['label' => 'Coklat Kayu Tua', 'value' => '#3D2B1F', 'check' => '#FFFFFF'],
        ['label' => 'Navy Malam', 'value' => '#22303F', 'check' => '#FFFFFF'],
        ['label' => 'Hitam Elegan', 'value' => '#1A1A1A', 'check' => '#FFFFFF'],
    ];

'@ @'
    /**
     * Palet warna rekomendasi untuk: Kategori Produk.
     * SATU daftar yang sama untuk SEMUA pilihan warna di Edit Web -- ubah/tambah
     * warna di app/Support/ColorPalette.php, jangan bikin daftar sendiri di sini.
     *
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public array $kategoriBgPresets = \App\Support\ColorPalette::PRESETS;

'@ "kategoriBgPresets"
$f1Text = Replace-ExactlyOnce $f1Text @'
    /**
     * @var array<int, array{label: string, value: string}>
     */
    public array $testimoniBgPresets = [
        ['label' => 'Krem Kanvas (Default)', 'value' => '#FAF8F4', 'check' => '#3D2B1F'],
        ['label' => 'Putih Gading', 'value' => '#FFFDF8', 'check' => '#3D2B1F'],
        ['label' => 'Beige Pasir', 'value' => '#EFE3D0', 'check' => '#3D2B1F'],
        ['label' => 'Sage Hijau Lembut', 'value' => '#DCE3CE', 'check' => '#3D2B1F'],
        ['label' => 'Terracotta', 'value' => '#C97B5A', 'check' => '#FFFFFF'],
        ['label' => 'Coklat Kayu Tua', 'value' => '#3D2B1F', 'check' => '#FFFFFF'],
        ['label' => 'Navy Malam', 'value' => '#22303F', 'check' => '#FFFFFF'],
        ['label' => 'Hitam Elegan', 'value' => '#1A1A1A', 'check' => '#FFFFFF'],
    ];

'@ @'
    /**
     * Palet warna rekomendasi untuk: Testimoni Pelanggan (warna frame section).
     * SATU daftar yang sama untuk SEMUA pilihan warna di Edit Web -- ubah/tambah
     * warna di app/Support/ColorPalette.php, jangan bikin daftar sendiri di sini.
     *
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public array $testimoniBgPresets = \App\Support\ColorPalette::PRESETS;

'@ "testimoniBgPresets"
$f1Text = Replace-ExactlyOnce $f1Text @'
    /**
     * Preset pertama disesuaikan jadi coklat tua (warna kartu gelap bawaan
     * section ini), bukan krem, karena mayoritas kartu default-nya gelap.
     *
     * @var array<int, array{label: string, value: string}>
     */
    public array $testimoniCardPresets = [
        ['label' => 'Coklat Tua (Default)', 'value' => '#2A1B12', 'check' => '#FFFFFF'],
        ['label' => 'Krem Hangat', 'value' => '#F4EDE0', 'check' => '#3D2B1F'],
        ['label' => 'Putih', 'value' => '#FFFFFF', 'check' => '#3D2B1F'],
        ['label' => 'Sage Hijau Lembut', 'value' => '#DCE3CE', 'check' => '#3D2B1F'],
        ['label' => 'Terracotta', 'value' => '#C97B5A', 'check' => '#FFFFFF'],
        ['label' => 'Navy Malam', 'value' => '#22303F', 'check' => '#FFFFFF'],
        ['label' => 'Hitam Elegan', 'value' => '#1A1A1A', 'check' => '#FFFFFF'],
    ];

'@ @'
    /**
     * Palet warna rekomendasi untuk: Testimoni Pelanggan (warna kartu Top 1/2/3).
     * SATU daftar yang sama untuk SEMUA pilihan warna di Edit Web -- ubah/tambah
     * warna di app/Support/ColorPalette.php, jangan bikin daftar sendiri di sini.
     *
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public array $testimoniCardPresets = \App\Support\ColorPalette::PRESETS;

'@ "testimoniCardPresets"
$f1Text = Replace-ExactlyOnce $f1Text @'
    /**
     * Palet sama dengan $produkUnggulanBgPresets & $kategoriBgPresets --
     * preset pertama putih karena latar bawaan section Lokasi memang putih.
     *
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public array $lokasiBgPresets = [
        ['label' => 'Putih (Default)', 'value' => '#FFFFFF', 'check' => '#3D2B1F'],
        ['label' => 'Krem Hangat', 'value' => '#F9F7F2', 'check' => '#3D2B1F'],
        ['label' => 'Beige Pasir', 'value' => '#EFE3D0', 'check' => '#3D2B1F'],
        ['label' => 'Sage Hijau Lembut', 'value' => '#DCE3CE', 'check' => '#3D2B1F'],
        ['label' => 'Terracotta', 'value' => '#C97B5A', 'check' => '#FFFFFF'],
        ['label' => 'Coklat Kayu Tua', 'value' => '#3D2B1F', 'check' => '#FFFFFF'],
        ['label' => 'Navy Malam', 'value' => '#22303F', 'check' => '#FFFFFF'],
        ['label' => 'Hitam Elegan', 'value' => '#1A1A1A', 'check' => '#FFFFFF'],
    ];

'@ @'
    /**
     * Palet warna rekomendasi untuk: Lokasi.
     * SATU daftar yang sama untuk SEMUA pilihan warna di Edit Web -- ubah/tambah
     * warna di app/Support/ColorPalette.php, jangan bikin daftar sendiri di sini.
     *
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public array $lokasiBgPresets = \App\Support\ColorPalette::PRESETS;

'@ "lokasiBgPresets"
$pending += @{ Path = "resources/views/pages/admin/edit-web.blade.php"; Text = $f1Text; CrLf = $f1.CrLf; Existing = $true }

$pending += @{ Path = "app/Support/ColorPalette.php"; Existing = $false; CrLf = $false; Text = @'
<?php

namespace App\Support;

/**
 * Palet warna rekomendasi untuk SEMUA pilihan warna di Admin > Edit Web
 * (Header, Sejak Berdiri, Produk Unggulan, Kategori Produk, Testimoni
 * Pelanggan -- termasuk warna kartu Top 1/2/3 -- dan Lokasi).
 *
 * Satu daftar ini dipakai bersama, jadi pilihan warnanya SELALU sama di
 * setiap bagian. Mau menambah/mengubah warna? Cukup ubah di sini -- jangan
 * membuat daftar warna sendiri di halaman lain.
 *
 * 'value' = hex warna, 'check' = warna centang di atas swatch yang terpilih
 * (gelap untuk warna terang, putih untuk warna gelap).
 */
final class ColorPalette
{
    /**
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public const PRESETS = [
        ['label' => 'Krem Hangat', 'value' => '#F9F7F2', 'check' => '#3D2B1F'],
        ['label' => 'Putih Gading', 'value' => '#FFFDF8', 'check' => '#3D2B1F'],
        ['label' => 'Beige Pasir', 'value' => '#EFE3D0', 'check' => '#3D2B1F'],
        ['label' => 'Sage Hijau Lembut', 'value' => '#DCE3CE', 'check' => '#3D2B1F'],
        ['label' => 'Terracotta', 'value' => '#C97B5A', 'check' => '#FFFFFF'],
        ['label' => 'Coklat Kayu Tua', 'value' => '#3D2B1F', 'check' => '#FFFFFF'],
        ['label' => 'Navy Malam', 'value' => '#22303F', 'check' => '#FFFFFF'],
        ['label' => 'Hitam Elegan', 'value' => '#1A1A1A', 'check' => '#FFFFFF'],
    ];
}

'@ }

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupRoot = Join-Path $root ".backup-samakan-palet-$stamp"

foreach ($item in $pending) {
    if ($item.Existing) {
        $src = Join-Path $root $item.Path
        $dst = Join-Path $backupRoot $item.Path
        $dstDir = Split-Path $dst -Parent
        if (-not (Test-Path $dstDir)) { New-Item -ItemType Directory -Path $dstDir -Force | Out-Null }
        Copy-Item $src $dst
    }
}

foreach ($item in $pending) {
    Write-Lf $item.Path $item.Text $item.CrLf
    if ($item.Existing) { Write-Host ("  diubah : " + $item.Path) } else { Write-Host ("  baru   : " + $item.Path) }
}

Write-Host ""
Write-Host "Selesai. Backup file yang diubah: .backup-samakan-palet-$stamp"
Write-Host "Langkah berikutnya: php artisan view:clear  (npm run build TIDAK perlu)"
