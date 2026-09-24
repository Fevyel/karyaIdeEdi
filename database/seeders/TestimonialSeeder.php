<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    /**
     * Dinonaktifkan: testimoni/interaksi harus berasal dari pelanggan asli
     * melalui alur Lacak Pesanan atau dibuat admin secara sengaja.
     */
    public function run(): void
    {
        $this->command?->info('TestimonialSeeder dinonaktifkan: tidak ada testimoni demo yang ditambahkan.');
    }
}