# apply-fix-mobile-tentang-kami.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   powershell -ExecutionPolicy Bypass -File .\apply-fix-mobile-tentang-kami.ps1
#
# Tujuan: merapikan STRUKTUR halaman "Tentang Kami" (/profil) KHUSUS tampilan
# HP (layar < 640px). Tablet dan desktop TIDAK berubah sama sekali: semua
# perubahan memakai variant Tailwind "max-sm:" (hanya aktif di bawah 640px),
# jadi class desktop/tablet yang lama tidak disentuh.
#
# File yang diubah HANYA satu:
#   - resources/views/pages/frontend/profil.blade.php
#
# Isi perubahan (murni class Tailwind, tidak ada logic PHP/data/teks yang diubah):
#   1) Nilai Kami ("Yang kami utamakan di setiap karya"): 4 kartu yang tadinya
#      tersusun 1 kolom ke bawah terus, sekarang 2 x 2 di HP (sama seperti
#      Semua Produk / Kategori / Testimoni di Beranda). Badge ikon, padding, dan
#      ukuran teks kartu ikut mengecil khusus di HP supaya muat rapi.
#   2) "Lebih dari sekadar furniture": foto lemari yang tadinya potret 4/5
#      (sangat tinggi) jadi kotak 1/1 di HP.
#   3) Jarak atas-bawah dan antar blok di 4 section (Hero, Lebih dari sekadar
#      furniture, Nilai Kami, Kenapa memilih) dirapatkan di HP.
#
# Pengaman:
#   - Semua pola dicek dulu. Kalau ada yang tidak ketemu persis / ketemu lebih
#     dari 1 kali, script berhenti dan TIDAK menulis apa pun.
#   - Backup file asli dibuat di folder .backup-mobile-tentang-kami-<waktu>/
#     (sama seperti backup sesi sebelumnya, sudah di-.gitignore).
#   - Encoding (ada/tidak ada BOM) dan jenis baris baru (LF/CRLF) dipertahankan.
#   - Aman dijalankan berulang: bagian yang sudah diterapkan dilewati.
#
# Setelah script selesai, jalankan:  npm run build

$ErrorActionPreference = "Stop"
$stamp   = Get-Date -Format "yyyyMMdd-HHmmss"
$relPath = "resources/views/pages/frontend/profil.blade.php"

if (-not (Test-Path "artisan")) {
    throw "File 'artisan' tidak ketemu. Jalankan script ini dari root project (C:\xampp\htdocs\karyaIdeEdi)."
}
if (-not (Test-Path $relPath)) {
    throw "Tidak ketemu: $relPath"
}

$full   = (Resolve-Path $relPath).Path
$bytes  = [System.IO.File]::ReadAllBytes($full)
$hasBom = ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF)
$text   = [System.IO.File]::ReadAllText($full, (New-Object System.Text.UTF8Encoding($false)))
$nl     = "`n"
if ($text.Contains("`r`n")) { $nl = "`r`n" }

function Convert-Nl($s) {
    return (($s -replace "`r`n", "`n") -replace "`n", $nl)
}

function Count-Match($haystack, $needle) {
    return ([regex]::Matches($haystack, [regex]::Escape($needle))).Count
}

$edits = New-Object System.Collections.ArrayList
function Add-Edit($label, $old, $new) {
    [void]$edits.Add(@{ Label = $label; Old = (Convert-Nl $old); New = (Convert-Nl $new) })
}

# ---- 1) Hero: jarak atas-bawah & antar blok lebih rapat di HP
$old = @'
<div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 py-14 sm:px-8 lg:grid-cols-2 lg:gap-10 lg:px-10 lg:py-20">
'@
$new = @'
<div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 py-14 max-sm:gap-8 max-sm:py-10 sm:px-8 lg:grid-cols-2 lg:gap-10 lg:px-10 lg:py-20">
'@
Add-Edit "Hero: jarak atas-bawah & antar blok lebih rapat di HP" $old $new

# ---- 2) Lebih dari sekadar furniture: padding & jarak lebih rapat di HP
$old = @'
    <section class="bg-admin-cream/40">
        <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 py-16 sm:px-8 lg:grid-cols-2 lg:gap-16 lg:px-10 lg:py-24">
'@
$new = @'
    <section class="bg-admin-cream/40">
        <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 py-16 max-sm:gap-8 max-sm:py-12 sm:px-8 lg:grid-cols-2 lg:gap-16 lg:px-10 lg:py-24">
'@
Add-Edit "Lebih dari sekadar furniture: padding & jarak lebih rapat di HP" $old $new

# ---- 3) Lebih dari sekadar furniture: foto jadi kotak (sebelumnya potret 4/5) di HP
$old = @'
<span class="relative flex aspect-4/5 w-full items-center justify-center overflow-hidden rounded-3xl bg-white shadow-lg">
'@
$new = @'
<span class="relative flex aspect-4/5 w-full items-center justify-center overflow-hidden rounded-3xl bg-white shadow-lg max-sm:aspect-square">
'@
Add-Edit "Lebih dari sekadar furniture: foto jadi kotak (sebelumnya potret 4/5) di HP" $old $new

# ---- 4) Nilai Kami: padding section lebih rapat di HP
$old = @'
    <section class="bg-admin-cream">
        <div class="mx-auto max-w-7xl px-6 py-16 sm:px-8 lg:px-10 lg:py-24">
'@
$new = @'
    <section class="bg-admin-cream">
        <div class="mx-auto max-w-7xl px-6 py-16 max-sm:py-12 sm:px-8 lg:px-10 lg:py-24">
'@
Add-Edit "Nilai Kami: padding section lebih rapat di HP" $old $new

# ---- 5) Nilai Kami: 2 kolom di HP (sebelumnya 1 kolom / ke bawah terus)
$old = @'
<div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
'@
$new = @'
<div class="mt-12 grid grid-cols-1 gap-6 max-sm:mt-8 max-sm:grid-cols-2 max-sm:gap-3 sm:grid-cols-2 lg:grid-cols-4">
'@
Add-Edit "Nilai Kami: 2 kolom di HP (sebelumnya 1 kolom / ke bawah terus)" $old $new

# ---- 6) Nilai Kami: badge ikon mengecil di HP
$old = @'
<span class="absolute left-3 top-3 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-admin-accent shadow-sm backdrop-blur-sm">
'@
$new = @'
<span class="absolute left-3 top-3 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-admin-accent shadow-sm backdrop-blur-sm max-sm:left-2 max-sm:top-2 max-sm:h-7 max-sm:w-7 max-sm:text-xs">
'@
Add-Edit "Nilai Kami: badge ikon mengecil di HP" $old $new

# ---- 7) Nilai Kami: isi kartu (padding & ukuran teks) mengecil di HP
$old = @'
                        <div class="p-6">
                            <p class="font-display text-xl text-admin-gold">{{ $value['no'] }}</p>
                            <p class="mt-2 text-base font-semibold text-[#3D2B1F]">{{ $value['title'] }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-admin-ink-soft">{{ $value['desc'] }}</p>
                        </div>
'@
$new = @'
                        <div class="p-6 max-sm:p-3">
                            <p class="font-display text-xl text-admin-gold max-sm:text-base">{{ $value['no'] }}</p>
                            <p class="mt-2 text-base font-semibold text-[#3D2B1F] max-sm:mt-1 max-sm:text-sm">{{ $value['title'] }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-admin-ink-soft max-sm:mt-1 max-sm:text-xs">{{ $value['desc'] }}</p>
                        </div>
'@
Add-Edit "Nilai Kami: isi kartu (padding & ukuran teks) mengecil di HP" $old $new

# ---- 8) Kenapa memilih: padding & jarak lebih rapat di HP
$old = @'
    <section class="bg-[#221B14]">
        <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 py-16 sm:px-8 lg:grid-cols-2 lg:gap-16 lg:px-10 lg:py-24">
'@
$new = @'
    <section class="bg-[#221B14]">
        <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 py-16 max-sm:gap-8 max-sm:py-12 sm:px-8 lg:grid-cols-2 lg:gap-16 lg:px-10 lg:py-24">
'@
Add-Edit "Kenapa memilih: padding & jarak lebih rapat di HP" $old $new

# ============================================================
# TAHAP 1: cek semua pola dulu (belum menulis apa pun)
# ============================================================
$pending = New-Object System.Collections.ArrayList
$skipped = 0
$failed  = New-Object System.Collections.ArrayList

foreach ($e in $edits) {
    $n = Count-Match $text $e.Old
    if ($n -eq 1) {
        [void]$pending.Add($e)
    }
    elseif ($n -eq 0 -and (Count-Match $text $e.New) -ge 1) {
        Write-Host "   Sudah diterapkan, dilewati: $($e.Label)" -ForegroundColor Yellow
        $skipped++
    }
    elseif ($n -eq 0) {
        [void]$failed.Add("TIDAK ketemu persis : $($e.Label)")
    }
    else {
        [void]$failed.Add("Ketemu $n kali (harus 1): $($e.Label)")
    }
}

if ($failed.Count -gt 0) {
    Write-Host ""
    foreach ($f in $failed) { Write-Host "   $f" -ForegroundColor Red }
    throw "Ada pola yang tidak cocok di $relPath (kemungkinan file sudah diubah manual). TIDAK ada yang ditulis/ditimpa."
}

if ($pending.Count -eq 0) {
    Write-Host ""
    Write-Host "Semua perubahan sudah pernah diterapkan. Tidak ada yang dilakukan." -ForegroundColor Yellow
    return
}

# ============================================================
# TAHAP 2: backup lalu tulis
# ============================================================
$backupDir  = ".backup-mobile-tentang-kami-$stamp"
$backupFile = Join-Path $backupDir $relPath
New-Item -ItemType Directory -Force -Path (Split-Path $backupFile -Parent) | Out-Null
Copy-Item $relPath $backupFile

$newText = $text
foreach ($e in $pending) {
    Write-Host "-> $($e.Label)" -ForegroundColor Cyan
    $newText = $newText.Replace($e.Old, $e.New)
}

[System.IO.File]::WriteAllText($full, $newText, (New-Object System.Text.UTF8Encoding($hasBom)))

Write-Host ""
Write-Host "Selesai. $($pending.Count) perubahan diterapkan ($skipped dilewati)." -ForegroundColor Green
Write-Host "Backup : $backupFile" -ForegroundColor Green
Write-Host "Lanjut : npm run build   (lalu refresh HP/browser)" -ForegroundColor Green
