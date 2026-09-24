# ============================================================
# FIX v3: Rectangle/celah gelap di bawah modal admin MASIH
# muncul walau sudah coba fix v1 (min-height di body) dan v2
# (pindah zoom ke html) -- karena akar masalahnya bukan soal DI
# MANA zoom dipasang, tapi elemen `position: fixed` di dalam
# ancestor manapun yang di-zoom (baik <body> maupun <html>) akan
# IKUT ter-scale 80% juga.
#
# Modal-modal admin SEMUANYA pakai pola class="fixed inset-0 ..."
# (nutup penuh layar) -- ada di 12 file berbeda (Pesanan, Produk,
# Kategori, Testimoni, Pelanggan, History, Interaksi, Pengaturan,
# Profil, Edit Web, Reset Data). Semuanya kena bug yang sama:
# cuma nutup 80% tinggi/lebar layar asli, sisa 20% kelihatan
# kosong.
#
# SOLUSI: 1 aturan CSS GLOBAL (bukan nge-patch 12 file satu-satu)
# yang otomatis kasih ukuran 125% (=100/80%) ke SEMUA elemen
# "fixed inset-0" di Admin Panel, supaya setelah di-scale 80%
# hasilnya balik pas 100% menutupi layar asli.
#
# HANYA mengubah 1 file: resources\css\app.css (nambah 1 blok
# CSS baru, tidak menghapus/mengubah apa pun yang sudah ada).
# Halaman frontend TIDAK terpengaruh (rule di-scope ke
# html[data-theme], atribut yang hanya ada di Admin Panel).
#
# PENTING -- setelah script ini selesai, WAJIB jalankan salah
# satu supaya CSS baru ke-compile:
#   npm run build      (sekali build, untuk production)
#   npm run dev         (mode watch, biarkan jalan saat development)
#   composer run dev    (kalau biasa pakai ini)
# Tanpa itu, perubahan CSS TIDAK akan kelihatan di browser.
#
# Cara pakai (dari terminal VS Code, di root project
# C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-fix-admin-zoom-gap-v3.ps1
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

$old = "html[data-site='frontend'] {`n" +
    "    zoom: 80%;`n" +
    "}"

$newBlock = @'
html[data-site='frontend'] {
    zoom: 80%;
}

/* Admin Panel: <html> juga dipasang `zoom: 80%` (lihat style di
   resources/views/layouts/admin-panel.blade.php). Efek sampingnya --
   SEMUA elemen `position: fixed` di dalamnya (dipakai di SETIAP modal
   admin: class="fixed inset-0 ...", ada di 12 file berbeda -- Pesanan,
   Produk, Kategori, Testimoni, Pelanggan, History, Interaksi,
   Pengaturan, Profil, Edit Web, Reset Data) IKUT ter-scale 80% juga.
   Akibatnya modal itu cuma nutup 80% tinggi & lebar layar ASLI, sisa
   20% di bawah/kanan kelihatan kosong (nembus ke warna halaman di
   baliknya, kelihatan kayak "rectangle" aneh).

   FIX: kasih box-nya ukuran 125% (= 100 / 80%) dari ukuran yang
   dihitung SEBELUM zoom, supaya HASIL akhirnya (setelah di-scale 80%)
   balik jadi pas 100% menutupi layar asli. Posisinya tetap dianggap
   "nempel" di pojok kiri-atas (top:0; left:0; dari `inset-0`), jadi
   perhitungan `flex items-center justify-center` di dalamnya TETAP pas
   di tengah screen asli (proporsi tengah ikut ter-scale sama rata).

   Scope ke `html[data-theme]` -- atribut ini SELALU ada di <html> admin
   (lihat data-theme="{{ auth()->user()->theme ?? 'glow' }}" di
   admin-panel.blade.php) dan TIDAK ADA di halaman frontend, jadi rule
   ini otomatis hanya berlaku di Admin Panel. */
html[data-theme] .fixed.inset-0 {
    width: 125vw;
    height: 125vh;
}
'@

if ($content.IndexOf($old) -lt 0) {
    Write-Host "[ERROR] Blok CSS yang dicari tidak ketemu persis di $file." -ForegroundColor Red
    Write-Host "        Kemungkinan file ini sudah diubah lagi sejak versi yang dipakai untuk bikin patch ini." -ForegroundColor Red
    Write-Host "        Tidak ada perubahan yang dilakukan." -ForegroundColor Red
    exit 1
}

if ($content.IndexOf("html[data-theme] .fixed.inset-0") -ge 0) {
    Write-Host "[INFO] Rule ini sudah ada sebelumnya di $file -- tidak ada perubahan." -ForegroundColor Yellow
    exit 0
}

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backup = "$file.bak-before-fix-admin-zoom-gap-v3-$timestamp"
Copy-Item $file $backup
Write-Host "Backup dibuat : $backup" -ForegroundColor DarkGray

$newContent = $content.Replace($old, $newBlock)
[System.IO.File]::WriteAllText((Resolve-Path $file), $newContent, (New-Object System.Text.UTF8Encoding($false)))

Write-Host "[OK] Rule kompensasi zoom untuk semua modal admin (fixed inset-0) sudah ditambahkan." -ForegroundColor Green
Write-Host ""
Write-Host "WAJIB dilakukan sekarang, supaya CSS baru ke-compile:" -ForegroundColor Cyan
Write-Host "  npm run build" -ForegroundColor White
Write-Host "(atau 'npm run dev' / 'composer run dev' kalau itu yang biasa kamu pakai)" -ForegroundColor Cyan
Write-Host ""
Write-Host "Baru setelah itu hard refresh (Ctrl+Shift+R) dan coba buka beberapa modal admin." -ForegroundColor Cyan
