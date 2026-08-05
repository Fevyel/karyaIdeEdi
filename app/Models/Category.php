<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Kategori produk — data master, dikelola di Admin Panel (menu "Kategori").
 * Produk memilih salah satu kategori lewat relasi (category_id), bukan
 * lagi mengetik nama kategori secara manual.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $cover
 * @property string|null $description
 * @property bool $is_active
 * @property int $sort_order
 */
class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'cover',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * URL publik cover kategori, atau null kalau belum diunggah.
     * (Cover ini yang dipakai di section "Produk Berdasarkan Kategori"
     * di frontend — bukan foto produk.)
     */
    public function coverUrl(): ?string
    {
        return $this->cover && Storage::disk('public')->exists($this->cover)
            ? Storage::disk('public')->url($this->cover)
            : null;
    }

    /** Buat slug unik dari nama kategori (dipakai saat create & update). */
    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'kategori';
        $slug = $base;
        $suffix = 2;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /** Hanya kategori aktif (tampil di frontend). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Urutan tampil: sort_order manual dulu, baru nama A-Z. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
