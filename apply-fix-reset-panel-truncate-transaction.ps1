# ============================================================
# FIX: Klik "Reset Data" / "Reset Pabrik" di admin dashboard
#      error 500 -- PDOException "There is no active transaction"
#      (ManagesTransactions.php:54)
#
# ROOT CAUSE: runResetPabrik() dan runResetData() di
# resources/views/components/admin/reset-data-panel.blade.php
# membungkus semua TRUNCATE TABLE di dalam DB::transaction(...).
#
# Di MySQL, TRUNCATE itu DDL -- otomatis melakukan IMPLICIT COMMIT
# begitu dijalankan. Jadi begitu baris "truncate table testimonials"
# pertama jalan, transaksi yang dibuka Laravel lewat DB::transaction()
# sudah "selesai" duluan di level MySQL. Waktu closure-nya selesai dan
# Laravel mau COMMIT beneran di akhir, transaksinya sudah tidak ada
# -> PDOException "There is no active transaction".
#
# Ini bukan soal urutan sesi/isolasi -- membungkus TRUNCATE dalam
# transaction MEMANG tidak pernah bisa dipakai di MySQL, jadi
# DB::transaction() di sini justru bikin error, bukan melindungi data.
#
# FIX: buang pembungkus DB::transaction(...) di kedua fungsi.
# Urutan perintahnya TETAP SAMA PERSIS (disable FK check -> truncate
# tabel satu-satu -> enable FK check lagi), cuma dijalankan langsung
# tanpa transaction wrapper. Tidak ada logika lain yang diubah.
#
# File ditulis pakai [System.IO.File]::WriteAllText dengan encoding
# UTF-8 TANPA BOM (sama seperti kondisi asli file blade ini).
#
# Cara pakai (dari VS Code integrated terminal, di root project):
#   .\apply-fix-reset-panel-truncate-transaction.ps1
#
# Setelah itu langsung coba lagi tombol Reset Data / Reset Pabrik di
# admin dashboard -- tidak perlu npm run build (murni logic PHP,
# tidak ada perubahan Tailwind/JS).
# ============================================================

$ErrorActionPreference = "Stop"

function Backup-File {
    param([string]$Path)
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$Path.bak-before-fix-reset-panel-truncate-$stamp"
    Copy-Item -Path $Path -Destination $backupPath -Force
    Write-Host "  Backup dibuat: $backupPath" -ForegroundColor DarkGray
    return $backupPath
}

function Replace-ExactlyOnce {
    param(
        [string]$Path,
        [string]$Old,
        [string]$New,
        [bool]$UseBom
    )

    $content = [System.IO.File]::ReadAllText((Join-Path (Get-Location) $Path))

    $occurrences = ([regex]::Matches($content, [regex]::Escape($Old))).Count
    if ($occurrences -eq 0) {
        Write-Host "[ERROR] Teks yang mau diganti tidak ditemukan di $Path." -ForegroundColor Red
        Write-Host "        Kemungkinan file ini sudah beda dari yang saya kira -- SAYA BERHENTI, tidak ada yang diubah di file ini." -ForegroundColor Red
        exit 1
    }
    if ($occurrences -gt 1) {
        Write-Host "[ERROR] Teks yang mau diganti muncul $occurrences kali di $Path (harusnya cuma 1)." -ForegroundColor Red
        Write-Host "        SAYA BERHENTI supaya tidak salah ganti bagian yang lain." -ForegroundColor Red
        exit 1
    }

    Backup-File -Path $Path | Out-Null

    $newContent = $content.Replace($Old, $New)
    $encoding = New-Object System.Text.UTF8Encoding($UseBom)
    [System.IO.File]::WriteAllText((Join-Path (Get-Location) $Path), $newContent, $encoding)

    Write-Host "  OK -- $Path sudah di-patch." -ForegroundColor Green
}

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Fix: Reset Panel - Truncate di dalam Transaction" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

$targetPath = "resources\views\components\admin\reset-data-panel.blade.php"

if (-not (Test-Path $targetPath)) {
    Write-Host "[ERROR] File tidak ditemukan: $targetPath" -ForegroundColor Red
    exit 1
}

# ------------------------------------------------------------
# FIX 1: runResetPabrik() -- buang DB::transaction() wrapper
# ------------------------------------------------------------
Write-Host "[1/2] Membetulkan runResetPabrik() ..." -ForegroundColor Yellow

$oldPabrik = @'
    private function runResetPabrik(): void
    {
        DB::transaction(function () {
            Schema::disableForeignKeyConstraints();
            DB::table('testimonials')->truncate();
            DB::table('transactions')->truncate();
            DB::table('product_images')->truncate();
            DB::table('products')->truncate();
            DB::table('categories')->truncate();
            Schema::enableForeignKeyConstraints();
        });

        Storage::disk('public')->deleteDirectory('produk');
        Storage::disk('public')->deleteDirectory('testimoni');

        $this->resultMessage = 'Reset pabrik selesai -- produk, kategori, pesanan, dan testimoni sudah dikosongkan. Pengaturan website tidak ikut berubah.';
    }
'@

$newPabrik = @'
    private function runResetPabrik(): void
    {
        // TRUNCATE di MySQL otomatis implicit-commit, jadi TIDAK BOLEH
        // dibungkus DB::transaction() -- kalau dibungkus, Laravel akan
        // gagal COMMIT di akhir karena transaksinya sudah "hilang"
        // duluan begitu TRUNCATE pertama jalan (PDOException "There is
        // no active transaction"). Urutan perintah di bawah ini PERSIS
        // sama seperti sebelumnya, cuma tanpa wrapper transaction.
        Schema::disableForeignKeyConstraints();
        DB::table('testimonials')->truncate();
        DB::table('transactions')->truncate();
        DB::table('product_images')->truncate();
        DB::table('products')->truncate();
        DB::table('categories')->truncate();
        Schema::enableForeignKeyConstraints();

        Storage::disk('public')->deleteDirectory('produk');
        Storage::disk('public')->deleteDirectory('testimoni');

        $this->resultMessage = 'Reset pabrik selesai -- produk, kategori, pesanan, dan testimoni sudah dikosongkan. Pengaturan website tidak ikut berubah.';
    }
'@

Replace-ExactlyOnce -Path $targetPath -Old $oldPabrik -New $newPabrik -UseBom $false

# ------------------------------------------------------------
# FIX 2: runResetData() -- buang DB::transaction() wrapper
# ------------------------------------------------------------
Write-Host "[2/2] Membetulkan runResetData() ..." -ForegroundColor Yellow

$oldData = @'
    private function runResetData(): void
    {
        DB::transaction(function () {
            Schema::disableForeignKeyConstraints();
            DB::table('testimonials')->truncate();
            DB::table('transactions')->truncate();
            Schema::enableForeignKeyConstraints();
        });

        Storage::disk('public')->deleteDirectory('testimoni');

        $this->resultMessage = 'Reset data selesai -- pesanan dan testimoni sudah dikosongkan. Produk & kategori tetap aman.';
    }
'@

$newData = @'
    private function runResetData(): void
    {
        // Sama seperti runResetPabrik() -- TRUNCATE tidak boleh dibungkus
        // DB::transaction() karena implicit-commit di MySQL. Urutan
        // perintah tetap sama, cuma tanpa wrapper transaction.
        Schema::disableForeignKeyConstraints();
        DB::table('testimonials')->truncate();
        DB::table('transactions')->truncate();
        Schema::enableForeignKeyConstraints();

        Storage::disk('public')->deleteDirectory('testimoni');

        $this->resultMessage = 'Reset data selesai -- pesanan dan testimoni sudah dikosongkan. Produk & kategori tetap aman.';
    }
'@

Replace-ExactlyOnce -Path $targetPath -Old $oldData -New $newData -UseBom $false

Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " SELESAI! Coba lagi tombol Reset Data / Reset Pabrik" -ForegroundColor Cyan
Write-Host " di admin dashboard (php artisan serve masih jalan)." -ForegroundColor Cyan
Write-Host " Tidak perlu npm run build." -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
