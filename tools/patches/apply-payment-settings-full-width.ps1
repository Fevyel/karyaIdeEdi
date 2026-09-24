$ErrorActionPreference = 'Stop'

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$file = ".\resources\views\pages\admin\pengaturan.blade.php"

if (-not (Test-Path $file)) {
    throw "File tidak ditemukan: $file"
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backup = "$file.bak-payment-full-width-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "==============================================================" -ForegroundColor Yellow
Write-Host " Pengaturan - Rekening Pembayaran Full Width" -ForegroundColor Yellow
Write-Host "==============================================================" -ForegroundColor Yellow

Write-Host "`n[1/4] Backup ..." -ForegroundColor Cyan
Copy-Item $file $backup -Force
Write-Host "  Backup: $backup" -ForegroundColor DarkGray

Write-Host "`n[2/4] Ubah section Rekening Pembayaran menjadi full width ..." -ForegroundColor Cyan

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

$divPos = strpos($text, '<div class="', $commentPos);

if ($divPos === false) {
    throw new RuntimeException('Container Rekening Pembayaran tidak ditemukan.');
}

$classStart = $divPos + strlen('<div class="');
$classEnd = strpos($text, '"', $classStart);

if ($classEnd === false) {
    throw new RuntimeException('Class container Rekening Pembayaran tidak dapat dibaca.');
}

$class = substr($text, $classStart, $classEnd - $classStart);

if (!str_contains($class, 'col-span-full')) {
    $class = 'col-span-full w-full '.$class;
}

$text = substr($text, 0, $classStart)
    .$class
    .substr($text, $classEnd);

/*
|--------------------------------------------------------------------------
| Inner cards:
| dari lg:grid-cols-3 -> md:grid-cols-3 supaya setelah section full-width,
| tiga metode pembayaran langsung memakai ruang horizontal dengan rapi.
|--------------------------------------------------------------------------
*/
$sectionSliceStart = $commentPos;
$sectionSliceLength = min(12000, strlen($text) - $sectionSliceStart);
$sectionSlice = substr($text, $sectionSliceStart, $sectionSliceLength);

$oldGrid = 'grid grid-cols-1 gap-5 lg:grid-cols-3';
$newGrid = 'grid w-full grid-cols-1 gap-5 md:grid-cols-3';

if (str_contains($sectionSlice, $oldGrid)) {
    $sectionSlice = str_replace($oldGrid, $newGrid, $sectionSlice, $count);

    if ($count !== 1) {
        throw new RuntimeException('Grid rekening pembayaran ditemukan lebih dari sekali.');
    }

    $text = substr($text, 0, $sectionSliceStart)
        .$sectionSlice
        .substr($text, $sectionSliceStart + $sectionSliceLength);
}

if (file_put_contents($path, $text) === false) {
    throw new RuntimeException('Gagal menulis pengaturan.blade.php');
}

echo "PAYMENT_FULL_WIDTH_OK\n";
'@

$tmp = Join-Path $env:TEMP "payment-full-width-$stamp.php"
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
php artisan view:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host "Section Rekening Pembayaran sekarang full width." -ForegroundColor White
Write-Host "BCA, BRI, dan DANA tampil 3 kolom lebar di desktop." -ForegroundColor White
Write-Host "Field, data, route pembayaran, dan fungsi simpan TIDAK diubah." -ForegroundColor DarkGray
