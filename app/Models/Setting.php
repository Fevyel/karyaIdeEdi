<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $site_name
 * @property string|null $tagline
 * @property string|null $logo_path
 * @property string|null $email
 * @property string|null $whatsapp
 * @property string|null $alamat
 */
#[Fillable(['site_name', 'tagline', 'logo_path', 'email', 'whatsapp', 'alamat'])]
class Setting extends Model
{
    /**
     * Pengaturan situs disimpan sebagai satu baris tunggal (singleton).
     * Ambil baris itu, atau buat dengan nilai default kalau belum ada.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            ['site_name' => 'Karya Ide Edi', 'tagline' => 'Furniture custom berkualitas, dibuat dengan hati.']
        );
    }

    /**
     * URL publik logo, atau null kalau belum ada logo yang diunggah.
     */
    public function logoUrl(): ?string
    {
        return $this->logo_path
            ? Storage::disk('public')->url($this->logo_path)
            : null;
    }
}
