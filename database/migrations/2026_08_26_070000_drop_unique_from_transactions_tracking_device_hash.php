<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom tracking_device_hash awalnya dibuat unique() secara GLOBAL
     * di seluruh tabel transactions. Ini salah: satu pelanggan (satu
     * device/browser -> satu hash yang sama) wajar memesan lebih dari
     * sekali, jadi hash yang sama semestinya boleh muncul di banyak
     * baris transaksi berbeda. Constraint unique global inilah yang
     * menyebabkan UniqueConstraintViolationException saat pelanggan
     * yang sama membuka link tracking untuk pesanan keduanya.
     *
     * Kolom & data tidak diubah -- hanya index unique-nya yang dicabut.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('transactions_tracking_device_hash_unique');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unique('tracking_device_hash');
        });
    }
};
