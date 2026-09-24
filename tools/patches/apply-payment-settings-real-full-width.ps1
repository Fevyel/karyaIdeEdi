$ErrorActionPreference = 'Stop'

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$file = ".\resources\views\pages\admin\pengaturan.blade.php"

if (-not (Test-Path $file)) {
    throw "File tidak ditemukan: $file"
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backup = "$file.bak-payment-real-full-width-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "==================================================================" -ForegroundColor Yellow
Write-Host " Pengaturan - Rekening Pembayaran REAL Full Width" -ForegroundColor Yellow
Write-Host "==================================================================" -ForegroundColor Yellow

Write-Host "`n[1/4] Backup ..." -ForegroundColor Cyan
Copy-Item $file $backup -Force
Write-Host "  Backup: $backup" -ForegroundColor DarkGray

Write-Host "`n[2/4] Paksa section Rekening Pembayaran span seluruh grid ..." -ForegroundColor Cyan

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

$divPos = strpos($text, '<div', $commentPos);

if ($divPos === false) {
    throw new RuntimeException('Container utama Rekening Pembayaran tidak ditemukan.');
}

$tagEnd = strpos($text, '>', $divPos);

if ($tagEnd === false) {
    throw new RuntimeException('Tag pembuka Rekening Pembayaran tidak valid.');
}

$openingTag = substr($text, $divPos, $tagEnd - $divPos + 1);

/*
|--------------------------------------------------------------------------
| Jangan bergantung pada class Tailwind baru.
| Inline CSS memastikan section benar-benar melintasi SEMUA kolom parent grid.
|--------------------------------------------------------------------------
*/
if (preg_match('/\sstyle="[^"]*"/i', $openingTag)) {
    $newOpeningTag = preg_replace_callback(
        '/\sstyle="([^"]*)"/i',
        function (array $m): string {
            $style = rtrim(trim($m[1]), ';');

            foreach ([
                'grid-column: 1 / -1',
                'width: 100%',
                'max-width: none',
                'min-width: 0',
            ] as $rule) {
                if (! str_contains(strtolower($style), strtolower(strtok($rule, ':')) . ':')) {
                    $style .= ($style !== '' ? '; ' : '').$rule;
                }
            }

            return ' style="'.$style.';"';
        },
        $openingTag,
        1
    );
} else {
    $newOpeningTag = substr($openingTag, 0, -1)
        .' style="grid-column: 1 / -1; width: 100%; max-width: none; min-width: 0;">';
}

if ($newOpeningTag === $openingTag) {
    echo "OUTER_STYLE_ALREADY_PRESENT\n";
} else {
    $text = substr($text, 0, $divPos)
        .$newOpeningTag
        .substr($text, $tagEnd + 1);

    echo "OUTER_FULL_WIDTH_APPLIED\n";
}

/*
|--------------------------------------------------------------------------
| Pastikan grid BCA / BRI / DANA juga memakai lebar penuh section.
|--------------------------------------------------------------------------
*/
$commentPos = strpos($text, $comment);
$searchArea = substr($text, $commentPos, min(14000, strlen($text) - $commentPos));

$gridPattern = '/<div\s+class="([^"]*grid[^"]*grid-cols-1[^"]*(?:md:grid-cols-3|lg:grid-cols-3)[^"]*)"([^>]*)>/i';

if (preg_match($gridPattern, $searchArea, $m, PREG_OFFSET_CAPTURE)) {
    $fullMatch = $m[0][0];
    $relativePos = $m[0][1];
    $absolutePos = $commentPos + $relativePos;

    $gridTag = $fullMatch;

    if (preg_match('/\sstyle="([^"]*)"/i', $gridTag)) {
        $newGridTag = preg_replace(
            '/\sstyle="([^"]*)"/i',
            ' style="$1; width: 100%; min-width: 0;"',
            $gridTag,
            1
        );
    } else {
        $newGridTag = substr($gridTag, 0, -1)
            .' style="width: 100%; min-width: 0;">';
    }

    $text = substr($text, 0, $absolutePos)
        .$newGridTag
        .substr($text, $absolutePos + strlen($gridTag));

    echo "INNER_GRID_WIDTH_APPLIED\n";
} else {
    throw new RuntimeException('Grid BCA/BRI/DANA tidak ditemukan.');
}

if (file_put_contents($path, $text) === false) {
    throw new RuntimeException('Gagal menulis pengaturan.blade.php');
}

echo "PAYMENT_REAL_FULL_WIDTH_OK\n";
'@

$tmp = Join-Path $env:TEMP "payment-real-full-width-$stamp.php"
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
Write-Host "Perubahan kali ini memakai INLINE CSS, bukan class Tailwind baru." -ForegroundColor White
Write-Host "Jadi tidak perlu npm run build dan harus langsung terlihat setelah refresh." -ForegroundColor White
Write-Host ""
Write-Host "Yang diubah HANYA layout section Rekening Pembayaran." -ForegroundColor DarkGray
Write-Host "Data rekening, fungsi simpan, route, dan halaman transfer TIDAK diubah." -ForegroundColor DarkGray
Write-Host "Backup: $backup" -ForegroundColor DarkGray
