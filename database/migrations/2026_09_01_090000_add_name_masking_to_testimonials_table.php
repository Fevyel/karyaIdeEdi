<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saklar per-komentar: pembeli boleh minta namanya disamarkan saat
     * tampil sebagai testimoni publik (dicentang di form "Tambah Komentar"
     * pada halaman Lacak Pesanan, hanya untuk komentar pertama — lihat
     * TrackingController::storeComment & Testimonial::displayName()).
     * Nama ASLI di kolom `customer_name` tidak pernah diubah/ditimpa,
     * ini murni flag tampilan.
     */
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            if (! Schema::hasColumn('testimonials', 'is_name_masked')) {
                $table->boolean('is_name_masked')->default(false)->after('customer_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            if (Schema::hasColumn('testimonials', 'is_name_masked')) {
                $table->dropColumn('is_name_masked');
            }
        });
    }
};
