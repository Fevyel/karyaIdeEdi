<?php

use App\Http\Controllers\Admin\LogoutController;
use App\Http\Controllers\Admin\ThemeController;
use Illuminate\Support\Facades\Route;

// ==========================================================
// Halaman utama sementara (frontend customer belum dibangun).
// Root TIDAK lagi otomatis redirect ke /adminmode — admin
// harus mengetik sendiri /adminmode di belakang URL ini.
// ==========================================================
Route::view('/', 'home-placeholder')->name('home');

// ==========================================================
// Katalog Produk (frontend, publik — tidak butuh login).
// /produk        -> daftar semua produk aktif
// /produk/{slug} -> detail satu produk (route model binding by slug)
// ==========================================================
Route::get('/produk', function () {
    $query = \App\Models\Product::query()
        ->where('status', 'aktif')
        ->with(['category:id,name,slug'])
        ->withCount(['testimonials as approved_testimonials_count' => fn ($query) => $query->approved()])
        ->withAvg(['testimonials as average_rating' => fn ($query) => $query->approved()], 'rating');

    // Search dari header / mobile search.
    $search = trim((string) request('search', ''));
    if ($search !== '') {
        $query->where(function ($builder) use ($search) {
            $builder
                ->where('nama', 'like', "%{$search}%")
                ->orWhere('deskripsi_pendek', 'like', "%{$search}%");
        });
    }

    // Filter kategori dari header (satu slug) atau sidebar (multi-select).
    $categorySlugs = collect((array) request('categories', []))
        ->filter()
        ->values();

    if (request('category')) {
        $categorySlugs->push(request('category'));
    }

    $categorySlugs = $categorySlugs->unique()->values();

    if ($categorySlugs->isNotEmpty()) {
        $query->whereHas('category', fn ($categoryQuery) => $categoryQuery
            ->whereIn('slug', $categorySlugs)
            ->where('is_active', true));
    } else {
        // Kategori nonaktif tidak ikut muncul di katalog publik.
        $query->where(function ($builder) {
            $builder
                ->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('is_active', true))
                ->orWhereNull('category_id');
        });
    }

    // Filter harga.
    $priceMin = request()->filled('min_price') ? max(0, (float) request('min_price')) : null;
    $priceMax = request()->filled('max_price') ? max(0, (float) request('max_price')) : null;

    if ($priceMin !== null) {
        $query->whereRaw('COALESCE(NULLIF(harga_diskon, 0), harga) >= ?', [$priceMin]);
    }

    if ($priceMax !== null) {
        $query->whereRaw('COALESCE(NULLIF(harga_diskon, 0), harga) <= ?', [$priceMax]);
    }

    // Sorting mengikuti kontrol pada desain Figma.
    match (request('sort')) {
        'price_asc' => $query->orderByRaw('COALESCE(NULLIF(harga_diskon, 0), harga) ASC'),
        'price_desc' => $query->orderByRaw('COALESCE(NULLIF(harga_diskon, 0), harga) DESC'),
        'name_asc' => $query->orderBy('nama'),
        default => $query->latest(),
    };

    $products = $query
        ->paginate(12)
        ->withQueryString();

    $categories = \App\Models\Category::query()
        ->active()
        ->ordered()
        ->get(['id', 'name', 'slug']);

    $priceBounds = \App\Models\Product::query()
        ->where('status', 'aktif')
        ->selectRaw('MIN(COALESCE(NULLIF(harga_diskon, 0), harga)) as min_price, MAX(COALESCE(NULLIF(harga_diskon, 0), harga)) as max_price')
        ->first();

    $catalogMinPrice = (float) ($priceBounds->min_price ?? 0);
    $catalogMaxPrice = (float) ($priceBounds->max_price ?? max($catalogMinPrice, 1));

    if ($catalogMaxPrice <= $catalogMinPrice) {
        $catalogMaxPrice = $catalogMinPrice + 1;
    }

    return view('pages.frontend.produk-index', compact(
        'products',
        'categories',
        'catalogMinPrice',
        'catalogMaxPrice',
    ));
})->name('products.index');

Route::get('/produk/{product:slug}', function (\App\Models\Product $product) {
    abort_unless($product->status === 'aktif', 404);

    return view('pages.frontend.produk-show', ['product' => $product]);
})->name('products.show');

// ==========================================================
// Login Admin - HANYA bisa diakses lewat /adminmode
// (URL ini sengaja tidak ditautkan di menu customer)
// ==========================================================
Route::livewire('/adminmode', 'pages::admin.login')
    ->middleware('guest')
    ->name('admin.login');

// URL lama /adminmode/login tidak boleh dipakai langsung.
// Kalau ada yang membukanya, lempar ke halaman utama dulu —
// admin baru masuk ke /adminmode secara manual dari sana.
Route::redirect('/adminmode/login', '/');

// ==========================================================
// Admin Panel - halaman yang membutuhkan login, prefix /admin
// Jika belum login, middleware 'auth' otomatis mengarahkan
// kembali ke /adminmode (lihat bootstrap/app.php).
// ==========================================================
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::livewire('/dashboard', 'pages::admin.dashboard')
        ->name('dashboard');

    // Halaman Profil — data pemilik toko (foto, nama, kontak, bio, ganti password).
    Route::livewire('/profil', 'pages::admin.profil')
        ->name('profile');

    // Interaksi — moderasi komentar pembeli (setujui jadi testimoni publik, atau tolak & hapus).
    Route::livewire('/interaksi', 'pages::admin.interaksi')
        ->name('interaksi');

    // ------------------------------------------------------------
    // Halaman placeholder — routing & komponen sudah aktif,
    // isinya menyusul saat masing-masing fitur dikerjakan.
    // ------------------------------------------------------------
    Route::livewire('/produk', 'pages::admin.produk')
        ->name('products');

    Route::livewire('/produk/tambah', 'pages::admin.produk-form')
        ->name('products.create');

    Route::livewire('/produk/{product}/edit', 'pages::admin.produk-form')
        ->name('products.edit');

    // Kategori — data master (dikelola sebelum produk memilih kategorinya).
    Route::livewire('/kategori', 'pages::admin.kategori')
        ->name('categories');

    Route::livewire('/kategori/tambah', 'pages::admin.kategori-form')
        ->name('categories.create');

    Route::livewire('/kategori/{category}/edit', 'pages::admin.kategori-form')
        ->name('categories.edit');

    Route::livewire('/pesanan', 'pages::admin.pesanan')
        ->name('transactions');

    Route::livewire('/pelanggan', 'pages::admin.pelanggan')
        ->name('customers');

    Route::livewire('/laporan', 'pages::admin.laporan')
        ->name('reports');

    Route::livewire('/pengaturan', 'pages::admin.pengaturan')
        ->name('settings');

    // Simpan preferensi tema (Glow/Dark) — dipanggil lewat fetch() dari navbar, tanpa reload.
    Route::post('/theme', ThemeController::class)
        ->name('theme.update');

    Route::post('/logout', LogoutController::class)
        ->name('logout');
});