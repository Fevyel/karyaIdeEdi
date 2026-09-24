$ErrorActionPreference = 'Stop'

function Step($text) { Write-Host "`n$text" -ForegroundColor Cyan }

if (-not (Test-Path '.\artisan')) {
    throw "Jalankan script ini dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$file = '.\resources\views\pages\frontend\booking.blade.php'
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = "$file.bak-hero-text-$stamp"

Write-Host '=============================================================' -ForegroundColor Yellow
Write-Host ' Perbesar Teks Hero Dokumentasi' -ForegroundColor Yellow
Write-Host '=============================================================' -ForegroundColor Yellow

Step '[1/4] Backup file ...'
Copy-Item $file $backup -Force
Write-Host "  Backup: $backup" -ForegroundColor DarkGray

Step '[2/4] Menyesuaikan ukuran tipografi hero ...'
$content = Get-Content $file -Raw
$changed = 0

$replacements = @(
    @(
        'class="font-display text-3xl font-semibold leading-[1.15] text-[#3D2B1F] sm:text-4xl lg:text-[2.5rem] xl:text-[2.75rem]"',
        'class="font-display text-4xl font-semibold leading-[1.08] text-[#3D2B1F] sm:text-5xl lg:text-6xl"'
    ),
    @(
        'class="mt-3 font-display text-xl italic text-[#9B6E3E] sm:text-2xl"',
        'class="mt-4 font-display text-2xl italic leading-snug text-[#9B6E3E] sm:text-3xl lg:text-4xl"'
    ),
    @(
        'class="mt-6 max-w-md text-sm leading-relaxed text-[#6B6E76]"',
        'class="mt-7 max-w-2xl text-base leading-8 text-[#6B6E76] sm:text-lg lg:text-xl"'
    )
)

foreach ($pair in $replacements) {
    if ($content.Contains($pair[0])) {
        $content = $content.Replace($pair[0], $pair[1])
        $changed++
    }
}

if ($changed -eq 0) {
    throw 'Class hero Dokumentasi yang dicari tidak ditemukan. Tidak ada perubahan dilakukan.'
}

Set-Content -Path $file -Value $content -Encoding UTF8
Write-Host "  $changed bagian tipografi diperbarui." -ForegroundColor Green

Step '[3/4] Validasi syntax ...'
php -l $file | Out-Host

Step '[4/4] Bersihkan cache view ...'
php artisan view:clear | Out-Host

Write-Host "`nSELESAI" -ForegroundColor Green
Write-Host 'Yang diubah hanya ukuran teks hero Dokumentasi. Gambar/video hero dan section lain tidak disentuh.' -ForegroundColor Green
Write-Host 'Lanjutkan dengan: npm run build' -ForegroundColor Yellow
