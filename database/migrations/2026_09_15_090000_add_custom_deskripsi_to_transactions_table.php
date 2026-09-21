<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom deskripsi untuk pesanan custom (ide dari diskusi user,
     * 15 September 2026): saat order_type = 'custom', pemilihan produk
     * jadi opsional (lihat resources/views/pages/admin/pesanan.blade.php).
     * Kolom ini menampung catatan bebas dari admin — bahan, warna,
     * finishing, dan detail lain yang sudah didiskusikan dengan customer,
     * terutama saat pesanan custom TIDAK merujuk ke produk manapun.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->text('custom_deskripsi')->nullable()->after('custom_harga_satuan');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('custom_deskripsi');
        });
    }
};
