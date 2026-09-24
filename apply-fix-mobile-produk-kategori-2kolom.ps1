# apply-fix-mobile-produk-kategori-2kolom.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   powershell -ExecutionPolicy Bypass -File apply-fix-mobile-produk-kategori-2kolom.ps1
#
# Latar belakang: di HP, section "Semua Produk" & "Produk Berdasarkan
# Kategori" sebelumnya 1 kartu per baris (grid-cols-1), jadi terasa
# "ke bawah terus" (scroll panjang). Sekarang diubah jadi 2 kartu
# berdampingan sejak mobile (grid-cols-2), baru 3/4 kolom mulai lg
# seperti semula -- supaya lebih terasa "menyamping" seperti desktop.
#
# Supaya kartu tetap rapi di lebar sempit (2 kolom di layar ~360-430px),
# beberapa ukuran teks di dalam kartu produk ikut dikecilkan KHUSUS di
# mobile (kembali ke ukuran semula mulai sm: ke atas), dan deskripsi
# produk dibatasi maksimal 2 baris (line-clamp) supaya tinggi kartu
# tidak njomplang antar produk. Tidak ada logic PHP/data yang diubah.
#
# File yang disentuh:
#   - resources/views/partials/frontend/products.blade.php   (grid Semua Produk)
#   - resources/views/partials/frontend/categories.blade.php (grid Kategori)
#   - resources/views/partials/frontend/product-card.blade.php (ukuran teks kartu)
#
# Sama seperti script apply-fix-mobile-beranda.ps1: tiap pola dicek
# harus cocok PERSIS 1 kali sebelum diganti, backup dibuat lebih dulu,
# dan aman dijalankan berulang (dilewati kalau sudah pernah dipatch).

$ErrorActionPreference = "Stop"
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"

function Read-TextFile($path) {
    if (-not (Test-Path $path)) { throw "Tidak ketemu: $path (jalankan script ini dari root project, folder yang ada file 'artisan')" }
    $full  = (Resolve-Path $path).Path
    $bytes = [System.IO.File]::ReadAllBytes($full)
    $hasBom = ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF)
    $text  = [System.IO.File]::ReadAllText($full, (New-Object System.Text.UTF8Encoding($false)))
    return @{ Full = $full; HasBom = $hasBom; Text = $text }
}

function Apply-Fix($relPath, $label, $old, $new, $backupSuffix) {
    Write-Host "-> $label" -ForegroundColor Cyan
    $f = Read-TextFile $relPath

    $idx = $f.Text.IndexOf($old)
    $idxLast = $f.Text.LastIndexOf($old)
    if ($idx -lt 0) {
        if ($f.Text.IndexOf($new) -ge 0) {
            Write-Host "   Sudah dalam kondisi yang diperbaiki, dilewati." -ForegroundColor Yellow
            return
        }
        throw "Pola lama tidak ditemukan persis di $relPath. Kemungkinan file sudah diubah manual sebelumnya -- cek manual, tidak ada yang ditimpa."
    }
    if ($idx -ne $idxLast) {
        throw "Pola lama ditemukan LEBIH dari 1 kali di $relPath -- tidak aman diganti otomatis, cek manual, tidak ada yang ditimpa."
    }

    Copy-Item $relPath "$relPath.bak-$backupSuffix-$stamp"

    $newText = $f.Text.Replace($old, $new)
    [System.IO.File]::WriteAllText($f.Full, $newText, (New-Object System.Text.UTF8Encoding($f.HasBom)))

    Write-Host "   OK. Backup: $relPath.bak-$backupSuffix-$stamp" -ForegroundColor Green
}

# ============================================================
# 1) SEMUA PRODUK -- 2 kolom sejak mobile
# ============================================================
Apply-Fix `
    -relPath "resources/views/partials/frontend/products.blade.php" `
    -label "Semua Produk: grid-cols-2 di mobile (sebelumnya grid-cols-1)" `
    -old '<div class="mt-10 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">' `
    -new '<div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-2 sm:gap-8 lg:grid-cols-3">' `
    -backupSuffix "mobile-2col"

# ============================================================
# 2) PRODUK BERDASARKAN KATEGORI -- 2 kolom sejak mobile
# ============================================================
Apply-Fix `
    -relPath "resources/views/partials/frontend/categories.blade.php" `
    -label "Kategori: grid-cols-2 di mobile (sebelumnya grid-cols-1)" `
    -old '<div class="mt-10 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">' `
    -new '<div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-2 sm:gap-8 lg:grid-cols-4">' `
    -backupSuffix "mobile-2col"

# ============================================================
# 3) KARTU PRODUK -- teks dikecilkan & deskripsi dibatasi 2 baris
#    khusus di mobile, supaya rapi saat kartu jadi sempit (2 kolom)
# ============================================================
Apply-Fix `
    -relPath "resources/views/partials/frontend/product-card.blade.php" `
    -label "Kartu produk: judul mengecil di mobile" `
    -old '<p class="text-base font-semibold" style="color: {{ $cardTitleColor }};">{{ $product->nama }}</p>' `
    -new '<p class="text-sm font-semibold sm:text-base" style="color: {{ $cardTitleColor }};">{{ $product->nama }}</p>' `
    -backupSuffix "mobile-card-title"

Apply-Fix `
    -relPath "resources/views/partials/frontend/product-card.blade.php" `
    -label "Kartu produk: deskripsi dibatasi 2 baris & mengecil di mobile" `
    -old '<p class="mt-0.5 text-sm" style="color: {{ $cardBodyColor }};">{{ $product->deskripsi_pendek }}</p>' `
    -new '<p class="mt-0.5 line-clamp-2 text-xs sm:text-sm" style="color: {{ $cardBodyColor }};">{{ $product->deskripsi_pendek }}</p>' `
    -backupSuffix "mobile-card-desc"

Apply-Fix `
    -relPath "resources/views/partials/frontend/product-card.blade.php" `
    -label "Kartu produk: harga diskon mengecil di mobile" `
    -old '<span class="text-base font-semibold text-red-600">' `
    -new '<span class="text-sm font-semibold sm:text-base text-red-600">' `
    -backupSuffix "mobile-card-price-discount"

Apply-Fix `
    -relPath "resources/views/partials/frontend/product-card.blade.php" `
    -label "Kartu produk: harga coret mengecil di mobile" `
    -old '<span class="text-sm line-through" style="color: {{ $cardBodyColor }}; opacity: 0.7;">' `
    -new '<span class="text-xs sm:text-sm line-through" style="color: {{ $cardBodyColor }}; opacity: 0.7;">' `
    -backupSuffix "mobile-card-price-strike"

Apply-Fix `
    -relPath "resources/views/partials/frontend/product-card.blade.php" `
    -label "Kartu produk: harga normal mengecil di mobile" `
    -old '<span class="text-base font-semibold" style="color: {{ $cardPriceColor }};">' `
    -new '<span class="text-sm font-semibold sm:text-base" style="color: {{ $cardPriceColor }};">' `
    -backupSuffix "mobile-card-price-normal"

Write-Host ""
Write-Host "Selesai. Semua Produk & Kategori sekarang 2 kolom sejak mobile." -ForegroundColor Green
Write-Host "Kalau perubahan belum kelihatan di browser:"
Write-Host "  php artisan view:clear"
Write-Host "  (lalu hard refresh browser / mode inspect mobile: Ctrl+Shift+R)"
Write-Host ""
