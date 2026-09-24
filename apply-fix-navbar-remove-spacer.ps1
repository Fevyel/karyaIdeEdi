# apply-fix-navbar-remove-spacer.ps1  (versi perbaikan)
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   powershell -ExecutionPolicy Bypass -File apply-fix-navbar-remove-spacer.ps1
#
# Tujuan: hapus komentar + <div class="h-17" aria-hidden="true"></div> yang
# ada tepat sesudah </header> di navbar. Header sekarang "sticky" (bukan
# "fixed"), jadi spacer ini tidak diperlukan lagi dan malah menambah ruang
# kosong dobel di bawah navbar. Tidak ada bagian lain yang disentuh.
#
# Perbaikan dari versi sebelumnya: komentar spacer itu MULTI-BARIS, sedangkan
# regex lama tidak memakai mode Singleline sehingga "." tidak melewati baris
# baru dan hasilnya selalu 0 kecocokan. Sekarang memakai (?s).
# Backup juga baru dibuat SETELAH pengecekan lolos, jadi kalau gagal tidak
# meninggalkan file .bak sampah.
# Encoding asli file (ada/tidak ada BOM) dipertahankan apa adanya.

$ErrorActionPreference = "Stop"

$navbarPath = "resources/views/partials/frontend/navbar.blade.php"
if (-not (Test-Path $navbarPath)) { throw "Tidak ketemu: $navbarPath (jalankan script ini dari root project)" }

$fullPath = (Resolve-Path $navbarPath).Path

$bytes  = [System.IO.File]::ReadAllBytes($fullPath)
$hasBom = ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF)

$navbar = [System.IO.File]::ReadAllText($fullPath, (New-Object System.Text.UTF8Encoding($false)))

$patternSpacer = [regex]'(?s)\{\{-- Spacer.*?<div class="h-17" aria-hidden="true"></div>\r?\n?'

$countSpacer = $patternSpacer.Matches($navbar).Count
if ($countSpacer -ne 1) {
    throw "Blok komentar + div spacer h-17 ditemukan $countSpacer kali (harusnya 1, mungkin sudah pernah diubah/dihapus). Tidak ada yang ditimpa, cek manual."
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
Copy-Item $navbarPath "$navbarPath.bak-before-remove-spacer-$stamp"

$navbar = $patternSpacer.Replace($navbar, "")

[System.IO.File]::WriteAllText($fullPath, $navbar, (New-Object System.Text.UTF8Encoding($hasBom)))

Write-Host ""
Write-Host "Selesai." -ForegroundColor Green
Write-Host "Diubah: $navbarPath (komentar + div spacer h-17 dihapus)"
Write-Host "Backup asli disimpan sebagai $navbarPath.bak-before-remove-spacer-$stamp"
Write-Host ""
Write-Host "Kalau perubahan belum kelihatan di browser, jalankan: php artisan view:clear"
