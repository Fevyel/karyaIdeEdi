<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'slug',
        'category_id',
        'harga',
        'harga_diskon',
        'deskripsi_pendek',
        'deskripsi_lengkap',
        'thumbnail',
        'status',
        'featured',
        'stok',
        'berat',
        'panjang',
        'lebar',
        'tinggi',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'harga_diskon' => 'decimal:2',
            'featured' => 'boolean',
            'stok' => 'integer',
            'berat' => 'decimal:2',
            'panjang' => 'decimal:2',
            'lebar' => 'decimal:2',
            'tinggi' => 'decimal:2',
        ];
    }

    /**
     * Kategori produk — data master (lihat App\Models\Category).
     * Produk memilih kategori lewat dropdown, tidak lagi mengetik teks bebas.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    /**
     * Alias baca-saja ke kolom `nama`, supaya kode yang sudah ada
     * (mis. resources/views/pages/admin/dashboard.blade.php dan
     * interaksi.blade.php yang memanggil `$product->name`) tetap
     * berfungsi tanpa perlu diubah, sesuai instruksi "jangan mengubah
     * apa pun yang sudah selesai".
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->nama,
        );
    }
}