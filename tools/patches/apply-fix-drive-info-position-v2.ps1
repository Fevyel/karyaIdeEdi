$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script ini dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$file = ".\resources\views\pages\frontend\lamar-kerja.blade.php"

if (-not (Test-Path $file)) {
    throw "File tidak ditemukan: $file"
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backup = "$file.bak-fix-drive-info-position-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "=============================================================" -ForegroundColor Yellow
Write-Host " Career - Rapikan Posisi Info Google Drive" -ForegroundColor Yellow
Write-Host "=============================================================" -ForegroundColor Yellow

Step "[1/4] Membuat backup ..."
Copy-Item $file $backup -Force
Write-Host "  Backup: $backup" -ForegroundColor DarkGray

Step "[2/4] Merapikan posisi icon + teks info Google Drive ..."

$content = [System.IO.File]::ReadAllText((Resolve-Path $file))

$pattern = '(?s)<div\s+class="mt-3 flex items-start gap-2 rounded-2xl bg-\[#F7F0E8\] px-3\.5 py-3 text-\[#7B6A5C\]"\s+style="font-size:11px; line-height:1\.55;">.*?</div>\s*@error\(''portfolio_url''\)'

$replacement = @'
<div
    class="mt-3 w-full rounded-2xl bg-[#F7F0E8]"
    style="
        display:flex !important;
        align-items:flex-start !important;
        gap:10px !important;
        padding:10px 12px !important;
        border-radius:14px !important;
        font-size:12px !important;
        line-height:1.55 !important;
        color:#7B6A5C !important;
        min-height:0 !important;
    "
>
    <span
        style="
            display:inline-flex !important;
            align-items:center !important;
            justify-content:center !important;
            flex:0 0 18px !important;
            width:18px !important;
            height:18px !important;
            min-width:18px !important;
            min-height:18px !important;
            margin-top:1px !important;
            padding:0 !important;
            border-radius:9999px !important;
            background:#B87536 !important;
            color:#ffffff !important;
            font-size:9px !important;
            line-height:1 !important;
        "
    >
        <i class="fa-solid fa-info" style="font-size:9px !important; line-height:1 !important;"></i>
    </span>

    <p
        style="
            margin:0 !important;
            padding:0 !important;
            min-width:0 !important;
            flex:1 1 auto !important;
            font-size:12px !important;
            line-height:1.55 !important;
            color:#7B6A5C !important;
            overflow-wrap:anywhere !important;
        "
    >
        Pastikan folder atau file Google Drive dapat dilihat oleh siapa saja yang memiliki link.
    </p>
</div>

@error('portfolio_url')
'@

if ([regex]::IsMatch($content, $pattern)) {
    $content = [regex]::Replace($content, $pattern, $replacement.TrimEnd(), 1)
    Write-Host "  - Posisi info Google Drive sudah dirapikan." -ForegroundColor Green
} else {
    throw "Blok info Google Drive tidak ditemukan persis. Tidak ada perubahan dilakukan."
}

[System.IO.File]::WriteAllText((Resolve-Path $file), $content, $utf8NoBom)

Step "[3/4] Validasi syntax ..."
php -l $file | Out-Host
if ($LASTEXITCODE -ne 0) {
    throw "Syntax form bermasalah. Restore dari backup: $backup"
}

Step "[4/4] Bersihkan cache ..."
php artisan view:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host "Info Google Drive sekarang:" -ForegroundColor Yellow
Write-Host "  - icon kecil dan sejajar dengan baris pertama teks" -ForegroundColor White
Write-Host "  - teks 12px, tidak jumbo" -ForegroundColor White
Write-Host "  - padding compact" -ForegroundColor White
Write-Host "  - posisi stabil walaupun ada CSS global lain" -ForegroundColor White
Write-Host "Backup: $backup" -ForegroundColor DarkGray
