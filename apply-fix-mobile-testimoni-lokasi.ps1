# apply-fix-mobile-testimoni-lokasi.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   powershell -ExecutionPolicy Bypass -File apply-fix-mobile-testimoni-lokasi.ps1
#
# 2 perbaikan:
#
#   1) resources/views/partials/frontend/testimonials.blade.php
#      "Ulasan Pelanggan Kami" masih 1 kartu per baris di HP (grid-cols-1).
#      Disamakan dengan Semua Produk/Kategori: 2 kartu berdampingan sejak
#      mobile, padding kartu ikut sedikit dikecilkan khusus di mobile
#      (kembali normal mulai sm:) supaya tetap muat rapi.
#
#   2) resources/views/partials/frontend/lokasi.blade.php
#      Peta Google Maps section "Lokasi" sebelumnya cuma tampil sebagai
#      background full-bleed khusus desktop (class "hidden lg:block"),
#      jadi di HP petanya HILANG SAMA SEKALI, cuma teks & alamat saja.
#      Ditambahkan blok peta terpisah (kartu rounded biasa, bukan
#      background) yang KHUSUS tampil di layar < lg, taruh di bawah
#      info alamat/WhatsApp, sebelum tombol CTA. Peta desktop (full-bleed
#      di background) TIDAK diubah/dihapus sama sekali.
#
# Tidak ada logic PHP/data yang diubah. Pola dicek harus cocok PERSIS
# 1 kali sebelum diganti, backup dibuat lebih dulu, aman dijalankan
# berulang (dilewati kalau sudah pernah dipatch).

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
# 1) TESTIMONI -- 2 kolom sejak mobile
# ============================================================
Apply-Fix `
    -relPath "resources/views/partials/frontend/testimonials.blade.php" `
    -label "Testimoni: grid-cols-2 di mobile (sebelumnya grid-cols-1)" `
    -old '<div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">' `
    -new '<div class="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">' `
    -backupSuffix "mobile-2col"

Apply-Fix `
    -relPath "resources/views/partials/frontend/testimonials.blade.php" `
    -label "Testimoni: padding kartu mengecil di mobile" `
    -old @'
                        class="flex flex-col rounded-3xl p-6 shadow-sm transition-all duration-500 ease-out hover:-translate-y-1.5 hover:shadow-xl
'@ `
    -new @'
                        class="flex flex-col rounded-2xl p-4 shadow-sm transition-all duration-500 ease-out hover:-translate-y-1.5 hover:shadow-xl sm:rounded-3xl sm:p-6
'@ `
    -backupSuffix "mobile-card-padding"

# ============================================================
# 2) LOKASI -- peta muncul juga di mobile/tablet (< lg)
# ============================================================
Apply-Fix `
    -relPath "resources/views/partials/frontend/lokasi.blade.php" `
    -label "Lokasi: tambah kartu peta khusus mobile/tablet (peta desktop tidak diubah)" `
    -old @'
            </dl>

            <div class="mt-7 flex flex-wrap items-center gap-3">
'@ `
    -new @'
            </dl>

            {{-- ============ Peta khusus mobile/tablet (< lg) -- desktop sudah punya
                 peta full-bleed di background section (lihat blok "BACKGROUND: Google
                 Maps asli" di atas file ini, class "hidden lg:block"). Blok ini SUPAYA
                 peta tetap terlihat di layar sempit yang background-nya disembunyikan.
                 Sumber peta ($lokasiMapEmbedSrc) & filter warna PERSIS sama dengan versi
                 desktop, cuma ditampilkan sebagai kartu biasa (bukan background). ============ --}}
            <div class="mt-6 overflow-hidden rounded-2xl lg:hidden">
                <iframe
                    src="{{ $lokasiMapEmbedSrc }}"
                    class="h-48 w-full border-0 sm:h-64"
                    style="filter: grayscale(45%) sepia(65%) hue-rotate(-8deg) saturate(140%) brightness(1.05) contrast(0.94);"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Peta lokasi {{ $lokasiSetting->site_name }}"
                ></iframe>
            </div>

            <div class="mt-7 flex flex-wrap items-center gap-3">
'@ `
    -backupSuffix "mobile-map"

Write-Host ""
Write-Host "Selesai. Testimoni 2 kolom di mobile, peta Lokasi sekarang tampil juga di HP/tablet." -ForegroundColor Green
Write-Host "Kalau perubahan belum kelihatan di browser:"
Write-Host "  php artisan view:clear"
Write-Host "  (lalu hard refresh browser / mode inspect mobile: Ctrl+Shift+R)"
Write-Host ""
