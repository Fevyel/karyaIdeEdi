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
     * Nomor WhatsApp dalam format internasional siap pakai di link wa.me
     * (mis. "6281234567890"), atau null kalau belum diisi.
     *
     * Menerima format 08xxxxxxxxxx, 8xxxxxxxxxx, +62xxxxxxxxxx, dengan atau
     * tanpa spasi/tanda hubung. Semua tombol WhatsApp di website (navbar,
     * footer, halaman produk, keranjang, dst.) harus lewat method ini,
     * supaya normalisasi nomor cuma ada di satu tempat.
     */
    public function whatsappDigits(): ?string
    {
        $digits = $this->whatsapp ? preg_replace('/\D+/', '', $this->whatsapp) : '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }

        return $digits;
    }

    /**
     * URL public logo, atau null kalau belum ada logo yang diunggah.
     */
    public function logoUrl(): ?string
    {
        return $this->logo_path
            ? Storage::disk('public')->url($this->logo_path)
            : null;
    }
}