<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel `testimonials` sebelumnya cuma dipakai untuk alur "Interaksi"
     * (komentar pembeli yang menunggu disetujui admin). Sekarang tabel
     * yang sama dipakai juga untuk testimoni yang dibuat LANGSUNG oleh
     * admin lewat menu "Testimoni" (nama, foto, jabatan, rating, dll)
     * — supaya tidak ada 2 tabel terpisah untuk konsep yang sama.
     *
     * `is_approved` yang sudah ada dipakai ulang sebagai status
     * "tampil di frontend" (aktif/nonaktif) untuk testimoni buatan
     * admin, sama seperti maknanya untuk komentar pembeli yang disetujui.
     */
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('foto')->nullable()->after('customer_name');
            $table->string('jabatan')->nullable()->after('foto');
            $table->unsignedInteger('urutan')->default(0)->after('is_approved');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn(['foto', 'jabatan', 'urutan']);
        });
    }
};
