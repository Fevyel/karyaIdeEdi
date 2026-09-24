$ErrorActionPreference = 'Stop'

function Step($text) { Write-Host "`n$text" -ForegroundColor Cyan }

if (-not (Test-Path '.\artisan')) {
    throw "File artisan tidak ditemukan. Jalankan script dari root project C:\xampp\htdocs\karyaIdeEdi"
}

Write-Host '=============================================================' -ForegroundColor Yellow
Write-Host ' Perbesar Pita Media + Blend Antar Foto/Video Tanpa Batas' -ForegroundColor Yellow
Write-Host '=============================================================' -ForegroundColor Yellow

$file = '.\resources\views\pages\frontend\booking.blade.php'
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = "$file.bak-docmedia-blend-$stamp"

Step '[1/4] Backup file ...'
Copy-Item $file $backup -Force
Write-Host "  Backup: $backup" -ForegroundColor DarkGray

Step '[2/4] Perbaiki tampilan pita media ...'
$content = Get-Content $file -Raw

$old1 = '.kie-docmedia{position:relative;overflow:hidden;width:100%;background:linear-gradient(90deg,#2b1b12,#4a2d1d 48%,#2b1b12);padding:1rem 0;isolation:isolate}'
$new1 = '.kie-docmedia{position:relative;overflow:hidden;width:100%;background:#2b1b12;padding:0;isolation:isolate}'
if (-not $content.Contains($old1)) { throw 'Style utama .kie-docmedia tidak ditemukan persis. Tidak ada perubahan dilakukan.' }
$content = $content.Replace($old1, $new1)

$old2 = '.kie-docmedia__item{position:relative;flex:0 0 clamp(320px,28vw,460px);height:clamp(180px,16vw,250px);overflow:hidden;margin:0 -.45rem;background:#2b1b12;-webkit-mask-image:linear-gradient(to right,transparent 0,#000 9%,#000 91%,transparent 100%);mask-image:linear-gradient(to right,transparent 0,#000 9%,#000 91%,transparent 100%)}'
$new2 = '.kie-docmedia__item{position:relative;flex:0 0 clamp(440px,34vw,640px);height:clamp(260px,21vw,380px);overflow:hidden;margin:0 -4.25rem;background:transparent;-webkit-mask-image:linear-gradient(to right,transparent 0%,rgba(0,0,0,.18) 6%,#000 19%,#000 81%,rgba(0,0,0,.18) 94%,transparent 100%);mask-image:linear-gradient(to right,transparent 0%,rgba(0,0,0,.18) 6%,#000 19%,#000 81%,rgba(0,0,0,.18) 94%,transparent 100%)}'
if (-not $content.Contains($old2)) { throw 'Style ukuran/mask .kie-docmedia__item tidak ditemukan persis. Tidak ada perubahan dilakukan.' }
$content = $content.Replace($old2, $new2)

$old3 = '.kie-docmedia__item:after{content:'';position:absolute;inset:0;background:linear-gradient(to top,rgba(20,12,8,.62),transparent 58%);pointer-events:none}'
$new3 = '.kie-docmedia__item:after{content:'';position:absolute;inset:0;background:linear-gradient(to top,rgba(20,12,8,.5) 0%,rgba(20,12,8,.12) 34%,transparent 62%);pointer-events:none}'
if (-not $content.Contains($old3)) { throw 'Overlay item pita tidak ditemukan persis. Tidak ada perubahan dilakukan.' }
$content = $content.Replace($old3, $new3)

$old4 = '.kie-docmedia__item figcaption{position:absolute;z-index:2;left:1.35rem;right:1.35rem;bottom:1.05rem;color:#fff;font-size:.98rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-shadow:0 2px 8px rgba(0,0,0,.35)}.kie-docmedia__fade{position:absolute;z-index:4;top:0;bottom:0;width:11vw;pointer-events:none}.kie-docmedia__fade--left{left:0;background:linear-gradient(to right,#2b1b12,transparent)}.kie-docmedia__fade--right{right:0;background:linear-gradient(to left,#2b1b12,transparent)}'
$new4 = '.kie-docmedia__item figcaption{position:absolute;z-index:2;left:4.75rem;right:4.75rem;bottom:1.35rem;color:#fff;font-size:1.06rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-shadow:0 2px 10px rgba(0,0,0,.45)}.kie-docmedia__fade{position:absolute;z-index:4;top:0;bottom:0;width:8vw;pointer-events:none}.kie-docmedia__fade--left{left:0;background:linear-gradient(to right,#2b1b12 0%,rgba(43,27,18,.82) 28%,transparent 100%)}.kie-docmedia__fade--right{right:0;background:linear-gradient(to left,#2b1b12 0%,rgba(43,27,18,.82) 28%,transparent 100%)}'
if (-not $content.Contains($old4)) { throw 'Style caption/fade pita tidak ditemukan persis. Tidak ada perubahan dilakukan.' }
$content = $content.Replace($old4, $new4)

Set-Content -Path $file -Value $content -Encoding UTF8

Step '[3/4] Validasi syntax ...'
php -l $file | Out-Host

Step '[4/4] Selesai ...'
Write-Host 'SELESAI' -ForegroundColor Green
Write-Host '  - Outline/ruang luar pita dihilangkan' -ForegroundColor DarkGray
Write-Host '  - Media diperbesar' -ForegroundColor DarkGray
Write-Host '  - Foto/video saling overlap dengan mask gradient sehingga batas antar file tersamarkan' -ForegroundColor DarkGray
Write-Host '  - Autoplay video tetap dipertahankan' -ForegroundColor DarkGray
