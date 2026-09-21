<?php

namespace App\Support;

/**
 * Latar ("frame") section Beranda yang diatur admin lewat Edit Web.
 *
 * - Bawaan: warna POLOS (satu warna solid), tanpa gradasi otomatis.
 * - Gradasi hanya dipakai kalau admin menyalakannya lewat tombol "Gradasi"
 *   di Edit Web -- lengkap dengan warna awal, warna akhir, warna tengah
 *   (opsional), dan arah/sudutnya. Selama gradasi menyala, ia menggantikan
 *   warna polos; begitu dimatikan, frame kembali ke warna polos.
 *
 * Bentuk data gradasi yang disimpan di home_sections.data['bg_gradient']:
 * ['enabled' => bool, 'from' => '#RRGGBB', 'use_mid' => bool,
 *  'mid' => '#RRGGBB', 'to' => '#RRGGBB', 'angle' => 0..360]
 */
final class FrameBackground
{
    /**
     * Gradasi awal yang tampil saat admin pertama kali menyalakannya.
     *
     * @var array{enabled: bool, from: string, use_mid: bool, mid: string, to: string, angle: int}
     */
    public const DEFAULT_GRADIENT = [
        'enabled' => false,
        'from' => '#FEEDD8',
        'use_mid' => false,
        'mid' => '#F1D3AE',
        'to' => '#D9B58A',
        'angle' => 135,
    ];

    /**
     * Gradasi rekomendasi -- sekali klik langsung terisi, lalu tetap bisa
     * diubah warna & arahnya. 'mid' null = gradasi dua warna.
     *
     * @var array<int, array{label: string, from: string, mid: string|null, to: string, angle: int}>
     */
    public const PRESETS = [
        ['label' => 'Senja Hangat', 'from' => '#FEEDD8', 'mid' => null, 'to' => '#E3B98A', 'angle' => 135],
        ['label' => 'Pasir ke Terracotta', 'from' => '#EFE3D0', 'mid' => null, 'to' => '#C97B5A', 'angle' => 135],
        ['label' => 'Sage Lembut', 'from' => '#F4F7EC', 'mid' => null, 'to' => '#B9C8A0', 'angle' => 160],
        ['label' => 'Fajar', 'from' => '#FFF4E6', 'mid' => '#FBD9B5', 'to' => '#E9A57F', 'angle' => 120],
        ['label' => 'Krem ke Putih', 'from' => '#F9F7F2', 'mid' => null, 'to' => '#FFFFFF', 'angle' => 180],
        ['label' => 'Rose Kayu', 'from' => '#F3D9D0', 'mid' => null, 'to' => '#C48B7A', 'angle' => 145],
        ['label' => 'Kayu Tua', 'from' => '#5A3D2B', 'mid' => null, 'to' => '#2A1B12', 'angle' => 150],
        ['label' => 'Malam Navy', 'from' => '#2F4257', 'mid' => null, 'to' => '#151D27', 'angle' => 160],
    ];

    /**
     * Rapikan sebuah nilai jadi hex '#RRGGBB' huruf besar, atau null kalau tidak valid.
     */
    public static function hex(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? strtoupper($value) : null;
    }

    /**
     * Ubah data gradasi apa pun (null, sebagian, atau rusak) jadi bentuk
     * lengkap yang selalu valid -- nilai yang hilang/tidak valid diisi
     * dari DEFAULT_GRADIENT.
     *
     * @return array{enabled: bool, from: string, use_mid: bool, mid: string, to: string, angle: int}
     */
    public static function normalize(mixed $gradient): array
    {
        $gradient = is_array($gradient) ? $gradient : [];
        $default = self::DEFAULT_GRADIENT;

        $angle = $gradient['angle'] ?? $default['angle'];
        $angle = is_numeric($angle) ? (int) round((float) $angle) : $default['angle'];

        return [
            'enabled' => filter_var($gradient['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'from' => self::hex($gradient['from'] ?? null) ?? $default['from'],
            'use_mid' => filter_var($gradient['use_mid'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'mid' => self::hex($gradient['mid'] ?? null) ?? $default['mid'],
            'to' => self::hex($gradient['to'] ?? null) ?? $default['to'],
            'angle' => max(0, min(360, $angle)),
        ];
    }

    /**
     * Nilai CSS `linear-gradient(...)` dari data gradasi (dipakai juga untuk pratinjau di admin).
     */
    public static function cssGradient(mixed $gradient): string
    {
        $g = self::normalize($gradient);

        $stops = $g['use_mid']
            ? "{$g['from']} 0%, {$g['mid']} 50%, {$g['to']} 100%"
            : "{$g['from']} 0%, {$g['to']} 100%";

        return "linear-gradient({$g['angle']}deg, {$stops})";
    }

    /**
     * Rata-rata warna sebuah daftar hex -- dipakai sebagai "warna dasar"
     * gradasi untuk menghitung kontras teks & warna turunan di section.
     *
     * @param  array<int, string>  $hexes
     */
    public static function average(array $hexes): string
    {
        $r = $g = $b = 0;

        foreach ($hexes as $hex) {
            $hex = ltrim($hex, '#');
            $r += hexdec(substr($hex, 0, 2));
            $g += hexdec(substr($hex, 2, 2));
            $b += hexdec(substr($hex, 4, 2));
        }

        $n = max(1, count($hexes));

        return sprintf('#%02X%02X%02X', (int) round($r / $n), (int) round($g / $n), (int) round($b / $n));
    }

    /**
     * Tentukan latar akhir sebuah section Beranda.
     *
     * - `css`  : nilai untuk properti `background` (warna polos atau linear-gradient).
     * - `base` : satu warna hex representatif (warna polosnya, atau rata-rata
     *            gradasi) untuk perhitungan kontras teks di section.
     * - `is_gradient` : true kalau gradasi sedang menyala.
     *
     * @param  string|null  $solid  Warna polos pilihan admin (null = pakai $fallback).
     * @param  mixed  $gradient  Data 'bg_gradient' dari home_sections (boleh null).
     * @param  string  $fallback  Warna polos bawaan section.
     * @return array{css: string, base: string, is_gradient: bool}
     */
    public static function resolve(?string $solid, mixed $gradient, string $fallback): array
    {
        $g = self::normalize($gradient);

        if (is_array($gradient) && $g['enabled']) {
            $stops = $g['use_mid'] ? [$g['from'], $g['mid'], $g['to']] : [$g['from'], $g['to']];

            return [
                'css' => self::cssGradient($g),
                'base' => self::average($stops),
                'is_gradient' => true,
            ];
        }

        $color = self::hex($solid) ?? self::hex($fallback) ?? '#FFFFFF';

        return ['css' => $color, 'base' => $color, 'is_gradient' => false];
    }

    /**
     * Apakah gradasi saat ini sama persis dengan salah satu preset rekomendasi
     * (untuk menandai preset yang sedang dipakai di admin).
     *
     * @param  array{label: string, from: string, mid: string|null, to: string, angle: int}  $preset
     */
    public static function matchesPreset(mixed $gradient, array $preset): bool
    {
        $g = self::normalize($gradient);

        return $g['from'] === $preset['from']
            && $g['to'] === $preset['to']
            && $g['angle'] === $preset['angle']
            && $g['use_mid'] === ($preset['mid'] !== null)
            && ($preset['mid'] === null || $g['mid'] === $preset['mid']);
    }
}
