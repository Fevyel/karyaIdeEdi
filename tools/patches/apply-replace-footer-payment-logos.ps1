$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$footer = ".\resources\views\partials\frontend\footer.blade.php"

if (-not (Test-Path $footer)) {
    throw "Footer tidak ditemukan: $footer"
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = ".backup-footer-payment-logos-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "==============================================================" -ForegroundColor Yellow
Write-Host " Footer - Ganti Logo Pembayaran ke BCA / BANK BRI / DANA" -ForegroundColor Yellow
Write-Host "==============================================================" -ForegroundColor Yellow

Step "[1/5] Backup footer ..."
New-Item -ItemType Directory -Path "$backupDir\resources\views\partials\frontend" -Force | Out-Null
Copy-Item $footer "$backupDir\resources\views\partials\frontend\footer.blade.php" -Force
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/5] Membuat 3 logo lokal ..."
$logoDir = ".\public\images\payment"
New-Item -ItemType Directory -Path $logoDir -Force | Out-Null

$bca = @'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 220 72" role="img" aria-label="BCA">
  <rect width="220" height="72" rx="12" fill="white"/>
  <g transform="translate(16 14)">
    <path d="M20 0c10 8 15 16 15 24S30 40 20 48C10 40 5 32 5 24S10 8 20 0Z" fill="none" stroke="#1677c8" stroke-width="4"/>
    <path d="M0 24h40M20 3v42M7 10l26 28M33 10 7 38" stroke="#1677c8" stroke-width="3" stroke-linecap="round"/>
  </g>
  <text x="72" y="47" font-family="Arial, Helvetica, sans-serif" font-size="34" font-weight="800" fill="#1677c8" letter-spacing="1">BCA</text>
</svg>
'@

$bri = @'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 260 72" role="img" aria-label="BANK BRI">
  <rect width="260" height="72" rx="12" fill="white"/>
  <g transform="translate(18 14)" fill="none" stroke="#0b5fae" stroke-width="5" stroke-linejoin="round">
    <path d="M4 2h30c7 0 12 5 12 12s-5 12-12 12H16"/>
    <path d="M4 2v44h30c7 0 12-5 12-12s-5-12-12-12H16"/>
    <path d="M16 12v24"/>
  </g>
  <text x="78" y="31" font-family="Arial, Helvetica, sans-serif" font-size="16" font-weight="700" fill="#0b5fae" letter-spacing="2">BANK</text>
  <text x="78" y="52" font-family="Arial, Helvetica, sans-serif" font-size="28" font-weight="900" fill="#0b5fae" letter-spacing="1">BRI</text>
</svg>
'@

$dana = @'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 72" role="img" aria-label="DANA">
  <rect width="240" height="72" rx="12" fill="white"/>
  <g transform="translate(18 14)">
    <rect x="0" y="0" width="44" height="44" rx="14" fill="#1689e8"/>
    <path d="M12 12h10c9 0 15 6 15 10s-6 10-15 10H12V12Z" fill="none" stroke="white" stroke-width="4" stroke-linejoin="round"/>
  </g>
  <text x="78" y="48" font-family="Arial, Helvetica, sans-serif" font-size="32" font-weight="800" fill="#1689e8" letter-spacing="1">DANA</text>
</svg>
'@

[System.IO.File]::WriteAllText((Join-Path (Get-Location) "public\images\payment\bca.svg"), $bca, $utf8NoBom)
[System.IO.File]::WriteAllText((Join-Path (Get-Location) "public\images\payment\bank-bri.svg"), $bri, $utf8NoBom)
[System.IO.File]::WriteAllText((Join-Path (Get-Location) "public\images\payment\dana.svg"), $dana, $utf8NoBom)

Write-Host "  - public/images/payment/bca.svg" -ForegroundColor DarkGray
Write-Host "  - public/images/payment/bank-bri.svg" -ForegroundColor DarkGray
Write-Host "  - public/images/payment/dana.svg" -ForegroundColor DarkGray

Step "[3/5] Ganti blok 4 logo lama menjadi 3 logo baru ..."

$phpPatch = @'
<?php

$path = $argv[1];
$text = file_get_contents($path);

if ($text === false) {
    throw new RuntimeException('Gagal membaca footer.blade.php');
}

$new = <<<'BLADE'
            {{-- Metode pembayaran: BCA / BANK BRI / DANA --}}
            <div class="flex flex-wrap items-center justify-center gap-3">
                <div class="flex h-16 w-[132px] items-center justify-center rounded-xl bg-white/85 px-4 shadow-sm ring-1 ring-white/10">
                    <img src="{{ asset('images/payment/bca.svg') }}" alt="BCA" class="h-9 w-auto object-contain">
                </div>
                <div class="flex h-16 w-[132px] items-center justify-center rounded-xl bg-white/85 px-4 shadow-sm ring-1 ring-white/10">
                    <img src="{{ asset('images/payment/bank-bri.svg') }}" alt="BANK BRI" class="h-9 w-auto object-contain">
                </div>
                <div class="flex h-16 w-[132px] items-center justify-center rounded-xl bg-white/85 px-4 shadow-sm ring-1 ring-white/10">
                    <img src="{{ asset('images/payment/dana.svg') }}" alt="DANA" class="h-9 w-auto object-contain">
                </div>
            </div>
BLADE;

/*
|--------------------------------------------------------------------------
| Cari area payment lama secara aman.
| Prioritas: komentar "Badge metode pembayaran".
|--------------------------------------------------------------------------
*/
$commentPos = stripos($text, 'Badge metode pembayaran');

if ($commentPos === false) {
    $commentPos = stripos($text, 'metode pembayaran');
}

if ($commentPos === false) {
    throw new RuntimeException('Komentar/area metode pembayaran tidak ditemukan. Tidak ada perubahan dilakukan.');
}

/*
|--------------------------------------------------------------------------
| Cari container <div> pertama sesudah komentar dan pasangan penutupnya.
|--------------------------------------------------------------------------
*/
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
    throw new RuntimeException('Akhir container logo pembayaran tidak ditemukan.');
}

$oldBlock = substr($text, $start, $end - $start);

/*
|--------------------------------------------------------------------------
| Safety check: blok lama harus tampak seperti area 4 badge/logo pembayaran.
|--------------------------------------------------------------------------
*/
$signals = 0;
foreach (['visa', 'master', 'apple', 'paypal', 'payment', 'fa-cc', 'fa-pay'] as $signal) {
    if (stripos($oldBlock, $signal) !== false) {
        $signals++;
    }
}

if ($signals < 2 && substr_count(strtolower($oldBlock), '<div') < 4) {
    throw new RuntimeException('Safety check gagal: area yang ditemukan tidak cukup meyakinkan sebagai blok pembayaran.');
}

$text = substr($text, 0, $start).$new.substr($text, $end);

if (file_put_contents($path, $text) === false) {
    throw new RuntimeException('Gagal menulis footer.blade.php');
}

echo "PAYMENT_LOGOS_REPLACED\n";
'@

$tmp = Join-Path $env:TEMP "replace-payment-logos-$stamp.php"
[System.IO.File]::WriteAllText($tmp, $phpPatch, $utf8NoBom)

php $tmp (Resolve-Path $footer)
$exitCode = $LASTEXITCODE
Remove-Item $tmp -Force -ErrorAction SilentlyContinue

if ($exitCode -ne 0) {
    throw "Patch gagal. Backup tersedia di: $backupDir"
}

Step "[4/5] Validasi footer ..."
php -l $footer | Out-Host

if ($LASTEXITCODE -ne 0) {
    throw "Syntax footer bermasalah. Backup tersedia di: $backupDir"
}

Step "[5/5] Bersihkan cache view ..."
php artisan view:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host ""
Write-Host "4 logo lama sudah diganti menjadi 3 logo:" -ForegroundColor Yellow
Write-Host "  1. BCA" -ForegroundColor White
Write-Host "  2. BANK BRI" -ForegroundColor White
Write-Host "  3. DANA" -ForegroundColor White
Write-Host ""
Write-Host "Bagian footer lain tidak diubah." -ForegroundColor DarkGray
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
