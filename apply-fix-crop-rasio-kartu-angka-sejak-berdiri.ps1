<#
  Fix: rasio crop "Latar kartu angka" (Sejak Berdiri) di Edit Web salah kunci 1:1 (persegi),
  padahal di frontend (partials/frontend/mission.blade.php) kartu itu flex-1 mengisi sisa
  tinggi kolom kanan kolase -> rasio aslinya ~3:5 (lebih tinggi dari lebar), BUKAN kotak.

  Script ini HANYA mengubah: resources\views\pages\admin\edit-web.blade.php
  Tidak menyentuh mission.blade.php atau file lain.

  Cara pakai (dari terminal VS Code, di root project C:\xampp\htdocs\karyaIdeEdi):
    .\apply-fix-crop-rasio-kartu-angka-sejak-berdiri.ps1
#>

$ErrorActionPreference = 'Stop'

$relativePath = 'resources\views\pages\admin\edit-web.blade.php'
$path = Join-Path $PSScriptRoot $relativePath

if (-not (Test-Path $path)) {
    Write-Host "File tidak ditemukan: $path" -ForegroundColor Red
    exit 1
}

# Baca sebagai teks (auto-detect BOM UTF-8 yang memang sudah ada di file ini)
$content = [System.IO.File]::ReadAllText($path)

$replacements = @(
    # 1. Komentar penanda rasio di atas blok FOTO
    @{
        Old = '                            {{-- Foto latar kartu angka penghargaan (rasio 1:1, OPSIONAL) --}}' + "`n"
        New = '                            {{-- Foto latar kartu angka penghargaan (rasio 3:5, OPSIONAL -- disamakan dengan tinggi sisa kolom kanan di kolase mission.blade.php) --}}' + "`n"
        Label = 'Komentar rasio (baris 1235)'
    },
    # 2. Thumbnail preview kecil di panel admin
    @{
        Old = '                                    <img :src="previewUrl" alt="Preview foto latar kartu angka" class="aspect-square w-28 rounded-2xl object-cover ring-4 ring-admin-cream">' + "`n"
        New = '                                    <img :src="previewUrl" alt="Preview foto latar kartu angka" class="aspect-3/5 w-28 rounded-2xl object-cover ring-4 ring-admin-cream">' + "`n"
        Label = 'Thumbnail preview (baris 1238)'
    },
    # 3. Judul + deskripsi + kotak viewport di modal crop
    @{
        Old = ('                                            <h4 class="mb-1 text-sm font-semibold text-admin-ink">Sesuaikan Foto Latar Kartu Angka</h4>' + "`n" +
               '                                            <p class="mb-4 text-xs text-admin-ink-soft">Geser gambar untuk memindah, gunakan slider untuk zoom. Rasio 1:1, akan tampil dengan overlay gelap tipis supaya angka &amp; label tetap kebaca.</p>' + "`n" +
               '                                            <div x-ref="viewport" class="relative mx-auto aspect-square w-full max-w-64 cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none" x-on:pointerdown="startDrag($event)" x-on:pointermove="onDrag($event)" x-on:pointerup="endDrag()" x-on:pointerleave="endDrag()">' + "`n")
        New = ('                                            <h4 class="mb-1 text-sm font-semibold text-admin-ink">Sesuaikan Foto Latar Kartu Angka</h4>' + "`n" +
               '                                            <p class="mb-4 text-xs text-admin-ink-soft">Geser gambar untuk memindah, gunakan slider untuk zoom. Rasio 3:5 (mengikuti tinggi sisa kolom kanan kolase), akan tampil dengan overlay gelap tipis supaya angka &amp; label tetap kebaca.</p>' + "`n" +
               '                                            <div x-ref="viewport" class="relative mx-auto aspect-3/5 w-full max-w-64 cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none" x-on:pointerdown="startDrag($event)" x-on:pointermove="onDrag($event)" x-on:pointerup="endDrag()" x-on:pointerleave="endDrag()">' + "`n")
        Label = 'Judul + deskripsi + kotak viewport modal (baris 1256-1258)'
    },
    # 4. Konfigurasi Alpine.js (ASPECT_W/H + resolusi output canvas)
    @{
        Old = ("    Alpine.data('missionStatBgCropper', (existingPreviewUrl) => ({`n" +
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
               "        OUT_H: 900,`n")
        New = ("    Alpine.data('missionStatBgCropper', (existingPreviewUrl) => ({`n" +
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
               "        ASPECT_H: 5,`n" +
               "`n" +
               "        viewW: 0,`n" +
               "        viewH: 0,`n" +
               "`n" +
               "        OUT_W: 900,`n" +
               "        OUT_H: 1500,`n")
        Label = 'Alpine.data missionStatBgCropper: ASPECT_W/H + OUT_W/OUT_H (baris 2494-2519)'
    }
)

$appliedCount = 0
foreach ($r in $replacements) {
    if ($content.Contains($r.Old)) {
        $content = $content.Replace($r.Old, $r.New)
        Write-Host "OK   : $($r.Label)" -ForegroundColor Green
        $appliedCount++
    } else {
        Write-Host "LEWAT: pola tidak ditemukan -> $($r.Label)" -ForegroundColor Yellow
        Write-Host "       (kemungkinan file sudah beda dari yang saya cek, cek manual dulu)" -ForegroundColor Yellow
    }
}

if ($appliedCount -eq 0) {
    Write-Host "" 
    Write-Host "Tidak ada perubahan yang diterapkan. File TIDAK ditulis ulang." -ForegroundColor Red
    exit 1
}

# Tulis balik dengan UTF-8 + BOM (menyamakan dengan kondisi asli file ini,
# supaya tidak memicu masalah BOM yang pernah muncul di project ini sebelumnya)
$utf8WithBom = New-Object System.Text.UTF8Encoding($true)
[System.IO.File]::WriteAllText($path, $content, $utf8WithBom)

Write-Host ""
Write-Host "Diperbarui : $relativePath ($appliedCount/$($replacements.Count) perubahan diterapkan)" -ForegroundColor Green
Write-Host ""
Write-Host "Langkah selanjutnya:" -ForegroundColor Cyan
Write-Host "  1. Cek diff dulu: git diff -- $relativePath"
Write-Host "  2. php artisan view:clear   (biar cache Blade lama kebuang)"
Write-Host "  3. Buka Admin > Edit Web > Sejak Berdiri, upload ULANG foto 'Latar kartu angka'"
Write-Host "     (foto yang SUDAH ada sebelumnya masih hasil crop kotak lama, perlu di-crop ulang)."
