# Diagnostik: tampilkan bagian file yang relevan + info encoding,
# supaya bisa dibandingkan persis dengan yang saya kira.
$path = "resources\views\pages\admin\edit-web.blade.php"

Write-Host "=== Info file ===" -ForegroundColor Cyan
$bytes = [System.IO.File]::ReadAllBytes($path)
Write-Host "3 byte pertama (hex): $($bytes[0].ToString('x2')) $($bytes[1].ToString('x2')) $($bytes[2].ToString('x2'))"
$content = [System.IO.File]::ReadAllText($path)
Write-Host "Panjang total karakter: $($content.Length)"
Write-Host "Ada CRLF? $($content -match "`r`n")"
Write-Host ""

Write-Host "=== Baris di sekitar 'testimoniUseCustomBg' ===" -ForegroundColor Cyan
Select-String -Path $path -Pattern "testimoniUseCustomBg" -Context 2,20 | ForEach-Object { $_.Line; $_.Context.PostContext }
Write-Host ""

Write-Host "=== Baris di sekitar 'function selectTestimoniCardPreset' ===" -ForegroundColor Cyan
Select-String -Path $path -Pattern "function selectTestimoniCardPreset" -Context 1,5 | ForEach-Object { $_.Context.PreContext; $_.Line; $_.Context.PostContext }
