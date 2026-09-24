$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script ini dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = ".backup-slow-running-strip-v2-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "==============================================================" -ForegroundColor Yellow
Write-Host " Perlambat Pita FURNITUR TOKO MEBEL / KARYA IDE-EDI (v2)" -ForegroundColor Yellow
Write-Host "==============================================================" -ForegroundColor Yellow

Step "[1/5] Mencari implementasi pita yang BENAR di project ..."

$roots = @(
    ".\resources\views",
    ".\resources\css",
    ".\resources\js"
)

$matches = @()

foreach ($root in $roots) {
    if (Test-Path $root) {
        $matches += Get-ChildItem $root -Recurse -File -ErrorAction SilentlyContinue |
            Select-String -SimpleMatch "FURNITUR TOKO MEBEL" -ErrorAction SilentlyContinue
    }
}

if (-not $matches -or $matches.Count -eq 0) {
    throw "Teks 'FURNITUR TOKO MEBEL' tidak ditemukan di folder resources."
}

Write-Host "Ditemukan di:" -ForegroundColor Green
$matches | Select-Object Path, LineNumber, Line | Format-Table -AutoSize | Out-Host

$targetFiles = $matches.Path | Sort-Object -Unique

Step "[2/5] Membuat backup file target ..."
foreach ($file in $targetFiles) {
    $relative = $file.Substring((Get-Location).Path.Length).TrimStart('\')
    $dest = Join-Path $backupDir $relative
    $destDir = Split-Path $dest -Parent
    New-Item -ItemType Directory -Path $destDir -Force | Out-Null
    Copy-Item $file $dest -Force
}
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[3/5] Mendeteksi elemen bergerak lalu memperlambat ke 120 detik ..."

$phpPatch = @'
<?php

$files = array_slice($argv, 1);
$duration = '120s';

function add_duration_to_tag(string $tag, string $duration): string
{
    if (preg_match('/\bstyle\s*=\s*(["\'])(.*?)\1/is', $tag, $m)) {
        $style = rtrim(trim($m[2]), ';');
        $newStyle = $style . '; animation-duration:' . $duration . ' !important;';
        return preg_replace(
            '/\bstyle\s*=\s*(["\'])(.*?)\1/is',
            'style="' . $newStyle . '"',
            $tag,
            1
        );
    }

    return preg_replace(
        '/>$/',
        ' style="animation-duration:' . $duration . ' !important;">',
        $tag,
        1
    );
}

$totalChanged = 0;

foreach ($files as $path) {
    $content = file_get_contents($path);

    if ($content === false) {
        throw new RuntimeException("Gagal membaca {$path}");
    }

    $needle = 'FURNITUR TOKO MEBEL';
    $pos = strpos($content, $needle);

    if ($pos === false) {
        continue;
    }

    $changed = false;

    /*
    |--------------------------------------------------------------------------
    | Strategi A:
    | Cari opening tag sebelum teks yang class/style-nya menunjukkan
    | elemen animasi/track. Kita tidak lagi mengasumsikan keyframes ada
    | di footer.
    |--------------------------------------------------------------------------
    */
    $windowStart = max(0, $pos - 6000);
    $before = substr($content, $windowStart, $pos - $windowStart);

    preg_match_all(
        '/<([a-zA-Z][a-zA-Z0-9:-]*)\b[^>]*>/s',
        $before,
        $tags,
        PREG_OFFSET_CAPTURE
    );

    $candidates = [];

    foreach ($tags[0] ?? [] as $entry) {
        [$tag, $offset] = $entry;
        $lower = strtolower($tag);

        $score = 0;

        if (str_contains($lower, 'animate-')) $score += 100;
        if (str_contains($lower, 'animation:')) $score += 100;
        if (str_contains($lower, 'marquee')) $score += 80;
        if (str_contains($lower, 'ticker')) $score += 80;
        if (str_contains($lower, 'running')) $score += 70;
        if (str_contains($lower, 'track')) $score += 50;
        if (str_contains($lower, 'scroll')) $score += 45;
        if (str_contains($lower, 'translate')) $score += 30;
        if (str_contains($lower, 'min-w-max')) $score += 15;
        if (str_contains($lower, 'whitespace-nowrap')) $score += 15;

        if ($score > 0) {
            // Lebih dekat ke teks sedikit lebih diprioritaskan.
            $distance = strlen($before) - $offset;
            $score += max(0, 20 - intdiv($distance, 250));
            $candidates[] = [
                'tag' => $tag,
                'offset' => $windowStart + $offset,
                'score' => $score,
            ];
        }
    }

    if ($candidates) {
        usort($candidates, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return $b['offset'] <=> $a['offset'];
            }
            return $b['score'] <=> $a['score'];
        });

        $best = $candidates[0];
        $newTag = add_duration_to_tag($best['tag'], $duration);

        $content = substr_replace(
            $content,
            $newTag,
            $best['offset'],
            strlen($best['tag'])
        );

        $changed = true;
        echo "INLINE_DURATION: {$path}\n";
        echo "TARGET_TAG: " . preg_replace('/\s+/', ' ', $best['tag']) . "\n";
    }

    /*
    |--------------------------------------------------------------------------
    | Strategi B:
    | Bila tidak menemukan parent animasi, cari class animate-* di sekitar
    | teks dan beri CSS override berdasarkan selector attribute.
    |--------------------------------------------------------------------------
    */
    if (!$changed) {
        $near = substr(
            $content,
            max(0, $pos - 8000),
            min(strlen($content), 16000)
        );

        if (preg_match('/class\s*=\s*(["\'])([^"\']*(?:animate-|marquee|ticker|running|scroll)[^"\']*)\1/is', $near, $m)) {
            $classList = preg_split('/\s+/', trim($m[2]));
            $class = null;

            foreach ($classList as $item) {
                if (preg_match('/(?:animate-|marquee|ticker|running|scroll)/i', $item)) {
                    $class = $item;
                    break;
                }
            }

            if ($class) {
                $safeClass = htmlspecialchars($class, ENT_QUOTES);
                $override = "\n<style>\n"
                    . "/* KIE slow running strip */\n"
                    . '[class~="' . $safeClass . '"]{animation-duration:' . $duration . ' !important;}' . "\n"
                    . "</style>\n";

                if (stripos($content, '</body>') !== false) {
                    $content = preg_replace('/<\/body>/i', $override . '</body>', $content, 1);
                } else {
                    $content .= $override;
                }

                $changed = true;
                echo "CSS_OVERRIDE: {$path} / {$class}\n";
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Strategi C:
    | Cari shorthand animation / animation-duration di file sumber target.
    |--------------------------------------------------------------------------
    */
    if (!$changed) {
        $count = 0;

        $content2 = preg_replace_callback(
            '/animation\s*:\s*([^;{}]*?)\b(\d+(?:\.\d+)?)s\b([^;{}]*?;)/i',
            function ($m) use ($duration, &$count) {
                $count++;
                return 'animation:' . $m[1] . $duration . $m[3];
            },
            $content,
            1
        );

        if ($content2 !== null && $count > 0) {
            $content = $content2;
            $changed = true;
            echo "ANIMATION_SHORTHAND: {$path}\n";
        }
    }

    if (!$changed) {
        throw new RuntimeException(
            "Teks pita ditemukan di {$path}, tetapi elemen animasinya belum dapat diidentifikasi secara aman."
        );
    }

    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException("Gagal menulis {$path}");
    }

    $totalChanged++;
}

if ($totalChanged < 1) {
    throw new RuntimeException('Tidak ada file yang berhasil diubah.');
}

echo "TOTAL_CHANGED={$totalChanged}\n";
'@

$tmp = Join-Path $env:TEMP "slow-running-strip-v2-$stamp.php"
[System.IO.File]::WriteAllText($tmp, $phpPatch, $utf8NoBom)

$args = @($tmp)
foreach ($file in $targetFiles) {
    $args += $file
}

& php @args
$exitCode = $LASTEXITCODE

Remove-Item $tmp -Force -ErrorAction SilentlyContinue

if ($exitCode -ne 0) {
    throw "Patch gagal. Backup tersedia di: $backupDir"
}

Step "[4/5] Validasi syntax target Blade/PHP ..."
foreach ($file in $targetFiles) {
    if ($file -match '\.(php|blade\.php)$') {
        php -l $file | Out-Host
        if ($LASTEXITCODE -ne 0) {
            throw "Syntax error pada $file. Backup: $backupDir"
        }
    }
}

Step "[5/5] Bersihkan cache view ..."
php artisan view:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host "Durasi pita sekarang 120 detik supaya geraknya jauh lebih tenang." -ForegroundColor White
Write-Host "Arah gerak, teks, warna, ukuran, dan layout tidak diubah." -ForegroundColor White
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
