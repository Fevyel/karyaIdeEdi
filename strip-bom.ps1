# ============================================================
# FIX: Hapus BOM (Byte Order Mark) yang kepasang otomatis oleh
# "Set-Content -Encoding UTF8" (Windows PowerShell 5.1 selalu
# nambahin BOM, walau parameternya cuma bilang "UTF8" polos).
# Ini nyisir SEMUA file .php dan .blade.php di project (kecuali
# vendor/ dan node_modules/) dan bersihin BOM kalau ketemu.
# File yang memang tidak ada BOM-nya dilewati, tidak disentuh.
#
# Cara pakai: jalankan dari folder project.
#   .\strip-bom.ps1
# ============================================================

$ErrorActionPreference = "Stop"

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Membersihkan BOM dari file .php & .blade.php" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

$files = Get-ChildItem -Path . -Recurse -File -Include *.php |
    Where-Object { $_.FullName -notmatch '\\vendor\\' -and $_.FullName -notmatch '\\node_modules\\' -and $_.FullName -notmatch '\\storage\\framework\\' }

$fixed = 0
$checked = 0

foreach ($f in $files) {
    $checked++
    $bytes = [System.IO.File]::ReadAllBytes($f.FullName)

    if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
        $newBytes = $bytes[3..($bytes.Length - 1)]
        [System.IO.File]::WriteAllBytes($f.FullName, $newBytes)
        Write-Host "  BOM dihapus: $($f.FullName.Substring((Get-Location).Path.Length + 1))" -ForegroundColor Green
        $fixed++
    }
}

Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Selesai. $checked file dicek, $fixed file diperbaiki." -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan

if ($fixed -eq 0) {
    Write-Host "Tidak ada BOM ketemu -- kalau error 'namespace declaration' masih muncul, kemungkinan penyebabnya bukan BOM. Kirim error lengkapnya lagi." -ForegroundColor Yellow
} else {
    Write-Host "Coba refresh /lacak lagi sekarang." -ForegroundColor White
}
