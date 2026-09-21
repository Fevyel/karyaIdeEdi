<?php

namespace App\Support;

/**
 * Palet warna rekomendasi untuk SEMUA pilihan warna di Admin > Edit Web
 * (Header, Sejak Berdiri, Produk Unggulan, Kategori Produk, Testimoni
 * Pelanggan -- termasuk warna kartu Top 1/2/3 -- dan Lokasi).
 *
 * Satu daftar ini dipakai bersama, jadi pilihan warnanya SELALU sama di
 * setiap bagian. Mau menambah/mengubah warna? Cukup ubah di sini -- jangan
 * membuat daftar warna sendiri di halaman lain.
 *
 * 'value' = hex warna, 'check' = warna centang di atas swatch yang terpilih
 * (gelap untuk warna terang, putih untuk warna gelap).
 */
final class ColorPalette
{
    /**
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public const PRESETS = [
        ['label' => 'Putih Polos', 'value' => '#FFFFFF', 'check' => '#3D2B1F'],
        ['label' => 'Krem Hangat', 'value' => '#F9F7F2', 'check' => '#3D2B1F'],
        ['label' => 'Putih Gading', 'value' => '#FFFDF8', 'check' => '#3D2B1F'],
        ['label' => 'Beige Pasir', 'value' => '#EFE3D0', 'check' => '#3D2B1F'],
        ['label' => 'Sage Hijau Lembut', 'value' => '#DCE3CE', 'check' => '#3D2B1F'],
        ['label' => 'Terracotta', 'value' => '#C97B5A', 'check' => '#FFFFFF'],
        ['label' => 'Coklat Kayu Tua', 'value' => '#3D2B1F', 'check' => '#FFFFFF'],
        ['label' => 'Navy Malam', 'value' => '#22303F', 'check' => '#FFFFFF'],
        ['label' => 'Hitam Elegan', 'value' => '#1A1A1A', 'check' => '#FFFFFF'],
    ];
}
