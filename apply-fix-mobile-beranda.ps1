# apply-fix-mobile-beranda.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   powershell -ExecutionPolicy Bypass -File apply-fix-mobile-beranda.ps1
#
# Tujuan: 3 perbaikan tampilan MOBILE khusus halaman Beranda, supaya lebih
# konsisten dengan versi desktop. TIDAK ada logic PHP/data yang disentuh,
# murni class Tailwind. Tidak ada file lain yang diubah selain 3 di bawah.
#
#   1) resources/views/partials/frontend/footer.blade.php
#      Grid footer sekarang grid-cols-2 SEJAK mobile (harusnya 1 kolom dulu,
#      baru 2 kolom mulai sm, 4 kolom mulai lg -- sama seperti section lain
#      di halaman ini). Efek sebelumnya: kolom "Katalog"/"Company" berdempetan
#      sempit dan "Support" sendirian di bawah.
#
#   2) resources/views/partials/frontend/alur-booking.blade.php
#      Garis pembatas antar langkah (border-b) cuma ada di langkah 1 & 2.
#      Di HP (tersusun 1 kolom ke bawah), garis antara langkah 3 & 4 hilang
#      sehingga terlihat nempel tanpa pembatas.
#
#   3) resources/views/partials/frontend/hero.blade.php
#      Kartu statistik ("500+ Pelanggan", dst) punya min-width 128px yang
#      terlalu besar untuk 3 kartu sejajar di HP kecil, jadi pecah jadi
#      2 kartu di atas + 1 sendirian di bawah. Sekarang min-width mengecil
#      khusus di mobile (kembali ke 128px mulai sm ke atas seperti semula).
#
# Setiap perubahan dicek dulu jumlah kecocokannya (harus tepat 1). Kalau
# sudah pernah diubah manual / tidak ketemu persis, script berhenti dan
# TIDAK menimpa apa pun -- aman dijalankan berulang untuk dicek statusnya.
# Backup asli tiap file dibuat SETELAH pengecekan lolos (jadi kalau gagal,
# tidak ada file .bak sampah). Encoding asli (ada/tidak ada BOM) dipertahankan.

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
        # Cek apakah kemungkinan sudah dipatch sebelumnya (idempotent)
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
# 1) FOOTER -- grid 1 kolom dulu di mobile
# ============================================================
Apply-Fix `
    -relPath "resources/views/partials/frontend/footer.blade.php" `
    -label "Footer: grid-cols-1 di mobile (sebelumnya grid-cols-2)" `
    -old '<div class="grid grid-cols-2 gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8">' `
    -new '<div class="grid grid-cols-1 gap-8 sm:grid-cols-2 sm:gap-10 lg:grid-cols-4 lg:gap-8">' `
    -backupSuffix "mobile-grid"

# ============================================================
# 2) ALUR BOOKING -- garis pembatas konsisten saat 1 kolom di mobile
# ============================================================
Apply-Fix `
    -relPath "resources/views/partials/frontend/alur-booking.blade.php" `
    -label "Alur Booking: garis pembatas langkah 3 muncul juga di mobile" `
    -old '<div class="relative p-7 sm:p-8 {{ $index < 3 ? ''lg:border-r lg:border-[#E9E0D5]'' : '''' }} {{ $index < 2 ? ''border-b sm:border-b lg:border-b-0 border-[#E9E0D5]'' : '''' }}">' `
    -new '<div class="relative border-[#E9E0D5] p-7 sm:p-8 {{ $index < 3 ? ''lg:border-r'' : '''' }} {{ $index < 2 ? ''border-b sm:border-b lg:border-b-0'' : '''' }} {{ $index === 2 ? ''border-b sm:border-b-0'' : '''' }}">' `
    -backupSuffix "mobile-border"

# ============================================================
# 3) HERO -- kartu statistik muat 3 sejajar di HP kecil
# ============================================================
Apply-Fix `
    -relPath "resources/views/partials/frontend/hero.blade.php" `
    -label "Hero: gap kartu statistik mengecil di mobile" `
    -old '<dl class="animate-fade-in-up mx-auto mt-11 flex max-w-2xl flex-wrap items-stretch justify-center gap-3" style="animation-delay: .48s;">' `
    -new '<dl class="animate-fade-in-up mx-auto mt-11 flex max-w-2xl flex-wrap items-stretch justify-center gap-2 sm:gap-3" style="animation-delay: .48s;">' `
    -backupSuffix "mobile-stats-gap"

Apply-Fix `
    -relPath "resources/views/partials/frontend/hero.blade.php" `
    -label "Hero: min-width kartu statistik mengecil di mobile" `
    -old 'class="flex min-w-[128px] flex-1 flex-col items-center gap-1.5 rounded-2xl border px-5 py-4 backdrop-blur-md"' `
    -new 'class="flex min-w-[88px] flex-1 flex-col items-center gap-1.5 rounded-2xl border px-3 py-3 backdrop-blur-md sm:min-w-[128px] sm:px-5 sm:py-4"' `
    -backupSuffix "mobile-stats-width"

Write-Host ""
Write-Host "Selesai. 3 perbaikan mobile diterapkan (footer, alur-booking, hero)." -ForegroundColor Green
Write-Host "Kalau perubahan belum kelihatan di browser:"
Write-Host "  php artisan view:clear"
Write-Host "  (lalu hard refresh browser / mode inspect mobile: Ctrl+Shift+R)"
Write-Host ""
