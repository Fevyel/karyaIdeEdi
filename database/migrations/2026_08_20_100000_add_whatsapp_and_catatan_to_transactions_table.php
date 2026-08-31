<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tahap Admin -> Pesanan -> Tambah Pesanan.
     *
     * Kolom ini BENAR-BENAR belum ada di schema sebelumnya (dicek dulu
     * lewat migration awal 2026_07_27_070002 & migration tambahan
     * 2026_08_18_090000 — keduanya tidak punya whatsapp/catatan), padahal
     * form tambah pesanan wajib mencatat nomor WhatsApp customer & catatan
     * pesanan. Migration baru dibuat khusus untuk itu, bukan mengedit
     * migration lama yang sudah pernah dijalankan.
     *
     * - whatsapp: nullable supaya data lama (kalau ada) tidak pecah.
     * - catatan : nullable, isi bebas (mis. request warna/ukuran custom).
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('whatsapp', 30)->nullable()->after('customer_name');
            $table->text('catatan')->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['whatsapp', 'catatan']);
        });
    }
};
