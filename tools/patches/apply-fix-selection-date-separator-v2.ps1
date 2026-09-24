$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script ini dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$files = @(
    ".\resources\views\pages\admin\loker.blade.php",
    ".\resources\views\pages\frontend\karier.blade.php",
    ".\resources\views\pages\frontend\hasil-lamaran.blade.php"
)

foreach ($file in $files) {
    if (-not (Test-Path $file)) {
        throw "File tidak ditemukan: $file"
    }
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = ".backup-fix-selection-date-separator-v2-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "=============================================================" -ForegroundColor Yellow
Write-Host " Career - Fix Separator Tanggal Seleksi (v2)" -ForegroundColor Yellow
Write-Host "=============================================================" -ForegroundColor Yellow

Step "[1/4] Membuat backup ..."
foreach ($file in $files) {
    $relative = $file.TrimStart('.', '\')
    $dest = Join-Path $backupDir $relative
    $destDir = Split-Path $dest -Parent
    New-Item -ItemType Directory -Path $destDir -Force | Out-Null
    Copy-Item $file $dest -Force
}
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/4] Memperbaiki separator tanggal tanpa karakter mojibake ..."

$phpPatch = @'
<?php

$root = $argv[1] ?? getcwd();

$files = [
    'resources/views/pages/admin/loker.blade.php',
    'resources/views/pages/frontend/karier.blade.php',
    'resources/views/pages/frontend/hasil-lamaran.blade.php',
];

$patterns = [
    [
        '~(\{\{\s*\$job->selectionStartDate\(\)->translatedFormat\(\'d M\'\)\s*\}\}).*?(\{\{\s*\$job->selection_end->translatedFormat\(\'d M Y\'\)\s*\}\})~u',
        '$1 &ndash; $2',
    ],
    [
        '~(\{\{\s*\$job->selectionStartDate\(\)->translatedFormat\(\'d F Y\'\)\s*\}\}).*?(\{\{\s*\$job->selection_end->translatedFormat\(\'d F Y\'\)\s*\}\})~u',
        '$1 &ndash; $2',
    ],
];

foreach ($files as $relative) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);

    if (! is_file($path)) {
        throw new RuntimeException("File tidak ditemukan: {$relative}");
    }

    $content = file_get_contents($path);
    if ($content === false) {
        throw new RuntimeException("Gagal membaca: {$relative}");
    }

    $total = 0;

    foreach ($patterns as [$pattern, $replacement]) {
        $count = 0;
        $content = preg_replace($pattern, $replacement, $content, -1, $count);

        if ($content === null) {
            throw new RuntimeException("Regex gagal pada: {$relative}");
        }

        $total += $count;
    }

    // Tulis ulang sebagai UTF-8 tanpa BOM.
    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException("Gagal menulis: {$relative}");
    }

    echo "{$relative}: {$total} rentang tanggal diperbaiki\n";
}
'@

$tmp = Join-Path $env:TEMP "fix-selection-date-separator-v2-$stamp.php"
[System.IO.File]::WriteAllText($tmp, $phpPatch, $utf8NoBom)

php $tmp (Get-Location).Path
$exitCode = $LASTEXITCODE

Remove-Item $tmp -Force -ErrorAction SilentlyContinue

if ($exitCode -ne 0) {
    throw "Patch tanggal gagal. Backup tersedia di: $backupDir"
}

Step "[3/4] Validasi syntax ..."
foreach ($file in $files) {
    php -l $file | Out-Host
    if ($LASTEXITCODE -ne 0) {
        throw "Syntax error pada $file. Restore tersedia di: $backupDir"
    }
}

Step "[4/4] Bersihkan cache view ..."
php artisan view:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host "Rentang tanggal seleksi sekarang memakai separator HTML aman: &ndash;" -ForegroundColor White
Write-Host "Contoh tampilan browser: 26 Sep - 27 Sep 2026" -ForegroundColor White
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
