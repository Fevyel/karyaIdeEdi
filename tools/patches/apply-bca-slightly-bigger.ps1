$ErrorActionPreference = 'Stop'

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$footer = ".\resources\views\partials\frontend\footer.blade.php"

if (-not (Test-Path $footer)) {
    throw "Footer tidak ditemukan: $footer"
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backup = "$footer.bak-bca-slightly-bigger-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "==============================================================" -ForegroundColor Yellow
Write-Host " Footer - BCA Sedikit Lebih Besar" -ForegroundColor Yellow
Write-Host "==============================================================" -ForegroundColor Yellow

Write-Host "`n[1/4] Backup footer ..." -ForegroundColor Cyan
Copy-Item $footer $backup -Force
Write-Host "  Backup: $backup" -ForegroundColor DarkGray

Write-Host "`n[2/4] Perbesar HANYA logo BCA ..." -ForegroundColor Cyan

$phpPatch = @'
<?php

$path = $argv[1];
$text = file_get_contents($path);

if ($text === false) {
    throw new RuntimeException('Gagal membaca footer.');
}

$pattern = '~(<img\b[^>]*payment-official/bca\.png[^>]*style="[^"]*max-width:)\s*\d+px\s*(;\s*max-height:)\s*\d+px~i';

if (!preg_match($pattern, $text)) {
    throw new RuntimeException(
        'Tag logo BCA dengan ukuran inline tidak ditemukan. Tidak ada perubahan dilakukan.'
    );
}

$text = preg_replace(
    $pattern,
    '${1}30px${2}14px',
    $text,
    1,
    $count
);

if ($count !== 1) {
    throw new RuntimeException('Logo BCA tidak berhasil diperbarui secara aman.');
}

if (file_put_contents($path, $text) === false) {
    throw new RuntimeException('Gagal menulis footer.');
}

echo "BCA_SIZE_UPDATED\n";
'@

$tmp = Join-Path $env:TEMP "bca-slightly-bigger-$stamp.php"
[System.IO.File]::WriteAllText($tmp, $phpPatch, $utf8NoBom)

php $tmp (Resolve-Path $footer)
$exitCode = $LASTEXITCODE

Remove-Item $tmp -Force -ErrorAction SilentlyContinue

if ($exitCode -ne 0) {
    throw "Patch gagal. Backup: $backup"
}

Write-Host "`n[3/4] Validasi ..." -ForegroundColor Cyan
php -l $footer | Out-Host

if ($LASTEXITCODE -ne 0) {
    throw "Syntax footer bermasalah. Backup: $backup"
}

Write-Host "`n[4/4] Bersihkan cache ..." -ForegroundColor Cyan
php artisan view:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host "BCA diperbesar sedikit: 27x12 -> maksimal 30x14 px." -ForegroundColor White
Write-Host "BRI, DANA, ukuran kotak, warna, jarak, dan layout TIDAK diubah." -ForegroundColor DarkGray
