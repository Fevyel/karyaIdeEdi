# ============================================================
# FIX: Error 500 di halaman Ketentuan Layanan (Terms of Service)
# "Undefined array key 1" di terms-of-service.blade.php baris 173
#
# Penyebab: kode nganggep SEMUA body section yang berbentuk array
# itu pasti isinya link format "url|Label". Padahal section
# "Proses Pemesanan Custom" isinya bullet list teks biasa (tanpa
# tanda '|'), jadi explode('|', $link) gagal ambil elemen ke-2.
#
# Fix: tambah pengecekan - kalau item array-nya beneran ada '|'
# baru dirender jadi tombol link, kalau enggak dirender jadi
# bullet list biasa (sama kayak di halaman Privacy Policy).
#
# Script ini HANYA mengganti blok kode yang error (baris ~169-185),
# bagian lain file tidak disentuh.
#
# Cara pakai:
# 1. Jalankan dari folder project (C:\xampp\htdocs\karyaIdeEdi)
# 2. .\apply-fix-terms-links-array.ps1
# ============================================================

$ErrorActionPreference = "Stop"
$targetFile = "resources\views\pages\frontend\terms-of-service.blade.php"

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Fix: Error 500 Ketentuan Layanan (array key 1)" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    Write-Host "Contoh: C:\xampp\htdocs\karyaIdeEdi" -ForegroundColor Yellow
    exit 1
}

if (-not (Test-Path $targetFile)) {
    Write-Host "[ERROR] File tidak ditemukan: $targetFile" -ForegroundColor Red
    exit 1
}

# Backup dulu sebelum diubah
$backupFile = "$targetFile.bak"
Copy-Item $targetFile $backupFile -Force
Write-Host "[1/3] Backup dibuat: $backupFile" -ForegroundColor Green

$content = Get-Content $targetFile -Raw -Encoding UTF8

$oldBlock = @'
                                @foreach ($section['body'] as $paragraph)
                                    @if (is_array($paragraph))
                                        <div class="flex flex-col gap-2 sm:flex-row sm:gap-4">
                                            @foreach ($paragraph as $link)
                                                @php [$linkHref, $linkLabel] = explode('|', $link); @endphp
                                                <a href="{{ $linkHref }}" class="inline-flex items-center gap-2 rounded-full border border-admin-accent/30 px-4 py-2 text-xs font-semibold text-admin-accent transition-colors duration-300 hover:bg-admin-cream">
                                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                                    {{ $linkLabel }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-sm leading-relaxed text-[#4A423B] sm:text-[15px]">
                                            {!! $paragraph !!}
                                        </p>
                                    @endif
                                @endforeach
'@

$newBlock = @'
                                @foreach ($section['body'] as $paragraph)
                                    @if (is_array($paragraph) && str_contains($paragraph[0] ?? '', '|'))
                                        <div class="flex flex-col gap-2 sm:flex-row sm:gap-4">
                                            @foreach ($paragraph as $link)
                                                @php [$linkHref, $linkLabel] = explode('|', $link); @endphp
                                                <a href="{{ $linkHref }}" class="inline-flex items-center gap-2 rounded-full border border-admin-accent/30 px-4 py-2 text-xs font-semibold text-admin-accent transition-colors duration-300 hover:bg-admin-cream">
                                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                                    {{ $linkLabel }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @elseif (is_array($paragraph))
                                        <ul class="ml-1 list-disc space-y-2 pl-4 text-sm leading-relaxed text-[#4A423B] sm:text-[15px]">
                                            @foreach ($paragraph as $point)
                                                <li>{{ $point }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="text-sm leading-relaxed text-[#4A423B] sm:text-[15px]">
                                            {!! $paragraph !!}
                                        </p>
                                    @endif
                                @endforeach
'@

if ($content -notlike "*$([regex]::Escape($oldBlock))*" -and -not $content.Contains($oldBlock)) {
    Write-Host "[ERROR] Blok kode yang mau diganti tidak ditemukan persis di file." -ForegroundColor Red
    Write-Host "Kemungkinan file sudah pernah diubah manual. Cek manual ya, ge." -ForegroundColor Yellow
    Write-Host "Backup tetap aman di: $backupFile" -ForegroundColor Yellow
    exit 1
}

$newContent = $content.Replace($oldBlock, $newBlock)

Write-Host "[2/3] Menulis perubahan ke file..." -ForegroundColor Green
# Tulis dengan UTF-8 + BOM supaya karakter khusus (é, &, dll) tidak korup
$utf8Bom = New-Object System.Text.UTF8Encoding $true
[System.IO.File]::WriteAllText((Resolve-Path $targetFile), $newContent, $utf8Bom)

Write-Host "[3/3] Selesai." -ForegroundColor Green
Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " FIX DITERAPKAN! Refresh halaman /ketentuan-layanan" -ForegroundColor Cyan
Write-Host " Kalau ada apa-apa, file asli ada di: $backupFile" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
