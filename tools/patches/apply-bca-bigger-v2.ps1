$ErrorActionPreference = 'Stop'

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$footer = ".\resources\views\partials\frontend\footer.blade.php"

if (-not (Test-Path $footer)) {
    throw "Footer tidak ditemukan: $footer"
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backup = "$footer.bak-bca-bigger-v2-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "==============================================================" -ForegroundColor Yellow
Write-Host " Footer - Perbesar HANYA Logo BCA (v2)" -ForegroundColor Yellow
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
        'Tag BCA dengan inline max-width/max-height tidak ditemukan. Tidak ada perubahan dilakukan.'
    );
}

$text = preg_replace(
    $pattern,
    '${1}36px${2}16px',
    $text,
    1,
    $count
);

if ($count !== 1) {
    throw new RuntimeException('Ukuran BCA tidak berhasil diubah secara aman.');
}

if (file_put_contents($path, $text) === false) {
    throw new RuntimeException('Gagal menulis footer.');
}

echo "BCA_BIGGER_V2_OK\n";
'@

$tmp = Join-Path $env:TEMP "bca-bigger-v2-$stamp.php"
[System.IO.File]::WriteAllText($tmp, $phpPatch, $utf8NoBom)

php $tmp (Resolve-Path $footer)
$exitCode = $LASTEXITCODE

Remove-Item $tmp -Force -ErrorAction SilentlyContinue

if ($exitCode -ne 0) {
    throw "Patch gagal. Backup: $backup"
}

Write-Host "`n[3/4] Validasi syntax ..." -ForegroundColor Cyan
php -l $footer | Out-Host

if ($LASTEXITCODE -ne 0) {
    throw "Syntax footer bermasalah. Backup: $backup"
}

Write-Host "`n[4/4] Bersihkan cache ..." -ForegroundColor Cyan
php artisan view:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host "Logo BCA sekarang maksimal 36x16 px." -ForegroundColor White
Write-Host "BRI, DANA, ukuran kotak, warna, gap, dan layout TIDAK diubah." -ForegroundColor DarkGray
