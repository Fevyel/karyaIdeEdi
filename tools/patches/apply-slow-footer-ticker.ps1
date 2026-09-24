$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script ini dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$footer = ".\resources\views\partials\frontend\footer.blade.php"

if (-not (Test-Path $footer)) {
    throw "Footer tidak ditemukan: $footer"
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backup = "$footer.bak-slow-footer-ticker-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "=============================================================" -ForegroundColor Yellow
Write-Host " Footer - Perlambat Pita Karya Ide Edi" -ForegroundColor Yellow
Write-Host "=============================================================" -ForegroundColor Yellow

Step "[1/4] Membuat backup ..."
Copy-Item $footer $backup -Force
Write-Host "  Backup: $backup" -ForegroundColor DarkGray

Step "[2/4] Memperlambat animasi horizontal footer ..."

$phpPatch = @'
<?php

$path = $argv[1];
$content = file_get_contents($path);

if ($content === false) {
    throw new RuntimeException('Gagal membaca footer.');
}

$original = $content;
$changed = 0;
$targetDuration = '90s';

/*
|--------------------------------------------------------------------------
| Cari keyframes horizontal di footer lalu perlambat pemakaiannya.
|--------------------------------------------------------------------------
*/
preg_match_all(
    '~@keyframes\s+([A-Za-z0-9_-]+)\s*\{(.*?)\}~si',
    $content,
    $keyframes,
    PREG_SET_ORDER
);

$horizontalAnimations = [];

foreach ($keyframes as $frame) {
    $name = $frame[1];
    $body = $frame[2];

    if (
        stripos($body, 'translateX') !== false ||
        stripos($body, 'translate3d') !== false
    ) {
        $horizontalAnimations[] = $name;
    }
}

foreach ($horizontalAnimations as $name) {
    // animation: nama 20s linear infinite
    $pattern = '~(animation\s*:\s*'.preg_quote($name, '~').'\s+)(\d+(?:\.\d+)?s)(\s+linear\s+infinite)~i';
    $content = preg_replace_callback(
        $pattern,
        function ($m) use ($targetDuration, &$changed) {
            $changed++;
            return $m[1].$targetDuration.$m[3];
        },
        $content
    );

    // animation-duration khusus selector yang memakai keyframe ini.
    // Tidak disentuh bila tidak terkait keyframe horizontal.
    $selectorPattern = '~([^{}]+)\{([^{}]*animation\s*:\s*'.preg_quote($name, '~').'[^{}]*)\}~i';
    $content = preg_replace_callback(
        $selectorPattern,
        function ($m) use ($targetDuration, &$changed) {
            $body = $m[2];

            if (preg_match('~animation-duration\s*:\s*\d+(?:\.\d+)?s~i', $body)) {
                $body = preg_replace(
                    '~animation-duration\s*:\s*\d+(?:\.\d+)?s~i',
                    'animation-duration:'.$targetDuration,
                    $body,
                    1,
                    $count
                );
                $changed += $count;
            }

            return $m[1].'{'.$body.'}';
        },
        $content
    );
}

/*
|--------------------------------------------------------------------------
| Fallback untuk Tailwind arbitrary animation:
| animate-[ticker_25s_linear_infinite]
|--------------------------------------------------------------------------
*/
$content = preg_replace_callback(
    '~animate-\[([A-Za-z0-9_-]*(?:ticker|marquee|scroll)[A-Za-z0-9_-]*)_(\d+(?:\.\d+)?)s_linear_infinite\]~i',
    function ($m) use (&$changed) {
        $changed++;
        return 'animate-['.$m[1].'_90s_linear_infinite]';
    },
    $content
);

/*
|--------------------------------------------------------------------------
| Fallback untuk class semantic di footer.
| Hanya dipakai jika shorthand animation tidak ditemukan.
|--------------------------------------------------------------------------
*/
if ($changed === 0) {
    preg_match_all(
        '~class\s*=\s*["\']([^"\']*(?:ticker|marquee|running|scroll)[^"\']*)["\']~i',
        $content,
        $classMatches
    );

    $classes = [];

    foreach ($classMatches[1] ?? [] as $classList) {
        foreach (preg_split('/\s+/', trim($classList)) as $class) {
            if (
                $class !== '' &&
                preg_match('~(?:ticker|marquee|running|scroll)~i', $class) &&
                !str_contains($class, ':') &&
                !str_contains($class, '[')
            ) {
                $classes[$class] = true;
            }
        }
    }

    if ($classes) {
        $selectors = implode(',', array_map(
            fn ($class) => '.'.preg_replace('/[^A-Za-z0-9_-]/', '', $class),
            array_keys($classes)
        ));

        $override = "\n<style>\n/* KIE: footer ticker diperlambat */\n"
            .$selectors."{animation-duration:90s!important;}\n"
            ."</style>\n";

        if (stripos($content, '</footer>') !== false) {
            $content = preg_replace('~</footer>~i', $override.'</footer>', $content, 1);
        } else {
            $content .= $override;
        }

        $changed++;
    }
}

if ($changed === 0) {
    throw new RuntimeException(
        'Animasi ticker horizontal tidak ditemukan secara aman. Tidak ada perubahan dilakukan.'
    );
}

if (file_put_contents($path, $content) === false) {
    throw new RuntimeException('Gagal menulis footer.');
}

echo "Perubahan animasi: {$changed}\n";
echo "Durasi baru: {$targetDuration}\n";
'@

$tmp = Join-Path $env:TEMP "slow-footer-ticker-$stamp.php"
[System.IO.File]::WriteAllText($tmp, $phpPatch, $utf8NoBom)

php $tmp (Resolve-Path $footer)
$exitCode = $LASTEXITCODE

Remove-Item $tmp -Force -ErrorAction SilentlyContinue

if ($exitCode -ne 0) {
    throw "Patch gagal. Backup: $backup"
}

Step "[3/4] Validasi syntax ..."
php -l $footer | Out-Host

if ($LASTEXITCODE -ne 0) {
    throw "Syntax footer bermasalah. Restore dari: $backup"
}

Step "[4/4] Bersihkan cache view ..."
php artisan view:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host "Pita FURNITUR TOKO MEBEL / KARYA IDE-EDI sekarang bergerak lebih pelan." -ForegroundColor White
Write-Host "Durasi animasi ditetapkan menjadi 90 detik." -ForegroundColor White
Write-Host "Tidak ada bagian lain yang diubah." -ForegroundColor DarkGray
