$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script ini dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$file = ".\app\Models\JobVacancy.php"

if (-not (Test-Path $file)) {
    throw "File tidak ditemukan: $file"
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backup = "$file.bak-carbon-selection-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "=============================================================" -ForegroundColor Yellow
Write-Host " Fix JobVacancy CarbonImmutable / selectionStartDate" -ForegroundColor Yellow
Write-Host "=============================================================" -ForegroundColor Yellow

Step "[1/4] Membuat backup ..."
Copy-Item $file $backup -Force
Write-Host "  Backup: $backup" -ForegroundColor DarkGray

Step "[2/4] Memperbaiki return type selectionStartDate() ..."

$content = [System.IO.File]::ReadAllText((Resolve-Path $file))

# Laravel/PHP pada project ini mengembalikan Carbon\CarbonImmutable untuk cast date.
# Gunakan CarbonInterface agar kompatibel dengan Carbon maupun CarbonImmutable.
if ($content.Contains('use Illuminate\Support\Carbon;')) {
    $content = $content.Replace(
        'use Illuminate\Support\Carbon;',
        'use Carbon\CarbonInterface;'
    )
    Write-Host "  - Import Carbon konkret diganti menjadi CarbonInterface." -ForegroundColor Green
} elseif (-not $content.Contains('use Carbon\CarbonInterface;')) {
    # Sisipkan import setelah namespace apabila import Carbon lama tidak ada.
    $namespace = "namespace App\Models;"
    if (-not $content.Contains($namespace)) {
        throw "Namespace App\Models tidak ditemukan."
    }

    $content = $content.Replace(
        $namespace,
        $namespace + "`r`n`r`nuse Carbon\CarbonInterface;"
    )
    Write-Host "  - Import CarbonInterface ditambahkan." -ForegroundColor Green
}

$oldSignature = 'public function selectionStartDate(): ?Carbon'
$newSignature = 'public function selectionStartDate(): ?CarbonInterface'

if ($content.Contains($oldSignature)) {
    $content = $content.Replace($oldSignature, $newSignature)
    Write-Host "  - Return type selectionStartDate() sekarang CarbonInterface." -ForegroundColor Green
} elseif ($content.Contains($newSignature)) {
    Write-Host "  - Return type sudah benar, dilewati." -ForegroundColor DarkGray
} else {
    throw "Signature selectionStartDate() tidak ditemukan. Tidak ada perubahan lebih lanjut dilakukan."
}

[System.IO.File]::WriteAllText((Resolve-Path $file), $content, $utf8NoBom)

Step "[3/4] Validasi syntax ..."
php -l $file | Out-Host

if ($LASTEXITCODE -ne 0) {
    throw "Syntax error setelah patch. Restore dari: $backup"
}

Step "[4/4] Membersihkan cache Laravel ..."
php artisan optimize:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host "selectionStartDate() sekarang menerima Carbon maupun CarbonImmutable." -ForegroundColor White
Write-Host "Backup: $backup" -ForegroundColor DarkGray
