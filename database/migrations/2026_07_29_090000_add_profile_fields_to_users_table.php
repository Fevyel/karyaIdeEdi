<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('foto_profil')->nullable()->after('name');
            $table->string('nama_toko')->nullable()->after('foto_profil');
            $table->string('whatsapp', 20)->nullable()->after('email');
            $table->text('alamat')->nullable()->after('whatsapp');
            $table->text('bio')->nullable()->after('alamat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['foto_profil', 'nama_toko', 'whatsapp', 'alamat', 'bio']);
        });
    }
};