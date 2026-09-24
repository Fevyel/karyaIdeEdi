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
$backupDir = ".backup-clean-career-residual-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Backup-File($file) {
    if (Test-Path $file) {
        $relative = $file.TrimStart('.', '\')
        $dest = Join-Path $backupDir $relative
        $destDir = Split-Path $dest -Parent
        New-Item -ItemType Directory -Path $destDir -Force | Out-Null
        Copy-Item $file $dest -Force
    }
}

Write-Host "==============================================================" -ForegroundColor Yellow
Write-Host " Fix Sisa Referensi Career di Footer + Scan Project" -ForegroundColor Yellow
Write-Host "==============================================================" -ForegroundColor Yellow

Step "[1/5] Backup footer ..."
Backup-File $footer
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/5] Hapus sisa Career + Shipping & Returns dari footer ..."

$phpPatch = @'
<?php

$path = $argv[1];

if (!is_file($path)) {
    throw new RuntimeException("Footer tidak ditemukan.");
}

$text = file_get_contents($path);
if ($text === false) {
    throw new RuntimeException("Gagal membaca footer.");
}

/*
|--------------------------------------------------------------------------
| 1. Hapus item array/list satu baris yang masih memanggil careers.index
|--------------------------------------------------------------------------
*/
$text = preg_replace(
    '~^[^\r\n]*route\(\s*[\'"]careers\.index[\'"]\s*\)[^\r\n]*(?:\R|$)~mi',
    '',
    $text
);

/*
|--------------------------------------------------------------------------
| 2. Hapus blok <li> atau <a> Career yang tersisa
|--------------------------------------------------------------------------
*/
$text = preg_replace(
    '~<li\b[^>]*>.*?(?:careers\.index|/karier).*?</li>~is',
    '',
    $text
);

$text = preg_replace(
    '~<a\b[^>]*?(?:careers\.index|/karier)[^>]*>.*?</a>~is',
    '',
    $text
);

/*
|--------------------------------------------------------------------------
| 3. Hapus Shipping & Returns / #pengiriman
|--------------------------------------------------------------------------
*/
$text = preg_replace(
    '~<li\b[^>]*>.*?(?:#pengiriman|Shipping\s*&(?:amp;)?\s*Returns).*?</li>~is',
    '',
    $text
);

$text = preg_replace(
    '~<a\b[^>]*?(?:#pengiriman)[^>]*>.*?</a>~is',
    '',
    $text
);

/*
|--------------------------------------------------------------------------
| 4. Kalau footer memakai array PHP/Blade, hapus baris item terkait
|--------------------------------------------------------------------------
*/
$lines = preg_split('/\R/', $text);
$out = [];

foreach ($lines as $line) {
    $lower = strtolower($line);

    $remove =
        str_contains($lower, "careers.index") ||
        str_contains($lower, "route('careers") ||
        str_contains($lower, 'route("careers') ||
        str_contains($lower, "/karier") ||
        str_contains($lower, "#pengiriman") ||
        str_contains($lower, "shipping & returns") ||
        str_contains($lower, "shipping &amp; returns");

    // Jangan menghapus baris komentar umum yang tidak mengandung ekspresi/link aktif
    // kecuali memang menyebut item yang diminta dihapus.
    if (!$remove) {
        $out[] = $line;
    }
}

$text = implode(PHP_EOL, $out);

/*
|--------------------------------------------------------------------------
| 5. Rapikan baris kosong berlebih
|--------------------------------------------------------------------------
*/
$text = preg_replace("/(?:\R[ \t]*){4,}/", PHP_EOL.PHP_EOL.PHP_EOL, $text);

if (file_put_contents($path, $text) === false) {
    throw new RuntimeException("Gagal menulis footer.");
}

echo "FOOTER_CLEAN_OK\n";
'@

$tmp = Join-Path $env:TEMP "clean-footer-career-$stamp.php"
[System.IO.File]::WriteAllText($tmp, $phpPatch, $utf8NoBom)

php $tmp (Resolve-Path $footer)
$exitCode = $LASTEXITCODE

Remove-Item $tmp -Force -ErrorAction SilentlyContinue

if ($exitCode -ne 0) {
    throw "Gagal membersihkan footer. Backup: $backupDir"
}

Step "[3/5] Validasi footer ..."
php -l $footer | Out-Host

if ($LASTEXITCODE -ne 0) {
    throw "Syntax footer bermasalah. Restore tersedia di: $backupDir"
}

Step "[4/5] Scan sisa referensi aktif Career/Loker ..."

$scanRoots = @(
    ".\app",
    ".\routes",
    ".\resources\views"
)

$patterns = @(
    "careers.index",
    "careers.",
    "/karier",
    "JobVacancy",
    "JobApplication",
    "CareerSelectionService",
    "career:finalize-selections",
    "admin.jobs",
    "Pelamar Baru",
    "#pengiriman",
    "Shipping & Returns"
)

$found = @()

foreach ($root in $scanRoots) {
    if (-not (Test-Path $root)) { continue }

    $files = Get-ChildItem $root -Recurse -File -ErrorAction SilentlyContinue |
        Where-Object {
            $_.FullName -notmatch '\\vendor\\' -and
            $_.FullName -notmatch '\\storage\\' -and
            $_.Name -notmatch '\.bak'
        }

    foreach ($pattern in $patterns) {
        $matches = $files | Select-String -SimpleMatch $pattern -ErrorAction SilentlyContinue
        if ($matches) {
            $found += $matches
        }
    }
}

if ($found.Count -gt 0) {
    Write-Host ""
    Write-Host "[PERINGATAN] Masih ada referensi berikut di source aktif:" -ForegroundColor Yellow
    $found |
        Sort-Object Path, LineNumber -Unique |
        Select-Object -First 40 Path, LineNumber, Line |
        Format-Table -AutoSize | Out-Host
} else {
    Write-Host "  - Tidak ditemukan referensi Career/Loker/Shipping aktif." -ForegroundColor Green
}

Step "[5/5] Bersihkan cache Laravel ..."
php artisan optimize:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host "Footer tidak lagi memanggil route careers.index." -ForegroundColor White
Write-Host "Shipping & Returns juga dibersihkan dari footer." -ForegroundColor White
Write-Host ""
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
Write-Host ""
Write-Host "Kalau bagian [PERINGATAN] muncul, kirim outputnya ke saya." -ForegroundColor Yellow
