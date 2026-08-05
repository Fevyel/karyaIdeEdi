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