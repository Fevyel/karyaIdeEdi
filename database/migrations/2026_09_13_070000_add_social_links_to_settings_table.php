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
        Schema::table('settings', function (Blueprint $table) {
            // Link sosial media resmi toko. Ditampilkan sebagai tombol/ikon
            // di navbar, footer, dan section lain di seluruh website —
            // hanya muncul kalau kolomnya terisi (pola sama seperti WhatsApp).
            $table->string('instagram_url')->nullable()->after('whatsapp');
            $table->string('tiktok_url')->nullable()->after('instagram_url');
            $table->string('facebook_url')->nullable()->after('tiktok_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['instagram_url', 'tiktok_url', 'facebook_url']);
        });
    }
};
