$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script ini dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$file = ".\resources\views\pages\frontend\pengrajin.blade.php"
if (-not (Test-Path $file)) {
    throw "File tidak ditemukan: $file"
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backup = "$file.bak-clean-hero-$stamp"

Write-Host "=============================================================" -ForegroundColor Yellow
Write-Host " Our Craftsmen - Hapus 01 Crafted + Garis Bawah Hero" -ForegroundColor Yellow
Write-Host "=============================================================" -ForegroundColor Yellow

Step "[1/4] Backup file ..."
Copy-Item $file $backup -Force
Write-Host "  Backup: $backup" -ForegroundColor DarkGray

Step "[2/4] Membersihkan hero Our Craftsmen ..."
$content = Get-Content $file -Raw

# Hapus kolom kanan: 01 / Crafted by People / deskripsi.
$patternRight = '(?s)\s*<div class="mt-12 hidden lg:block">\s*<div class="ml-auto w-64 border-l pl-8".*?</div>\s*</div>\s*(?=</div>\s*</section>)'
if ($content -match $patternRight) {
    $content = [regex]::Replace($content, $patternRight, "`r`n", 1)
    Write-Host "  - Blok 01 / Crafted by People dihapus." -ForegroundColor Green
} else {
    Write-Host "  - Blok 01 / Crafted by People tidak ditemukan; mungkin sudah terhapus." -ForegroundColor DarkGray
}

# Ubah grid hero menjadi 1 kolom agar area kanan tidak menyisakan ruang kosong.
$oldGrid = 'lg:grid-cols-[1fr_22rem] lg:gap-16'
if ($content.Contains($oldGrid)) {
    $content = $content.Replace($oldGrid, '')
    Write-Host "  - Grid hero dirapikan jadi 1 kolom." -ForegroundColor Green
}

# Hapus garis dekoratif paling bawah di hero jika masih ada.
$oldBottomLine = '<div class="absolute bottom-0 left-0 h-px w-full bg-linear-to-r from-transparent via-white/16 to-transparent"></div>'
if ($content.Contains($oldBottomLine)) {
    $content = $content.Replace($oldBottomLine, '')
    Write-Host "  - Garis dekoratif bawah hero dihapus." -ForegroundColor Green
}

# Hapus frame-seam setelah hero yang menimbulkan garis/batas antar section.
$patternSeam = "(?m)^\s*@include\('partials\.frontend\.frame-seam',\s*\['from'\s*=>\s*\$heroBase,\s*'to'\s*=>\s*'#F8F5F0'\]\)\s*\r?\n?"
if ($content -match $patternSeam) {
    $content = [regex]::Replace($content, $patternSeam, '', 1)
    Write-Host "  - Frame seam / garis pembatas bawah hero dihapus." -ForegroundColor Green
} else {
    Write-Host "  - Frame seam setelah hero tidak ditemukan; mungkin sudah terhapus." -ForegroundColor DarkGray
}

Set-Content -Path $file -Value $content -Encoding UTF8

Step "[3/4] Validasi syntax ..."
php -l $file | Out-Host

Step "[4/4] Bersihkan cache view ..."
php artisan view:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host "Yang diubah hanya:" -ForegroundColor Yellow
Write-Host "  1. Blok 01 / Crafted by People di hero dihapus." -ForegroundColor White
Write-Host "  2. Ruang kanan hero dirapikan." -ForegroundColor White
Write-Host "  3. Garis/frame pembatas di bawah hero dihapus." -ForegroundColor White
Write-Host "Bagian Pemilik & Founder dan Mulai dari Sebuah Ide tidak disentuh." -ForegroundColor DarkGray
