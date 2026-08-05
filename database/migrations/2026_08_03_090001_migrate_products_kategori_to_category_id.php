<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Ganti kolom `kategori` (string bebas, diketik manual di form produk)
     * menjadi `category_id` (foreign key ke tabel categories yang baru).
     *
     * PENTING — ini migrasi DATA, bukan cuma migrasi struktur:
     * setiap nilai `kategori` yang sudah ada di produk lama akan dibuatkan
     * baris kategori yang sepadan secara otomatis, lalu produk itu
     * diarahkan (di-relasikan) ke kategori barunya. Tidak ada produk yang
     * kehilangan kategorinya, dan tidak ada data yang dihapus sebelum
     * dipindahkan dengan aman.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('kategori')
                ->constrained()->nullOnDelete();
        });

        // ---- Migrasi data: kategori (string) -> categories (tabel) ----
        $distinctNames = DB::table('products')
            ->whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->distinct()
            ->pluck('kategori');

        foreach ($distinctNames as $index => $name) {
            $baseSlug = Str::slug($name) ?: 'kategori-'.($index + 1);
            $slug = $baseSlug;
            $suffix = 2;

            while (DB::table('categories')->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix++;
            }

            $categoryId = DB::table('categories')->insertGetId([
                'name' => $name,
                'slug' => $slug,
                'is_active' => true,
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('products')->where('kategori', $name)->update(['category_id' => $categoryId]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('kategori');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('kategori')->nullable()->after('slug');
        });

        DB::table('products')->orderBy('id')->each(function ($product): void {
            if (! $product->category_id) {
                return;
            }

            $name = DB::table('categories')->where('id', $product->category_id)->value('name');

            if ($name) {
                DB::table('products')->where('id', $product->id)->update(['kategori' => $name]);
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
