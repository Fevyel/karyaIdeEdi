$ZipPath     = "$env:USERPROFILE\Downloads\karyaIdeEdi-ui-revamp.zip"
$ProjectPath = "C:\xampp\htdocs\karyaIdeEdi"

$ErrorActionPreference = "Stop"
if (-not (Test-Path $ZipPath)) { Write-Host "Zip tidak ditemukan di: $ZipPath" -ForegroundColor Red; exit 1 }

$TempExtract = Join-Path $env:TEMP "karyaIdeEdi-ui-extract"
if (Test-Path $TempExtract) { Remove-Item $TempExtract -Recurse -Force }

Expand-Archive -Path $ZipPath -DestinationPath $TempExtract -Force
$SourceRoot = Join-Path $TempExtract "karyaIdeEdi"
robocopy $SourceRoot $ProjectPath /E /NFL /NDL /NJH /NJS /NC /NS | Out-Null
Remove-Item $TempExtract -Recurse -Force

Write-Host "Selesai." -ForegroundColor Green
