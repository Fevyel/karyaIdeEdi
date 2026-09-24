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
 * @property string|null $instagram_url
 * @property string|null $tiktok_url
 * @property string|null $facebook_url
 * @property string|null $alamat
 * @property string|null $bca_account_number
 * @property string|null $bca_account_name
 * @property string|null $bri_account_number
 * @property string|null $bri_account_name
 * @property string|null $dana_account_number
 * @property string|null $dana_account_name
 */
#[Fillable(['site_name', 'tagline', 'logo_path', 'email', 'whatsapp', 'instagram_url', 'tiktok_url', 'facebook_url', 'alamat', 'bca_account_number', 'bca_account_name', 'bri_account_number', 'bri_account_name', 'dana_account_number', 'dana_account_name'])]
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

    /**
     * Kolom link sosial media yang didukung, beserta label tampilan dan
     * domain resmi masing-masing platform. Dipakai untuk mendeteksi kalau
     * admin salah menaruh link di kolom yang salah (mis. link TikTok
     * dimasukkan ke kolom Instagram) — lihat detectSocialPlatform().
     *
     * @return array<string, array{label: string, hosts: array<int, string>}>
     */
    public static function socialPlatforms(): array
    {
        return [
            'instagram_url' => ['label' => 'Instagram', 'hosts' => ['instagram.com', 'instagr.am']],
            'tiktok_url' => ['label' => 'TikTok', 'hosts' => ['tiktok.com']],
            'facebook_url' => ['label' => 'Facebook', 'hosts' => ['facebook.com', 'fb.com', 'fb.watch']],
        ];
    }

    /**
     * Cocokkan host dari sebuah URL dengan daftar domain di socialPlatforms().
     * Mengembalikan nama kolom platform yang cocok (mis. 'tiktok_url'), atau
     * null kalau URL tidak dikenali sebagai domain sosial media manapun yang
     * didukung.
     */
    public static function detectSocialPlatform(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host);

        if ($host === '' || $host === null) {
            return null;
        }

        foreach (static::socialPlatforms() as $field => $platform) {
            foreach ($platform['hosts'] as $knownHost) {
                if ($host === $knownHost || str_ends_with($host, '.'.$knownHost)) {
                    return $field;
                }
            }
        }

        return null;
    }

    /**
     * Link Gmail compose (bukan mailto biasa) yang langsung membuka jendela
     * "Kirim Pesan" Gmail dengan alamat tujuan sudah terisi, atau null kalau
     * email belum diisi di Pengaturan.
     */
    public function gmailComposeUrl(): ?string
    {
        return $this->email
            ? 'https://mail.google.com/mail/?view=cm&fs=1&to='.urlencode($this->email)
            : null;
    }
}
