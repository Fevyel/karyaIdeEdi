<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ganti cara menghitung notifikasi "Interaksi" & "Pesanan" dari
     * perbandingan timestamp (created_at > *_read_at) menjadi flag
     * boolean per-baris — persis seperti inbox TikTok:
     *
     * - Baris baru selalu masuk dengan is_read_admin = false.
     * - Begitu admin membuka halaman terkait, SEMUA baris yang
     *   is_read_admin = false pada kategori itu langsung diubah
     *   menjadi true (lihat App\Models\User::markInteraksiRead() /
     *   markPesananRead()).
     * - Badge cukup COUNT(*) WHERE is_read_admin = false — tidak ada
     *   lagi perbandingan tanggal yang rawan salah hitung (sebelumnya
     *   ini yang menyebabkan badge ikut menghitung SEMUA data, bukan
     *   cuma yang belum dibaca).
     *
     * Kategori "Dashboard" (peringatan stok menipis) TETAP pakai
     * pendekatan timestamp lama (dashboard_read_at) karena sifatnya
     * beda — itu kondisi/state produk, bukan baris baru yang dibuat.
     */
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->boolean('is_read_admin')->default(false)->after('is_featured_home');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->boolean('is_read_admin')->default(false)->after('total');
        });

        // Semua baris yang sudah ada sebelum migration ini dianggap
        // sudah pernah "dilihat" (supaya admin tidak tiba-tiba disambut
        // badge raksasa berisi seluruh data lama begitu update dijalankan).
        DB::table('testimonials')->update(['is_read_admin' => true]);
        DB::table('transactions')->update(['is_read_admin' => true]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['interaksi_read_at', 'pesanan_read_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('interaksi_read_at')->nullable()->after('theme');
            $table->timestamp('pesanan_read_at')->nullable()->after('theme');
        });

        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn('is_read_admin');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('is_read_admin');
        });
    }
};
