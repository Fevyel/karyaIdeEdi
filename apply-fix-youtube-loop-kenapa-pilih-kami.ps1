<#
  Fix: video YouTube di section "Kenapa Pilih Kami" berhenti di layar kosong
  begitu video selesai, tidak looping ulang dari awal.

  Sebabnya: URL embed YouTube belum punya parameter loop=1&playlist=<ID video>.
  Ini WAJIB dua-duanya -- loop=1 saja TIDAK cukup untuk video tunggal (loop=1
  tanpa playlist cuma bikin YouTube ulangi antrian, dan kalau cuma 1 video di
  antrian, dianggap "selesai" dan berhenti).

  Script ini HANYA mengubah: app/Models/HomeSection.php
  Tidak menyentuh file lain (JS/Alpine, blade, dll -- tidak perlu, karena
  parameter loop ini cukup ditambah sekali di sumber embed_url-nya).

  Cara pakai (dari terminal VS Code, di root project C:\xampp\htdocs\karyaIdeEdi):
    .\apply-fix-youtube-loop-kenapa-pilih-kami.ps1
#>

$ErrorActionPreference = 'Stop'

$relativePath = 'app\Models\HomeSection.php'
$path = Join-Path $PSScriptRoot $relativePath

if (-not (Test-Path $path)) {
    Write-Host "File tidak ditemukan: $path" -ForegroundColor Red
    exit 1
}

$content = [System.IO.File]::ReadAllText($path)

$old = "                'embed_url' => 'https://www.youtube-nocookie.com/embed/'.`$m[1].'?enablejsapi=1&playsinline=1&rel=0&modestbranding=1',`n"
$new = "                'embed_url' => 'https://www.youtube-nocookie.com/embed/'.`$m[1].'?enablejsapi=1&playsinline=1&rel=0&modestbranding=1&loop=1&playlist='.`$m[1],`n"

if (-not $content.Contains($old)) {
    Write-Host "LEWAT: pola tidak ditemukan -- file kemungkinan sudah beda dari yang saya cek." -ForegroundColor Yellow
    Write-Host "Tidak ada yang ditulis ulang." -ForegroundColor Yellow
    exit 1
}

$content = $content.Replace($old, $new)

$utf8WithBom = New-Object System.Text.UTF8Encoding($true)
[System.IO.File]::WriteAllText($path, $content, $utf8WithBom)

Write-Host "OK   : embed_url YouTube ditambah loop=1&playlist=<id video>" -ForegroundColor Green
Write-Host ""
Write-Host "Diperbarui : $relativePath" -ForegroundColor Green
Write-Host ""
Write-Host "Langkah selanjutnya:" -ForegroundColor Cyan
Write-Host "  1. Cek diff dulu: git diff -- $relativePath"
Write-Host "  2. php artisan view:clear"
Write-Host "  3. Hard refresh browser (Ctrl+Shift+R), tonton videonya sampai habis, cek apakah otomatis mengulang."
