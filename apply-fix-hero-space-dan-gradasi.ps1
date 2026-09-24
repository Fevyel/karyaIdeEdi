# apply-fix-hero-space-dan-gradasi.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   powershell -ExecutionPolicy Bypass -File apply-fix-hero-space-dan-gradasi.ps1
#
# Yang dilakukan:
# 1) navbar.blade.php -> hapus box-shadow di <header> (penyebab "space kecil" di bawah navbar)
# 2) hero.blade.php    -> gradasi foto dibuat transparan di bagian atas (foto tidak ketutup),
#                         lalu tambah text-shadow di judul & deskripsi biar tetap kebaca jelas.

$ErrorActionPreference = "Stop"

$navbarPath = "resources/views/partials/frontend/navbar.blade.php"
$heroPath   = "resources/views/partials/frontend/hero.blade.php"

if (-not (Test-Path $navbarPath)) { throw "Tidak ketemu: $navbarPath (jalankan script ini dari root project)" }
if (-not (Test-Path $heroPath))   { throw "Tidak ketemu: $heroPath (jalankan script ini dari root project)" }

# Backup dulu (sesuai kebiasaan project ini)
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
Copy-Item $navbarPath "$navbarPath.bak-before-fix-hero-space-$stamp"
Copy-Item $heroPath   "$heroPath.bak-before-fix-hero-space-$stamp"

# ---------- 1. navbar.blade.php ----------
$navbar = Get-Content $navbarPath -Raw -Encoding UTF8

$oldHeader = '<header class="sticky top-0 z-100 border-b border-admin-border/80 bg-admin-surface/95 shadow-[0_4px_20px_-10px_rgba(34,26,20,0.22)] backdrop-blur-md">'
$newHeader = '<header class="sticky top-0 z-100 border-b border-admin-border/80 bg-admin-surface/95 backdrop-blur-md">'

if ($navbar -notmatch [regex]::Escape($oldHeader)) {
    throw "Baris <header> di navbar.blade.php tidak cocok persis (mungkin sudah pernah diubah). Tidak ada yang ditimpa, cek manual."
}
$navbar = $navbar.Replace($oldHeader, $newHeader)
Set-Content $navbarPath -Value $navbar -Encoding UTF8 -NoNewline

# ---------- 2. hero.blade.php ----------
$hero = Get-Content $heroPath -Raw -Encoding UTF8

$oldGradient = 'style="background: linear-gradient(180deg, color-mix(in oklab, {{ $heroBgColor }} 8%, transparent) 0%, color-mix(in oklab, {{ $heroBgColor }} 22%, transparent) 35%, color-mix(in oklab, {{ $heroBgColor }} 55%, transparent) 70%, {{ $heroBgColor }} 100%);"'
$newGradient = 'style="background: linear-gradient(180deg, transparent 0%, transparent 40%, color-mix(in oklab, {{ $heroBgColor }} 35%, transparent) 65%, color-mix(in oklab, {{ $heroBgColor }} 70%, transparent) 85%, {{ $heroBgColor }} 100%);"'

$oldH1 = 'style="color: {{ $heroHeadingColor }};"'
$newH1 = 'style="color: {{ $heroHeadingColor }}; text-shadow: 0 2px 10px rgba(0,0,0,0.35);"'

$oldP = 'style="color: {{ $heroTextColor }};"'
$newP = 'style="color: {{ $heroTextColor }}; text-shadow: 0 1px 6px rgba(0,0,0,0.25);"'

if ($hero -notmatch [regex]::Escape($oldGradient)) {
    throw "Blok gradient di hero.blade.php tidak cocok persis (mungkin sudah pernah diubah). Tidak ada yang ditimpa, cek manual."
}
$hero = $hero.Replace($oldGradient, $newGradient)

# heroHeadingColor & heroTextColor cuma dipakai 1x masing-masing di file ini (di h1 & p),
# jadi aman diganti langsung.
if ($hero -notmatch [regex]::Escape($oldH1)) {
    throw "Style heroHeadingColor di <h1> tidak cocok persis. Tidak ada yang ditimpa, cek manual."
}
$hero = $hero.Replace($oldH1, $newH1)

if ($hero -notmatch [regex]::Escape($oldP)) {
    throw "Style heroTextColor di <p> tidak cocok persis. Tidak ada yang ditimpa, cek manual."
}
$hero = $hero.Replace($oldP, $newP)

Set-Content $heroPath -Value $hero -Encoding UTF8 -NoNewline

Write-Host ""
Write-Host "Selesai." -ForegroundColor Green
Write-Host "Diubah: $navbarPath"
Write-Host "Diubah: $heroPath"
Write-Host "Backup asli disimpan sebagai *.bak-before-fix-hero-space-$stamp"
Write-Host ""
Write-Host "Kalau perubahan belum kelihatan di browser, jalankan: php artisan view:clear"
