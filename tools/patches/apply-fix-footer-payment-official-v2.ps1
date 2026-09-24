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
$backupDir = ".backup-fix-payment-official-v2-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "====================================================================" -ForegroundColor Yellow
Write-Host " Footer Payment v2 - Official Assets + Restore Ukuran/Warna Lama" -ForegroundColor Yellow
Write-Host "====================================================================" -ForegroundColor Yellow

function Get-OldFooterSource {
    $candidates = @()

    $dirBackups = Get-ChildItem "." -Directory -Filter ".backup-footer-payment-logos-*" -ErrorAction SilentlyContinue |
        Sort-Object LastWriteTime -Descending

    foreach ($dir in $dirBackups) {
        $candidate = Join-Path $dir.FullName "resources\views\partials\frontend\footer.blade.php"
        if (Test-Path $candidate) {
            $candidates += Get-Item $candidate
        }
    }

    $fileBackups = Get-ChildItem ".\resources\views\partials\frontend" -File -Filter "footer.blade.php.bak-*" -ErrorAction SilentlyContinue |
        Sort-Object LastWriteTime -Descending

    $candidates += $fileBackups

    foreach ($candidate in $candidates) {
        try {
            $text = [System.IO.File]::ReadAllText($candidate.FullName)
            $lower = $text.ToLowerInvariant()

            $hasOldPayment =
                ($lower.Contains("badge metode pembayaran") -or $lower.Contains("metode pembayaran")) -and
                (
                    $lower.Contains("visa") -or
                    $lower.Contains("mastercard") -or
                    $lower.Contains("apple-pay") -or
                    $lower.Contains("paypal") -or
                    $lower.Contains("fa-cc-visa")
                )

            if ($hasOldPayment) {
                return $candidate.FullName
            }
        } catch {
        }
    }

    return $null
}

function Invoke-Download {
    param(
        [Parameter(Mandatory=$true)][string]$Uri,
        [Parameter(Mandatory=$true)][string]$OutFile
    )

    $params = @{
        Uri = $Uri
        OutFile = $OutFile
        MaximumRedirection = 8
        TimeoutSec = 45
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
        throw "File hasil download terlalu kecil / tidak valid: $Uri"
    }
}

function Get-ImageExtension {
    param([Parameter(Mandatory=$true)][string]$Path)

    $bytes = [System.IO.File]::ReadAllBytes((Resolve-Path $Path))

    if ($bytes.Length -ge 8 -and
        $bytes[0] -eq 0x89 -and
        $bytes[1] -eq 0x50 -and
        $bytes[2] -eq 0x4E -and
        $bytes[3] -eq 0x47) {
        return ".png"
    }

    if ($bytes.Length -ge 3 -and
        $bytes[0] -eq 0xFF -and
        $bytes[1] -eq 0xD8 -and
        $bytes[2] -eq 0xFF) {
        return ".jpg"
    }

    if ($bytes.Length -ge 12) {
        $head12 = [System.Text.Encoding]::ASCII.GetString($bytes, 0, 12)
        if ($head12.StartsWith("RIFF") -and $head12.Substring(8,4) -eq "WEBP") {
            return ".webp"
        }
    }

    $sampleLength = [Math]::Min($bytes.Length, 2048)
    $sample = [System.Text.Encoding]::UTF8.GetString($bytes, 0, $sampleLength)

    if ($sample -match '(?is)<svg\b') {
        return ".svg"
    }

    return $null
}

function Save-DetectedImage {
    param(
        [Parameter(Mandatory=$true)][string]$TempFile,
        [Parameter(Mandatory=$true)][string]$BaseName,
        [Parameter(Mandatory=$true)][string]$Directory
    )

    $ext = Get-ImageExtension -Path $TempFile

    if (-not $ext) {
        throw "Format gambar tidak dikenali: $TempFile"
    }

    $final = Join-Path $Directory ($BaseName + $ext)

    Get-ChildItem $Directory -File -ErrorAction SilentlyContinue |
        Where-Object { $_.BaseName -eq $BaseName } |
        Remove-Item -Force -ErrorAction SilentlyContinue

    Move-Item $TempFile $final -Force

    return $final
}

function Find-OfficialImageFromPage {
    param(
        [Parameter(Mandatory=$true)][string[]]$Pages,
        [Parameter(Mandatory=$true)][string]$MatchRegex,
        [Parameter(Mandatory=$true)][string[]]$AllowedHosts,
        [Parameter(Mandatory=$true)][string]$TempDir,
        [Parameter(Mandatory=$true)][string]$BaseName
    )

    foreach ($page in $Pages) {
        try {
            try {
                $response = Invoke-WebRequest -Uri $page -UseBasicParsing -TimeoutSec 45 -MaximumRedirection 8 -Headers @{"User-Agent"="Mozilla/5.0"}
            } catch {
                $response = Invoke-WebRequest -Uri $page -TimeoutSec 45 -MaximumRedirection 8 -Headers @{"User-Agent"="Mozilla/5.0"}
            }

            $html = [string]$response.Content
            if (-not $html) { continue }

            $tags = [regex]::Matches($html, '(?is)<img\b[^>]*>')

            foreach ($tagMatch in $tags) {
                $tag = $tagMatch.Value

                if ($tag -notmatch $MatchRegex) {
                    continue
                }

                $srcMatch = [regex]::Match($tag, '(?is)\b(?:src|data-src)\s*=\s*["'']([^"'']+)["'']')
                if (-not $srcMatch.Success) {
                    continue
                }

                $src = $srcMatch.Groups[1].Value.Trim()
                if (-not $src -or $src.StartsWith("data:")) {
                    continue
                }

                try {
                    $absolute = ([System.Uri]::new([System.Uri]$page, $src)).AbsoluteUri
                    $host = ([System.Uri]$absolute).Host.ToLowerInvariant()

                    $allowed = $false
                    foreach ($allowedHost in $AllowedHosts) {
                        $h = $allowedHost.ToLowerInvariant()
                        if ($host -eq $h -or $host.EndsWith("." + $h)) {
                            $allowed = $true
                            break
                        }
                    }

                    if (-not $allowed) {
                        continue
                    }

                    $temp = Join-Path $TempDir ($BaseName + "-scan.tmp")
                    Invoke-Download -Uri $absolute -OutFile $temp

                    $ext = Get-ImageExtension -Path $temp
                    if ($ext) {
                        return @{
                            Url = $absolute
                            TempFile = $temp
                        }
                    }

                    Remove-Item $temp -Force -ErrorAction SilentlyContinue
                } catch {
                    continue
                }
            }
        } catch {
            continue
        }
    }

    return $null
}

Step "[1/6] Cari backup footer sebelum logo pembayaran diubah ..."
$oldFooter = Get-OldFooterSource

if (-not $oldFooter) {
    throw @"
Backup footer asli sebelum perubahan logo pembayaran tidak ditemukan.
Patch dihentikan supaya ukuran dan warna lama TIDAK ditebak.

Cariannya:
- .backup-footer-payment-logos-*
- resources\views\partials\frontend\footer.blade.php.bak-*
"@
}

Write-Host "  Sumber gaya lama: $oldFooter" -ForegroundColor Green

Step "[2/6] Backup footer saat ini ..."
New-Item -ItemType Directory -Path "$backupDir\resources\views\partials\frontend" -Force | Out-Null
Copy-Item $footer "$backupDir\resources\views\partials\frontend\footer.blade.php" -Force
Write-Host "  Backup baru: $backupDir" -ForegroundColor DarkGray

Step "[3/6] Ambil logo dari sumber resmi ..."

$officialDir = ".\public\images\payment-official"
New-Item -ItemType Directory -Path $officialDir -Force | Out-Null

$tempDir = Join-Path $env:TEMP "kie-payment-official-$stamp"
New-Item -ItemType Directory -Path $tempDir -Force | Out-Null

try {
    # BCA: file langsung dari halaman Brand Assets resmi BCA.
    $bcaUrl = "https://www.bca.co.id/-/media/Feature/Card/List-Card/Tentang-BCA/Brand-Assets/Logo-BCA/Logo-BCA_Biru.png"
    $bcaTemp = Join-Path $tempDir "bca.tmp"
    Invoke-Download -Uri $bcaUrl -OutFile $bcaTemp
    $bcaFinal = Save-DetectedImage -TempFile $bcaTemp -BaseName "bca" -Directory $officialDir
    Write-Host "  BCA  : $bcaUrl" -ForegroundColor DarkGray

    # BRI: cari logo langsung dari website resmi BRI / Kartu Kredit BRI.
    $briFound = Find-OfficialImageFromPage `
        -Pages @(
            "https://bri.co.id/web/guest",
            "https://kartukredit.bri.co.id/web/kartukredit/services?lang=id&service=654"
        ) `
        -MatchRegex '(?i)(logo[-_ ]?bri|alt\s*=\s*["''][^"'']*\bbri\b[^"'']*["''])' `
        -AllowedHosts @("bri.co.id","kartukredit.bri.co.id") `
        -TempDir $tempDir `
        -BaseName "bri"

    if (-not $briFound) {
        throw @"
Logo BRI tidak berhasil diambil langsung dari domain resmi BRI.
Patch dihentikan sebelum footer diubah agar tidak menampilkan logo palsu/broken.
"@
    }

    $briFinal = Save-DetectedImage -TempFile $briFound.TempFile -BaseName "bri" -Directory $officialDir
    Write-Host "  BRI  : $($briFound.Url)" -ForegroundColor DarkGray

    # DANA: cari logo dari situs resmi; prioritaskan asset DANA-Logo.
    $danaFound = Find-OfficialImageFromPage `
        -Pages @(
            "https://www.dana.id/?lng=id",
            "https://www.dana.id/personal?lng=id"
        ) `
        -MatchRegex '(?i)(DANA-Logo|alt\s*=\s*["''][^"'']*\bdana\b[^"'']*["''])' `
        -AllowedHosts @("dana.id","a.m.dana.id") `
        -TempDir $tempDir `
        -BaseName "dana"

    if ($danaFound) {
        $danaFinal = Save-DetectedImage -TempFile $danaFound.TempFile -BaseName "dana" -Directory $officialDir
        Write-Host "  DANA : $($danaFound.Url)" -ForegroundColor DarkGray
    } else {
        # Fallback resmi yang digunakan oleh footer website DANA.
        $danaUrl = "https://a.m.dana.id/danaweb/v3/DANA-Logo-white.svg"
        $danaTemp = Join-Path $tempDir "dana.tmp"
        Invoke-Download -Uri $danaUrl -OutFile $danaTemp
        $danaFinal = Save-DetectedImage -TempFile $danaTemp -BaseName "dana" -Directory $officialDir
        Write-Host "  DANA : $danaUrl" -ForegroundColor DarkGray
    }

    foreach ($logo in @($bcaFinal, $briFinal, $danaFinal)) {
        if (-not (Test-Path $logo) -or (Get-Item $logo).Length -lt 200) {
            throw "Logo tidak valid: $logo"
        }
    }

    $bcaAsset = ($bcaFinal -replace '^[.\\\/]*public[\\\/]', '') -replace '\\','/'
    $briAsset = ($briFinal -replace '^[.\\\/]*public[\\\/]', '') -replace '\\','/'
    $danaAsset = ($danaFinal -replace '^[.\\\/]*public[\\\/]', '') -replace '\\','/'

    Step "[4/6] Kembalikan ukuran + warna badge PERSIS dari blok lama, lalu isi 3 logo official ..."

    $phpPatch = @'
<?php

$currentPath = $argv[1];
$oldPath = $argv[2];
$bcaAsset = $argv[3];
$briAsset = $argv[4];
$danaAsset = $argv[5];

$current = file_get_contents($currentPath);
$old = file_get_contents($oldPath);

if ($current === false || $old === false) {
    throw new RuntimeException('Gagal membaca footer.');
}

function paymentBlock(string $text): array
{
    $commentPos = stripos($text, 'Badge metode pembayaran');

    if ($commentPos === false) {
        $commentPos = stripos($text, 'metode pembayaran');
    }

    if ($commentPos === false) {
        throw new RuntimeException('Area metode pembayaran tidak ditemukan.');
    }

    $start = strpos($text, '<div', $commentPos);

    if ($start === false) {
        throw new RuntimeException('Container metode pembayaran tidak ditemukan.');
    }

    preg_match_all(
        '~<div\b[^>]*>|</div>~i',
        substr($text, $start),
        $matches,
        PREG_OFFSET_CAPTURE
    );

    $depth = 0;
    $end = null;

    foreach ($matches[0] as [$tag, $relativeOffset]) {
        $isClose = stripos($tag, '</div') === 0;

        if (!$isClose) {
            $depth++;
        } else {
            $depth--;

            if ($depth === 0) {
                $end = $start + $relativeOffset + strlen($tag);
                break;
            }
        }
    }

    if ($end === null) {
        throw new RuntimeException('Penutup blok metode pembayaran tidak ditemukan.');
    }

    return [
        'start' => $start,
        'end' => $end,
        'block' => substr($text, $start, $end - $start),
    ];
}

function openingTags(string $block): array
{
    preg_match_all('~<div\b[^>]*>|</div>~i', $block, $matches);

    $depth = 0;
    $rootOpen = null;
    $firstChildOpen = null;

    foreach ($matches[0] as $tag) {
        $isClose = stripos($tag, '</div') === 0;

        if (!$isClose) {
            $depth++;

            if ($depth === 1 && $rootOpen === null) {
                $rootOpen = $tag;
            }

            if ($depth === 2 && $firstChildOpen === null) {
                $firstChildOpen = $tag;
            }
        } else {
            $depth--;
        }
    }

    if (!$rootOpen || !$firstChildOpen) {
        throw new RuntimeException('Struktur badge lama tidak dapat dibaca.');
    }

    return [$rootOpen, $firstChildOpen];
}

$currentInfo = paymentBlock($current);
$oldInfo = paymentBlock($old);

[$rootOpen, $badgeOpen] = openingTags($oldInfo['block']);

$logos = [
    ['asset' => $bcaAsset, 'alt' => 'BCA'],
    ['asset' => $briAsset, 'alt' => 'Bank BRI'],
    ['asset' => $danaAsset, 'alt' => 'DANA'],
];

$new = $rootOpen.PHP_EOL;

foreach ($logos as $logo) {
    $new .= '                '.$badgeOpen.PHP_EOL;
    $new .= '                    <img src="{{ asset(\''.$logo['asset'].'\') }}" alt="'.$logo['alt'].'" style="display:block;max-height:24px;max-width:76px;width:auto;height:auto;object-fit:contain;">'.PHP_EOL;
    $new .= '                </div>'.PHP_EOL;
}

$new .= '            </div>';

$current = substr($current, 0, $currentInfo['start'])
    .$new
    .substr($current, $currentInfo['end']);

if (file_put_contents($currentPath, $current) === false) {
    throw new RuntimeException('Gagal menulis footer.');
}

echo "FOOTER_PAYMENT_V2_OK\n";
'@

    $tmpPatch = Join-Path $tempDir "patch-payment-v2.php"
    [System.IO.File]::WriteAllText($tmpPatch, $phpPatch, $utf8NoBom)

    php $tmpPatch (Resolve-Path $footer) (Resolve-Path $oldFooter) $bcaAsset $briAsset $danaAsset

    if ($LASTEXITCODE -ne 0) {
        throw "Gagal menambal footer."
    }

    Step "[5/6] Validasi ..."
    php -l $footer | Out-Host

    if ($LASTEXITCODE -ne 0) {
        throw "Syntax footer bermasalah. Backup: $backupDir"
    }

    foreach ($asset in @($bcaAsset, $briAsset, $danaAsset)) {
        $full = Join-Path ".\public" ($asset -replace '/', '\')
        if (-not (Test-Path $full)) {
            throw "Asset footer tidak ditemukan setelah patch: $full"
        }
    }

    Step "[6/6] Clear cache ..."
    php artisan optimize:clear | Out-Host

    Write-Host ""
    Write-Host "SELESAI" -ForegroundColor Green
    Write-Host ""
    Write-Host "Perbaikan:" -ForegroundColor Yellow
    Write-Host "  - tidak ada lagi gambar broken karena logo di-download dulu lalu diverifikasi" -ForegroundColor White
    Write-Host "  - BCA diambil dari Brand Assets resmi BCA" -ForegroundColor White
    Write-Host "  - BRI diambil dari domain resmi BRI" -ForegroundColor White
    Write-Host "  - DANA diambil dari domain resmi DANA" -ForegroundColor White
    Write-Host "  - ukuran dan warna BADGE diambil dari footer lama, bukan dibuat ulang" -ForegroundColor White
    Write-Host "  - hanya jumlah badge berubah dari 4 menjadi 3" -ForegroundColor White
    Write-Host ""
    Write-Host "Backup footer saat ini: $backupDir" -ForegroundColor DarkGray
    Write-Host "Sumber gaya lama      : $oldFooter" -ForegroundColor DarkGray
}
finally {
    Remove-Item $tempDir -Recurse -Force -ErrorAction SilentlyContinue
}
