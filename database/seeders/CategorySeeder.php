<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Kategori dasar furniture, supaya form Tambah Produk langsung
     * ada pilihan begitu migration dijalankan di toko yang belum
     * pernah punya produk sama sekali. Aman dijalankan berkali-kali
     * (updateOrCreate berdasarkan slug).
     */
    public function run(): void
    {
        $categories = ['Sofa', 'Meja', 'Kursi', 'Lemari', 'Rak', 'Tempat Tidur', 'Dekorasi'];

        foreach ($categories as $index => $name) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $index],
            );
        }
    }
}
