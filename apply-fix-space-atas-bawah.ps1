# apply-fix-space-atas-bawah.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   powershell -ExecutionPolicy Bypass -File apply-fix-space-atas-bawah.ps1
#
# Yang dilakukan:
# resources/css/app.css -> tambah margin:0; padding:0; di html,body supaya
# warna latar body (krem, bg-admin-canvas) tidak lagi nongol sebagai
# "space" kosong di atas navbar dan di bawah footer.

$ErrorActionPreference = "Stop"

$cssPath = "resources/css/app.css"

if (-not (Test-Path $cssPath)) { throw "Tidak ketemu: $cssPath (jalankan script ini dari root project)" }

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
Copy-Item $cssPath "$cssPath.bak-before-fix-space-atas-bawah-$stamp"

$css = Get-Content $cssPath -Raw -Encoding UTF8

$old = @"
html, body {
    overscroll-behavior-y: none;
}
"@

$new = @"
html, body {
    margin: 0;
    padding: 0;
    overscroll-behavior-y: none;
}
"@

if ($css -notmatch [regex]::Escape($old)) {
    throw "Blok 'html, body { overscroll-behavior-y: none; }' tidak cocok persis (mungkin sudah pernah diubah). Tidak ada yang ditimpa, cek manual."
}

$css = $css.Replace($old, $new)
Set-Content $cssPath -Value $css -Encoding UTF8 -NoNewline

Write-Host ""
Write-Host "Selesai." -ForegroundColor Green
Write-Host "Diubah: $cssPath"
Write-Host "Backup asli disimpan sebagai *.bak-before-fix-space-atas-bawah-$stamp"
Write-Host ""
Write-Host "PENTING: ini file CSS, jadi WAJIB build ulang Vite:" -ForegroundColor Yellow
Write-Host "  npm run build"
Write-Host "(atau kalau lagi jalankan 'npm run dev' / 'composer run dev', biarkan saja, otomatis kepantau)"
