# apply-fix-hero-cta-gap.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya), SETELAH
# apply-hero-redesign-premium.ps1 sudah dijalankan sebelumnya:
#   powershell -ExecutionPolicy Bypass -File apply-fix-hero-cta-gap.ps1
#
# Masalah: dua tombol CTA ("Lihat Katalog" & "Jelajahi Tentang Kami") sama-sama
# solid (hitam & coklat) dan jaraknya (gap-3) kelihatan mepet -- di layar
# jadinya terlihat seperti satu tombol yang "menyatu" dua warna, bukan dua
# tombol terpisah.
#
# Perbaikan:
# 1) Jarak antar tombol diperbesar sedikit (gap-3 -> gap-4).
# 2) Tombol kedua ("Jelajahi Tentang Kami") diubah dari solid coklat jadi
#    outline/glass -- pakai $heroBorderColor & $heroDividerColor yang SUDAH
#    ada di file ini (variabel ini awalnya memang dibuat untuk tombol outline
#    ini, lihat komentar "Border tombol outline ... " di hero.blade.php).
#    Efeknya: tombol pertama tetap jadi aksi utama yang tegas (solid hitam),
#    tombol kedua jadi aksi sekunder yang lebih halus (border tipis + kaca) --
#    dua tombol jadi jelas terpisah & ada hierarki, bukan dua blok warna yang
#    saling menempel. Label & link tombol TIDAK berubah sama sekali.

$ErrorActionPreference = "Stop"

$heroPath = "resources/views/partials/frontend/hero.blade.php"
if (-not (Test-Path $heroPath)) { throw "Tidak ketemu: $heroPath (jalankan script ini dari root project)" }

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
Copy-Item $heroPath "$heroPath.bak-before-cta-gap-$stamp"

$hero = Get-Content $heroPath -Raw -Encoding UTF8

# ---------- 1. Perbesar jarak antar tombol ----------
$oldGap = 'flex flex-wrap items-center justify-center gap-3" style="animation-delay: .4s;">'
$newGap = 'flex flex-wrap items-center justify-center gap-4" style="animation-delay: .4s;">'

if ($hero -notmatch [regex]::Escape($oldGap)) {
    throw "Baris wrapper CTA (gap-3) tidak cocok persis (mungkin sudah pernah diubah). Tidak ada yang ditimpa, cek manual."
}
$hero = $hero.Replace($oldGap, $newGap)

# ---------- 2. Tombol kedua: solid coklat -> outline/glass ----------
$patternSecondaryBtn = [regex]'<a\s*href="\{\{ route\(''profile\.index''\) \}\}"\s*class="group inline-flex items-center gap-2 rounded-lg bg-\[#7A4A23\] px-7 py-3\.5 text-sm font-medium text-white shadow-lg shadow-black/20 transition-all duration-300 hover:-translate-y-0\.5 hover:bg-\[#5C3719\] hover:shadow-xl hover:shadow-black/30"\s*>\s*Jelajahi Tentang Kami\s*<x-icon-arrow direction="right" class="transition-transform duration-300 group-hover:translate-x-1" />\s*</a>'

$countSecondaryBtn = $patternSecondaryBtn.Matches($hero).Count
if ($countSecondaryBtn -ne 1) {
    throw "Tombol 'Jelajahi Tentang Kami' ditemukan $countSecondaryBtn kali (harusnya 1, mungkin sudah pernah diubah). Tidak ada yang ditimpa, cek manual."
}

$newSecondaryBtn = @'
<a
                href="{{ route('profile.index') }}"
                class="group inline-flex items-center gap-2 rounded-lg border px-7 py-3.5 text-sm font-medium backdrop-blur-md transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg"
                style="border-color: {{ $heroBorderColor }}; color: {{ $heroHeadingColor }}; background: {{ $heroDividerColor }};"
            >
                Jelajahi Tentang Kami
                <x-icon-arrow direction="right" class="transition-transform duration-300 group-hover:translate-x-1" />
            </a>
'@

# MatchEvaluator dipakai (bukan string replacement biasa) supaya tanda $ di
# dalam {{ $heroBorderColor }} dkk TIDAK ditafsirkan regex sebagai backreference.
$evaluator = [System.Text.RegularExpressions.MatchEvaluator] { param($m) $newSecondaryBtn }
$hero = $patternSecondaryBtn.Replace($hero, $evaluator)

Set-Content $heroPath -Value $hero -Encoding UTF8 -NoNewline

Write-Host ""
Write-Host "Selesai." -ForegroundColor Green
Write-Host "Diubah: $heroPath"
Write-Host "Backup asli disimpan sebagai $heroPath.bak-before-cta-gap-$stamp"
Write-Host ""
Write-Host "Kalau perubahan belum kelihatan di browser, jalankan: php artisan view:clear"
