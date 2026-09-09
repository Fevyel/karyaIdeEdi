# ============================================================
# FIX: Warna teks di dalam kartu produk (Beranda > "Semua Produk")
#      tidak menyesuaikan warna latar section "Produk Unggulan"
#      yang diatur admin di Edit Web -- akibatnya judul, deskripsi,
#      rating, dan harga jadi nyaris tak terlihat kalau warna latar
#      dipilih gelap.
#
# Penyebab: judul (#4B3A26), deskripsi/rating (admin-ink-soft),
# dan harga (#1A1A1A) di partials/frontend/product-card.blade.php
# hardcode -- cuma cocok kalau latar section-nya terang. Judul
# section & link "Lihat Semua Produk" di products.blade.php SUDAH
# otomatis menyesuaikan kontras ($contrastProdukColors), tapi
# kartu produk di dalamnya kelupaan ikut pola itu.
#
# Fix: $contrastProdukColors di products.blade.php ditambah key
# 'title' / 'body' / 'price', lalu dikirim ke product-card.blade.php
# lewat variabel baru $cardTextColors. Kalau product-card dipanggil
# tanpa $cardTextColors (dari tempat lain nanti), dia fallback ke
# warna aslinya sendiri -- untuk warna latar bawaan section ini
# (putih, belum pernah diganti admin) hasilnya PERSIS sama seperti
# sebelumnya, jadi tampilan default tidak berubah sama sekali.
#
# File ditulis pakai [System.IO.File]::WriteAllText dengan encoding
# UTF-8 TANPA BOM (sama seperti kondisi asli kedua file ini -- sudah
# dicek satu-satu).
#
# Cara pakai (dari VS Code integrated terminal, di root project):
#   .\apply-fix-warna-teks-kartu-produk.ps1
#
# Setelah itu langsung cek di browser (php artisan serve) --
# tidak perlu npm run build karena tidak ada class Tailwind baru
# yang sebelumnya belum pernah dipakai di project ini (cuma inline
# style="color: ...").
# ============================================================

$ErrorActionPreference = "Stop"

function Backup-File {
    param([string]$Path)
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$Path.bak-before-warna-teks-kartu-produk-$stamp"
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
Write-Host " Fix: Warna Teks Kartu Produk Ikut Kontras Latar" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

# ------------------------------------------------------------
# FILE 1: resources/views/partials/frontend/products.blade.php
# ------------------------------------------------------------
$productsPath = "resources\views\partials\frontend\products.blade.php"

Write-Host "[1/3] Menambah warna 'title'/'body'/'price' di helper contrastProdukColors ($productsPath) ..." -ForegroundColor Yellow

$productsOld1 = @'
    $contrastProdukColors = function (string $hex): array {
        $hex = ltrim($hex, '#');

        if (strtoupper($hex) === 'FFFFFF') {
            return ['heading' => '#4B3A26', 'link' => '#4B3A26'];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $linearize = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $linearize($r) + 0.7152 * $linearize($g) + 0.0722 * $linearize($b);

        return $luminance > 0.5
            ? ['heading' => '#1A1208', 'link' => '#1A1208']
            : ['heading' => '#FFFFFF', 'link' => '#FFFFFF'];
    };

    $produkUnggulanColors = $contrastProdukColors($produkUnggulanBgColor);
'@

$productsNew1 = @'
    // 'title'/'body'/'price' dipakai oleh partials/frontend/product-card.blade.php
    // (lewat $cardTextColors) supaya teks di dalam kartu produk -- judul,
    // deskripsi, rating, harga -- ikut menyesuaikan warna latar section ini,
    // sama seperti heading & link di atas. Sebelumnya kartu produk hardcode
    // warna coklat tua/hitam jadi nyaris tidak kelihatan kalau admin pilih
    // warna latar gelap.
    $contrastProdukColors = function (string $hex): array {
        $hex = ltrim($hex, '#');

        if (strtoupper($hex) === 'FFFFFF') {
            return [
                'heading' => '#4B3A26',
                'link' => '#4B3A26',
                // Sama persis dengan warna kartu produk bawaan sebelumnya
                // (judul #4B3A26, deskripsi admin-ink-soft ~#756A5D, harga
                // #1A1A1A) -- supaya tampilan default (belum diganti admin)
                // tidak berubah sama sekali.
                'title' => '#4B3A26',
                'body' => '#756A5D',
                'price' => '#1A1A1A',
            ];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $linearize = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $linearize($r) + 0.7152 * $linearize($g) + 0.0722 * $linearize($b);

        return $luminance > 0.5
            ? ['heading' => '#1A1208', 'link' => '#1A1208', 'title' => '#1A1208', 'body' => 'rgba(26, 18, 8, 0.72)', 'price' => '#1A1208']
            : ['heading' => '#FFFFFF', 'link' => '#FFFFFF', 'title' => '#FFFFFF', 'body' => 'rgba(255, 255, 255, 0.75)', 'price' => '#FFFFFF'];
    };

    $produkUnggulanColors = $contrastProdukColors($produkUnggulanBgColor);
'@

Replace-ExactlyOnce -Path $productsPath -Old $productsOld1 -New $productsNew1 -UseBom $false

Write-Host "[2/3] Mengirim warna kontras ke product-card lewat \$cardTextColors ..." -ForegroundColor Yellow

$productsOld2 = @'
                    @include('partials.frontend.product-card', ['product' => $product])
'@

$productsNew2 = @'
                    @include('partials.frontend.product-card', ['product' => $product, 'cardTextColors' => $produkUnggulanColors])
'@

Replace-ExactlyOnce -Path $productsPath -Old $productsOld2 -New $productsNew2 -UseBom $false

# ------------------------------------------------------------
# FILE 2: resources/views/partials/frontend/product-card.blade.php
# ------------------------------------------------------------
$cardPath = "resources\views\partials\frontend\product-card.blade.php"

Write-Host "[3/3] Mengganti warna hardcode judul/deskripsi/rating/harga jadi ikut kontras ($cardPath) ..." -ForegroundColor Yellow

$cardOld1 = @'
@php
    $hasDiscount = $product->harga_diskon && (float) $product->harga_diskon > 0;
'@

$cardNew1 = @'
@php
    // Warna teks kartu (judul, deskripsi/rating, harga) bisa dikirim dari
    // halaman pemanggil lewat $cardTextColors -- dipakai di Beranda > Semua
    // Produk (partials/frontend/products.blade.php) supaya ikut kontras
    // warna latar section "Produk Unggulan" yang diatur admin di Edit Web.
    // Kalau tidak dikirim (dipakai di tempat lain nanti), fallback ke warna
    // asli kartu ini -- sama persis seperti sebelum bisa diedit.
    $cardTextColors = $cardTextColors ?? [];
    $cardTitleColor = $cardTextColors['title'] ?? '#4B3A26';
    $cardBodyColor = $cardTextColors['body'] ?? '#756A5D';
    $cardPriceColor = $cardTextColors['price'] ?? '#1A1A1A';

    $hasDiscount = $product->harga_diskon && (float) $product->harga_diskon > 0;
'@

Replace-ExactlyOnce -Path $cardPath -Old $cardOld1 -New $cardNew1 -UseBom $false

$cardOld2 = @'
        <p class="text-base font-semibold text-[#4B3A26]">{{ $product->nama }}</p>
        <p class="mt-0.5 text-sm text-admin-ink-soft">{{ $product->deskripsi_pendek }}</p>
'@

$cardNew2 = @'
        <p class="text-base font-semibold" style="color: {{ $cardTitleColor }};">{{ $product->nama }}</p>
        <p class="mt-0.5 text-sm" style="color: {{ $cardBodyColor }};">{{ $product->deskripsi_pendek }}</p>
'@

Replace-ExactlyOnce -Path $cardPath -Old $cardOld2 -New $cardNew2 -UseBom $false

$cardOld3 = @'
                <span class="text-xs text-admin-ink-soft">
                    {{ number_format($averageRating, 1) }} ({{ $reviewCount }})
                </span>
            @else
                <div class="flex items-center gap-0.5 text-admin-ink-soft/25">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="fa-regular fa-star text-xs"></i>
                    @endfor
                </div>
                <span class="text-xs text-admin-ink-soft/70">Belum ada ulasan</span>
'@

$cardNew3 = @'
                <span class="text-xs" style="color: {{ $cardBodyColor }};">
                    {{ number_format($averageRating, 1) }} ({{ $reviewCount }})
                </span>
            @else
                <div class="flex items-center gap-0.5 text-admin-ink-soft/25">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="fa-regular fa-star text-xs"></i>
                    @endfor
                </div>
                <span class="text-xs" style="color: {{ $cardBodyColor }}; opacity: 0.7;">Belum ada ulasan</span>
'@

Replace-ExactlyOnce -Path $cardPath -Old $cardOld3 -New $cardNew3 -UseBom $false

$cardOld4 = @'
                <span class="text-sm text-admin-ink-soft/70 line-through">
                    Rp{{ number_format((float) $product->harga, 0, ',', '.') }}
                </span>
            @else
                <span class="text-base font-semibold text-[#1A1A1A]">
                    Rp{{ number_format((float) $product->harga, 0, ',', '.') }}
                </span>
            @endif
'@

$cardNew4 = @'
                <span class="text-sm line-through" style="color: {{ $cardBodyColor }}; opacity: 0.7;">
                    Rp{{ number_format((float) $product->harga, 0, ',', '.') }}
                </span>
            @else
                <span class="text-base font-semibold" style="color: {{ $cardPriceColor }};">
                    Rp{{ number_format((float) $product->harga, 0, ',', '.') }}
                </span>
            @endif
'@

Replace-ExactlyOnce -Path $cardPath -Old $cardOld4 -New $cardNew4 -UseBom $false

Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Selesai. Cek di browser (php artisan serve):" -ForegroundColor Cyan
Write-Host " Beranda -> ganti warna latar 'Produk Unggulan' di" -ForegroundColor Cyan
Write-Host " Admin > Edit Web, lalu lihat kartu produk di section" -ForegroundColor Cyan
Write-Host " 'Semua Produk' -- judul/deskripsi/rating/harga" -ForegroundColor Cyan
Write-Host " sekarang ikut menyesuaikan warna latarnya." -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
