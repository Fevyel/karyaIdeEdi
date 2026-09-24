$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script ini dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$footer = ".\resources\views\partials\frontend\footer.blade.php"

if (-not (Test-Path $footer)) {
    throw "File footer tidak ditemukan: $footer"
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = ".backup-footer-payment-official-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "==============================================================" -ForegroundColor Yellow
Write-Host " Footer - Pakai Logo Official + Kembalikan Gaya Compact" -ForegroundColor Yellow
Write-Host "==============================================================" -ForegroundColor Yellow

Step "[1/5] Backup footer ..."
New-Item -ItemType Directory -Path "$backupDir\resources\views\partials\frontend" -Force | Out-Null
Copy-Item $footer "$backupDir\resources\views\partials\frontend\footer.blade.php" -Force
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/5] Cek folder logo official ..."
$officialDir = ".\public\images\payment-official"
New-Item -ItemType Directory -Path $officialDir -Force | Out-Null

$requiredFiles = @(
    ".\public\images\payment-official\bca.png",
    ".\public\images\payment-official\bri.png",
    ".\public\images\payment-official\dana.png"
)

$missing = @($requiredFiles | Where-Object { -not (Test-Path $_) })

if ($missing.Count -gt 0) {
    Write-Host "  Logo official belum lengkap. Siapkan file ini dulu:" -ForegroundColor Yellow
    $missing | ForEach-Object { Write-Host "   - $_" -ForegroundColor DarkGray }
    Write-Host ""
    Write-Host "  Patch tetap bisa dijalankan, tetapi logo baru akan tampil setelah file resmi diletakkan di folder tersebut." -ForegroundColor Yellow
}

Step "[3/5] Patch blok pembayaran di footer ..."

$phpPatch = @'
<?php

$path = $argv[1];
$text = file_get_contents($path);

if ($text === false) {
    throw new RuntimeException('Gagal membaca footer.blade.php');
}

$new = <<<'BLADE'
            {{-- Badge metode pembayaran: memakai file logo OFFICIAL yang disimpan lokal --}}
            <div class="flex flex-wrap items-center justify-center gap-3 sm:gap-4">
                <div class="flex h-11 w-[106px] items-center justify-center rounded-xl border border-white/10 bg-white/5 px-3 shadow-sm backdrop-blur-sm">
                    <img src="{{ asset('images/payment-official/bca.png') }}" alt="BCA" class="h-5 w-auto object-contain">
                </div>
                <div class="flex h-11 w-[106px] items-center justify-center rounded-xl border border-white/10 bg-white/5 px-3 shadow-sm backdrop-blur-sm">
                    <img src="{{ asset('images/payment-official/bri.png') }}" alt="BANK BRI" class="h-5 w-auto object-contain">
                </div>
                <div class="flex h-11 w-[106px] items-center justify-center rounded-xl border border-white/10 bg-white/5 px-3 shadow-sm backdrop-blur-sm">
                    <img src="{{ asset('images/payment-official/dana.png') }}" alt="DANA" class="h-5 w-auto object-contain">
                </div>
            </div>
BLADE;

/*
|--------------------------------------------------------------------------
| 1) Coba hapus blok hasil patch sebelumnya (asset custom payment).
|--------------------------------------------------------------------------
*/
if (strpos($text, "asset('images/payment/bca.svg')") !== false ||
    strpos($text, 'asset("images/payment/bca.svg")') !== false ||
    strpos($text, "asset('images/payment/bank-bri.svg')") !== false ||
    strpos($text, 'asset("images/payment/bank-bri.svg")') !== false ||
    strpos($text, "asset('images/payment/dana.svg')") !== false ||
    strpos($text, 'asset("images/payment/dana.svg")') !== false) {

    $pattern = '~\{\{\-\-\s*Metode pembayaran: BCA\s*/\s*BANK BRI\s*/\s*DANA\s*\-\-\}\}\s*<div class="flex flex-wrap items-center justify-center gap-3">.*?</div>\s*</div>\s*</div>~si';

    if (preg_match($pattern, $text)) {
        $text = preg_replace($pattern, $new, $text, 1, $count);
        if ($count > 0) {
            file_put_contents($path, $text);
            echo "PAYMENT_PATCHED\n";
            exit(0);
        }
    }
}

/*
|--------------------------------------------------------------------------
| 2) Fallback: cari komentar badge metode pembayaran, lalu ganti container
|    pertama sesudah komentar itu.
|--------------------------------------------------------------------------
*/
$commentPos = stripos($text, 'Badge metode pembayaran');
if ($commentPos === false) {
    $commentPos = stripos($text, 'metode pembayaran');
}
if ($commentPos === false) {
    throw new RuntimeException('Komentar area metode pembayaran tidak ditemukan.');
}

$start = strpos($text, '<div', $commentPos);
if ($start === false) {
    throw new RuntimeException('Container logo pembayaran tidak ditemukan.');
}

$offset = $start;
$depth = 0;
$end = null;
$len = strlen($text);

while ($offset < $len) {
    $nextOpen = strpos($text, '<div', $offset);
    $nextClose = strpos($text, '</div>', $offset);

    if ($nextClose === false) {
        break;
    }

    if ($nextOpen !== false && $nextOpen < $nextClose) {
        $depth++;
        $offset = $nextOpen + 4;
        continue;
    }

    $depth--;
    $offset = $nextClose + 6;

    if ($depth === 0) {
        $end = $offset;
        break;
    }
}

if ($end === null) {
    throw new RuntimeException('Akhir blok pembayaran tidak ditemukan.');
}

$oldBlock = substr($text, $start, $end - $start);

$signals = 0;
foreach (['visa','master','apple','paypal','bca','bri','dana','payment','fa-cc','fa-pay'] as $signal) {
    if (stripos($oldBlock, $signal) !== false) {
        $signals++;
    }
}

if ($signals < 1 && substr_count(strtolower($oldBlock), '<div') < 3) {
    throw new RuntimeException('Safety check gagal: area pembayaran tidak cukup meyakinkan.');
}

$text = substr($text, 0, $start) . $new . substr($text, $end);

if (file_put_contents($path, $text) === false) {
    throw new RuntimeException('Gagal menulis footer.blade.php');
}

echo "PAYMENT_PATCHED\n";
'@

$tmp = Join-Path $env:TEMP "patch-footer-payment-official-$stamp.php"
[System.IO.File]::WriteAllText($tmp, $phpPatch, $utf8NoBom)

php $tmp (Resolve-Path $footer)
$exitCode = $LASTEXITCODE
Remove-Item $tmp -Force -ErrorAction SilentlyContinue

if ($exitCode -ne 0) {
    throw "Patch footer gagal. Backup tersedia di: $backupDir"
}

Step "[4/5] Validasi syntax Blade ..."
php -l $footer | Out-Host
if ($LASTEXITCODE -ne 0) {
    throw "Syntax footer bermasalah. Backup tersedia di: $backupDir"
}

Step "[5/5] Bersihkan cache view ..."
php artisan view:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host ""
Write-Host "Catatan penting:" -ForegroundColor Yellow
Write-Host "1. Script ini TIDAK menggambar ulang logo." -ForegroundColor White
Write-Host "2. Footer sekarang diarahkan untuk memakai file logo OFFICIAL milik brand dari folder:" -ForegroundColor White
Write-Host "   public\images\payment-official\" -ForegroundColor DarkGray
Write-Host "3. Nama file yang dipakai:" -ForegroundColor White
Write-Host "   - bca.png" -ForegroundColor DarkGray
Write-Host "   - bri.png" -ForegroundColor DarkGray
Write-Host "   - dana.png" -ForegroundColor DarkGray
Write-Host "4. Gaya badge dibuat compact/netral agar tidak merusak warna besar footer." -ForegroundColor White
Write-Host ""
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
