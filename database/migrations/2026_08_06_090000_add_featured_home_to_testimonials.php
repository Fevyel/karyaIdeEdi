<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kurasi testimoni yang tampil di beranda: admin memilih SENDIRI
     * maksimal 3 komentar (dari yang sudah approved + aktif) untuk
     * ditampilkan di section "Ulasan Pelanggan Kami", bukan otomatis
     * yang terbaru. Batas 3 slot ini ditegakkan di kode (lihat menu
     * Testimoni), bukan lewat constraint database.
     */
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->boolean('is_featured_home')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn('is_featured_home');
        });
    }
};
