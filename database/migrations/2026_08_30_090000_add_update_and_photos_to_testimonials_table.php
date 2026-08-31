<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan dukungan untuk:
     * - transaction_id : menautkan komentar ke pesanan yang mengirimnya
     *   (dipakai TrackingController untuk mencari "komentar pertama" milik
     *   sebuah pesanan, jauh lebih akurat daripada menebak dari nama+produk).
     * - parent_id       : komentar "Update" menunjuk balik ke komentar
     *   pertamanya (self-reference). Komentar biasa (bukan update) nilainya
     *   NULL. Satu komentar pertama hanya boleh punya maksimal 1 update
     *   (dijaga di level aplikasi/controller, bukan constraint DB, supaya
     *   pesan errornya bisa ramah ke pengguna).
     * - photos          : daftar path foto (maks 5, divalidasi di
     *   TrackingController), disimpan sebagai JSON array. Foto bersifat
     *   opsional dan terpisah dari kolom `foto` (foto profil tunggal yang
     *   dipakai testimoni buatan admin di menu Testimoni).
     */
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            if (! Schema::hasColumn('testimonials', 'transaction_id')) {
                $table->foreignId('transaction_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('testimonials', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('transaction_id')->constrained('testimonials')->nullOnDelete();
            }

            if (! Schema::hasColumn('testimonials', 'photos')) {
                $table->json('photos')->nullable()->after('foto');
            }
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            if (Schema::hasColumn('testimonials', 'photos')) {
                $table->dropColumn('photos');
            }

            if (Schema::hasColumn('testimonials', 'parent_id')) {
                $table->dropConstrainedForeignId('parent_id');
            }

            if (Schema::hasColumn('testimonials', 'transaction_id')) {
                $table->dropConstrainedForeignId('transaction_id');
            }
        });
    }
};
