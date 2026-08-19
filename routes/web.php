<?php

use App\Http\Controllers\Admin\LogoutController;
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

    $products = $query->latest()->paginate(12)->withQueryString();
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

// Testimoni Publik
Route::get('/testimoni', function () {
    $testimonials = \App\Models\Testimonial::query()
        ->approved()
        ->active()
        ->where('is_featured_home', false)
        ->latest()
        ->paginate(9)
        ->withQueryString();

    return view('pages.frontend.testimoni', compact('testimonials'));
})->name('testimonials.index');

// Admin Auth & Panel
Route::livewire('/adminmode', 'pages::admin.login')->middleware('guest')->name('admin.login');
Route::redirect('/adminmode/login', '/');

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::livewire('/dashboard', 'pages::admin.dashboard')->name('dashboard');
    Route::livewire('/profil', 'pages::admin.profil')->name('profile');
    Route::livewire('/interaksi', 'pages::admin.interaksi')->name('interaksi');
    Route::livewire('/produk', 'pages::admin.produk')->name('products');
    Route::livewire('/produk/tambah', 'pages::admin.produk-form')->name('products.create');
    Route::livewire('/produk/{product}/edit', 'pages::admin.produk-form')->name('products.edit');
    Route::livewire('/kategori', 'pages::admin.kategori')->name('categories');
    Route::livewire('/kategori/tambah', 'pages::admin.kategori-form')->name('categories.create');
    Route::livewire('/kategori/{category}/edit', 'pages::admin.kategori-form')->name('categories.edit');
    Route::livewire('/pesanan', 'pages::admin.pesanan')->name('transactions');
    Route::livewire('/pelanggan', 'pages::admin.pelanggan')->name('customers');
    Route::livewire('/laporan', 'pages::admin.laporan')->name('reports');
    Route::livewire('/pengaturan', 'pages::admin.pengaturan')->name('settings');

    Route::post('/theme', ThemeController::class)->name('theme.update');
    Route::post('/logout', LogoutController::class)->name('logout');
});