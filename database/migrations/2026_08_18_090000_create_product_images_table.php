<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foto tambahan produk (di luar thumbnail utama yang sudah ada di
     * kolom `products.thumbnail`). Tabel terpisah supaya tidak mengubah
     * struktur/kolom thumbnail yang sudah dipakai produk lama — produk
     * lama tanpa baris di tabel ini tetap bekerja normal (backward
     * compatible), hanya slot foto tambahannya kosong.
     *
     * sort_order dipakai untuk urutan tampil di gallery frontend:
     * 1 = foto tambahan pertama (slot ke-2 setelah thumbnail utama),
     * 2 = foto tambahan kedua, 3 = foto tambahan ketiga (maksimal 3,
     * sehingga total foto per produk = thumbnail + 3 = 4).
     */
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->unsignedTinyInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
