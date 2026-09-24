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
$backupDir = ".backup-fix-selection-date-separator-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "=============================================================" -ForegroundColor Yellow
Write-Host " Career - Fix Karakter Rusak Pada Rentang Tanggal Seleksi" -ForegroundColor Yellow
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

Step "[2/4] Mengganti separator tanggal menjadi HTML entity aman ..."

foreach ($file in $files) {
    $content = [System.IO.File]::ReadAllText((Resolve-Path $file))
    $before = $content

    # Perbaiki mojibake yang sudah terlanjur tertulis.
    $content = $content.Replace('â€“', '&ndash;')
    $content = $content.Replace('â€”', '&mdash;')

    # Perbaiki karakter en dash asli agar tidak kembali rusak karena encoding editor/patch.
    $content = $content.Replace(' – ', ' &ndash; ')

    if ($content -ne $before) {
        [System.IO.File]::WriteAllText((Resolve-Path $file), $content, $utf8NoBom)
        Write-Host "  - Diperbaiki: $file" -ForegroundColor Green
    } else {
        Write-Host "  - Tidak ada karakter rusak pada: $file" -ForegroundColor DarkGray
    }
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
Write-Host "Rentang tanggal sekarang tampil normal, contoh:" -ForegroundColor Yellow
Write-Host "  26 Sep - 27 Sep 2026  (dirender sebagai en dash di browser)" -ForegroundColor White
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
