# ============================================================
# FIX v4: Perbaikan dari fix v3 kemarin (apply-fix-admin-zoom-gap-v3.ps1)
# yang TERNYATA SALAH -- bikin scrollbar muncul + modal jadi geser
# dari tengah (bukan lagi rectangle kosong, tapi masalah baru).
#
# KENAPA v3 SALAH:
#   v3 kasih `width: 125vw; height: 125vh;` ke elemen fixed inset-0
#   (wrapper modal). Karena `zoom` juga mempengaruhi LAYOUT (bukan
#   cuma tampilan kayak transform), Chrome menganggap box 125vw x
#   125vh itu beneran bagian dari halaman yang perlu di-scroll --
#   makanya muncul scrollbar kanan & bawah, dan modal (yang di-center
#   di dalam box 125% itu) jadi keliatan geser dari tengah layar asli.
#
# FIX YANG BENAR (v4):
#   Pakai `zoom` BERSARANG buat "membatalkan" zoom leluhur, bukan
#   memperbesar ukuran box:
#     - wrapper modal (fixed inset-0): zoom: 125%
#       -> 80% (dari <html>) x 125% (sini) = 100% -> wrapper ini jadi
#          "tidak ter-zoom" dari sudut pandang browser, termasuk
#          perhitungan position:fixed-nya -- BENERAN nutup 100%
#          viewport asli, TANPA bikin scrollbar (beda dari v3).
#     - anak langsung yang BUKAN backdrop (:not(.inset-0)): zoom: 80%
#       -> supaya kartu modalnya balik ke ukuran senada dgn Admin
#          Panel lainnya (backdrop-nya sendiri sengaja dikecualikan,
#          biar tetap menutupi 100% layar).
#
# Berlaku otomatis untuk SEMUA 12 file modal admin (pola classnya
# seragam), tanpa perlu sentuh file blade satu-satu.
#
# HANYA mengubah 1 file: resources\css\app.css (ganti isi 1 blok
# CSS yang ditambahkan fix v3 kemarin, dengan versi yang benar).
#
# WAJIB setelah ini jalankan salah satu (supaya CSS ke-compile):
#   npm run build
#   npm run dev
#   composer run dev
#
# Cara pakai (dari terminal VS Code, di root project
# C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-fix-admin-zoom-gap-v4.ps1
# ============================================================

$ErrorActionPreference = "Stop"

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

$file = "resources\css\app.css"
if (-not (Test-Path $file)) {
    Write-Host "[ERROR] File tidak ketemu: $file" -ForegroundColor Red
    exit 1
}

$content = [System.IO.File]::ReadAllText((Resolve-Path $file))

# Blok versi v3 (yang salah, hasil apply-fix-admin-zoom-gap-v3.ps1)
$oldBlockV3 = @'
html[data-theme] .fixed.inset-0 {
    width: 125vw;
    height: 125vh;
}
'@

# Blok versi v4 (yang benar)
$newBlockV4 = @'
html[data-theme] .fixed.inset-0 {
    zoom: 125%;
}
html[data-theme] .fixed.inset-0 > :not(.inset-0) {
    zoom: 80%;
}
'@

$alreadyV4 = $content.IndexOf("html[data-theme] .fixed.inset-0 > :not(.inset-0)") -ge 0

if ($alreadyV4) {
    Write-Host "[INFO] Versi v4 sudah terpasang di $file -- tidak ada perubahan." -ForegroundColor Yellow
    exit 0
}

if ($content.IndexOf($oldBlockV3) -lt 0) {
    Write-Host "[ERROR] Blok CSS versi v3 tidak ketemu persis di $file." -ForegroundColor Red
    Write-Host "        Kemungkinan file ini sudah diubah lagi sejak versi yang dipakai untuk bikin patch ini," -ForegroundColor Red
    Write-Host "        atau fix v3 belum pernah dijalankan sama sekali." -ForegroundColor Red
    Write-Host "        Tidak ada perubahan yang dilakukan." -ForegroundColor Red
    exit 1
}

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backup = "$file.bak-before-fix-admin-zoom-gap-v4-$timestamp"
Copy-Item $file $backup
Write-Host "Backup dibuat : $backup" -ForegroundColor DarkGray

$newContent = $content.Replace($oldBlockV3, $newBlockV4)
[System.IO.File]::WriteAllText((Resolve-Path $file), $newContent, (New-Object System.Text.UTF8Encoding($false)))

Write-Host "[OK] Rule v3 yang salah (width/height 125vw/125vh) sudah diganti dengan versi v4 (zoom bersarang)." -ForegroundColor Green
Write-Host ""
Write-Host "WAJIB dilakukan sekarang, supaya CSS baru ke-compile:" -ForegroundColor Cyan
Write-Host "  npm run build" -ForegroundColor White
Write-Host "(atau 'npm run dev' / 'composer run dev' kalau itu yang biasa kamu pakai)" -ForegroundColor Cyan
Write-Host ""
Write-Host "Baru setelah itu hard refresh (Ctrl+Shift+R). Scrollbar aneh & modal geser" -ForegroundColor Cyan
Write-Host "harusnya hilang, DAN rectangle kosong di bawah juga sudah gak ada." -ForegroundColor Cyan
