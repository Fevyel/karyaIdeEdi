$ErrorActionPreference = 'Stop'

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$file = ".\resources\views\pages\admin\pengaturan.blade.php"

if (-not (Test-Path $file)) {
    throw "File tidak ditemukan: $file"
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backup = "$file.bak-payment-true-full-width-v2-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "==================================================================" -ForegroundColor Yellow
Write-Host " Pengaturan - Rekening Pembayaran TRUE FULL WIDTH v2" -ForegroundColor Yellow
Write-Host "==================================================================" -ForegroundColor Yellow

Write-Host "`n[1/4] Backup ..." -ForegroundColor Cyan
Copy-Item $file $backup -Force
Write-Host "  Backup: $backup" -ForegroundColor DarkGray

Write-Host "`n[2/4] Paksa Rekening Pembayaran memakai lebar viewport penuh ..." -ForegroundColor Cyan

$phpPatch = @'
<?php

$path = $argv[1];
$text = file_get_contents($path);

if ($text === false) {
    throw new RuntimeException('Gagal membaca pengaturan.blade.php');
}

$comment = '{{-- ================= SECTION 3: REKENING PEMBAYARAN ================= --}}';
$commentPos = strpos($text, $comment);

if ($commentPos === false) {
    throw new RuntimeException('Section Rekening Pembayaran tidak ditemukan.');
}

/*
|--------------------------------------------------------------------------
| Pastikan outer section punya ID yang stabil.
|--------------------------------------------------------------------------
*/
$divPos = strpos($text, '<div', $commentPos);
$tagEnd = $divPos !== false ? strpos($text, '>', $divPos) : false;

if ($divPos === false || $tagEnd === false) {
    throw new RuntimeException('Container Rekening Pembayaran tidak ditemukan.');
}

$tag = substr($text, $divPos, $tagEnd - $divPos + 1);

if (! str_contains($tag, 'id="rekening-pembayaran-fullwidth"')) {
    $tag = substr($tag, 0, -1).' id="rekening-pembayaran-fullwidth">';

    $text = substr($text, 0, $divPos)
        .$tag
        .substr($text, $tagEnd + 1);
}

/*
|--------------------------------------------------------------------------
| Hapus style + JS patch lama.
|--------------------------------------------------------------------------
*/
$text = preg_replace(
    '/\s*<style id="rekening-pembayaran-fullwidth-style">.*?<\/style>\s*/is',
    "\n",
    $text
);

$text = preg_replace(
    '/\s*<script id="rekening-pembayaran-fullwidth-script">.*?<\/script>\s*/is',
    "\n",
    $text
);

/*
|--------------------------------------------------------------------------
| Cari inner grid lalu beri ID. Ini tidak mengubah field/data.
|--------------------------------------------------------------------------
*/
$commentPos = strpos($text, $comment);
$area = substr($text, $commentPos, min(18000, strlen($text) - $commentPos));

$gridPattern = '/<div\s+class="([^"]*grid[^"]*grid-cols-1[^"]*(?:md:grid-cols-3|lg:grid-cols-3)[^"]*)"([^>]*)>/i';

if (! preg_match($gridPattern, $area, $m, PREG_OFFSET_CAPTURE)) {
    throw new RuntimeException('Grid BCA/BRI/DANA tidak ditemukan.');
}

$oldGridTag = $m[0][0];
$gridPos = $commentPos + $m[0][1];

$newGridTag = preg_replace('/\s+id="rekening-pembayaran-grid"/i', '', $oldGridTag);
$newGridTag = substr($newGridTag, 0, -1).' id="rekening-pembayaran-grid">';

$text = substr($text, 0, $gridPos)
    .$newGridTag
    .substr($text, $gridPos + strlen($oldGridTag));

/*
|--------------------------------------------------------------------------
| TRUE FULL WIDTH:
| Desktop: 100vw dikurangi margin kiri+kanan 20px.
| Ini sengaja TIDAK mengikuti lebar parent yang sempit.
|--------------------------------------------------------------------------
*/
$css = <<<'BLADE'

<style id="rekening-pembayaran-fullwidth-style">
    #rekening-pembayaran-fullwidth {
        box-sizing: border-box !important;
        width: calc(100vw - 40px) !important;
        max-width: calc(100vw - 40px) !important;
        min-width: 0 !important;
        position: relative !important;
    }

    #rekening-pembayaran-grid {
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        gap: 1.25rem !important;
        width: 100% !important;
        max-width: none !important;
        min-width: 0 !important;
    }

    #rekening-pembayaran-grid > div {
        width: 100% !important;
        min-width: 0 !important;
        box-sizing: border-box !important;
    }

    @media (max-width: 1023px) {
        #rekening-pembayaran-fullwidth {
            width: 100% !important;
            max-width: 100% !important;
        }
    }

    @media (max-width: 767px) {
        #rekening-pembayaran-grid {
            grid-template-columns: 1fr !important;
        }
    }

    @media (min-width: 768px) and (max-width: 1023px) {
        #rekening-pembayaran-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }
</style>
BLADE;

/*
|--------------------------------------------------------------------------
| Sisipkan CSS tepat setelah section payment.
|--------------------------------------------------------------------------
*/
$commentPos = strpos($text, $comment);
$outerDivPos = strpos($text, '<div', $commentPos);

preg_match_all(
    '~<div\b[^>]*>|</div>~i',
    substr($text, $outerDivPos),
    $tags,
    PREG_OFFSET_CAPTURE
);

$depth = 0;
$outerEnd = null;

foreach ($tags[0] as [$divTag, $relative]) {
    if (stripos($divTag, '</div') === 0) {
        $depth--;

        if ($depth === 0) {
            $outerEnd = $outerDivPos + $relative + strlen($divTag);
            break;
        }
    } else {
        $depth++;
    }
}

if ($outerEnd === null) {
    throw new RuntimeException('Penutup section Rekening Pembayaran tidak ditemukan.');
}

$text = substr($text, 0, $outerEnd)
    .$css
    .substr($text, $outerEnd);

if (file_put_contents($path, $text) === false) {
    throw new RuntimeException('Gagal menulis pengaturan.blade.php');
}

echo "PAYMENT_TRUE_FULL_WIDTH_V2_OK\n";
'@

$tmp = Join-Path $env:TEMP "payment-true-full-width-v2-$stamp.php"
[System.IO.File]::WriteAllText($tmp, $phpPatch, $utf8NoBom)

php $tmp (Resolve-Path $file)
$exitCode = $LASTEXITCODE

Remove-Item $tmp -Force -ErrorAction SilentlyContinue

if ($exitCode -ne 0) {
    throw "Patch gagal. Backup: $backup"
}

Write-Host "`n[3/4] Validasi ..." -ForegroundColor Cyan
php -l $file | Out-Host

if ($LASTEXITCODE -ne 0) {
    throw "Syntax Blade bermasalah. Backup: $backup"
}

Write-Host "`n[4/4] Clear cache ..." -ForegroundColor Cyan
php artisan optimize:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host ""
Write-Host "Desktop: lebar payment = calc(100vw - 40px)." -ForegroundColor White
Write-Host "Artinya card memakai hampir seluruh lebar layar, dengan margin 20px kiri-kanan." -ForegroundColor White
Write-Host "BCA / BRI / DANA tetap 3 kolom penuh." -ForegroundColor White
Write-Host ""
Write-Host "Data rekening, penyimpanan, route, dan halaman transfer TIDAK diubah." -ForegroundColor DarkGray
Write-Host "Backup: $backup" -ForegroundColor DarkGray
