$path = "resources\views\partials\frontend\testimonials.blade.php"

Write-Host "=== Cek testimonials.blade.php ===" -ForegroundColor Cyan
$content = [System.IO.File]::ReadAllText($path)
$sudahBaru = $content.Contains("testimoniCardColors[$rank]") -or $content.Contains('$testimoniCardColors = $testimoniSection')
Write-Host "Mengandung kode versi BARU (per-rank)? $sudahBaru"
$adaLama = $content.Contains('$testimoniCardColor = $testimoniSection')
Write-Host "Masih ada kode versi LAMA (1 warna)? $adaLama"
