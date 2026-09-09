<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    protected $fillable = [
        'section_key',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    /**
     * Ambil (atau buat baris kosong untuk) section tertentu.
     */
    public static function forSection(string $key): self
    {
        return static::query()->firstOrCreate(
            ['section_key' => $key],
            ['data' => []],
        );
    }

    /**
     * Ambil data section, digabung dengan nilai default -- supaya field
     * yang belum pernah disimpan admin tetap punya nilai fallback aman.
     *
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public static function dataFor(string $key, array $defaults = []): array
    {
        $section = static::query()->where('section_key', $key)->first();

        return array_replace_recursive($defaults, $section?->data ?? []);
    }
}
