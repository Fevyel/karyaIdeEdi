$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script ini dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$file = ".\resources\views\pages\frontend\booking.blade.php"

if (-not (Test-Path $file)) {
    throw "File tidak ditemukan: $file"
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backup = "$file.bak-remove-docmedia-strip-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "=============================================================" -ForegroundColor Yellow
Write-Host " Dokumentasi - Hapus Pita Foto/Video Bergerak" -ForegroundColor Yellow
Write-Host "=============================================================" -ForegroundColor Yellow

Step "[1/4] Membuat backup ..."
Copy-Item $file $backup -Force
Write-Host "  Backup: $backup" -ForegroundColor DarkGray

Step "[2/4] Menghapus KHUSUS pita media pada halaman Dokumentasi ..."

$phpPatch = @'
<?php

$path = $argv[1];
$content = file_get_contents($path);

if ($content === false) {
    throw new RuntimeException('Gagal membaca booking.blade.php');
}

$sectionStart = strpos($content, '<section class="kie-docmedia"');

if ($sectionStart === false) {
    throw new RuntimeException(
        'Section .kie-docmedia tidak ditemukan. Tidak ada perubahan dilakukan.'
    );
}

$beforeSection = substr($content, 0, $sectionStart);
$phpStart = strrpos($beforeSection, '@php');

if ($phpStart === false) {
    throw new RuntimeException('Awal @php pita media tidak ditemukan.');
}

$dataBlock = substr($content, $phpStart, $sectionStart - $phpStart);

if (
    strpos($dataBlock, 'dokMediaItems') === false ||
    strpos($dataBlock, 'dokumentasi-media') === false
) {
    throw new RuntimeException(
        'Blok @php terdekat bukan data pita dokumentasi. Tidak ada perubahan dilakukan.'
    );
}

$styleEnd = strpos($content, '</style>', $sectionStart);

if ($styleEnd === false) {
    throw new RuntimeException('Penutup </style> pita media tidak ditemukan.');
}

$styleEnd += strlen('</style>');

$endifPos = strpos($content, '@endif', $styleEnd);

if ($endifPos === false) {
    throw new RuntimeException('Penutup @endif pita media tidak ditemukan.');
}

$removeEnd = $endifPos + strlen('@endif');

$block = substr($content, $phpStart, $removeEnd - $phpStart);

foreach ([
    'kie-docmedia',
    'dokMediaItems',
    'dokumentasi-media',
] as $signature) {
    if (strpos($block, $signature) === false) {
        throw new RuntimeException(
            "Safety check gagal: signature {$signature} tidak ada."
        );
    }
}

$newContent =
    rtrim(substr($content, 0, $phpStart))
    . PHP_EOL . PHP_EOL
    . ltrim(substr($content, $removeEnd));

if (file_put_contents($path, $newContent) === false) {
    throw new RuntimeException('Gagal menulis booking.blade.php');
}

echo "PITA_MEDIA_REMOVED\n";
'@

$tmp = Join-Path $env:TEMP "remove-docmedia-strip-$stamp.php"
[System.IO.File]::WriteAllText($tmp, $phpPatch, $utf8NoBom)

php $tmp (Resolve-Path $file)
$exitCode = $LASTEXITCODE

Remove-Item $tmp -Force -ErrorAction SilentlyContinue

if ($exitCode -ne 0) {
    throw "Patch gagal. Backup tersedia di: $backup"
}

Step "[3/4] Validasi syntax ..."
php -l $file | Out-Host

if ($LASTEXITCODE -ne 0) {
    throw "Syntax booking.blade.php bermasalah. Restore dari: $backup"
}

Step "[4/4] Bersihkan cache ..."
php artisan view:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host "Pita foto/video bergerak sudah dihapus dari halaman Dokumentasi." -ForegroundColor White
Write-Host "Hero, Galeri Video, Galeri Foto, footer, dan bagian lain TIDAK diubah." -ForegroundColor White
Write-Host "Backup: $backup" -ForegroundColor DarkGray
