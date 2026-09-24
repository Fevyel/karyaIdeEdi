# apply-fix-strip-bom-navbar-hero.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   powershell -ExecutionPolicy Bypass -File apply-fix-strip-bom-navbar-hero.ps1
#
# Penyebab gap atas & bawah navbar: dua file partial ini diawali karakter BOM
# (EF BB BF, sisa dari Set-Content -Encoding UTF8 di PowerShell 5.1). Karena
# di-@include di tengah <body>, BOM ikut tercetak sebagai teks kosong dan
# membuat satu baris setinggi line-height (+-24px) di:
#   - navbar.blade.php -> tepat di atas navbar   (gap atas)
#   - hero.blade.php   -> tepat di bawah navbar  (gap bawah)
#
# Skrip ini HANYA membuang 3 byte BOM di awal kedua file. Isi lainnya tidak
# disentuh sama sekali (dibaca & ditulis ulang sebagai byte mentah).
# Aman dijalankan berulang: file yang sudah bersih dilewati.

$ErrorActionPreference = "Stop"

$targets = @(
    "resources/views/partials/frontend/navbar.blade.php",
    "resources/views/partials/frontend/hero.blade.php"
)

foreach ($relPath in $targets) {
    if (-not (Test-Path $relPath)) { throw "Tidak ketemu: $relPath (jalankan script ini dari root project)" }
}

foreach ($relPath in $targets) {
    $fullPath = (Resolve-Path $relPath).Path
    $bytes = [System.IO.File]::ReadAllBytes($fullPath)

    if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
        $clean = New-Object byte[] ($bytes.Length - 3)
        [System.Array]::Copy($bytes, 3, $clean, 0, $clean.Length)
        [System.IO.File]::WriteAllBytes($fullPath, $clean)
        Write-Host "BOM dihapus : $relPath" -ForegroundColor Green
    }
    else {
        Write-Host "Sudah bersih (tidak ada BOM) : $relPath" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "Selesai. Lanjutkan dengan: php artisan view:clear" -ForegroundColor Green
