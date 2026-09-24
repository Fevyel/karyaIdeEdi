$ErrorActionPreference = 'Stop'

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$file = ".\resources\views\pages\admin\pengaturan.blade.php"

if (-not (Test-Path $file)) {
    throw "File tidak ditemukan: $file"
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backup = "$file.bak-payment-force-full-width-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "==================================================================" -ForegroundColor Yellow
Write-Host " Pengaturan - FORCE Rekening Pembayaran Full Width" -ForegroundColor Yellow
Write-Host "==================================================================" -ForegroundColor Yellow

Write-Host "`n[1/4] Backup ..." -ForegroundColor Cyan
Copy-Item $file $backup -Force
Write-Host "  Backup: $backup" -ForegroundColor DarkGray

Write-Host "`n[2/4] Buat Rekening Pembayaran melebar sampai sisi kanan area admin ..." -ForegroundColor Cyan

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
| 1. Tandai OUTER payment card dengan ID stabil.
|--------------------------------------------------------------------------
*/
$outerDivPos = strpos($text, '<div', $commentPos);

if ($outerDivPos === false) {
    throw new RuntimeException('Container Rekening Pembayaran tidak ditemukan.');
}

$outerTagEnd = strpos($text, '>', $outerDivPos);

if ($outerTagEnd === false) {
    throw new RuntimeException('Tag pembuka Rekening Pembayaran tidak valid.');
}

$outerTag = substr($text, $outerDivPos, $outerTagEnd - $outerDivPos + 1);

/* Bersihkan patch style/class full-width sebelumnya agar hasil deterministic. */
$outerTag = preg_replace('/\s+id="rekening-pembayaran-fullwidth"/i', '', $outerTag);
$outerTag = preg_replace('/\s+data-payment-fullwidth="1"/i', '', $outerTag);
$outerTag = preg_replace('/\s+style="[^"]*grid-column:[^"]*"/i', '', $outerTag);

if (preg_match('/\sclass="([^"]*)"/i', $outerTag, $classMatch)) {
    $classes = preg_split('/\s+/', trim($classMatch[1])) ?: [];
    $classes = array_values(array_filter(
        $classes,
        fn (string $class): bool => ! in_array($class, ['col-span-full', 'w-full'], true)
    ));
    $newClass = implode(' ', $classes);
    $outerTag = preg_replace(
        '/\sclass="[^"]*"/i',
        ' class="'.$newClass.'"',
        $outerTag,
        1
    );
}

$outerTag = substr($outerTag, 0, -1)
    .' id="rekening-pembayaran-fullwidth" data-payment-fullwidth="1"'
    .' style="width:100%;max-width:none;min-width:0;overflow:visible;">';

$text = substr($text, 0, $outerDivPos)
    .$outerTag
    .substr($text, $outerTagEnd + 1);

/*
|--------------------------------------------------------------------------
| 2. Paksa inner BCA / BRI / DANA benar-benar membagi lebar menjadi 3.
|    Tidak bergantung pada Tailwind build.
|--------------------------------------------------------------------------
*/
$commentPos = strpos($text, $comment);
$searchArea = substr($text, $commentPos, min(18000, strlen($text) - $commentPos));

$gridPattern = '/<div\s+class="([^"]*grid[^"]*grid-cols-1[^"]*(?:md:grid-cols-3|lg:grid-cols-3)[^"]*)"([^>]*)>/i';

if (! preg_match($gridPattern, $searchArea, $m, PREG_OFFSET_CAPTURE)) {
    throw new RuntimeException('Grid BCA/BRI/DANA tidak ditemukan.');
}

$gridTag = $m[0][0];
$gridRelative = $m[0][1];
$gridAbsolute = $commentPos + $gridRelative;

$gridTag = preg_replace('/\s+id="rekening-pembayaran-grid"/i', '', $gridTag);
$gridTag = preg_replace('/\s+style="[^"]*"/i', '', $gridTag);

$gridTag = substr($gridTag, 0, -1)
    .' id="rekening-pembayaran-grid"'
    .' style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.25rem;width:100%;min-width:0;">';

$text = substr($text, 0, $gridAbsolute)
    .$gridTag
    .substr($text, $gridAbsolute + strlen($m[0][0]));

/*
|--------------------------------------------------------------------------
| 3. Tambahkan CSS + JS scoped.
|
| Kenapa JS?
| Payment section ternyata berada di dalam parent yang sempit.
| Jadi width:100% hanya berarti 100% dari parent kecil itu.
| Script ini menghitung ruang nyata dari posisi kiri payment sampai sisi kanan
| viewport admin, lalu menetapkan width pixel yang benar.
|--------------------------------------------------------------------------
*/
$styleScript = <<<'BLADE'

<style id="rekening-pembayaran-fullwidth-style">
    #rekening-pembayaran-fullwidth {
        box-sizing: border-box;
    }

    #rekening-pembayaran-fullwidth > #rekening-pembayaran-grid {
        width: 100% !important;
    }

    @media (max-width: 767px) {
        #rekening-pembayaran-fullwidth {
            width: 100% !important;
        }

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

<script id="rekening-pembayaran-fullwidth-script">
(() => {
    if (window.__rekeningPembayaranFullwidthInstalled) {
        window.__fitRekeningPembayaranFullwidth?.();
        return;
    }

    window.__rekeningPembayaranFullwidthInstalled = true;

    const fit = () => {
        const el = document.getElementById('rekening-pembayaran-fullwidth');
        if (!el) return;

        if (window.innerWidth < 1024) {
            el.style.width = '100%';
            return;
        }

        const rect = el.getBoundingClientRect();
        const rightPadding = 40;
        const available = Math.floor(window.innerWidth - rect.left - rightPadding);

        if (available > 0) {
            el.style.setProperty('width', available + 'px', 'important');
            el.style.setProperty('max-width', available + 'px', 'important');
        }
    };

    window.__fitRekeningPembayaranFullwidth = fit;

    const run = () => {
        requestAnimationFrame(() => {
            fit();
            setTimeout(fit, 80);
            setTimeout(fit, 250);
        });
    };

    window.addEventListener('resize', run, { passive: true });
    document.addEventListener('DOMContentLoaded', run);
    document.addEventListener('livewire:navigated', run);
    document.addEventListener('livewire:initialized', run);

    run();
})();
</script>
BLADE;

/* Hapus style/script versi patch ini jika pernah ada, agar idempotent. */
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
| Taruh tepat setelah section payment.
| Temukan akhir outer div dengan balancing <div>.
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

foreach ($tags[0] as [$tag, $relative]) {
    if (stripos($tag, '</div') === 0) {
        $depth--;

        if ($depth === 0) {
            $outerEnd = $outerDivPos + $relative + strlen($tag);
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
    .$styleScript
    .substr($text, $outerEnd);

if (file_put_contents($path, $text) === false) {
    throw new RuntimeException('Gagal menulis pengaturan.blade.php');
}

echo "PAYMENT_FORCE_FULL_WIDTH_OK\n";
'@

$tmp = Join-Path $env:TEMP "payment-force-full-width-$stamp.php"
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
Write-Host "Sekarang section payment tidak lagi mengikuti lebar parent sempit." -ForegroundColor White
Write-Host "Lebarnya dihitung dari posisi kiri section sampai sisi kanan area browser." -ForegroundColor White
Write-Host "BCA / BRI / DANA dibagi 3 kolom penuh pada desktop." -ForegroundColor White
Write-Host ""
Write-Host "Data rekening, fungsi simpan, route, dan halaman transfer TIDAK diubah." -ForegroundColor DarkGray
Write-Host "Backup: $backup" -ForegroundColor DarkGray
