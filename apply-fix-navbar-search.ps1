# ============================================================
# FIX: Aktifkan fitur Cari Produk + dropdown Kategori di Navbar
#
# Sebelumnya: search bar & dropdown kategori di navbar cuma
# tampilan (disabled, onsubmit="return false"), dropdown
# kategori juga isinya teks statis (Kursi/Meja/dst), bukan
# data asli dari database.
#
# Sekarang: form dikirim via GET ke route('products.index')
# yang SUDAH mendukung parameter ?search= dan ?category=
# (dicek di routes/web.php), dan dropdown kategori diambil
# langsung dari tabel categories (kategori aktif, urut sesuai
# sort_order).
#
# Script ini HANYA mengganti 2 blok kode di
# resources/views/partials/frontend/navbar.blade.php,
# bagian lain file tidak disentuh.
#
# Cara pakai:
# 1. Jalankan dari folder project (C:\xampp\htdocs\karyaIdeEdi)
# 2. .\apply-fix-navbar-search.ps1
# ============================================================

$ErrorActionPreference = "Stop"
$targetFile = "resources\views\partials\frontend\navbar.blade.php"

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Fix: Aktifkan Cari Produk + Kategori di Navbar" -ForegroundColor Cyan
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

$backupFile = "$targetFile.bak"
Copy-Item $targetFile $backupFile -Force
Write-Host "[1/4] Backup dibuat: $backupFile" -ForegroundColor Green

$content = Get-Content $targetFile -Raw -Encoding UTF8

# ---------- BLOK 1: tambah data kategori + nilai search/category aktif ----------
$oldBlock1 = @'
@php
    $navSetting = \App\Models\Setting::current();

    // Menu tengah. `route` bernilai null untuk halaman yang belum dibuat,
    // supaya link mengarah ke '#' tanpa memicu RouteNotFoundException.
    $navMenu = [
        ['label' => 'Beranda',   'route' => 'home', 'icon' => 'fa-house'],
        ['label' => 'Profil',    'route' => 'profile.index', 'icon' => 'fa-couch'],
        ['label' => 'Produk',    'route' => 'products.index', 'icon' => 'fa-layer-group'],
        ['label' => 'Testimoni', 'route' => 'testimonials.index', 'icon' => 'fa-star'],
        ['label' => 'Booking',   'route' => 'booking.index', 'icon' => 'fa-calendar-check'],
    ];
@endphp
'@

$newBlock1 = @'
@php
    $navSetting = \App\Models\Setting::current();

    // Menu tengah. `route` bernilai null untuk halaman yang belum dibuat,
    // supaya link mengarah ke '#' tanpa memicu RouteNotFoundException.
    $navMenu = [
        ['label' => 'Beranda',   'route' => 'home', 'icon' => 'fa-house'],
        ['label' => 'Profil',    'route' => 'profile.index', 'icon' => 'fa-couch'],
        ['label' => 'Produk',    'route' => 'products.index', 'icon' => 'fa-layer-group'],
        ['label' => 'Testimoni', 'route' => 'testimonials.index', 'icon' => 'fa-star'],
        ['label' => 'Booking',   'route' => 'booking.index', 'icon' => 'fa-calendar-check'],
    ];

    // Kategori asli dari database untuk dropdown search navbar.
    // Search & filter kategori dikirim ke route('products.index') via GET
    // (param 'search' & 'category'), yang sudah mendukung keduanya.
    $navCategories = \App\Models\Category::query()->active()->ordered()->get(['name', 'slug']);
    $navSearchValue = request()->routeIs('products.index') ? (string) request('search', '') : '';
    $navSelectedCategory = request()->routeIs('products.index') ? (string) request('category', '') : '';
@endphp
'@

# ---------- BLOK 2: form search jadi aktif & terhubung ke katalog ----------
$oldBlock2 = @'
            {{-- Search bar modern + dropdown kategori + tombol search (tampilan saja) --}}
            <form class="hidden items-stretch overflow-hidden rounded-lg border border-admin-border bg-admin-canvas transition-all duration-300 focus-within:border-admin-accent focus-within:shadow-[0_0_0_3px_rgba(156,107,63,0.12)] md:flex" onsubmit="return false;">
                <input
                    type="text"
                    placeholder="Cari produk..."
                    disabled
                    class="w-44 bg-transparent px-4 py-2 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:outline-none lg:w-64"
                >
                <span class="my-2 h-auto w-px shrink-0 bg-admin-border"></span>
                <select
                    disabled
                    class="hidden shrink-0 bg-transparent px-3 py-2 text-sm text-admin-ink-soft focus:outline-none lg:block"
                >
                    <option>Semua Produk</option>
                    <option>Kursi</option>
                    <option>Meja</option>
                    <option>Lemari</option>
                    <option>Sofa</option>
                </select>
                <button
                    type="button"
                    disabled
                    aria-label="Cari"
                    class="flex shrink-0 items-center justify-center bg-admin-panel px-3.5 text-white transition-colors duration-300"
                >
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </button>
            </form>
'@

$newBlock2 = @'
            {{-- Search bar + dropdown kategori — mengirim GET ke katalog produk (route products.index) --}}
            <form action="{{ route('products.index') }}" method="GET" class="hidden items-stretch overflow-hidden rounded-lg border border-admin-border bg-admin-canvas transition-all duration-300 focus-within:border-admin-accent focus-within:shadow-[0_0_0_3px_rgba(156,107,63,0.12)] md:flex">
                <input
                    type="text"
                    name="search"
                    value="{{ $navSearchValue }}"
                    placeholder="Cari produk..."
                    class="w-44 bg-transparent px-4 py-2 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:outline-none lg:w-64"
                >
                <span class="my-2 h-auto w-px shrink-0 bg-admin-border"></span>
                <select
                    name="category"
                    class="hidden shrink-0 bg-transparent px-3 py-2 text-sm text-admin-ink-soft focus:outline-none lg:block"
                >
                    <option value="" {{ $navSelectedCategory === '' ? 'selected' : '' }}>Semua Produk</option>
                    @foreach ($navCategories as $navCategory)
                        <option value="{{ $navCategory->slug }}" {{ $navSelectedCategory === $navCategory->slug ? 'selected' : '' }}>
                            {{ $navCategory->name }}
                        </option>
                    @endforeach
                </select>
                <button
                    type="submit"
                    aria-label="Cari"
                    class="flex shrink-0 items-center justify-center bg-admin-panel px-3.5 text-white transition-colors duration-300 hover:bg-admin-accent"
                >
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </button>
            </form>
'@

if (-not $content.Contains($oldBlock1) -or -not $content.Contains($oldBlock2)) {
    Write-Host "[ERROR] Blok kode yang mau diganti tidak ditemukan persis di file." -ForegroundColor Red
    Write-Host "Kemungkinan file sudah pernah diubah manual. Cek manual ya, ge." -ForegroundColor Yellow
    Write-Host "Backup tetap aman di: $backupFile" -ForegroundColor Yellow
    exit 1
}

Write-Host "[2/4] Menyisipkan data kategori..." -ForegroundColor Green
$newContent = $content.Replace($oldBlock1, $newBlock1)

Write-Host "[3/4] Mengaktifkan form search & dropdown kategori..." -ForegroundColor Green
$newContent = $newContent.Replace($oldBlock2, $newBlock2)

# Tulis dengan UTF-8 + BOM supaya karakter khusus tidak korup
$utf8Bom = New-Object System.Text.UTF8Encoding $true
[System.IO.File]::WriteAllText((Resolve-Path $targetFile), $newContent, $utf8Bom)

Write-Host "[4/4] Selesai." -ForegroundColor Green
Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " FIX DITERAPKAN! Refresh halaman mana saja," -ForegroundColor Cyan
Write-Host " coba ketik di search bar / pilih kategori, lalu Enter." -ForegroundColor Cyan
Write-Host " File asli ada di: $backupFile" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
