<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom ini dipakai oleh route detail produk (routes/web.php) untuk
     * algoritma "Related Products": produk yang paling sering dibuka
     * pembeli akan direkomendasikan di halaman detail produk lain.
     * Sebelumnya kolom ini belum pernah dimigrasikan, jadi query selalu
     * jatuh ke fallback (cari se-kategori) yang bisa kosong kalau tidak
     * ada produk lain di kategori yang sama.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('views_count')->default(0)->after('stok');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('views_count');
        });
    }
};