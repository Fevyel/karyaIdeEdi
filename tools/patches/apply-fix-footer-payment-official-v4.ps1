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
$backupDir = ".backup-footer-payment-v4-$stamp"
$assetDir = ".\public\images\payment-official"
$tempDir = Join-Path $env:TEMP "kie-payment-v4-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "==============================================================" -ForegroundColor Yellow
Write-Host " Footer Payment v4 - Official White Logos + Exact Old Badge" -ForegroundColor Yellow
Write-Host "==============================================================" -ForegroundColor Yellow

function Download-File {
    param(
        [Parameter(Mandatory=$true)][string]$Uri,
        [Parameter(Mandatory=$true)][string]$OutFile
    )

    $params = @{
        Uri = $Uri
        OutFile = $OutFile
        MaximumRedirection = 10
        TimeoutSec = 60
        ErrorAction = "Stop"
        Headers = @{
            "User-Agent" = "Mozilla/5.0"
            "Accept" = "image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8"
        }
    }

    try {
        Invoke-WebRequest @params -UseBasicParsing | Out-Null
    } catch {
        Invoke-WebRequest @params | Out-Null
    }

    if (-not (Test-Path $OutFile) -or (Get-Item $OutFile).Length -lt 200) {
        throw "Download gagal / file tidak valid: $Uri"
    }
}

function Assert-Png {
    param([Parameter(Mandatory=$true)][string]$Path)

    $bytes = [System.IO.File]::ReadAllBytes((Resolve-Path $Path))
    if ($bytes.Length -lt 8 -or
        $bytes[0] -ne 0x89 -or
        $bytes[1] -ne 0x50 -or
        $bytes[2] -ne 0x4E -or
        $bytes[3] -ne 0x47) {
        throw "Bukan PNG valid: $Path"
    }
}

function Assert-Svg {
    param([Parameter(Mandatory=$true)][string]$Path)

    $raw = [System.IO.File]::ReadAllText((Resolve-Path $Path))
    if ($raw -notmatch '(?is)<svg\b') {
        throw "Bukan SVG valid: $Path"
    }
}

Step "[1/5] Backup footer ..."
New-Item -ItemType Directory -Path "$backupDir\resources\views\partials\frontend" -Force | Out-Null
Copy-Item $footer "$backupDir\resources\views\partials\frontend\footer.blade.php" -Force
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/5] Download 3 logo resmi versi putih ..."
New-Item -ItemType Directory -Path $assetDir -Force | Out-Null
New-Item -ItemType Directory -Path $tempDir -Force | Out-Null

try {
    # BCA - official Brand Assets BCA.
    $bcaUrl = "https://www.bca.co.id/-/media/Feature/Card/List-Card/Tentang-BCA/Brand-Assets/Logo-BCA/Logo-BCA_Putih.png"
    $bcaTmp = Join-Path $tempDir "bca.png"
    Download-File -Uri $bcaUrl -OutFile $bcaTmp
    Assert-Png $bcaTmp
    Move-Item $bcaTmp (Join-Path $assetDir "bca.png") -Force
    Write-Host "  BCA  : OK" -ForegroundColor Green

    # BRI - official BRIAPI asset, white logo.
    $briUrl = "https://developers.bri.co.id/sites/default/files/2024-02/bri_0.png"
    $briTmp = Join-Path $tempDir "bri.png"
    Download-File -Uri $briUrl -OutFile $briTmp
    Assert-Png $briTmp
    Move-Item $briTmp (Join-Path $assetDir "bri.png") -Force
    Write-Host "  BRI  : OK" -ForegroundColor Green

    # DANA - official DANA asset, white logo.
    $danaCandidates = @(
        "https://a.m.dana.id/danaweb/v3/DANA-Logo-white.svg",
        "https://a.m.dana.id/danaweb/v3/DANA-Logo.svg"
    )

    $danaSaved = $false
    foreach ($url in $danaCandidates) {
        try {
            $danaTmp = Join-Path $tempDir "dana.svg"
            Download-File -Uri $url -OutFile $danaTmp
            Assert-Svg $danaTmp
            Move-Item $danaTmp (Join-Path $assetDir "dana.svg") -Force
            Write-Host "  DANA : OK" -ForegroundColor Green
            $danaSaved = $true
            break
        } catch {
            Remove-Item (Join-Path $tempDir "dana.svg") -Force -ErrorAction SilentlyContinue
        }
    }

    if (-not $danaSaved) {
        throw "Logo DANA resmi tidak berhasil di-download. Footer belum diubah."
    }

    Step "[3/5] Restore ukuran + warna badge persis seperti versi lama ..."

    $phpPatch = @'
<?php

$path = $argv[1];
$text = file_get_contents($path);

if ($text === false) {
    throw new RuntimeException('Gagal membaca footer.blade.php');
}

/*
|--------------------------------------------------------------------------
| Ini adalah ukuran + warna PERSIS dari footer sebelum payment logo diubah:
| parent : flex items-center gap-1.5
| badge  : h-5 w-8 rounded-sm bg-[#4A4A4A]
|
| Ukuran gambar memakai inline CSS agar TIDAK bergantung pada npm build.
|--------------------------------------------------------------------------
*/
$new = <<<'BLADE'
            {{-- Badge metode pembayaran -- ukuran & warna asli, logo resmi --}}
            <div class="flex items-center gap-1.5">
                <span class="flex h-5 w-8 items-center justify-center overflow-hidden rounded-sm bg-[#4A4A4A]">
                    <img src="{{ asset('images/payment-official/bca.png') }}" alt="BCA" style="display:block;max-width:27px;max-height:12px;width:auto;height:auto;object-fit:contain;">
                </span>
                <span class="flex h-5 w-8 items-center justify-center overflow-hidden rounded-sm bg-[#4A4A4A]">
                    <img src="{{ asset('images/payment-official/bri.png') }}" alt="Bank BRI" style="display:block;max-width:27px;max-height:12px;width:auto;height:auto;object-fit:contain;">
                </span>
                <span class="flex h-5 w-8 items-center justify-center overflow-hidden rounded-sm bg-[#4A4A4A]">
                    <img src="{{ asset('images/payment-official/dana.svg') }}" alt="DANA" style="display:block;max-width:27px;max-height:12px;width:auto;height:auto;object-fit:contain;">
                </span>
            </div>
BLADE;

function locatePaymentBlock(string $text): array
{
    $commentPos = stripos($text, 'Badge metode pembayaran');

    if ($commentPos === false) {
        $commentPos = stripos($text, 'Metode pembayaran');
    }

    if ($commentPos === false) {
        throw new RuntimeException('Area metode pembayaran tidak ditemukan.');
    }

    $start = strpos($text, '<div', $commentPos);

    if ($start === false) {
        throw new RuntimeException('Container pembayaran tidak ditemukan.');
    }

    preg_match_all(
        '~<div\b[^>]*>|</div>~i',
        substr($text, $start),
        $tags,
        PREG_OFFSET_CAPTURE
    );

    $depth = 0;
    $end = null;

    foreach ($tags[0] as [$tag, $relative]) {
        if (stripos($tag, '</div') === 0) {
            $depth--;

            if ($depth === 0) {
                $end = $start + $relative + strlen($tag);
                break;
            }
        } else {
            $depth++;
        }
    }

    if ($end === null) {
        throw new RuntimeException('Akhir blok pembayaran tidak ditemukan.');
    }

    return [$start, $end];
}

[$start, $end] = locatePaymentBlock($text);

$text = substr($text, 0, $start).$new.substr($text, $end);

if (file_put_contents($path, $text) === false) {
    throw new RuntimeException('Gagal menulis footer.');
}

echo "PAYMENT_V4_OK\n";
'@

    $tmpPatch = Join-Path $tempDir "patch-payment-v4.php"
    [System.IO.File]::WriteAllText($tmpPatch, $phpPatch, $utf8NoBom)

    php $tmpPatch (Resolve-Path $footer)
    if ($LASTEXITCODE -ne 0) {
        throw "Patch footer gagal. Backup: $backupDir"
    }

    Step "[4/5] Validasi ..."
    php -l $footer | Out-Host
    if ($LASTEXITCODE -ne 0) {
        throw "Syntax footer bermasalah. Backup: $backupDir"
    }

    Assert-Png (Join-Path $assetDir "bca.png")
    Assert-Png (Join-Path $assetDir "bri.png")
    Assert-Svg (Join-Path $assetDir "dana.svg")

    Step "[5/5] Clear cache ..."
    php artisan optimize:clear | Out-Host

    Write-Host ""
    Write-Host "SELESAI" -ForegroundColor Green
    Write-Host ""
    Write-Host "Hasil yang ditargetkan:" -ForegroundColor Yellow
    Write-Host "  - BCA putih official" -ForegroundColor White
    Write-Host "  - BRI putih official dari BRIAPI, tidak biru dan tidak terpotong" -ForegroundColor White
    Write-Host "  - DANA putih official" -ForegroundColor White
    Write-Host "  - ukuran badge tetap h-5 x w-8" -ForegroundColor White
    Write-Host "  - warna badge tetap #4A4A4A" -ForegroundColor White
    Write-Host "  - gap tetap 1.5" -ForegroundColor White
    Write-Host "  - tidak perlu npm run build karena ukuran img memakai inline CSS" -ForegroundColor White
    Write-Host ""
    Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
}
finally {
    Remove-Item $tempDir -Recurse -Force -ErrorAction SilentlyContinue
}
