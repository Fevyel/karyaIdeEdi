<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('nama_penerima')->nullable()->after('whatsapp');
            $table->text('alamat_lengkap')->nullable()->after('nama_penerima');
            $table->string('kecamatan')->nullable()->after('alamat_lengkap');
            $table->string('kota')->nullable()->after('kecamatan');
            $table->string('provinsi')->nullable()->after('kota');
            $table->string('kode_pos', 10)->nullable()->after('provinsi');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'nama_penerima',
                'alamat_lengkap',
                'kecamatan',
                'kota',
                'provinsi',
                'kode_pos',
            ]);
        });
    }
};
