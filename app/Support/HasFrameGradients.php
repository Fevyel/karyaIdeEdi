<?php

namespace App\Support;

/**
 * State & aksi gradasi "Warna Frame" untuk halaman Admin > Edit Web
 * (pages/admin/edit-web.blade.php). Satu array per section, bentuknya sama
 * dengan App\Support\FrameBackground::DEFAULT_GRADIENT.
 *
 * Sisi tampilan beranda memakai App\Support\FrameBackground::resolve().
 */
trait HasFrameGradients
{
    public array $missionGradient = FrameBackground::DEFAULT_GRADIENT;

    public array $produkUnggulanGradient = FrameBackground::DEFAULT_GRADIENT;

    public array $kategoriGradient = FrameBackground::DEFAULT_GRADIENT;

    public array $testimoniGradient = FrameBackground::DEFAULT_GRADIENT;

    public array $lokasiGradient = FrameBackground::DEFAULT_GRADIENT;

    public array $sejarahGradient = FrameBackground::DEFAULT_GRADIENT;

    public array $tentangKami2Gradient = FrameBackground::DEFAULT_GRADIENT;

    public array $nilaiKamiGradient = FrameBackground::DEFAULT_GRADIENT;

    /**
     * Isi state gradasi sebuah section dari data yang tersimpan (dipanggil di mount()).
     */
    protected function loadFrameGradient(string $section, mixed $stored): void
    {
        $this->{$this->frameGradientProperty($section)} = FrameBackground::normalize($stored);
    }

    /**
     * Tombol nyala/mati gradasi.
     */
    public function toggleFrameGradient(string $section): void
    {
        $property = $this->frameGradientProperty($section);

        $gradient = FrameBackground::normalize($this->{$property});
        $gradient['enabled'] = ! $gradient['enabled'];

        $this->{$property} = $gradient;
    }

    /**
     * Klik salah satu gradasi rekomendasi -- terisi otomatis dan gradasi menyala.
     */
    public function applyFrameGradientPreset(string $section, int $index): void
    {
        $property = $this->frameGradientProperty($section);
        $preset = FrameBackground::PRESETS[$index] ?? null;

        if ($preset === null) {
            return;
        }

        $gradient = FrameBackground::normalize($this->{$property});

        $this->{$property} = array_merge($gradient, [
            'enabled' => true,
            'from' => $preset['from'],
            'use_mid' => $preset['mid'] !== null,
            'mid' => $preset['mid'] ?? $gradient['mid'],
            'to' => $preset['to'],
            'angle' => $preset['angle'],
        ]);
    }

    /**
     * Tukar warna awal dengan warna akhir.
     */
    public function swapFrameGradientColors(string $section): void
    {
        $property = $this->frameGradientProperty($section);

        $gradient = FrameBackground::normalize($this->{$property});
        [$gradient['from'], $gradient['to']] = [$gradient['to'], $gradient['from']];

        $this->{$property} = $gradient;
    }

    /**
     * Klik salah satu tombol arah (0 = ke atas, 90 = ke kanan, 180 = ke bawah, 270 = ke kiri).
     */
    public function setFrameGradientAngle(string $section, int $angle): void
    {
        $property = $this->frameGradientProperty($section);

        $gradient = FrameBackground::normalize($this->{$property});
        $gradient['angle'] = max(0, min(360, $angle));

        $this->{$property} = $gradient;
    }

    /**
     * Aturan validasi gradasi sebuah section -- disatukan ke validate() di method save section itu.
     *
     * @return array<string, array<int, string>>
     */
    protected function frameGradientRules(string $section): array
    {
        $property = $this->frameGradientProperty($section);
        $hex = ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        return [
            "{$property}.enabled" => ['boolean'],
            "{$property}.from" => $hex,
            "{$property}.use_mid" => ['boolean'],
            "{$property}.mid" => $hex,
            "{$property}.to" => $hex,
            "{$property}.angle" => ['required', 'integer', 'between:0,360'],
        ];
    }

    /**
     * Data gradasi yang siap disimpan ke home_sections.data['bg_gradient'].
     *
     * @return array{enabled: bool, from: string, use_mid: bool, mid: string, to: string, angle: int}
     */
    protected function frameGradientPayload(string $section): array
    {
        return FrameBackground::normalize($this->{$this->frameGradientProperty($section)});
    }

    /**
     * Nama property untuk sebuah section -- sekaligus daftar putih supaya
     * method publik di atas tidak bisa dipakai menyentuh property lain.
     */
    private function frameGradientProperty(string $section): string
    {
        abort_unless(
            in_array($section, ['mission', 'produkUnggulan', 'kategori', 'testimoni', 'lokasi', 'sejarah', 'tentangKami2', 'nilaiKami'], true),
            404,
        );

        return $section.'Gradient';
    }
}
