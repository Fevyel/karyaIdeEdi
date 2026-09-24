<#
  Fix: upload video di "Kenapa Pilih Kami" gagal ("failed to upload") walau
  file di bawah limit 50MB yang sudah diatur di aplikasi & Livewire.

  Sebabnya: PHP sendiri (php.ini) punya batas upload_max_filesize &
  post_max_size sendiri, terpisah dari validasi Laravel/Livewire. Kalau
  nilainya di bawah ukuran file yang diupload, Apache/PHP menolak request-nya
  duluan sebelum sempat sampai ke kode aplikasi -- makanya errornya cuma
  "failed to upload" yang generik, bukan pesan validasi yang jelas.

  Script ini:
  1. Mencari php.ini yang benar-benar dipakai PHP CLI kamu (via `php --ini`),
     dengan fallback ke lokasi umum XAMPP (C:\xampp\php\php.ini) kalau
     `php` tidak ada di PATH.
  2. Membuat backup (.bak-sebelum-naikkan-upload-limit) sebelum mengubah apa pun.
  3. Menaikkan upload_max_filesize & post_max_size jadi 64M (kalau nilai
     sekarang lebih kecil dari itu). Tidak menyentuh baris/pengaturan lain
     sama sekali.

  PENTING: PHP dipakai APACHE (bukan cuma CLI) untuk melayani situs kamu.
  Di instalasi XAMPP standar cuma ada SATU php.ini yang dipakai keduanya,
  jadi ini biasanya sudah benar -- tapi kalau kamu punya php.ini terpisah
  untuk Apache, kabari saya, filenya beda.

  WAJIB: restart Apache dari XAMPP Control Panel (Stop lalu Start) setelah
  script ini selesai -- perubahan php.ini TIDAK berlaku sampai Apache di-restart.

  Cara pakai (dari terminal VS Code, di root project C:\xampp\htdocs\karyaIdeEdi):
    .\apply-fix-php-ini-upload-limit-64mb.ps1
#>

$ErrorActionPreference = 'Stop'

# 1. Cari php.ini yang aktif dipakai
$phpIniPath = $null

try {
    $phpIniLine = & php --ini 2>$null | Select-String 'Loaded Configuration File:\s*(.+)'
    if ($phpIniLine) {
        $candidate = $phpIniLine.Matches[0].Groups[1].Value.Trim()
        if ($candidate -and $candidate -ne '(none)' -and (Test-Path $candidate)) {
            $phpIniPath = $candidate
        }
    }
} catch {
    # `php` tidak ada di PATH -- lanjut ke fallback di bawah
}

if (-not $phpIniPath) {
    $fallback = 'C:\xampp\php\php.ini'
    if (Test-Path $fallback) {
        $phpIniPath = $fallback
        Write-Host "Tidak bisa deteksi otomatis lewat 'php --ini', pakai lokasi umum XAMPP: $fallback" -ForegroundColor Yellow
    } else {
        Write-Host "php.ini tidak ditemukan otomatis. Cari manual filenya, lalu kabari lokasinya." -ForegroundColor Red
        exit 1
    }
}

Write-Host "php.ini yang dipakai: $phpIniPath" -ForegroundColor Cyan

# 2. Backup dulu sebelum ubah apa-apa
$backupPath = "$phpIniPath.bak-sebelum-naikkan-upload-limit"
if (-not (Test-Path $backupPath)) {
    Copy-Item $phpIniPath $backupPath
    Write-Host "Backup dibuat: $backupPath" -ForegroundColor Green
} else {
    Write-Host "Backup sudah ada sebelumnya, tidak ditimpa: $backupPath" -ForegroundColor Yellow
}

# 3. Baca isi, cari baris upload_max_filesize & post_max_size (yang AKTIF, bukan yang diawali ';')
$lines = Get-Content $phpIniPath

$targets = @('upload_max_filesize', 'post_max_size')
$minValueMB = 64

$changed = $false

for ($i = 0; $i -lt $lines.Count; $i++) {
    foreach ($target in $targets) {
        if ($lines[$i] -match "^\s*$target\s*=\s*(\S+)") {
            $currentRaw = $Matches[1]
            $currentMB = 0

            if ($currentRaw -match '^(\d+)M$') { $currentMB = [int]$Matches[1] }
            elseif ($currentRaw -match '^(\d+)G$') { $currentMB = [int]$Matches[1] * 1024 }
            elseif ($currentRaw -match '^(\d+)K$') { $currentMB = [math]::Round([int]$Matches[1] / 1024) }
            elseif ($currentRaw -match '^(\d+)$') { $currentMB = [math]::Round([int]$Matches[1] / 1048576) }

            Write-Host "Ketemu: $target = $currentRaw (~${currentMB}MB)" -ForegroundColor Cyan

            if ($currentMB -lt $minValueMB) {
                $lines[$i] = "$target = ${minValueMB}M"
                Write-Host "  -> dinaikkan jadi ${minValueMB}M" -ForegroundColor Green
                $changed = $true
            } else {
                Write-Host "  -> sudah cukup besar, tidak diubah" -ForegroundColor Yellow
            }
        }
    }
}

if (-not $changed) {
    Write-Host ""
    Write-Host "Tidak ada yang perlu dinaikkan (nilai sekarang sudah >= ${minValueMB}MB), atau baris tidak ketemu." -ForegroundColor Yellow
    Write-Host "Kalau tidak ketemu sama sekali, cek manual apakah baris 'upload_max_filesize' / 'post_max_size' diawali ';' (dinonaktifkan) di php.ini." -ForegroundColor Yellow
    exit 0
}

Set-Content -Path $phpIniPath -Value $lines -Encoding UTF8

Write-Host ""
Write-Host "Diperbarui: $phpIniPath" -ForegroundColor Green
Write-Host ""
Write-Host "WAJIB: restart Apache dari XAMPP Control Panel (Stop, lalu Start lagi)." -ForegroundColor Cyan
Write-Host "Baru setelah itu coba upload video yang tadi gagal lagi." -ForegroundColor Cyan
