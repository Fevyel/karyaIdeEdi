# ============================================================
# FITUR BARU (scaffold): Menu "Edit Web" di sidebar admin --
# baru kerangka halaman + route + menu sidebar-nya dulu, BELUM
# ada field/fitur edit konten apa pun di dalamnya (sesuai
# permintaan: "buat bagiannya aja dulu").
#
# APA YANG DIBUAT:
# 1. Halaman baru: resources\views\pages\admin\edit-web.blade.php
#    (Livewire page component kosong, cuma header + empty state
#    "masih dalam pengembangan").
# 2. Route baru di routes\web.php: admin.website-editor -> /edit-web
# 3. Menu baru "Edit Web" di sidebar admin (layouts/admin-panel.blade.php),
#    diletakkan sebelum Pengaturan.
#
# Tidak ada file/fitur LAIN yang disentuh atau dihapus. Ini murni
# menambah kerangka baru -- aman dijalankan berapa kali pun konteks
# datanya (tidak menyentuh database sama sekali).
#
# Cara pakai:
# 1. Jalankan dari folder project (C:\xampp\htdocs\karyaIdeEdi)
# 2. .\apply-add-edit-web-page.ps1
# ============================================================

$ErrorActionPreference = "Stop"
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Fitur baru: Menu Edit Web (scaffold)" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    Write-Host "Contoh: C:\xampp\htdocs\karyaIdeEdi" -ForegroundColor Yellow
    exit 1
}

$pageFile = "resources\views\pages\admin\edit-web.blade.php"
$routesFile = "routes\web.php"
$layoutFile = "resources\views\layouts\admin-panel.blade.php"

foreach ($f in @($routesFile, $layoutFile)) {
    if (-not (Test-Path $f)) {
        Write-Host "[ERROR] File tidak ditemukan: $f" -ForegroundColor Red
        exit 1
    }
}

if (Test-Path $pageFile) {
    Write-Host "[ERROR] File sudah ada: $pageFile" -ForegroundColor Red
    Write-Host "Kemungkinan script ini sudah pernah dijalankan. Cek manual ya, ge." -ForegroundColor Yellow
    exit 1
}

# PENTING: WAJIB tanpa BOM untuk semua file .php / .blade.php.
$utf8NoBom = New-Object System.Text.UTF8Encoding $false

# ---------- [1/3] Buat halaman scaffold ----------
$pageContent = @'
<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * SCAFFOLD -- baru kerangka halaman & menu sidebar-nya dulu.
 * Belum ada field/fitur edit apa pun di sini; ini cuma tempat
 * kosong (empty state) yang nanti diisi bertahap per bagian
 * (misalnya: konten Beranda, konten Profil, dll).
 */
new #[Layout('layouts::admin-panel')] #[Title('Edit Web')] class extends Component
{
    //
};
?>

<div class="mx-auto max-w-6xl space-y-6">

    <div>
        <h2 class="font-display text-xl font-semibold text-admin-ink sm:text-2xl">
            Edit Web
        </h2>
        <p class="mt-1 text-sm text-admin-ink-soft">
            Kelola konten yang tampil di halaman-halaman website (Beranda, Profil, dll).
        </p>
    </div>

    <div class="rounded-2xl border border-dashed border-admin-border bg-admin-surface px-5 py-16 text-center">
        <i class="fa-solid fa-pen-to-square mb-3 text-2xl text-admin-ink-soft"></i>
        <p class="text-sm font-medium text-admin-ink">
            Halaman ini masih dalam pengembangan.
        </p>
        <p class="mt-1 text-xs text-admin-ink-soft">
            Bagian untuk mengedit konten website akan ditambahkan di sini.
        </p>
    </div>

</div>

'@
[System.IO.File]::WriteAllText((Join-Path (Get-Location) $pageFile), $pageContent, $utf8NoBom)
Write-Host "[1/3] Halaman dibuat: $pageFile" -ForegroundColor Green

# ---------- [2/3] Tambah route ----------
Copy-Item $routesFile "$routesFile.bak-before-edit-web-$stamp" -Force
$routesContent = Get-Content $routesFile -Raw -Encoding UTF8

$routeAnchor = "    Route::livewire('/pengaturan', 'pages::admin.pengaturan')->name('settings');"
$routeNew = @"
    Route::livewire('/edit-web', 'pages::admin.edit-web')->name('website-editor');
    Route::livewire('/pengaturan', 'pages::admin.pengaturan')->name('settings');
"@

if (-not $routesContent.Contains($routeAnchor)) {
    Write-Host "[ERROR] Baris acuan route Pengaturan tidak ditemukan di $routesFile." -ForegroundColor Red
    Write-Host "Kemungkinan file sudah pernah diubah manual. Cek manual ya, ge." -ForegroundColor Yellow
    Write-Host "Backup tetap aman di: $routesFile.bak-before-edit-web-$stamp" -ForegroundColor Yellow
    exit 1
}

$routesContent = $routesContent.Replace($routeAnchor, $routeNew.TrimEnd("`r", "`n"))
[System.IO.File]::WriteAllText((Resolve-Path $routesFile), $routesContent, $utf8NoBom)
Write-Host "[2/3] Route 'admin.website-editor' (/edit-web) ditambahkan." -ForegroundColor Green

# ---------- [3/3] Tambah menu sidebar ----------
Copy-Item $layoutFile "$layoutFile.bak-before-edit-web-$stamp" -Force
$layoutContent = Get-Content $layoutFile -Raw -Encoding UTF8

$navAnchor = "                                `$navItem('admin.settings', 'fa-gear', 'Pengaturan'),"
$navNew = @"
                                `$navItem('admin.website-editor', 'fa-pen-to-square', 'Edit Web'),
                                `$navItem('admin.settings', 'fa-gear', 'Pengaturan'),
"@

if (-not $layoutContent.Contains($navAnchor)) {
    Write-Host "[ERROR] Baris acuan menu Pengaturan tidak ditemukan di $layoutFile." -ForegroundColor Red
    Write-Host "Kemungkinan file sudah pernah diubah manual. Cek manual ya, ge." -ForegroundColor Yellow
    Write-Host "Backup tetap aman di: $layoutFile.bak-before-edit-web-$stamp" -ForegroundColor Yellow
    Write-Host "" -ForegroundColor Yellow
    Write-Host "Route & halaman Edit Web TETAP sudah aktif (langkah 1-2 berhasil)." -ForegroundColor Yellow
    Write-Host "Bisa diakses langsung ke /admin/edit-web, cuma menu sidebar-nya belum muncul." -ForegroundColor Yellow
    exit 1
}

$layoutContent = $layoutContent.Replace($navAnchor, $navNew.TrimEnd("`r", "`n"))
[System.IO.File]::WriteAllText((Resolve-Path $layoutFile), $layoutContent, $utf8NoBom)
Write-Host "[3/3] Menu 'Edit Web' ditambahkan di sidebar admin." -ForegroundColor Green

Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " SELESAI!" -ForegroundColor Cyan
Write-Host " Menu 'Edit Web' sekarang ada di sidebar admin," -ForegroundColor Cyan
Write-Host " isinya masih placeholder kosong (sesuai permintaan)." -ForegroundColor Cyan
Write-Host " Hard refresh browser (Ctrl+Shift+R) untuk lihat perubahannya." -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
