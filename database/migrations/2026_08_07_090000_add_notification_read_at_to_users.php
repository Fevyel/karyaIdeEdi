<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dipakai untuk sistem notifikasi (badge sidebar & ikon admin di
     * frontend, mirip inbox TikTok): kapan terakhir admin "melihat"
     * masing-masing kategori. Item yang dibuat SETELAH timestamp ini
     * dianggap belum dibaca (badge > 0). Mengunjungi halaman terkait,
     * atau membuka panel notifikasi, mengisi ulang timestamp ini ke
     * waktu sekarang — badge langsung hilang tanpa reload.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('interaksi_read_at')->nullable()->after('theme');
            $table->timestamp('pesanan_read_at')->nullable()->after('theme');
            $table->timestamp('dashboard_read_at')->nullable()->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['interaksi_read_at', 'pesanan_read_at', 'dashboard_read_at']);
        });
    }
};
