<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alamat asal pembeli (Provinsi & Kabupaten/Kota) yang ditampilkan di
     * bawah nama pada kartu testimoni publik — sesuai sketsa layout baru.
     * Sama seperti nama & foto, kolom ini OPSIONAL:
     * - Diisi pembeli sendiri (opsional) di form komentar pada halaman
     *   Lacak Pesanan, hanya untuk komentar pertama — lihat
     *   TrackingController::storeComment.
     * - Bisa juga diisi admin lewat menu "Testimoni" saat membuat
     *   testimoni langsung — lihat pages/admin/testimoni-form.blade.php.
     */
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            if (! Schema::hasColumn('testimonials', 'provinsi')) {
                $table->string('provinsi')->nullable()->after('is_name_masked');
            }

            if (! Schema::hasColumn('testimonials', 'kabupaten')) {
                $table->string('kabupaten')->nullable()->after('provinsi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            if (Schema::hasColumn('testimonials', 'kabupaten')) {
                $table->dropColumn('kabupaten');
            }

            if (Schema::hasColumn('testimonials', 'provinsi')) {
                $table->dropColumn('provinsi');
            }
        });
    }
};
