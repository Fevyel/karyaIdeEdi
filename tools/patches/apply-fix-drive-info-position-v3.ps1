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
$backup = "$file.bak-fix-drive-info-v3-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "=============================================================" -ForegroundColor Yellow
Write-Host " Career - Rapikan Info Google Drive (Robust v3)" -ForegroundColor Yellow
Write-Host "=============================================================" -ForegroundColor Yellow

Step "[1/4] Membuat backup ..."
Copy-Item $file $backup -Force
Write-Host "  Backup: $backup" -ForegroundColor DarkGray

Step "[2/4] Mencari blok info Google Drive berdasarkan isi teks ..."

$content = [System.IO.File]::ReadAllText((Resolve-Path $file))

$pattern = '(?s)<div\b[^>]*>\s*<span\b[^>]*>\s*<i\b[^>]*fa-info[^>]*></i>\s*</span>\s*<p\b[^>]*>\s*Pastikan folder atau file Google Drive dapat dilihat oleh siapa saja yang memiliki link\.\s*</p>\s*</div>'

$replacement = @'
<div
    class="mt-3 w-full rounded-xl bg-[#F7F0E8]"
    style="
        display:flex !important;
        align-items:flex-start !important;
        gap:8px !important;
        padding:9px 11px !important;
        min-height:auto !important;
        height:auto !important;
        font-size:12px !important;
        line-height:1.45 !important;
        color:#7B6A5C !important;
    "
>
    <span
        aria-hidden="true"
        style="
            display:inline-flex !important;
            align-items:center !important;
            justify-content:center !important;
            flex:0 0 18px !important;
            width:18px !important;
            height:18px !important;
            min-width:18px !important;
            min-height:18px !important;
            margin:1px 0 0 0 !important;
            padding:0 !important;
            border:0 !important;
            border-radius:9999px !important;
            background:#B87536 !important;
            color:#fff !important;
            font-size:9px !important;
            line-height:1 !important;
        "
    >
        <i
            class="fa-solid fa-info"
            style="
                display:block !important;
                margin:0 !important;
                padding:0 !important;
                font-size:9px !important;
                line-height:1 !important;
            "
        ></i>
    </span>

    <p
        style="
            flex:1 1 auto !important;
            min-width:0 !important;
            margin:0 !important;
            padding:0 !important;
            font-size:12px !important;
            line-height:1.45 !important;
            color:#7B6A5C !important;
            white-space:normal !important;
            overflow-wrap:anywhere !important;
        "
    >
        Pastikan folder atau file Google Drive dapat dilihat oleh siapa saja yang memiliki link.
    </p>
</div>
'@

$matches = [regex]::Matches($content, $pattern)

if ($matches.Count -eq 0) {
    # Fallback: cari kalimatnya, supaya kita tahu file memang punya bagian yang dimaksud.
    if ($content.Contains('Pastikan folder atau file Google Drive dapat dilihat oleh siapa saja yang memiliki link.')) {
        throw "Kalimat info Google Drive ditemukan, tetapi struktur HTML-nya berbeda. Kirim output command Get-Content yang saya berikan setelah ini."
    }

    throw "Kalimat info Google Drive tidak ditemukan di file."
}

if ($matches.Count -gt 1) {
    throw "Ditemukan lebih dari 1 blok info Google Drive. Berhenti agar tidak mengubah blok yang salah."
}

$content = [regex]::Replace($content, $pattern, $replacement.Trim(), 1)

[System.IO.File]::WriteAllText((Resolve-Path $file), $content, $utf8NoBom)

Write-Host "  - Blok info Google Drive ditemukan dan dirapikan." -ForegroundColor Green

Step "[3/4] Validasi syntax ..."
php -l $file | Out-Host
if ($LASTEXITCODE -ne 0) {
    throw "Syntax form bermasalah. Restore dari backup: $backup"
}

Step "[4/4] Bersihkan cache ..."
php artisan view:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host "Info Google Drive sekarang compact dan sejajar." -ForegroundColor White
Write-Host "Backup: $backup" -ForegroundColor DarkGray
