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
$backup = "$file.bak-fix-drive-card-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "=============================================================" -ForegroundColor Yellow
Write-Host " Career - Rapikan Card Google Drive" -ForegroundColor Yellow
Write-Host "=============================================================" -ForegroundColor Yellow

Step "[1/4] Membuat backup ..."
Copy-Item $file $backup -Force
Write-Host "  Backup: $backup" -ForegroundColor DarkGray

Step "[2/4] Mengganti tampilan Google Drive ..."

$content = [System.IO.File]::ReadAllText((Resolve-Path $file))

$pattern = '(?s)\{\{-- FULL WIDTH GOOGLE DRIVE --\}\}\s*<div class="rounded-3xl border border-\[#E0D4C6\].*?@error\(''portfolio_url''\)<p.*?@enderror\s*</div>'

$newBlock = @'
{{-- FULL WIDTH GOOGLE DRIVE --}}
<div
    class="rounded-3xl border border-[#E0D4C6] bg-[#FCFAF7] p-5 sm:p-6"
    style="font-size:14px; line-height:1.5;"
>
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="flex min-w-0 items-center gap-3">
            <span
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#A96D37] text-white"
                style="font-size:15px;"
            >
                <i class="fa-brands fa-google-drive"></i>
            </span>

            <div class="min-w-0">
                <p class="text-sm font-semibold text-[#493224]" style="font-size:14px; line-height:1.4;">
                    Kirim lewat Google Drive
                </p>
                <p class="mt-0.5 text-[11px] text-[#8A7B6D]" style="font-size:11px; line-height:1.5;">
                    Alternatif upload langsung. Satu link wajib berisi CV dan portofolio.
                </p>
            </div>
        </div>

        <span
            class="inline-flex w-fit shrink-0 rounded-full bg-[#F2E5D5] px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-[#9B6A3C]"
            style="font-size:10px; line-height:1;"
        >
            CV + Portofolio
        </span>
    </div>

    <div class="mt-4">
        <label
            for="portfolio_url"
            class="mb-1.5 block text-xs font-semibold text-[#6F5845]"
            style="font-size:12px; line-height:1.4;"
        >
            Link Google Drive
        </label>

        <input
            id="portfolio_url"
            name="portfolio_url"
            type="url"
            value="{{ old('portfolio_url') }}"
            placeholder="https://drive.google.com/..."
            class="w-full rounded-2xl border border-[#DED1C1] bg-white px-4 py-3 text-sm text-[#493224] outline-none transition focus:border-[#A96D37] focus:ring-2 focus:ring-[#A96D37]/15"
            style="font-size:14px; line-height:1.5; min-height:48px;"
        >
    </div>

    <div
        class="mt-3 flex items-start gap-2 rounded-2xl bg-[#F7F0E8] px-3.5 py-3 text-[#7B6A5C]"
        style="font-size:11px; line-height:1.55;"
    >
        <span
            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#B87536] text-white"
            style="font-size:9px;"
        >
            <i class="fa-solid fa-info"></i>
        </span>

        <p class="min-w-0 break-words">
            Pastikan folder atau file Google Drive dapat dilihat oleh siapa saja yang memiliki link.
        </p>
    </div>

    @error('portfolio_url')
        <p class="mt-2 text-xs font-medium text-red-600" style="font-size:12px; line-height:1.4;">
            {{ $message }}
        </p>
    @enderror
</div>
'@

if ([regex]::IsMatch($content, $pattern)) {
    $content = [regex]::Replace($content, $pattern, $newBlock.Trim(), 1)
    Write-Host "  - Card Google Drive dirapikan." -ForegroundColor Green
} else {
    throw "Blok Google Drive tidak ditemukan dengan aman. Tidak ada perubahan dilakukan."
}

[System.IO.File]::WriteAllText((Resolve-Path $file), $content, $utf8NoBom)

Step "[3/4] Validasi syntax ..."
php -l $file | Out-Host
if ($LASTEXITCODE -ne 0) {
    throw "Syntax form bermasalah. Restore backup: $backup"
}

Step "[4/4] Bersihkan cache ..."
php artisan view:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host "Perbaikan:" -ForegroundColor Yellow
Write-Host "  - Ukuran placeholder/input Google Drive dinormalkan." -ForegroundColor White
Write-Host "  - Teks informasi diperkecil dan dibuat wrap rapi." -ForegroundColor White
Write-Host "  - Ikon info diperkecil dan disejajarkan." -ForegroundColor White
Write-Host "  - Card tetap full width di bawah CV + Portofolio." -ForegroundColor White
Write-Host "Backup: $backup" -ForegroundColor DarkGray
