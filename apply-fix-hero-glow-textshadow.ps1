# apply-fix-hero-glow-textshadow.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   powershell -ExecutionPolicy Bypass -File apply-fix-hero-glow-textshadow.ps1
#
# Masalah: glow di belakang teks header dirender pakai <span> absolute +
# blur-2xl + background warna solid (kotak blur). Hasilnya jadi blob/smudge
# oval-kotak yang tidak estetik, bukan cahaya yang ngikutin bentuk teks.
#
# Perbaikan: ganti pendekatan dari "kotak blur di belakang teks" -> jadi
# "glow lewat text-shadow berlapis" (tipis + medium + lebar) langsung di
# teksnya. Efeknya ngikutin bentuk huruf persis -> terasa menyala/melayang
# tapi tetap tajam & gampang dibaca. Hanya file hero.blade.php yang disentuh,
# hanya bagian glow-nya saja -- warna latar, cropper foto, kontras teks
# otomatis, CTA, dll TIDAK diubah.

$ErrorActionPreference = "Stop"

$heroPath = "resources/views/partials/frontend/hero.blade.php"

if (-not (Test-Path $heroPath)) { throw "Tidak ketemu: $heroPath (jalankan script ini dari root project)" }

# Backup dulu (sesuai kebiasaan project ini)
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
Copy-Item $heroPath "$heroPath.bak-before-fix-hero-glow-$stamp"

$hero = Get-Content $heroPath -Raw -Encoding UTF8

# ---------- 1. Ganti nilai warna glow: dari rgba polos -> text-shadow 3 lapis ----------
$oldWhite = "        'white' => 'rgba(255,255,255,0.55)',"
$newWhite = "        'white' => '0 0 10px rgba(255,255,255,0.85), 0 0 22px rgba(255,255,255,0.45), 0 0 46px rgba(255,255,255,0.25)',"

$oldBlack = "        'black' => 'rgba(0,0,0,0.55)',"
$newBlack = "        'black' => '0 0 10px rgba(0,0,0,0.8), 0 0 22px rgba(0,0,0,0.4), 0 0 46px rgba(0,0,0,0.22)',"

if ($hero -notmatch [regex]::Escape($oldWhite)) { throw "Baris mapping 'white' glow tidak cocok persis (mungkin sudah pernah diubah). Tidak ada yang ditimpa, cek manual." }
if ($hero -notmatch [regex]::Escape($oldBlack)) { throw "Baris mapping 'black' glow tidak cocok persis (mungkin sudah pernah diubah). Tidak ada yang ditimpa, cek manual." }

$hero = $hero.Replace($oldWhite, $newWhite)
$hero = $hero.Replace($oldBlack, $newBlack)

# ---------- 2. Judul (h1) & angka statistik (dt) -- style-nya identik, sama-sama pakai $heroHeadingColor ----------
$oldHeading = 'style="color: {{ $heroHeadingColor }}; text-shadow: 0 2px 10px rgba(0,0,0,0.35);"'
$newHeading = 'style="color: {{ $heroHeadingColor }}; text-shadow: {{ $heroGlowColor ? $heroGlowColor.'', '' : '''' }}0 2px 10px rgba(0,0,0,0.35);"'

$countHeading = ([regex]::Matches($hero, [regex]::Escape($oldHeading))).Count
if ($countHeading -ne 2) { throw "Style heroHeadingColor ditemukan $countHeading kali (harusnya 2: judul & statistik). Tidak ada yang ditimpa, cek manual." }
$hero = $hero.Replace($oldHeading, $newHeading)

# ---------- 3. Deskripsi (p) ----------
$oldBody = 'style="color: {{ $heroTextColor }}; text-shadow: 0 1px 6px rgba(0,0,0,0.25);"'
$newBody = 'style="color: {{ $heroTextColor }}; text-shadow: {{ $heroGlowColor ? $heroGlowColor.'', '' : '''' }}0 1px 6px rgba(0,0,0,0.25);"'

if ($hero -notmatch [regex]::Escape($oldBody)) { throw "Style heroTextColor di <p> tidak cocok persis. Tidak ada yang ditimpa, cek manual." }
$hero = $hero.Replace($oldBody, $newBody)

# ---------- 4. Hapus 3 kotak blur di belakang tiap baris judul (headline_prefix, site_name, tagline) ----------
$patternHeadlineBox = '(?s)\s*@if\s*\(\$heroGlowColor\)\s*<span class="pointer-events-none absolute -inset-x-3 -inset-y-1\.5 -z-10 rounded-full blur-2xl sm:-inset-x-4 sm:-inset-y-2" style="background: \{\{ \$heroGlowColor \}\};"></span>\s*@endif'
$countHeadlineBox = ([regex]::Matches($hero, $patternHeadlineBox)).Count
if ($countHeadlineBox -ne 3) { throw "Kotak blur judul ditemukan $countHeadlineBox kali (harusnya 3). Tidak ada yang ditimpa, cek manual." }
$hero = [regex]::Replace($hero, $patternHeadlineBox, "")

# ---------- 5. Hapus kotak blur di belakang deskripsi ----------
$patternBodyBox = '(?s)\s*@if\s*\(\$heroGlowColor\)\s*<span class="pointer-events-none absolute -inset-x-4 -inset-y-3 -z-10 rounded-\[2rem\] blur-2xl sm:-inset-x-6 sm:-inset-y-4" style="background: \{\{ \$heroGlowColor \}\};"></span>\s*@endif'
$countBodyBox = ([regex]::Matches($hero, $patternBodyBox)).Count
if ($countBodyBox -ne 1) { throw "Kotak blur deskripsi ditemukan $countBodyBox kali (harusnya 1). Tidak ada yang ditimpa, cek manual." }
$hero = [regex]::Replace($hero, $patternBodyBox, "")

# ---------- 6. Hapus kotak blur di belakang tiap item statistik ----------
$patternStatBox = '(?s)\s*@if\s*\(\$heroGlowColor\)\s*<span class="pointer-events-none absolute -inset-x-3 -inset-y-2 -z-10 rounded-2xl blur-2xl" style="background: \{\{ \$heroGlowColor \}\};"></span>\s*@endif'
$countStatBox = ([regex]::Matches($hero, $patternStatBox)).Count
if ($countStatBox -ne 1) { throw "Kotak blur statistik ditemukan $countStatBox kali (harusnya 1). Tidak ada yang ditimpa, cek manual." }
$hero = [regex]::Replace($hero, $patternStatBox, "")

Set-Content $heroPath -Value $hero -Encoding UTF8 -NoNewline

Write-Host ""
Write-Host "Selesai." -ForegroundColor Green
Write-Host "Diubah: $heroPath"
Write-Host "Backup asli disimpan sebagai $heroPath.bak-before-fix-hero-glow-$stamp"
Write-Host ""
Write-Host "Kalau perubahan belum kelihatan di browser, jalankan: php artisan view:clear"
