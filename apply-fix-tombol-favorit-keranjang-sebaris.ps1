<#
    apply-fix-tombol-favorit-keranjang-sebaris.ps1
    ================================================
    Tujuan:
      1. Tombol favorit & keranjang di kartu produk (halaman /produk) jadi
         SEBARIS (flex-row), bukan numpuk vertikal (flex-col).
      2. Geser posisinya sedikit menjauh dari pojok kartu
         (right-3/top-3 -> right-4/top-4).

    File yang diubah:
      resources/views/partials/frontend/product-card.blade.php

    Jalankan dari ROOT project (folder yang ada file artisan-nya):
      cd C:\xampp\htdocs\karyaIdeEdi
      .\apply-fix-tombol-favorit-keranjang-sebaris.ps1

    Script ini otomatis bikin backup sebelum mengubah apa pun, dan TIDAK
    menyentuh file lain selain product-card.blade.php.
#>

$ErrorActionPreference = 'Stop'

$target = 'resources\views\partials\frontend\product-card.blade.php'

if (-not (Test-Path $target)) {
    Write-Host "File tidak ditemukan: $target" -ForegroundColor Red
    Write-Host "Pastikan script ini dijalankan dari root project (folder yang ada artisan)." -ForegroundColor Yellow
    exit 1
}

$content = Get-Content -Path $target -Raw

$old = 'absolute right-3 top-3 z-10 flex flex-col gap-2'
$new = 'absolute right-4 top-4 z-10 flex flex-row gap-2'

if ($content -notmatch [regex]::Escape($old)) {
    Write-Host "Pola lama tidak ditemukan di $target." -ForegroundColor Yellow
    Write-Host "Kemungkinan file sudah pernah diubah sebelumnya, atau strukturnya beda." -ForegroundColor Yellow
    Write-Host "Tidak ada perubahan dilakukan (aman, tidak ada backup yang dibuat)." -ForegroundColor Yellow
    exit 0
}

$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupPath = "$target.bak-tombol-sebaris-$timestamp"
Copy-Item -Path $target -Destination $backupPath
Write-Host "Backup dibuat: $backupPath" -ForegroundColor Green

$updated = $content.Replace($old, $new)
Set-Content -Path $target -Value $updated -NoNewline

Write-Host "Selesai. Tombol favorit & keranjang sekarang sebaris (flex-row)" -ForegroundColor Green
Write-Host "dan posisinya digeser ke right-4/top-4 (tidak mepet pojok lagi)." -ForegroundColor Green
Write-Host ""
Write-Host "Cek hasilnya di browser (php artisan serve masih jalan), lalu kalau" -ForegroundColor Cyan
Write-Host "sudah oke, boleh commit perubahannya." -ForegroundColor Cyan
