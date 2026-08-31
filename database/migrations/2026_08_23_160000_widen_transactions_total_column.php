<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BUG FIX (ditemukan dari storage/logs/laravel.log — SQLSTATE 22003
     * "Out of range value for column 'total'"):
     *
     * Kolom `total` sebelumnya `decimal(12,2)`, kapasitas maksimal
     * 9.999.999.999,99 (10 digit sebelum koma). Karena harga produk custom
     * furniture di project ini bisa sangat besar (mis. produk "joki F1"
     * seharga Rp1.000.000.000) dan quantity bisa lebih dari 1, hasil
     * kali harga x quantity bisa gampang melebihi kapasitas itu (mis.
     * 100 x Rp1.000.000.000 = Rp100.000.000.000 -> overflow, INSERT gagal).
     *
     * Diperbesar ke decimal(18,2) — kapasitas hingga
     * 9.999.999.999.999.999,99, jauh lebih dari cukup untuk skenario
     * bisnis toko furniture ini, tanpa perlu diperbesar lagi di masa depan.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('total', 18, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('total', 12, 2)->default(0)->change();
        });
    }
};