<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pisahkan konsep "keputusan moderasi" dari "tampil/sembunyi":
     *
     * - approval_status (pending/approved/rejected): keputusan admin atas
     *   komentar yang masuk lewat menu Interaksi. Berbeda dari perilaku
     *   lama, komentar yang DITOLAK sekarang TIDAK dihapus otomatis —
     *   tetap tersimpan supaya bisa dilihat di tab "Ditolak" (ada tombol
     *   Delete terpisah kalau admin memang mau menghapusnya permanen).
     * - is_active (boolean): saklar tampil/sembunyikan terpisah dari
     *   status approval, dipakai baik oleh komentar yang sudah disetujui
     *   maupun testimoni yang dibuat langsung admin lewat menu "Testimoni".
     *
     * Data lama (`is_approved`) dipetakan otomatis, tidak ada data hilang:
     *   is_approved = true  -> approval_status = approved
     *   is_approved = false -> approval_status = pending
     *   (keduanya -> is_active = true, sesuai perilaku lama yang selalu
     *   menampilkan begitu is_approved = true)
     */
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('approval_status')->default('pending')->after('comment');
            $table->boolean('is_active')->default(true)->after('approval_status');
        });

        DB::table('testimonials')->where('is_approved', true)->update(['approval_status' => 'approved']);
        DB::table('testimonials')->where('is_approved', false)->update(['approval_status' => 'pending']);

        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn('is_approved');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->boolean('is_approved')->default(false)->after('comment');
        });

        DB::table('testimonials')->where('approval_status', 'approved')->update(['is_approved' => true]);

        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'is_active']);
        });
    }
};
