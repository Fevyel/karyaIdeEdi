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

    /**
     * Pecah sebuah tautan video jadi info yang dibutuhkan untuk merender
     * pemutarnya di Beranda (dipakai section "Kenapa Pilih Kami").
     *
     * `provider` menentukan cara render di partials/frontend/expertise.blade.php:
     * - 'direct'    : file video langsung (.mp4/.webm/.ogg/.mov/dst) -> tag <video>,
     *                 kontrol penuh (autoplay+suara saat masuk viewport, fade
     *                 keluar, tombol mute manual) bisa jalan 100%.
     * - 'youtube'   : embed YouTube -> iframe + YouTube postMessage API, kontrol
     *                 hampir penuh (autoplay+suara, fade keluar, tombol mute).
     * - 'facebook', 'tiktok', 'instagram', 'gdrive' : iframe embed resmi
     *                 masing-masing platform. Platform ini TIDAK menyediakan API
     *                 publik untuk kita paksa autoplay bersuara + fade volume dari
     *                 luar, jadi hanya di-lazy-load saat section masuk layar dan
     *                 sisanya mengikuti pemutar bawaan platform tsb.
     * - null        : tautan tidak dikenali / kosong -> tidak dirender sebagai video.
     *
     * @return array{provider: string|null, embed_url: string|null}
     */
    public static function classifyVideoUrl(?string $url): array
    {
        $url = trim((string) $url);

        if ($url === '') {
            return ['provider' => null, 'embed_url' => null];
        }

        // YouTube (watch, youtu.be, shorts, sudah berbentuk embed sekalipun).
        if (preg_match('#(?:youtube(?:-nocookie)?\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([A-Za-z0-9_-]{6,})#i', $url, $m)) {
            return [
                'provider' => 'youtube',
                'embed_url' => 'https://www.youtube-nocookie.com/embed/'.$m[1].'?enablejsapi=1&playsinline=1&rel=0&modestbranding=1&loop=1&playlist='.$m[1],
            ];
        }

        // Google Drive (link "berbagi" biasa: .../file/d/{id}/view...).
        if (preg_match('#drive\.google\.com/(?:file/d/([A-Za-z0-9_-]+)|open\?id=([A-Za-z0-9_-]+))#i', $url, $m)) {
            $id = $m[1] !== '' ? $m[1] : ($m[2] ?? '');

            return [
                'provider' => 'gdrive',
                'embed_url' => 'https://drive.google.com/file/d/'.$id.'/preview',
            ];
        }

        // Facebook (facebook.com/.../videos/... atau tautan pendek fb.watch).
        if (preg_match('#(?:facebook\.com|fb\.watch)#i', $url)) {
            return [
                'provider' => 'facebook',
                'embed_url' => 'https://www.facebook.com/plugins/video.php?href='.urlencode($url).'&show_text=0',
            ];
        }

        // TikTok.
        if (preg_match('#tiktok\.com.*?/video/(\d+)#i', $url, $m)) {
            return [
                'provider' => 'tiktok',
                'embed_url' => 'https://www.tiktok.com/embed/v2/'.$m[1],
            ];
        }

        // Instagram (Reel maupun Post).
        if (preg_match('#instagram\.com/(?:reel|p|tv)/([A-Za-z0-9_-]+)#i', $url, $m)) {
            return [
                'provider' => 'instagram',
                'embed_url' => 'https://www.instagram.com/'.(str_contains($url, '/reel/') ? 'reel' : 'p').'/'.$m[1].'/embed',
            ];
        }

        // Fallback: anggap tautan video langsung (mp4/webm/ogg/mov/dst, atau URL
        // streaming lain yang bisa dibaca langsung oleh tag <video>).
        return ['provider' => 'direct', 'embed_url' => $url];
    }
}
