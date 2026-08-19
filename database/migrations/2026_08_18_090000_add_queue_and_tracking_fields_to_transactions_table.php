<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tahap lanjutan Admin -> Pesanan (persiapan input manual admin).
     * Migration BARU (bukan edit migration lama 2026_07_27_070002 yang
     * sudah pernah dijalankan):
     *
     * - queue_number + queue_date : nomor antrean harian. Unique-nya
     *   digabung (queue_date, queue_number) — BUKAN unique global —
     *   supaya nomor antrean boleh mulai dari 1 lagi tiap hari.
     * - tracking_token            : token acak aman (nanti dipakai
     *   halaman tracking customer, belum dibuat di tahap ini).
     * - status                    : enum diperluas supaya menampung
     *   alur pesanan custom furniture, tanpa menghapus nilai lama.
     *
     * order_code SUDAH unique sejak migration awal, tidak diulang di sini.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedInteger('queue_number')->nullable()->after('order_code');
            $table->date('queue_date')->nullable()->after('queue_number');
            $table->string('tracking_token', 64)->nullable()->unique()->after('queue_date');

            $table->unique(['queue_date', 'queue_number'], 'transactions_queue_date_number_unique');
        });

        // Mengubah nilai ENUM butuh raw statement karena project ini tidak
        // meng-install doctrine/dbal (dibutuhkan Schema::table()->change()
        // untuk kolom enum). Project ini pakai MySQL (DB_CONNECTION=mysql).
        DB::statement("ALTER TABLE transactions MODIFY status ENUM('pending','confirmed','processing','preparing','completed','cancelled') NOT NULL DEFAULT 'completed'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY status ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'completed'");

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('transactions_queue_date_number_unique');
            $table->dropColumn(['queue_number', 'queue_date', 'tracking_token']);
        });
    }
};