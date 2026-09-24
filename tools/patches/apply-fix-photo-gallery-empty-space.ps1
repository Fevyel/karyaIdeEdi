$ErrorActionPreference = 'Stop'

function Write-Step($text) { Write-Host "`n$text" -ForegroundColor Cyan }
if (-not (Test-Path '.\artisan')) { throw "Jalankan script ini dari root project Laravel (folder karyaIdeEdi)." }

Write-Host '=============================================================' -ForegroundColor Yellow
Write-Host ' Rapikan Space Kosong Galeri Foto Dokumentasi' -ForegroundColor Yellow
Write-Host '=============================================================' -ForegroundColor Yellow

$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupDir = Join-Path (Get-Location) ".backup-fix-photo-gallery-space-$timestamp"
$bookingPath = '.\resources\views\pages\frontend\booking.blade.php'

Write-Step '[1/5] Membuat backup ...'
New-Item -ItemType Directory -Path (Join-Path $backupDir 'resources\views\pages\frontend') -Force | Out-Null
Copy-Item $bookingPath (Join-Path $backupDir 'resources\views\pages\frontend\booking.blade.php') -Force
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Write-Step '[2/5] Memperbaiki komposisi bento ...'
$tmpPhp = Join-Path $env:TEMP "patch-fix-photo-gallery-space-$timestamp.php"
@'
<?php
$path = getcwd() . DIRECTORY_SEPARATOR . 'resources/views/pages/frontend/booking.blade.php';
$content = file_get_contents($path);
if ($content === false) throw new RuntimeException('Gagal membaca booking.blade.php');

$oldGrid = 'class="mt-10 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4" style="grid-auto-rows: 220px;"';
$newGrid = 'class="mt-10 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4" style="grid-auto-flow: dense; grid-auto-rows: 220px;"';
if (strpos($content, $oldGrid) !== false) $content = str_replace($oldGrid, $newGrid, $content);
elseif (strpos($content, $newGrid) === false) throw new RuntimeException('Grid Galeri Foto tidak ditemukan.');

$old = <<<'BLADE'
                            $layoutClass = match ($i % 6) {
                                0 => 'md:col-span-2 md:row-span-2',
                                1 => 'xl:row-span-2',
                                2 => 'md:col-span-1',
                                3 => 'md:col-span-1',
                                4 => 'md:col-span-2',
                                default => 'md:col-span-1',
                            };
BLADE;
$new = <<<'BLADE'
                            if ($i < 4) {
                                $layoutClass = match ($i) {
                                    0 => 'md:col-span-2 md:row-span-2',
                                    1 => 'xl:row-span-2',
                                    2 => 'md:col-span-1',
                                    3 => 'md:col-span-1',
                                };
                            } else {
                                $tailCount = max($dokPhotoCount - 4, 1);
                                $tailIndex = $i - 4;
                                $fullRows = intdiv($tailCount, 4);
                                $remainder = $tailCount % 4;
                                $isLastPartialRow = $remainder > 0 && $tailIndex >= ($fullRows * 4);
                                $positionInLastRow = $tailIndex - ($fullRows * 4);

                                if (! $isLastPartialRow) {
                                    $layoutClass = 'md:col-span-1 xl:col-span-1';
                                } elseif ($remainder === 1) {
                                    $layoutClass = 'md:col-span-2 xl:col-span-4';
                                } elseif ($remainder === 2) {
                                    $layoutClass = 'md:col-span-1 xl:col-span-2';
                                } elseif ($remainder === 3) {
                                    $layoutClass = $positionInLastRow === 0
                                        ? 'md:col-span-2 xl:col-span-2'
                                        : 'md:col-span-1 xl:col-span-1';
                                } else {
                                    $layoutClass = 'md:col-span-1 xl:col-span-1';
                                }
                            }
BLADE;
if (strpos($content, $old) !== false) $content = str_replace($old, $new, $content);
elseif (strpos($content, '$tailCount = max($dokPhotoCount - 4, 1);') === false) throw new RuntimeException('Blok layout Galeri Foto tidak ditemukan.');

file_put_contents($path, $content);
echo "PATCH_OK\n";
'@ | Set-Content -Path $tmpPhp -Encoding UTF8

php $tmpPhp
Remove-Item $tmpPhp -Force

Write-Step '[3/5] Validasi syntax ...'
php -l $bookingPath | Out-Host

Write-Step '[4/5] Ringkasan ...'
Write-Host '  - Hero tidak disentuh.' -ForegroundColor DarkGray
Write-Host '  - Galeri video tidak disentuh.' -ForegroundColor DarkGray
Write-Host '  - Baris terakhir foto sekarang mengisi lebar tersedia secara otomatis.' -ForegroundColor DarkGray

Write-Step '[5/5] Selesai ...'
Write-Host 'SELESAI' -ForegroundColor Green
