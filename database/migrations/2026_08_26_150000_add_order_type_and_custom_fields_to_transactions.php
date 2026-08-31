<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah dukungan pesanan "Tetap" vs "Custom" (ide dari diskusi user,
     * 26 Agustus 2026):
     * - order_type: 'tetap' (default, pakai harga produk dari database
     *   seperti sekarang) atau 'custom' (ukuran & harga didiskusikan
     *   manual dengan customer).
     * - custom_tinggi/lebar/panjang: ukuran mebel custom dalam cm.
     * - custom_harga_satuan: harga satuan hasil negosiasi dengan customer,
     *   dipakai menggantikan harga produk dari database KHUSUS saat
     *   order_type = 'custom'. Untuk order_type = 'tetap', kolom ini
     *   tetap null - harga tetap selalu diambil dari Product seperti
     *   sebelumnya (tidak diubah).
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('order_type', 20)->default('tetap')->after('product_id');
            $table->decimal('custom_tinggi', 8, 2)->nullable()->after('order_type');
            $table->decimal('custom_lebar', 8, 2)->nullable()->after('custom_tinggi');
            $table->decimal('custom_panjang', 8, 2)->nullable()->after('custom_lebar');
            $table->decimal('custom_harga_satuan', 12, 2)->nullable()->after('custom_panjang');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'order_type',
                'custom_tinggi',
                'custom_lebar',
                'custom_panjang',
                'custom_harga_satuan',
            ]);
        });
    }
};
