# ============================================================
# FIX: Rasio crop foto "Kenapa Pilih Kami" di Beranda beda
# dengan rasio yang dipakai admin waktu nge-crop.
#
# Sebelumnya:
#   - Admin > Edit Web > Kenapa Pilih Kami nge-crop foto pakai
#     rasio 10:9 (keahlianFotoCropper, sama seperti Header).
#   - Tapi di Beranda, foto itu ditampilkan di panel dengan
#     tinggi TETAP per breakpoint (h-90 / sm:h-115 / lg:min-h-140)
#     + object-contain -- BUKAN rasio 10:9. Efeknya bagian foto
#     yang kelihatan di Beranda bisa beda dari yang admin pilih
#     waktu geser & zoom di modal crop -> risiko "salah crop".
#
# Fix ini SAMAKAN keduanya: panel foto di Beranda sekarang
# dikunci aspect-10/9 (persis rasio crop admin) + object-cover
# supaya fotonya isi penuh sesuai crop-an admin, sama pola-nya
# dengan foto Header di partials/frontend/hero.blade.php.
#
# HANYA mengubah 1 file: resources/views/partials/frontend/expertise.blade.php
# (panel kanan/teks/checklist/ikon sosial TIDAK disentuh sama sekali).
#
# Cara pakai: taruh script ini di folder project
# (C:\xampp\htdocs\karyaIdeEdi), lalu jalankan dari terminal
# VS Code, di folder project:
#   .\apply-fix-crop-kenapa-pilih-kami.ps1
# ============================================================

$ErrorActionPreference = "Stop"

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

$target = "resources\views\partials\frontend\expertise.blade.php"

if (-not (Test-Path $target)) {
    Write-Host "[ERROR] File tidak ketemu: $target" -ForegroundColor Red
    exit 1
}

# Baca APA ADANYA (tanpa normalisasi line-ending / BOM) supaya .Replace()
# di bawah cocok persis dengan isi file yang sebenarnya.
$content = [System.IO.File]::ReadAllText((Resolve-Path $target))

$old = '<section class="bg-white">' + "`n" +
    '    <div class="grid grid-cols-1 lg:grid-cols-2 lg:items-stretch">' + "`n" +
    "`n" +
    '        {{-- ============ KIRI: Panel foto ============ --}}' + "`n" +
    '        <div class="relative h-90 overflow-hidden bg-[#1A1A1A] sm:h-115 lg:h-auto lg:min-h-140">' + "`n" +
    '            <img' + "`n" +
    '                src="{{ $keahlianImageUrl }}"' + "`n" +
    '                alt="{{ $keahlianData[''title''] }}"' + "`n" +
    '                class="absolute left-1/2 top-1/2 h-[78%] w-auto -translate-x-1/2 -translate-y-1/2 object-contain"' + "`n" +
    '            >' + "`n" +
    '        </div>'

$new = '<section class="bg-white">' + "`n" +
    '    <div class="grid grid-cols-1 lg:grid-cols-2 lg:items-center">' + "`n" +
    "`n" +
    '        {{--' + "`n" +
    '            ============ KIRI: Foto ============' + "`n" +
    '            Rasio dikunci aspect-10/9 -- SAMA PERSIS dengan rasio crop di' + "`n" +
    '            Admin > Edit Web > Kenapa Pilih Kami (lihat keahlianFotoCropper,' + "`n" +
    '            ASPECT_W/ASPECT_H = 10/9 di edit-web.blade.php). Sebelumnya panel' + "`n" +
    '            ini pakai tinggi tetap per breakpoint (h-90/sm:h-115/lg:min-h-140)' + "`n" +
    '            + object-contain, jadi bagian foto yang kelihatan di Beranda BISA' + "`n" +
    '            beda dari yang admin pilih waktu nge-crop -- fix ini menyamakan' + "`n" +
    '            keduanya supaya tidak ada risiko salah crop.' + "`n" +
    '        --}}' + "`n" +
    '        <div class="relative aspect-10/9 w-full overflow-hidden bg-[#1A1A1A]">' + "`n" +
    '            <img' + "`n" +
    '                src="{{ $keahlianImageUrl }}"' + "`n" +
    '                alt="{{ $keahlianData[''title''] }}"' + "`n" +
    '                class="absolute inset-0 h-full w-full object-cover"' + "`n" +
    '            >' + "`n" +
    '        </div>'

if ($content.IndexOf($old) -lt 0) {
    Write-Host "[ERROR] Teks yang mau diganti tidak ketemu persis di $target." -ForegroundColor Red
    Write-Host "        Kemungkinan file ini sudah diubah lagi sejak versi yang dipakai untuk bikin patch ini." -ForegroundColor Red
    Write-Host "        Tidak ada perubahan yang dilakukan -- kirim isi file ini kalau mau dibantu cek lagi." -ForegroundColor Red
    exit 1
}

$occurrences = ([regex]::Matches($content, [regex]::Escape($old))).Count
if ($occurrences -ne 1) {
    Write-Host "[ERROR] Teks itu ketemu $occurrences kali (harusnya cuma 1) -- dibatalkan supaya aman, tidak ada yang diubah." -ForegroundColor Red
    exit 1
}

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupPath = "$target.bak-before-fix-crop-kenapa-pilih-kami-$timestamp"
Copy-Item $target $backupPath
Write-Host "Backup dibuat : $backupPath" -ForegroundColor DarkGray

$newContent = $content.Replace($old, $new)
[System.IO.File]::WriteAllText((Resolve-Path $target), $newContent, (New-Object System.Text.UTF8Encoding($false)))

Write-Host "Diperbarui    : $target" -ForegroundColor Green
Write-Host ""
Write-Host "Selesai. Refresh halaman Beranda -- panel foto 'Kenapa Pilih Kami' sekarang" -ForegroundColor Cyan
Write-Host "rasionya 10:9, persis sama dengan yang di-crop admin di Edit Web." -ForegroundColor Cyan
