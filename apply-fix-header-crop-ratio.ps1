# apply-fix-header-crop-ratio.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   powershell -ExecutionPolicy Bypass -File apply-fix-header-crop-ratio.ps1
#
# Masalah: cropper "Sesuaikan Foto Header" (Admin > Edit Web > Header) masih
# memakai rasio 10:9 (sisa layout lama: foto di kolom kanan), padahal hero
# sekarang memakai foto sebagai latar FULL-WIDTH dengan tinggi tetap 760px
# (lg:min-h-[760px], object-cover). Akibatnya foto 10:9 (1000x900) dipotong
# ~40% atas-bawah lalu diperbesar ~1,9x di hero -> terlihat ter-zoom & buram,
# jauh beda dari yang kelihatan di jendela crop.
#
# Perbaikan (HANYA di resources/views/pages/admin/edit-web.blade.php, HANYA
# bagian Foto Header):
#   - Rasio crop 10:9 -> 19:10  (= lebar layout desktop ~1440 : tinggi hero 760)
#   - Ukuran hasil crop 1000x900 -> 1900x1000 (tajam di layar lebar/retina)
#   - Jendela crop & thumbnail ikut rasio 19:10 (pakai inline style, jadi
#     TIDAK perlu npm run build)
#   - Teks petunjuk & komentar disesuaikan
# Cropper lain (Tentang Kami, Sejak Berdiri, dst.) tidak disentuh.
#
# Semua penggantian dicek "harus ketemu tepat 1x" DULU; kalau ada yang tidak
# cocok, script berhenti dan file tidak ditimpa sama sekali.
# Encoding asli file (ada/tidak ada BOM) & line ending dipertahankan.

$ErrorActionPreference = "Stop"

$path = "resources/views/pages/admin/edit-web.blade.php"
if (-not (Test-Path $path)) { throw "Tidak ketemu: $path (jalankan script ini dari root project)" }

$fullPath = (Resolve-Path $path).Path
$bytes    = [System.IO.File]::ReadAllBytes($fullPath)
$hasBom   = ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF)
$content  = [System.IO.File]::ReadAllText($fullPath, (New-Object System.Text.UTF8Encoding($false)))

function Replace-ExactlyOnce {
    param(
        [string]$Text,
        [string]$Pattern,
        [string]$Replacement,
        [string]$Label
    )
    $rx = New-Object System.Text.RegularExpressions.Regex($Pattern, [System.Text.RegularExpressions.RegexOptions]::Singleline)
    $count = $rx.Matches($Text).Count
    if ($count -ne 1) {
        throw "[$Label] ditemukan $count kali (harusnya 1, mungkin sudah pernah diubah). Tidak ada yang ditimpa, cek manual."
    }
    return $rx.Replace($Text, $Replacement)
}

function Replace-LiteralOnce {
    param(
        [string]$Text,
        [string]$Old,
        [string]$New,
        [string]$Label
    )
    return Replace-ExactlyOnce -Text $Text -Pattern ([regex]::Escape($Old)) -Replacement $New.Replace('$', '$$') -Label $Label
}

# 1) Thumbnail preview di samping tombol kamera (10:9 kecil -> 19:10 lebih lebar)
$patThumb = @'
<div class="relative w-32 shrink-0">(\r?\n\s*)<img :src="previewUrl" alt="Preview foto header" class="aspect-10/9 w-32 rounded-2xl object-cover ring-4 ring-admin-cream">
'@
$repThumb = @'
<div class="relative shrink-0" style="width: 12rem;">${1}<img :src="previewUrl" alt="Preview foto header" class="rounded-2xl object-cover ring-4 ring-admin-cream" style="width: 12rem; aspect-ratio: 19 / 10;">
'@
$content = Replace-ExactlyOnce -Text $content -Pattern $patThumb -Replacement $repThumb -Label "thumbnail foto header"

# 2) Teks petunjuk di modal crop
$content = Replace-LiteralOnce -Text $content `
    -Old 'Hasil crop mengikuti proporsi foto di section Header (10:9).' `
    -New 'Hasil crop mengikuti proporsi tampilan foto di hero beranda (19:10).' `
    -Label "teks modal crop header"

# 3) Jendela crop di modal: rasio 19:10 dan selebar modal (bukan max-w-80)
$content = Replace-LiteralOnce -Text $content `
    -Old 'class="relative mx-auto aspect-10/9 w-full max-w-80 cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none"' `
    -New 'class="relative mx-auto w-full cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none" style="aspect-ratio: 19 / 10;"' `
    -Label "viewport modal crop header"

# 4) Nilai rasio & ukuran output di JS heroImageCropper (10:9 / 1000x900 -> 19:10 / 1900x1000)
$patJs = @'
(Alpine\.data\('heroImageCropper'.*?ASPECT_W: )10(,\r?\n\s*ASPECT_H: )9(,.*?OUT_W: )1000(,\r?\n\s*OUT_H: )900,
'@
$content = Replace-ExactlyOnce -Text $content -Pattern $patJs -Replacement '${1}19${2}10${3}1900${4}1000,' -Label "ASPECT/OUT heroImageCropper"

# 5) Komentar di atas heroImageCropper
$content = Replace-LiteralOnce -Text $content `
    -Old '(10:9, mengikuti frame foto di partials/frontend/hero.blade.php).' `
    -New '(19:10, mengikuti tampilan foto latar di partials/frontend/hero.blade.php).' `
    -Label "komentar rasio hero"

# Semua pengecekan lolos -> baru backup & tulis
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
Copy-Item $path "$path.bak-before-header-crop-ratio-$stamp"

[System.IO.File]::WriteAllText($fullPath, $content, (New-Object System.Text.UTF8Encoding($hasBom)))

Write-Host ""
Write-Host "Selesai." -ForegroundColor Green
Write-Host "Diubah: $path (rasio crop Foto Header 10:9 -> 19:10, output 1900x1000)"
Write-Host "Backup asli disimpan sebagai $path.bak-before-header-crop-ratio-$stamp"
Write-Host ""
Write-Host "Lanjutkan: php artisan view:clear  lalu refresh Admin > Edit Web > Header (Ctrl+F5)."
