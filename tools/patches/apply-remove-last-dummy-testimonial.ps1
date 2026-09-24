$ErrorActionPreference = 'Stop'

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = ".backup-remove-last-dummy-testimonial-$stamp"
$tempPhp = Join-Path $env:TEMP "remove-last-dummy-testimonial-$stamp.php"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

New-Item -ItemType Directory -Path $backupDir -Force | Out-Null

Write-Host "======================================================================" -ForegroundColor Yellow
Write-Host " FINAL CLEANUP - Hapus Sisa 1 Testimoni Dummy" -ForegroundColor Yellow
Write-Host "======================================================================" -ForegroundColor Yellow

$php = @'
<?php

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Testimonial;
use Illuminate\Support\Facades\DB;

$backupPath = $argv[1];

DB::transaction(function () use ($backupPath): void {
    $candidate = Testimonial::query()
        ->whereKey(1)
        ->where('customer_name', 'Budi Santoso')
        ->where('approval_status', 'approved')
        ->whereNull('product_id')
        ->first();

    if (! $candidate) {
        echo "DUMMY_NOT_FOUND_OR_ALREADY_REMOVED".PHP_EOL;
        return;
    }

    $payload = [
        'removed_at' => now()->toIso8601String(),
        'row' => $candidate->getAttributes(),
    ];

    file_put_contents(
        $backupPath,
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    $candidate->delete();

    echo "DELETED_DUMMY_TESTIMONIAL_ID=1".PHP_EOL;
    echo "CUSTOMER_NAME=Budi Santoso".PHP_EOL;
});

$remaining = Testimonial::query()
    ->where('customer_name', 'Budi Santoso')
    ->whereNull('product_id')
    ->count();

echo "REMAINING_BUDI_SANTOSO_WITHOUT_PRODUCT={$remaining}".PHP_EOL;
'@

[System.IO.File]::WriteAllText($tempPhp, $php, $utf8NoBom)

try {
    $backupPath = Join-Path (Resolve-Path $backupDir) "deleted-testimonial.json"

    php $tempPhp $backupPath

    if ($LASTEXITCODE -ne 0) {
        throw "Cleanup database gagal."
    }

    php artisan optimize:clear | Out-Host

    Write-Host ""
    Write-Host "SELESAI" -ForegroundColor Green
    Write-Host "Hanya testimoni dummy exact ID #1 / Budi Santoso yang ditargetkan." -ForegroundColor White
    Write-Host "Produk, kategori, pesanan, pelanggan, dan testimoni asli lain tidak disentuh." -ForegroundColor White
    Write-Host "Backup row yang dihapus: $backupPath" -ForegroundColor DarkGray
}
finally {
    Remove-Item $tempPhp -Force -ErrorAction SilentlyContinue
}
