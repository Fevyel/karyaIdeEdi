# ============================================================
# FIX 1: Tombol "Jelajahi Profil" di Hero (Beranda) hilang/nyaris
#        tak terlihat kalau admin pilih warna latar Header yang
#        gelap (Coklat Kayu Tua, Navy Malam, Hitam Elegan, dst).
#
# Penyebab: teks & border tombol itu hardcode warna coklat tua
# (#3D2B1F) dan abu muda (#DCDDD7) -- warna itu cuma cocok kalau
# latarnya terang. Judul/deskripsi/statistik di section yang sama
# SUDAH otomatis menyesuaikan kontras ($contrastTextColors), tapi
# tombol "Jelajahi Profil" kelupaan ikut pola itu.
#
# Fix: tombol sekarang ikut $heroHeadingColor (teks) & warna border
# baru $heroBorderColor yang juga dihitung dari kontras latar --
# untuk warna latar bawaan (#F9F7F2, belum pernah diganti admin),
# hasilnya PERSIS sama seperti sebelumnya (#3D2B1F / #DCDDD7),
# jadi tampilan default tidak berubah sama sekali.
#
# FIX 2: Swatch preset warna di Admin > Edit Web > Header (8
#        lingkaran warna: Krem Hangat, Putih Gading, dst) sekarang
#        dikasih gradien halus + kilau tipis (pola color-mix yang
#        sama seperti dipakai di resources/css/app.css untuk
#        scrollbar), supaya tidak flat/polos satu warna rata.
#        Warna & urutan preset TIDAK berubah, cuma tampilannya.
#
# File ditulis pakai [System.IO.File]::WriteAllText dengan encoding
# yang SAMA seperti file aslinya (hero.blade.php: UTF-8 tanpa BOM,
# edit-web.blade.php: UTF-8 dengan BOM -- sudah dicek satu-satu,
# TIDAK memakai Set-Content -Encoding UTF8 supaya BOM tidak
# tertukar/berubah dari kondisi aslinya).
#
# Cara pakai (dari VS Code integrated terminal, di root project):
#   .\apply-fix-hero-button-dan-swatch-warna.ps1
#
# Setelah itu langsung cek di browser (php artisan serve) --
# tidak perlu npm run build karena tidak ada class Tailwind baru
# yang sebelumnya belum pernah dipakai di project ini.
# ============================================================

$ErrorActionPreference = "Stop"

function Backup-File {
    param([string]$Path)
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$Path.bak-before-hero-button-swatch-fix-$stamp"
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

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Fix: Tombol Jelajahi Profil + Swatch Warna" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

# ------------------------------------------------------------
# FILE 1: resources/views/partials/frontend/hero.blade.php
# ------------------------------------------------------------
$heroPath = "resources\views\partials\frontend\hero.blade.php"
Write-Host "[1/3] Menambahkan warna border kontras (helper contrastTextColors) di $heroPath ..." -ForegroundColor Yellow

$heroOld1 = @'
        if (strtoupper($hex) === 'F9F7F2') {
            return [
                'heading' => '#3D2B1F',
                'body' => '#6B6E76',
                'divider' => 'rgba(61, 43, 31, 0.1)',
            ];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $linearize = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $linearize($r) + 0.7152 * $linearize($g) + 0.0722 * $linearize($b);

        return $luminance > 0.5
            ? ['heading' => '#1A1208', 'body' => 'rgba(26, 18, 8, 0.72)', 'divider' => 'rgba(26, 18, 8, 0.12)']
            : ['heading' => '#FFFFFF', 'body' => 'rgba(255, 255, 255, 0.78)', 'divider' => 'rgba(255, 255, 255, 0.18)'];
    };

    $heroTextColors = $contrastTextColors($heroBgColor);
    $heroHeadingColor = $heroTextColors['heading'];
    $heroTextColor = $heroTextColors['body'];
    $heroDividerColor = $heroTextColors['divider'];
'@

$heroNew1 = @'
        if (strtoupper($hex) === 'F9F7F2') {
            return [
                'heading' => '#3D2B1F',
                'body' => '#6B6E76',
                'divider' => 'rgba(61, 43, 31, 0.1)',
                // Sama persis dengan border tombol "Jelajahi Profil" bawaan
                // sebelumnya (#DCDDD7) -- supaya tampilan default tidak berubah.
                'border' => '#DCDDD7',
            ];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $linearize = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $linearize($r) + 0.7152 * $linearize($g) + 0.0722 * $linearize($b);

        return $luminance > 0.5
            ? ['heading' => '#1A1208', 'body' => 'rgba(26, 18, 8, 0.72)', 'divider' => 'rgba(26, 18, 8, 0.12)', 'border' => 'rgba(26, 18, 8, 0.22)']
            : ['heading' => '#FFFFFF', 'body' => 'rgba(255, 255, 255, 0.78)', 'divider' => 'rgba(255, 255, 255, 0.18)', 'border' => 'rgba(255, 255, 255, 0.35)'];
    };

    $heroTextColors = $contrastTextColors($heroBgColor);
    $heroHeadingColor = $heroTextColors['heading'];
    $heroTextColor = $heroTextColors['body'];
    $heroDividerColor = $heroTextColors['divider'];
    // Border tombol outline "Jelajahi Profil" -- dulu hardcode #DCDDD7 sehingga
    // teks & garisnya "hilang" (nyaris tak terlihat) kalau admin pilih warna
    // latar gelap (mis. Coklat Kayu Tua / Navy Malam / Hitam Elegan), karena
    // teks tombol tetap coklat tua di atas latar gelap. Sekarang ikut kontras.
    $heroBorderColor = $heroTextColors['border'];
'@

Replace-ExactlyOnce -Path $heroPath -Old $heroOld1 -New $heroNew1 -UseBom $false

Write-Host "[2/3] Mengganti tombol Jelajahi Profil supaya ikut warna kontras ..." -ForegroundColor Yellow

$heroOld2 = @'
                <a
                    href="{{ route('profile.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-[#DCDDD7] px-5 py-2.5 text-sm font-medium text-[#3D2B1F] transition-all duration-300 hover:border-admin-accent hover:text-admin-accent"
                >
                    Jelajahi Profil
                </a>
'@

$heroNew2 = @'
                <a
                    href="{{ route('profile.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg border px-5 py-2.5 text-sm font-medium transition-all duration-300 hover:!border-admin-accent hover:!text-admin-accent"
                    style="color: {{ $heroHeadingColor }}; border-color: {{ $heroBorderColor }};"
                >
                    Jelajahi Profil
                </a>
'@

Replace-ExactlyOnce -Path $heroPath -Old $heroOld2 -New $heroNew2 -UseBom $false

# ------------------------------------------------------------
# FILE 2: resources/views/pages/admin/edit-web.blade.php
# ------------------------------------------------------------
$editWebPath = "resources\views\pages\admin\edit-web.blade.php"
Write-Host "[3/3] Mengganti swatch preset warna (Admin > Edit Web > Header) jadi bergradien ..." -ForegroundColor Yellow

$editWebOld = @'
                                @foreach ($headerBgPresets as $preset)
                                    <button
                                        type="button"
                                        wire:click="selectHeaderBgPreset('{{ $preset['value'] }}')"
                                        title="{{ $preset['label'] }}"
                                        class="group flex flex-col items-center gap-1"
                                    >
                                        <span
                                            class="flex h-9 w-9 items-center justify-center rounded-full border transition
                                            {{ $headerUseCustomBg && strtoupper($headerBgColor) === $preset['value']
                                                ? 'border-admin-accent ring-2 ring-admin-accent ring-offset-2 ring-offset-admin-surface'
                                                : 'border-admin-border group-hover:border-admin-accent/60' }}"
                                            style="background-color: {{ $preset['value'] }};"
                                        >
                                            @if ($headerUseCustomBg && strtoupper($headerBgColor) === $preset['value'])
                                                <i class="fa-solid fa-check text-xs" style="color: {{ $preset['check'] }};"></i>
                                            @endif
                                        </span>
                                        <span class="max-w-14 truncate text-[10px] text-admin-ink-soft">{{ $preset['label'] }}</span>
                                    </button>
                                @endforeach
'@

$editWebNew = @'
                                @foreach ($headerBgPresets as $preset)
                                    <button
                                        type="button"
                                        wire:click="selectHeaderBgPreset('{{ $preset['value'] }}')"
                                        title="{{ $preset['label'] }}"
                                        class="group flex flex-col items-center gap-1"
                                    >
                                        {{--
                                            Swatch dikasih gradien halus (color-mix, pola yang sama
                                            dipakai di resources/css/app.css untuk scrollbar) +
                                            ring highlight tipis supaya ada dimensi/kilau, tidak
                                            flat/polos satu warna rata seperti sebelumnya.
                                        --}}
                                        <span
                                            class="flex h-9 w-9 items-center justify-center rounded-full shadow-sm ring-1 ring-inset ring-white/40 transition duration-200 group-hover:scale-110 group-hover:shadow-md
                                            {{ $headerUseCustomBg && strtoupper($headerBgColor) === $preset['value']
                                                ? 'border-2 border-admin-accent ring-2 ring-admin-accent ring-offset-2 ring-offset-admin-surface'
                                                : 'border border-admin-border group-hover:border-admin-accent/60' }}"
                                            style="background: linear-gradient(135deg, color-mix(in oklab, {{ $preset['value'] }} 100%, white 30%), {{ $preset['value'] }} 55%, color-mix(in oklab, {{ $preset['value'] }} 100%, black 16%));"
                                        >
                                            @if ($headerUseCustomBg && strtoupper($headerBgColor) === $preset['value'])
                                                <i class="fa-solid fa-check text-xs drop-shadow-sm" style="color: {{ $preset['check'] }};"></i>
                                            @endif
                                        </span>
                                        <span class="max-w-14 truncate text-[10px] text-admin-ink-soft">{{ $preset['label'] }}</span>
                                    </button>
                                @endforeach
'@

Replace-ExactlyOnce -Path $editWebPath -Old $editWebOld -New $editWebNew -UseBom $true

Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Selesai. Semua backup .bak-before-hero-button-swatch-fix-* dibuat di lokasi file aslinya." -ForegroundColor Cyan
Write-Host " Langsung dicek di browser (php artisan serve), tidak perlu npm run build." -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
