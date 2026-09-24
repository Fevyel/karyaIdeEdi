<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Production-safe: tidak menyisipkan akun, kategori, testimoni,
     * atau data contoh apa pun ke database aktif.
     *
     * Data operasional dikelola melalui panel Admin.
     */
    public function run(): void
    {
        $this->command?->info('Production-safe seeder: tidak ada data demo yang ditambahkan.');
    }
}