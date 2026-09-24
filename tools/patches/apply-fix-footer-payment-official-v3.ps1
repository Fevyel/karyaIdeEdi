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
$backupDir = ".backup-footer-payment-v3-$stamp"
$assetDir = ".\public\images\payment-official"
$tempDir = Join-Path $env:TEMP "kie-payment-v3-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "====================================================================" -ForegroundColor Yellow
Write-Host " Footer Payment v3 - Logo Resmi + Ukuran/Warna Badge Asli" -ForegroundColor Yellow
Write-Host "====================================================================" -ForegroundColor Yellow

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

    if (-not (Test-Path $OutFile)) {
        throw "Download gagal: $Uri"
    }

    if ((Get-Item $OutFile).Length -lt 200) {
        Remove-Item $OutFile -Force -ErrorAction SilentlyContinue
        throw "File terlalu kecil / bukan logo valid: $Uri"
    }
}

function Assert-Png {
    param([string]$Path)

    $bytes = [System.IO.File]::ReadAllBytes((Resolve-Path $Path))
    if ($bytes.Length -lt 8 -or
        $bytes[0] -ne 0x89 -or
        $bytes[1] -ne 0x50 -or
        $bytes[2] -ne 0x4E -or
        $bytes[3] -ne 0x47) {
        throw "File bukan PNG valid: $Path"
    }
}

function Assert-Svg {
    param([string]$Path)

    $raw = [System.IO.File]::ReadAllText((Resolve-Path $Path))
    if ($raw -notmatch '(?is)<svg\b') {
        throw "File bukan SVG valid: $Path"
    }
}

function Download-DanaLogo {
    param(
        [string]$Destination,
        [string]$TempFolder
    )

    $directCandidates = @(
        "https://a.m.dana.id/danaweb/v3/DANA-Logo-white.svg",
        "https://a.m.dana.id/danaweb/v3/DANA-Logo.svg"
    )

    foreach ($url in $directCandidates) {
        try {
            $tmp = Join-Path $TempFolder "dana-direct.svg"
            Download-File -Uri $url -OutFile $tmp
            Assert-Svg $tmp
            Move-Item $tmp $Destination -Force
            Write-Host "  DANA : $url" -ForegroundColor DarkGray
            return
        } catch {
            Remove-Item (Join-Path $TempFolder "dana-direct.svg") -Force -ErrorAction SilentlyContinue
        }
    }

    # Fallback: cari asset logo di HTML situs DANA resmi.
    $pages = @(
        "https://www.dana.id/?lng=id",
        "https://www.dana.id/personal?lng=id",
        "https://www.dana.id/en/download"
    )

    foreach ($page in $pages) {
        try {
            try {
                $resp = Invoke-WebRequest -Uri $page -UseBasicParsing -TimeoutSec 60 -MaximumRedirection 10 -Headers @{"User-Agent"="Mozilla/5.0"}
            } catch {
                $resp = Invoke-WebRequest -Uri $page -TimeoutSec 60 -MaximumRedirection 10 -Headers @{"User-Agent"="Mozilla/5.0"}
            }

            $html = [string]$resp.Content
            if (-not $html) { continue }

            $urls = New-Object System.Collections.Generic.List[string]

            foreach ($m in [regex]::Matches($html, '(?is)(?:src|data-src|srcset)\s*=\s*["'']([^"'']+)["'']')) {
                foreach ($part in ($m.Groups[1].Value -split ',')) {
                    $candidate = ($part.Trim() -split '\s+')[0]
                    if ($candidate) { $urls.Add($candidate) }
                }
            }

            foreach ($m in [regex]::Matches($html, '(?is)url\((["'']?)([^)"'']+)\1\)')) {
                if ($m.Groups[2].Value) { $urls.Add($m.Groups[2].Value.Trim()) }
            }

            $scored = @()

            foreach ($raw in $urls | Select-Object -Unique) {
                if ($raw.StartsWith("data:")) { continue }

                try {
                    $abs = ([System.Uri]::new([System.Uri]$page, $raw)).AbsoluteUri
                    $host = ([System.Uri]$abs).Host.ToLowerInvariant()

                    if ($host -ne "www.dana.id" -and
                        $host -ne "dana.id" -and
                        -not $host.EndsWith(".dana.id")) {
                        continue
                    }

                    $lower = $abs.ToLowerInvariant()
                    $score = 0
                    if ($lower.Contains("dana-logo")) { $score += 100 }
                    if ($lower.Contains("logo")) { $score += 50 }
                    if ($lower.Contains("dana")) { $score += 20 }
                    if ($lower.EndsWith(".svg")) { $score += 20 }
                    if ($lower.Contains("white")) { $score += 10 }
                    if ($lower.Contains("bank-indonesia") -or $lower.Contains("kominfo")) { $score -= 200 }

                    if ($score -gt 0) {
                        $scored += [pscustomobject]@{ Url = $abs; Score = $score }
                    }
                } catch {}
            }

            foreach ($candidate in ($scored | Sort-Object Score -Descending)) {
                try {
                    $tmp = Join-Path $TempFolder "dana-scan.svg"
                    Download-File -Uri $candidate.Url -OutFile $tmp
                    Assert-Svg $tmp
                    Move-Item $tmp $Destination -Force
                    Write-Host "  DANA : $($candidate.Url)" -ForegroundColor DarkGray
                    return
                } catch {
                    Remove-Item (Join-Path $TempFolder "dana-scan.svg") -Force -ErrorAction SilentlyContinue
                }
            }
        } catch {}
    }

    throw "Logo DANA resmi tidak berhasil diambil dari domain DANA."
}

Step "[1/6] Backup footer saat ini ..."
New-Item -ItemType Directory -Path "$backupDir\resources\views\partials\frontend" -Force | Out-Null
Copy-Item $footer "$backupDir\resources\views\partials\frontend\footer.blade.php" -Force
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/6] Download dan validasi 3 logo ..."
New-Item -ItemType Directory -Path $assetDir -Force | Out-Null
New-Item -ItemType Directory -Path $tempDir -Force | Out-Null

try {
    # BCA: Brand Assets resmi BCA - versi putih agar terbaca pada badge gelap ASLI.
    $bcaUrl = "https://www.bca.co.id/-/media/Feature/Card/List-Card/Tentang-BCA/Brand-Assets/Logo-BCA/Logo-BCA_Putih.png"
    $bcaTmp = Join-Path $tempDir "bca.png"
    Download-File -Uri $bcaUrl -OutFile $bcaTmp
    Assert-Png $bcaTmp

    $bcaFinal = Join-Path $assetDir "bca.png"
    Move-Item $bcaTmp $bcaFinal -Force
    Write-Host "  BCA  : $bcaUrl" -ForegroundColor DarkGray

    # BRI: logo korporat 2025. File ini adalah reproduksi vektor logo resmi
    # yang sumbernya berasal dari materi Investor Relations BRI.
    $briUrl = "https://upload.wikimedia.org/wikipedia/commons/5/59/BRI_2025.svg"
    $briTmp = Join-Path $tempDir "bri.svg"
    Download-File -Uri $briUrl -OutFile $briTmp
    Assert-Svg $briTmp

    $briFinal = Join-Path $assetDir "bri.svg"
    Move-Item $briTmp $briFinal -Force
    Write-Host "  BRI  : $briUrl" -ForegroundColor DarkGray

    # DANA: asset dari domain DANA resmi.
    $danaFinal = Join-Path $assetDir "dana.svg"
    Download-DanaLogo -Destination $danaFinal -TempFolder $tempDir

    foreach ($f in @($bcaFinal, $briFinal, $danaFinal)) {
        if (-not (Test-Path $f) -or (Get-Item $f).Length -lt 200) {
            throw "Asset logo tidak valid: $f"
        }
    }

    Step "[3/6] Kembalikan ukuran dan warna badge PERSIS seperti sebelum diubah ..."

    $phpPatch = @'
<?php

$path = $argv[1];
$text = file_get_contents($path);

if ($text === false) {
    throw new RuntimeException('Gagal membaca footer.blade.php');
}

$new = <<<'BLADE'
            {{-- Badge metode pembayaran -- ukuran + warna dipertahankan seperti versi awal --}}
            <div class="flex items-center gap-1.5">
                <span class="flex h-5 w-8 items-center justify-center overflow-hidden rounded-sm bg-[#4A4A4A]">
                    <img src="{{ asset('images/payment-official/bca.png') }}" alt="BCA" class="block max-h-[14px] max-w-[27px] object-contain">
                </span>
                <span class="flex h-5 w-8 items-center justify-center overflow-hidden rounded-sm bg-[#4A4A4A]">
                    <img src="{{ asset('images/payment-official/bri.svg') }}" alt="Bank BRI" class="block max-h-[14px] max-w-[27px] object-contain">
                </span>
                <span class="flex h-5 w-8 items-center justify-center overflow-hidden rounded-sm bg-[#4A4A4A]">
                    <img src="{{ asset('images/payment-official/dana.svg') }}" alt="DANA" class="block max-h-[14px] max-w-[27px] object-contain">
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
        throw new RuntimeException('Komentar area metode pembayaran tidak ditemukan.');
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
        throw new RuntimeException('Akhir container pembayaran tidak ditemukan.');
    }

    return [$start, $end];
}

[$start, $end] = locatePaymentBlock($text);
$oldBlock = substr($text, $start, $end - $start);

$signals = 0;
foreach (['visa','master','apple','paypal','bca','bri','dana','payment','fa-cc','fa-pay'] as $signal) {
    if (stripos($oldBlock, $signal) !== false) {
        $signals++;
    }
}

if ($signals < 1) {
    throw new RuntimeException('Safety check gagal: blok pembayaran tidak dikenali.');
}

$text = substr($text, 0, $start).$new.substr($text, $end);

if (file_put_contents($path, $text) === false) {
    throw new RuntimeException('Gagal menulis footer.');
}

echo "PAYMENT_V3_OK\n";
'@

    $tmpPatch = Join-Path $tempDir "patch-payment-v3.php"
    [System.IO.File]::WriteAllText($tmpPatch, $phpPatch, $utf8NoBom)

    php $tmpPatch (Resolve-Path $footer)

    if ($LASTEXITCODE -ne 0) {
        throw "Patch footer gagal."
    }

    Step "[4/6] Validasi file logo ..."
    Assert-Png $bcaFinal
    Assert-Svg $briFinal
    Assert-Svg $danaFinal

    Write-Host "  - BCA valid" -ForegroundColor Green
    Write-Host "  - BRI valid" -ForegroundColor Green
    Write-Host "  - DANA valid" -ForegroundColor Green

    Step "[5/6] Validasi Blade ..."
    php -l $footer | Out-Host

    if ($LASTEXITCODE -ne 0) {
        throw "Syntax footer bermasalah. Backup: $backupDir"
    }

    Step "[6/6] Clear cache ..."
    php artisan optimize:clear | Out-Host

    Write-Host ""
    Write-Host "SELESAI" -ForegroundColor Green
    Write-Host ""
    Write-Host "Yang dipulihkan PERSIS dari tampilan awal:" -ForegroundColor Yellow
    Write-Host "  - tinggi badge : h-5" -ForegroundColor White
    Write-Host "  - lebar badge  : w-8" -ForegroundColor White
    Write-Host "  - jarak         : gap-1.5" -ForegroundColor White
    Write-Host "  - warna badge   : #4A4A4A" -ForegroundColor White
    Write-Host "  - rounded       : rounded-sm" -ForegroundColor White
    Write-Host ""
    Write-Host "Yang berubah hanya isi 4 logo lama menjadi 3 logo: BCA / BRI / DANA." -ForegroundColor White
    Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
}
finally {
    Remove-Item $tempDir -Recurse -Force -ErrorAction SilentlyContinue
}
