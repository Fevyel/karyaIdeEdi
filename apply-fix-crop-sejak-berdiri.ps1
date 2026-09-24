# ============================================================
# FIX: Rasio crop foto "Sejak Berdiri" (section ke-3 Beranda)
# beda dengan rasio yang dipakai admin waktu nge-crop.
#
# AKAR MASALAH (2 foto dari 3 kena, "Foto besar" sudah benar):
#   - Grid kolase di Beranda pakai `grid-rows-2` (auto) tanpa
#     tinggi pasti, sementara "Foto kecil" & "Latar kartu angka"
#     dikasih h-full. Karena tinggi baris grid auto (bukan
#     angka pasti), h-full di situ browser anggap 'auto' juga,
#     jadi kelas aspect-square yang nempel di foto kecil DIABAIKAN
#     browser -- ukuran akhirnya ngikutin sisa ruang, BUKAN 1:1.
#   - Latar kartu angka malah dari awal tidak dikasih kelas aspect
#     sama sekali di Beranda, jadi bentuknya ngikutin sisa tinggi
#     kolom kanan (kotak tinggi/portrait), padahal admin nge-crop
#     kotak 1:1.
#   - Hasilnya: Admin > Edit Web > Sejak Berdiri crop 1:1 buat
#     kedua foto itu, tapi di Beranda yang kepakai malah rasio
#     lain (foto kecil jadi ~3:2 melebar, kartu angka jadi ~1:2
#     memanjang ke bawah) -> foto kepotong beda dari yang dipilih
#     admin waktu geser & zoom di modal crop.
#
# FIX ini SAMAKAN keduanya, permanen (bukan cuma keliatan sama
# kebetulan tergantung isi teks/lebar layar):
#   1. Kolase di Beranda dikunci aspect-[9/8] + grid-rows-[1fr_3fr]
#      -- ukuran grid jadi PASTI, tidak lagi ngikut auto/konten,
#      supaya rasio final foto kecil & kartu angka konsisten di
#      semua ukuran layar (bukan cuma pas dites di 1 device).
#   2. Rasio crop di Admin (edit-web.blade.php) disesuaikan JADI
#      SAMA PERSIS dengan rasio nyata sel grid tsb:
#        - Foto besar (kiri)       : 3:4  (SUDAH benar, tidak diubah)
#        - Foto kecil (kanan atas) : 1:1 -> 3:2
#        - Latar kartu angka       : 1:1 -> 1:2
#
# Mengubah 2 file:
#   - resources\views\partials\frontend\mission.blade.php
#   - resources\views\pages\admin\edit-web.blade.php
#
# CATATAN PENTING: foto "Foto kecil" & "Latar kartu angka" yang
# SUDAH ke-upload sebelumnya di-crop pakai rasio 1:1 lama, jadi
# begitu rasio baru aktif, tampilannya di Beranda kemungkinan
# kepotong kurang pas (karena browser cover-crop otomatis dari
# sumber file 1:1 ke kotak baru yang tidak 1:1). Cukup upload
# ulang & crop ulang 2 foto itu lewat Admin > Edit Web > Sejak
# Berdiri supaya hasilnya rapi -- foto lain tidak perlu diapa2in.
#
# Cara pakai: taruh script ini di folder project
# (C:\xampp\htdocs\karyaIdeEdi), lalu jalankan dari terminal
# VS Code, di folder project:
#   .\apply-fix-crop-sejak-berdiri.ps1
# ============================================================

$ErrorActionPreference = "Stop"

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

function Apply-Fix {
    param(
        [string]$File,
        [string]$Old,
        [string]$New,
        [string]$Label
    )

    if (-not (Test-Path $File)) {
        Write-Host "[ERROR] File tidak ketemu: $File" -ForegroundColor Red
        exit 1
    }

    $content = [System.IO.File]::ReadAllText((Resolve-Path $File))

    if ($content.IndexOf($Old) -lt 0) {
        Write-Host "[ERROR] ($Label) Teks yang mau diganti tidak ketemu persis di $File." -ForegroundColor Red
        Write-Host "        Kemungkinan file ini sudah diubah lagi sejak versi yang dipakai untuk bikin patch ini." -ForegroundColor Red
        Write-Host "        Tidak ada perubahan yang dilakukan -- kirim isi file ini kalau mau dibantu cek lagi." -ForegroundColor Red
        exit 1
    }

    $occurrences = ([regex]::Matches($content, [regex]::Escape($Old))).Count
    if ($occurrences -ne 1) {
        Write-Host "[ERROR] ($Label) Teks itu ketemu $occurrences kali (harusnya cuma 1) di $File -- dibatalkan supaya aman." -ForegroundColor Red
        exit 1
    }

    $newContent = $content.Replace($Old, $New)
    [System.IO.File]::WriteAllText((Resolve-Path $File), $newContent, (New-Object System.Text.UTF8Encoding($false)))
    Write-Host "  [OK] $Label" -ForegroundColor Green
}

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"

# ------------------------------------------------------------
# FILE 1: resources\views\partials\frontend\mission.blade.php
# ------------------------------------------------------------
$file1 = "resources\views\partials\frontend\mission.blade.php"
if (-not (Test-Path $file1)) {
    Write-Host "[ERROR] File tidak ketemu: $file1" -ForegroundColor Red
    exit 1
}
$backup1 = "$file1.bak-before-fix-crop-sejak-berdiri-$timestamp"
Copy-Item $file1 $backup1
Write-Host "Backup dibuat : $backup1" -ForegroundColor DarkGray

Write-Host "Memproses $file1 ..." -ForegroundColor Cyan

Apply-Fix -File $file1 -Label "Grid kolase: tinggi dikunci pasti (aspect-[9/8], baris 1fr:3fr)" `
    -Old '<div class="grid grid-cols-3 grid-rows-2 gap-4">' `
    -New '<div class="grid aspect-[9/8] grid-cols-3 grid-rows-[1fr_3fr] gap-4">'

Apply-Fix -File $file1 -Label "Foto besar (kiri): pakai ukuran sel grid apa adanya (sudah 3:4, tidak berubah tampilan)" `
    -Old 'class="col-span-2 row-span-2 aspect-3/4 w-full rounded-2xl object-cover shadow-lg"' `
    -New 'class="col-span-2 row-span-2 h-full w-full rounded-2xl object-cover shadow-lg"'

Apply-Fix -File $file1 -Label "Foto kecil (kanan atas): lepas aspect-square yang diabaikan browser, ikut sel grid (jadi 3:2)" `
    -Old 'class="col-span-1 row-span-1 aspect-square h-full w-full rounded-2xl object-cover shadow-lg"' `
    -New 'class="col-span-1 row-span-1 h-full w-full rounded-2xl object-cover shadow-lg"'

Apply-Fix -File $file1 -Label "Latar kartu angka: tambah w-full supaya ikut ukuran sel grid (jadi 1:2)" `
    -Old 'class="col-span-1 row-span-1 flex h-full flex-col justify-center rounded-2xl p-4 shadow-lg"' `
    -New 'class="col-span-1 row-span-1 flex h-full w-full flex-col justify-center rounded-2xl p-4 shadow-lg"'

# ------------------------------------------------------------
# FILE 2: resources\views\pages\admin\edit-web.blade.php
# ------------------------------------------------------------
$file2 = "resources\views\pages\admin\edit-web.blade.php"
if (-not (Test-Path $file2)) {
    Write-Host "[ERROR] File tidak ketemu: $file2" -ForegroundColor Red
    exit 1
}
$backup2 = "$file2.bak-before-fix-crop-sejak-berdiri-$timestamp"
Copy-Item $file2 $backup2
Write-Host "Backup dibuat : $backup2" -ForegroundColor DarkGray

Write-Host "Memproses $file2 ..." -ForegroundColor Cyan

$oldKecil = "    Alpine.data('missionFotoKecilCropper', (existingPreviewUrl) => ({`n" +
    "        open: false,`n" +
    "        rawImage: null,`n" +
    "        previewUrl: existingPreviewUrl || null,`n" +
    "        natW: 0,`n" +
    "        natH: 0,`n" +
    "        scale: 1,`n" +
    "        minScale: 1,`n" +
    "        maxScale: 1,`n" +
    "        zoomPercent: 0,`n" +
    "        posX: 0,`n" +
    "        posY: 0,`n" +
    "        dragging: false,`n" +
    "        dragStartX: 0,`n" +
    "        dragStartY: 0,`n" +
    "        startPosX: 0,`n" +
    "        startPosY: 0,`n" +
    "`n" +
    "        ASPECT_W: 1,`n" +
    "        ASPECT_H: 1,`n" +
    "`n" +
    "        viewW: 0,`n" +
    "        viewH: 0,`n" +
    "`n" +
    "        OUT_W: 900,`n" +
    "        OUT_H: 900,"

$newKecil = "    Alpine.data('missionFotoKecilCropper', (existingPreviewUrl) => ({`n" +
    "        open: false,`n" +
    "        rawImage: null,`n" +
    "        previewUrl: existingPreviewUrl || null,`n" +
    "        natW: 0,`n" +
    "        natH: 0,`n" +
    "        scale: 1,`n" +
    "        minScale: 1,`n" +
    "        maxScale: 1,`n" +
    "        zoomPercent: 0,`n" +
    "        posX: 0,`n" +
    "        posY: 0,`n" +
    "        dragging: false,`n" +
    "        dragStartX: 0,`n" +
    "        dragStartY: 0,`n" +
    "        startPosX: 0,`n" +
    "        startPosY: 0,`n" +
    "`n" +
    "        ASPECT_W: 3,`n" +
    "        ASPECT_H: 2,`n" +
    "`n" +
    "        viewW: 0,`n" +
    "        viewH: 0,`n" +
    "`n" +
    "        OUT_W: 900,`n" +
    "        OUT_H: 600,"

Apply-Fix -File $file2 -Label "Cropper 'Foto kecil (kanan atas)': rasio crop 1:1 -> 3:2" -Old $oldKecil -New $newKecil

$oldStatBg = "    Alpine.data('missionStatBgCropper', (existingPreviewUrl) => ({`n" +
    "        open: false,`n" +
    "        rawImage: null,`n" +
    "        previewUrl: existingPreviewUrl || null,`n" +
    "        natW: 0,`n" +
    "        natH: 0,`n" +
    "        scale: 1,`n" +
    "        minScale: 1,`n" +
    "        maxScale: 1,`n" +
    "        zoomPercent: 0,`n" +
    "        posX: 0,`n" +
    "        posY: 0,`n" +
    "        dragging: false,`n" +
    "        dragStartX: 0,`n" +
    "        dragStartY: 0,`n" +
    "        startPosX: 0,`n" +
    "        startPosY: 0,`n" +
    "`n" +
    "        ASPECT_W: 1,`n" +
    "        ASPECT_H: 1,`n" +
    "`n" +
    "        viewW: 0,`n" +
    "        viewH: 0,`n" +
    "`n" +
    "        OUT_W: 900,`n" +
    "        OUT_H: 900,"

$newStatBg = "    Alpine.data('missionStatBgCropper', (existingPreviewUrl) => ({`n" +
    "        open: false,`n" +
    "        rawImage: null,`n" +
    "        previewUrl: existingPreviewUrl || null,`n" +
    "        natW: 0,`n" +
    "        natH: 0,`n" +
    "        scale: 1,`n" +
    "        minScale: 1,`n" +
    "        maxScale: 1,`n" +
    "        zoomPercent: 0,`n" +
    "        posX: 0,`n" +
    "        posY: 0,`n" +
    "        dragging: false,`n" +
    "        dragStartX: 0,`n" +
    "        dragStartY: 0,`n" +
    "        startPosX: 0,`n" +
    "        startPosY: 0,`n" +
    "`n" +
    "        ASPECT_W: 1,`n" +
    "        ASPECT_H: 2,`n" +
    "`n" +
    "        viewW: 0,`n" +
    "        viewH: 0,`n" +
    "`n" +
    "        OUT_W: 700,`n" +
    "        OUT_H: 1400,"

Apply-Fix -File $file2 -Label "Cropper 'Latar kartu angka': rasio crop 1:1 -> 1:2" -Old $oldStatBg -New $newStatBg

Write-Host ""
Write-Host "Selesai. Semua 6 perubahan berhasil diterapkan." -ForegroundColor Cyan
Write-Host "Refresh Beranda -- kolase foto 'Sejak Berdiri' sekarang rasionya PASTI" -ForegroundColor Cyan
Write-Host "(bukan lagi auto/ngikut konten), dan rasio crop di Admin > Edit Web >" -ForegroundColor Cyan
Write-Host "Sejak Berdiri sudah disamakan persis." -ForegroundColor Cyan
Write-Host ""
Write-Host "WAJIB: upload ulang & crop ulang 'Foto kecil' dan 'Latar kartu angka'" -ForegroundColor Yellow
Write-Host "di Admin > Edit Web > Sejak Berdiri, karena file lama di-crop pakai" -ForegroundColor Yellow
Write-Host "rasio 1:1 yang sudah tidak dipakai lagi." -ForegroundColor Yellow
