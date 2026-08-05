<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kategori sekarang jadi data master (dikelola sendiri di Admin Panel),
     * bukan lagi teks bebas yang diketik di form produk.
     *
     * - cover: foto sampul kategori (dipakai di section "Produk Berdasarkan
     *   Kategori" di frontend) — BUKAN diambil dari foto produk.
     * - sort_order: urutan tampil manual (angka kecil tampil duluan).
     * - is_active: kategori nonaktif disembunyikan dari frontend, tapi
     *   produk di dalamnya tetap aman/tidak terhapus.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('cover')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
