<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel produk furniture - dipakai untuk katalog, statistik dashboard,
     * dan direferensikan oleh testimoni (interaksi pembeli).
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('slug')->unique();
            $table->string('kategori');
            $table->decimal('harga', 12, 2)->default(0);
            $table->decimal('harga_diskon', 12, 2)->nullable();
            $table->string('deskripsi_pendek', 500);
            $table->text('deskripsi_lengkap');
            $table->string('thumbnail');
            $table->string('status')->default('aktif');
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('stok')->default(0);
            $table->decimal('berat', 8, 2)->default(0);
            $table->decimal('panjang', 8, 2)->default(0);
            $table->decimal('lebar', 8, 2)->default(0);
            $table->decimal('tinggi', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};