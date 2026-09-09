<?php

use App\Http\Controllers\Admin\LogoutController;
use App\Http\Controllers\CartStatusController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\Admin\ThemeController;
use Illuminate\Support\Facades\Route;

// Halaman utama
Route::view('/', 'home-placeholder')->name('home');

// Katalog Produk Publik
Route::get('/produk', function () {
    $query = \App\Models\Product::query()
        ->where('status', 'aktif')
        ->with(['category:id,name,slug']);

    $search = trim((string) request('search', ''));
    if ($search !== '') {
        $query->where(function ($builder) use ($search) {
            $builder->where('nama', 'like', "%{$search}%")
                ->orWhere('deskripsi_pendek', 'like', "%{$search}%");
        });
    }

    // Dukungan filter kategori dari homepage (?category=slug)
    // dan dari sidebar shop (?categories[]=slug&categories[]=slug).
    $categorySlugs = collect((array) request('categories', []))
        ->map(fn ($slug) => trim((string) $slug))
        ->filter()
        ->values();

    $singleCategory = trim((string) request('category', ''));
    if ($singleCategory !== '' && ! $categorySlugs->contains($singleCategory)) {
        $categorySlugs->push($singleCategory);
    }

    if ($categorySlugs->isNotEmpty()) {
        $query->whereHas('category', function ($categoryQuery) use ($categorySlugs) {
            $categoryQuery->whereIn('slug', $categorySlugs->all());
        });
    }

    // Sorting tetap melalui query GET agar filter + sorting bisa dipakai bersamaan.
    match (request('sort')) {
        'price_asc' => $query->orderByRaw('COALESCE(NULLIF(harga_diskon, 0), harga) ASC'),
        'price_desc' => $query->orderByRaw('COALESCE(NULLIF(harga_diskon, 0), harga) DESC'),
        'name_asc' => $query->orderBy('nama'),
        default => $query->latest(),
    };

    $products = $query->paginate(12)->withQueryString();
    $categories = \App\Models\Category::query()->active()->ordered()->get();

    return view('pages.frontend.produk-index', compact('products', 'categories'));
})->name('products.index');

// Detail Produk (Algoritma Rekomendasi Frekuensi Buka)
Route::get('/produk/{product:slug}', function (\App\Models\Product $product) {
    abort_unless($product->status === 'aktif', 404);

    // Increment frekuensi produk dibuka
    if (\Illuminate\Support\Facades\Schema::hasColumn('products', 'views_count')) {
        $product->increment('views_count');
    }

    // Ambil 4 produk yang paling sering dilihat pembeli (atau se-kategori sebagai fallback)
    $relatedProducts = \App\Models\Product::query()
        ->where('status', 'aktif')
        ->where('id', '!=', $product->id)
        ->when(\Illuminate\Support\Facades\Schema::hasColumn('products', 'views_count'), function ($q) {
            return $q->orderBy('views_count', 'desc');
        }, function ($q) use ($product) {
            return $q->where('category_id', $product->category_id)->latest();
        })
        ->take(4)
        ->get();

    return view('pages.frontend.produk-show', [
        'product' => $product,
        'relatedProducts' => $relatedProducts,
    ]);
})->name('products.show');

// Profil
Route::view('/profil', 'pages.frontend.profil')->name('profile.index');

// Koleksi pembeli â€” disimpan di browser/perangkat, tanpa akun dan tanpa mengubah database.
Route::view('/favorit', 'pages.frontend.favorit')->name('favorites.index');
Route::view('/keranjang', 'pages.frontend.keranjang')->name('cart.index');

// Dipanggil dari Keranjang (resources/js/app.js) untuk cek produk mana yang
// pesanannya sudah diinput admin DAN sudah dikonfirmasi berasal dari
// perangkat yang sama (pakai cookie device yang sama dengan fitur Lacak
// Pesanan) â€” lihat app/Http/Controllers/CartStatusController.php.
Route::get('/keranjang/status', [CartStatusController::class, 'check'])->name('cart.status');

// Halaman statis kolom "Company" di footer.
// Our Craftsmen: konten & foto masih dummy (belum ada data tukang asli),
// tandanya ada di komentar dalam masing-masing view â€” ganti begitu ada
// data & foto asli dari pemilik toko.
Route::view('/pengrajin-kami', 'pages.frontend.pengrajin')->name('craftsmen.index');
Route::view('/keberlanjutan', 'pages.frontend.keberlanjutan')->name('sustainability.index');
Route::view('/karier', 'pages.frontend.karier')->name('careers.index');

// Legal â€” Privacy Policy, Terms of Service, Cookies.
Route::view('/kebijakan-privasi', 'pages.frontend.privacy-policy')->name('legal.privacy');
Route::view('/ketentuan-layanan', 'pages.frontend.terms-of-service')->name('legal.terms');
Route::view('/kebijakan-cookie', 'pages.frontend.cookies')->name('legal.cookies');

// Testimoni Publik
Route::get('/testimoni', function () {
    $testimonials = \App\Models\Testimonial::query()
        ->approved()
        ->active()
        ->topLevel()
        ->with(['approvedUpdateComment', 'product:id,nama'])
        ->where('is_featured_home', false)
        ->latest()
        ->paginate(9)
        ->withQueryString();

    return view('pages.frontend.testimoni', compact('testimonials'));
})->name('testimonials.index');

Route::get('/booking', function () {
    $siteSetting = \App\Models\Setting::current();
    $waNumber = $siteSetting->whatsappDigits();

    $products = \App\Models\Product::query()
        ->where('status', 'aktif')
        ->with('category:id,name,slug')
        ->latest()
        ->take(8)
        ->get();

    return view('pages.frontend.booking', compact('siteSetting', 'waNumber', 'products'));
})->name('booking.index');

// Tracking Pesanan.
// /lacak hanya menampilkan pesanan yang pernah dibuka pada browser/perangkat ini.
// /lacak/{trackingToken} adalah link rahasia per pesanan dan sekaligus mendaftarkan
// token tersebut ke cookie perangkat setelah token terbukti valid.
Route::get('/lacak', [TrackingController::class, 'index'])->name('tracking.index');
Route::get('/lacak/{trackingToken}', [TrackingController::class, 'show'])->name('tracking.show');
Route::post('/lacak/{trackingToken}/komentar', [TrackingController::class, 'storeComment'])->name('tracking.comment');

// Admin Auth & Panel
Route::livewire('/adminmode', 'pages::admin.login')->middleware('guest')->name('admin.login');
Route::redirect('/adminmode/login', '/');

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::livewire('/dashboard', 'pages::admin.dashboard')->name('dashboard');
    Route::livewire('/profil', 'pages::admin.profil')->name('profile');
    Route::livewire('/interaksi', 'pages::admin.interaksi')->name('interaksi');
    Route::livewire('/testimoni', 'pages::admin.testimoni')->name('testimonials');
    Route::livewire('/testimoni/tambah', 'pages::admin.testimoni-form')->name('testimonials.create');
    Route::livewire('/testimoni/{testimonial}/edit', 'pages::admin.testimoni-form')->name('testimonials.edit');
    Route::livewire('/produk', 'pages::admin.produk')->name('products');
    Route::livewire('/produk/tambah', 'pages::admin.produk-form')->name('products.create');
    Route::livewire('/produk/{product}/edit', 'pages::admin.produk-form')->name('products.edit');
    Route::livewire('/kategori', 'pages::admin.kategori')->name('categories');
    Route::livewire('/kategori/tambah', 'pages::admin.kategori-form')->name('categories.create');
    Route::livewire('/kategori/{category}/edit', 'pages::admin.kategori-form')->name('categories.edit');
    Route::livewire('/pesanan', 'pages::admin.pesanan')->name('transactions');
    Route::livewire('/pesanan/history', 'pages::admin.history-pesanan')->name('transactions.history');
    Route::livewire('/pelanggan', 'pages::admin.pelanggan')->name('customers');
    Route::livewire('/laporan', 'pages::admin.laporan')->name('reports');
    Route::livewire('/edit-web', 'pages::admin.edit-web')->name('website-editor');
    Route::livewire('/pengaturan', 'pages::admin.pengaturan')->name('settings');

    Route::post('/theme', ThemeController::class)->name('theme.update');
    Route::post('/logout', LogoutController::class)->name('logout');
});