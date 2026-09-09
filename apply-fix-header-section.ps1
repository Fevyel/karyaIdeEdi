# ============================================================
# FIX: "Header" salah kesambung ke Navbar (harusnya section Hero)
#
# Masalah kemarin: AI sebelumnya mengira "Header" = Navbar (bar menu
# paling atas), padahal yang dimaksud adalah section PALING ATAS
# Beranda, TEPAT DI BAWAH navbar (judul besar + foto produk kanan +
# tombol Lihat Katalog/Jelajahi Profil) -- di project ini itu adalah
# partials/frontend/hero.blade.php.
#
# Akibatnya: navbar.blade.php kena edit yang salah sasaran DAN ada 7
# tag <a> yang kepotong (cuma atributnya doang tersisa) sehingga
# navbar tampil sebagai teks mentah, bukan link.
#
# Script ini:
# 1. Mengembalikan navbar.blade.php ke versi git terakhir yang benar
#    (git checkout) -- navbar TIDAK disentuh sama sekali di luar ini.
# 2. Menulis ulang hero.blade.php supaya section Header (warna, teks,
#    statistik, foto) benar-benar bisa diedit dari Admin > Edit Web.
#    Tombol CTA sengaja TETAP hardcode (sesuai aturan: tombol tidak
#    boleh diedit dari admin).
# 3. Menulis ulang edit-web.blade.php: form Header sekarang mengedit
#    Hero (bukan navbar lagi), sekalian ganti kontrol foto pakai
#    cropper drag+zoom yang sama persis seperti Produk/Kategori/
#    Pengaturan (rasio 10:9). Entry sidebar "Hero" yang tadinya
#    duplikat (masih kosong) dihapus, digabung jadi satu "Header".
# 4. Memastikan app/Models/HomeSection.php & migration
#    home_sections ada (kalau kemarin belum ke-commit).
#
# Cara pakai:
# 1. Jalankan dari folder project (C:\xampp\htdocs\karyaIdeEdi)
# 2. .\apply-fix-header-section.ps1
# 3. php artisan migrate   (kalau tabel home_sections belum ada)
# 4. php artisan storage:link   (kalau belum pernah, supaya foto hasil
#    upload di Edit Web bisa tampil -- cek dulu photo lain di produk
#    sudah tampil normal atau belum; kalau sudah berarti symlink-nya
#    sudah ada, lewati langkah ini)
# ============================================================

$ErrorActionPreference = "Stop"

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Fix: Header (Hero) -- bukan Navbar" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    Write-Host "Contoh: C:\xampp\htdocs\karyaIdeEdi" -ForegroundColor Yellow
    exit 1
}

# ---------- 1. Revert navbar ke versi git terakhir ----------
Write-Host "[1/4] Mengembalikan navbar.blade.php ke versi git (menghapus edit yang salah sasaran)..." -ForegroundColor Yellow
try {
    git checkout -- resources/views/partials/frontend/navbar.blade.php
    Write-Host "      OK - navbar.blade.php sudah dikembalikan." -ForegroundColor Green
} catch {
    Write-Host "      [WARNING] Gagal git checkout (bukan repo git / file belum pernah di-commit?). Navbar TIDAK diubah oleh script ini." -ForegroundColor Yellow
}
Write-Host ""

$hero = @'
{{--
    ==========================================================
    HERO SECTION — Homepage Karya Ide Edi
    ==========================================================
    Ini section "Header" yang bisa diedit admin lewat
    Admin > Edit Web > Header (lihat pages/admin/edit-web.blade.php).
    Data tersimpan di tabel home_sections dengan section_key = 'header'
    (App\Models\HomeSection). Yang BISA diedit admin: warna latar,
    warna teks, kata pembuka judul, tagline, deskripsi, 3 statistik,
    dan foto (posisi diatur lewat cropper drag+zoom, sama seperti
    Produk/Kategori/Pengaturan).

    Yang TIDAK bisa diedit dari admin (sengaja hardcode, sesuai aturan
    "tombol tidak boleh diedit"):
    - Label & link tombol CTA ("Lihat Katalog" -> products.index,
      "Jelajahi Profil" -> profile.index).
    - Nama toko (ambil dari App\Models\Setting, diatur lewat menu
      Pengaturan, bukan di sini).

    Pemakaian:
        @include('partials.frontend.hero')
    ==========================================================
--}}
@php
    $heroSetting = \App\Models\Setting::current();

    $headerSection = \App\Models\HomeSection::dataFor('header', [
        'bg_color' => null,
        'text_color' => null,
        'headline_prefix' => 'Furnitur',
        'tagline' => 'Menghidupkan Setiap Sudut',
        'description' => 'Setiap karya dibuat dengan tangan menggunakan material pilihan berkualitas tinggi, dirancang secara teliti dan detail untuk mempercantik interior Anda.',
        'stats' => [
            ['value' => '500+', 'label' => 'Pelanggan Puas'],
            ['value' => '2.000+', 'label' => 'Karya Produk'],
            ['value' => '12th', 'label' => 'Pengalaman'],
        ],
        'image_path' => null,
    ]);

    $heroBgColor = $headerSection['bg_color'] ?: '#F9F7F2';
    $heroHeadingColor = $headerSection['text_color'] ?: '#3D2B1F';
    $heroTextColor = $headerSection['text_color'] ?: '#6B6E76';

    $heroImageUrl = $headerSection['image_path']
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($headerSection['image_path'])
        : asset('images/admin-login/hero.png');
@endphp

<section class="relative overflow-hidden" style="background-color: {{ $heroBgColor }};">
    <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 py-12 sm:px-8 lg:grid-cols-2 lg:gap-8 lg:px-10 lg:py-16">

        {{-- ============ KIRI: Teks, CTA, Statistik ============ --}}
        <div class="animate-fade-in-up">
            <h1 class="font-display text-3xl leading-[1.15] sm:text-4xl lg:text-[2.5rem] xl:text-[2.75rem]" style="color: {{ $heroHeadingColor }};">
                <span class="font-semibold">{{ $headerSection['headline_prefix'] }}</span><br>
                <span class="font-semibold">{{ $heroSetting->site_name }}</span><br>
                <span class="font-normal">{{ $headerSection['tagline'] }}</span>
            </h1>

            <p class="mt-6 max-w-md text-sm leading-relaxed" style="color: {{ $heroTextColor }};">
                {{ $headerSection['description'] }}
            </p>

            {{-- CTA — TIDAK BISA DIEDIT ADMIN, sengaja hardcode. --}}
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <a
                    href="{{ route('products.index') }}"
                    class="group inline-flex items-center gap-2 rounded-lg bg-[#1A1A1A] px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-all duration-300 hover:bg-black hover:shadow-md"
                >
                    Lihat Katalog
                    <x-icon-arrow direction="right" class="transition-transform duration-300 group-hover:translate-x-1" />
                </a>
                <a
                    href="{{ route('profile.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-[#DCDDD7] px-5 py-2.5 text-sm font-medium text-[#3D2B1F] transition-all duration-300 hover:border-admin-accent hover:text-admin-accent"
                >
                    Jelajahi Profil
                </a>
            </div>

            {{-- Statistik --}}
            <dl class="mt-5 flex flex-wrap gap-x-10 gap-y-4 border-t border-[#3D2B1F]/10 pt-4">
                @foreach ($headerSection['stats'] as $stat)
                    <div>
                        <dt class="font-display text-xl sm:text-2xl" style="color: {{ $heroHeadingColor }};">{{ $stat['value'] }}</dt>
                        <dd class="mt-1 text-xs font-medium uppercase tracking-wide text-[#8A8880]">{{ $stat['label'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- ============ KANAN: Foto ============ --}}
        <div class="relative flex items-center justify-center">
            {{--
                Foto Header — diatur admin lewat Edit Web (cropper drag+zoom,
                rasio 10:9, sama seperti Produk/Kategori). Sebelum admin
                pernah mengganti foto, pakai foto bawaan
                public/images/admin-login/hero.png.
            --}}
            <span class="relative flex aspect-10/9 w-full items-center justify-center overflow-hidden rounded-[28px] bg-[#D7A26E] shadow-xl shadow-black/10">
                <img
                    src="{{ $heroImageUrl }}"
                    alt="Furniture {{ $heroSetting->site_name }}"
                    class="h-full w-full object-cover"
                >
            </span>
        </div>
    </div>
</section>

'@

Write-Host "[2/4] Menulis ulang hero.blade.php (section Header yang sekarang bisa diedit admin)..." -ForegroundColor Yellow
Set-Content -Path "resources\views\partials\frontend\hero.blade.php" -Value $hero -Encoding UTF8 -NoNewline
Write-Host "      OK" -ForegroundColor Green
Write-Host ""

$editWeb = @'
<?php

use App\Models\HomeSection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin-panel')] #[Title('Edit Web')] class extends Component
{
    /**
     * Daftar section Beranda yang bisa diedit dari sini.
     *
     * PENTING: Navbar TIDAK ada di daftar ini dan TIDAK PERNAH diedit dari
     * halaman ini. Navbar tampil di semua halaman (bukan cuma Beranda) dan
     * sifatnya sudah final -- tidak boleh diganggu gugat.
     *
     * "Header" di sini = section PALING ATAS Beranda, TEPAT DI BAWAH
     * navbar (judul besar "Furnitur ... Menghidupkan Setiap Sudut" +
     * foto produk kanan). Ini section pertama dari 9 section Beranda,
     * ditandai `@include('partials.frontend.hero')` di home-placeholder.
     *
     * `ready` = false berarti formnya belum dibangun (masih tampil
     * "Segera hadir") -- akan diisi bertahap per section.
     */
    public array $sections = [
        ['key' => 'header', 'label' => 'Header', 'icon' => 'fa-image', 'ready' => true],
        ['key' => 'keunggulan', 'label' => 'Keunggulan', 'icon' => 'fa-star', 'ready' => false],
        ['key' => 'sejak-berdiri', 'label' => 'Sejak Berdiri', 'icon' => 'fa-calendar-day', 'ready' => false],
        ['key' => 'aksi', 'label' => 'Aksi (Video)', 'icon' => 'fa-play', 'ready' => false],
        ['key' => 'produk-unggulan', 'label' => 'Produk Unggulan', 'icon' => 'fa-layer-group', 'ready' => false],
        ['key' => 'kategori', 'label' => 'Kategori Produk', 'icon' => 'fa-th-large', 'ready' => false],
        ['key' => 'testimoni', 'label' => 'Testimoni Pelanggan', 'icon' => 'fa-quote-left', 'ready' => false],
        ['key' => 'keahlian', 'label' => 'Kenapa Pilih Kami', 'icon' => 'fa-award', 'ready' => false],
        ['key' => 'faq', 'label' => 'FAQ', 'icon' => 'fa-circle-question', 'ready' => false],
        ['key' => 'footer', 'label' => 'Footer', 'icon' => 'fa-shoe-prints', 'ready' => false],
    ];

    public string $activeSection = 'header';

    // ================= HEADER (section Hero) =================

    public bool $headerUseCustomBg = false;

    public string $headerBgColor = '#F9F7F2';

    public bool $headerUseCustomText = false;

    public string $headerTextColor = '#3D2B1F';

    public string $headerHeadlinePrefix = 'Furnitur';

    public string $headerTagline = 'Menghidupkan Setiap Sudut';

    public string $headerDescription = '';

    /** @var array<int, array{value: string, label: string}> Selalu tepat 3 item (dt/dd statistik di bawah CTA). */
    public array $headerStats = [];

    /** Hasil crop (drag + zoom custom, sama persis seperti logoCropper/thumbnailCropper/categoryCoverCropper), dikirim sebagai data URL base64 (JPEG). Null = gambar tidak diganti. */
    public ?string $headerImageCroppedBase64 = null;

    public ?string $headerImagePathLama = null;

    private function headerDefaults(): array
    {
        return [
            'bg_color' => null,
            'text_color' => null,
            'headline_prefix' => 'Furnitur',
            'tagline' => 'Menghidupkan Setiap Sudut',
            'description' => 'Setiap karya dibuat dengan tangan menggunakan material pilihan berkualitas tinggi, dirancang secara teliti dan detail untuk mempercantik interior Anda.',
            'stats' => [
                ['value' => '500+', 'label' => 'Pelanggan Puas'],
                ['value' => '2.000+', 'label' => 'Karya Produk'],
                ['value' => '12th', 'label' => 'Pengalaman'],
            ],
            'image_path' => null,
        ];
    }

    public function mount(): void
    {
        $defaults = $this->headerDefaults();
        $data = HomeSection::dataFor('header', $defaults);

        $this->headerUseCustomBg = filled($data['bg_color']);
        $this->headerBgColor = $data['bg_color'] ?: $this->headerBgColor;

        $this->headerUseCustomText = filled($data['text_color']);
        $this->headerTextColor = $data['text_color'] ?: $this->headerTextColor;

        $this->headerHeadlinePrefix = $data['headline_prefix'];
        $this->headerTagline = $data['tagline'];
        $this->headerDescription = $data['description'];
        $this->headerStats = $data['stats'];
        $this->headerImagePathLama = $data['image_path'];
    }

    public function selectSection(string $key): void
    {
        $this->activeSection = $key;
    }

    /**
     * Prioritas: hasil crop baru (base64, siap dipakai langsung sebagai src) ->
     * foto lama yang tersimpan di storage -> foto bawaan hero.
     */
    public function getHeaderImagePreviewUrlProperty(): string
    {
        if ($this->headerImageCroppedBase64) {
            return $this->headerImageCroppedBase64;
        }

        return $this->headerImagePathLama
            ? Storage::disk('public')->url($this->headerImagePathLama)
            : asset('images/admin-login/hero.png');
    }

    /**
     * Decode data URL base64 hasil crop jadi binary gambar.
     * Return null kalau formatnya tidak valid (bukan gambar / bukan data URL yang benar).
     */
    private function decodeBase64Image(string $dataUrl): ?string
    {
        if (! preg_match('/^data:image\/(jpeg|jpg|png);base64,/', $dataUrl)) {
            return null;
        }

        $binary = base64_decode(preg_replace('/^data:image\/(jpeg|jpg|png);base64,/', '', $dataUrl));

        if ($binary === false || @getimagesizefromstring($binary) === false) {
            return null;
        }

        return $binary;
    }

    public function saveHeader(): void
    {
        $validated = $this->validate([
            'headerBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'headerTextColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'headerHeadlinePrefix' => ['required', 'string', 'max:40'],
            'headerTagline' => ['required', 'string', 'max:60'],
            'headerDescription' => ['required', 'string', 'max:400'],
            'headerStats' => ['required', 'array', 'size:3'],
            'headerStats.*.value' => ['required', 'string', 'max:12'],
            'headerStats.*.label' => ['required', 'string', 'max:30'],
            'headerImageCroppedBase64' => ['nullable', 'string'],
        ], [
            'headerHeadlinePrefix.required' => 'Kata pembuka judul wajib diisi.',
            'headerTagline.required' => 'Tagline wajib diisi.',
            'headerDescription.required' => 'Deskripsi wajib diisi.',
        ]);

        $imagePath = $this->headerImagePathLama;

        if ($this->headerImageCroppedBase64) {
            $binary = $this->decodeBase64Image($this->headerImageCroppedBase64);

            if ($binary !== null) {
                if ($this->headerImagePathLama) {
                    Storage::disk('public')->delete($this->headerImagePathLama);
                }

                $imagePath = 'home-sections/header-'.Str::uuid().'.jpg';
                Storage::disk('public')->put($imagePath, $binary);
                $this->headerImagePathLama = $imagePath;
                $this->headerImageCroppedBase64 = null;
            }
        }

        HomeSection::forSection('header')->update([
            'data' => [
                'bg_color' => $this->headerUseCustomBg ? $validated['headerBgColor'] : null,
                'text_color' => $this->headerUseCustomText ? $validated['headerTextColor'] : null,
                'headline_prefix' => $validated['headerHeadlinePrefix'],
                'tagline' => $validated['headerTagline'],
                'description' => $validated['headerDescription'],
                'stats' => $validated['headerStats'],
                'image_path' => $imagePath,
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }
};
?>

<div class="mx-auto max-w-6xl space-y-6" x-data="heroImageCropper(@js($this->headerImagePreviewUrl))">

    <div>
        <h2 class="font-display text-xl font-semibold text-admin-ink sm:text-2xl">
            Edit Web
        </h2>
        <p class="mt-1 text-sm text-admin-ink-soft">
            Kelola konten yang tampil di halaman Beranda, per bagian (section). Navbar tidak termasuk di sini --
            tampilannya sudah final dan sama di semua halaman.
        </p>
    </div>

    @if (session('edit-web-tersimpan'))
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 3000)"
            x-show="show"
            x-transition
            class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
        >
            <i class="fa-solid fa-circle-check"></i>
            Perubahan tersimpan.
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[260px_1fr]">

        {{-- ================= DAFTAR SECTION ================= --}}
        <div class="flex gap-2 overflow-x-auto rounded-2xl border border-admin-border bg-admin-surface p-2 lg:flex-col lg:overflow-visible">
            @foreach ($sections as $section)
                <button
                    type="button"
                    wire:click="selectSection('{{ $section['key'] }}')"
                    @if(! $section['ready']) disabled @endif
                    class="flex shrink-0 items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-left text-sm font-medium transition
                    {{ $activeSection === $section['key']
                        ? 'bg-admin-panel text-white'
                        : ($section['ready']
                            ? 'text-admin-ink hover:bg-admin-cream'
                            : 'cursor-not-allowed text-admin-ink-soft/60') }}"
                >
                    <i class="fa-solid {{ $section['icon'] }} w-4 text-center text-xs"></i>
                    <span class="whitespace-nowrap">{{ $section['label'] }}</span>
                    @unless ($section['ready'])
                        <span class="ml-auto rounded-full bg-admin-cream px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-admin-ink-soft">
                            Segera
                        </span>
                    @endunless
                </button>
            @endforeach
        </div>

        {{-- ================= PANEL SECTION AKTIF ================= --}}
        <div class="rounded-2xl border border-admin-border bg-admin-surface p-5 shadow-sm sm:p-6">

            @if ($activeSection === 'header')
                <form wire:submit="saveHeader" class="space-y-6">

                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-image text-admin-accent"></i>
                            Header (section paling atas Beranda)
                        </h3>
                        <p class="text-xs text-admin-ink-soft">
                            Ini section tepat di bawah navbar -- judul besar, deskripsi, foto, dan statistik.
                            Navbar (bar menu paling atas) tidak diedit di sini. Tombol "Lihat Katalog" &amp;
                            "Jelajahi Profil" juga tidak bisa diubah dari sini (link &amp; tulisannya tetap).
                        </p>
                    </div>

                    {{-- FOTO --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Foto</p>

                        <div class="flex flex-col items-center gap-5 sm:flex-row">
                            <div class="relative w-32 shrink-0">
                                <img :src="previewUrl" alt="Preview foto header" class="aspect-10/9 w-32 rounded-2xl object-cover ring-4 ring-admin-cream">

                                <label
                                    for="header_image_input"
                                    class="absolute -bottom-1 -right-1 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-admin-accent text-white shadow-sm ring-2 ring-admin-surface transition hover:bg-admin-accent-strong"
                                    title="Ganti foto"
                                >
                                    <i class="fa-solid fa-camera text-xs"></i>
                                </label>
                                <input x-ref="fileInput" id="header_image_input" type="file" accept="image/*" class="hidden" x-on:change="onFileChange($event)">
                            </div>

                            <div class="text-center sm:text-left">
                                <p class="text-sm font-medium text-admin-ink">Klik ikon kamera untuk ganti foto</p>
                                <p class="mt-1 text-xs text-admin-ink-soft">
                                    Setelah pilih foto, geser untuk memindah posisi &amp; pakai slider untuk zoom --
                                    sama seperti mengatur foto Produk atau Kategori.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- WARNA --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna</p>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="headerUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna latar khusus
                            </label>
                            <input
                                type="color" wire:model="headerBgColor"
                                @if(! $headerUseCustomBg) disabled @endif
                                class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:cursor-not-allowed disabled:opacity-40"
                            >
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="headerUseCustomText" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna teks khusus
                            </label>
                            <input
                                type="color" wire:model="headerTextColor"
                                @if(! $headerUseCustomText) disabled @endif
                                class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:cursor-not-allowed disabled:opacity-40"
                            >
                        </div>

                        <p class="text-xs text-admin-ink-soft">
                            Kalau tidak dicentang, section ini ikut warna tema default (bukan warna admin panel).
                            Warna teks berlaku untuk judul, deskripsi, dan angka statistik -- bukan warna tombol.
                        </p>
                    </div>

                    {{-- ISI TEKS --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Isi Teks</p>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Kata pembuka judul</label>
                                <input
                                    type="text" maxlength="40" wire:model="headerHeadlinePrefix"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                <p class="mt-1 text-[11px] text-admin-ink-soft">Nama toko ambil otomatis dari Pengaturan, tidak diketik di sini.</p>
                                @error('headerHeadlinePrefix')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Tagline (baris ketiga judul)</label>
                                <input
                                    type="text" maxlength="60" wire:model="headerTagline"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('headerTagline')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Deskripsi</label>
                                <textarea
                                    rows="3" maxlength="400" wire:model="headerDescription"
                                    class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                ></textarea>
                                @error('headerDescription')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <p class="mb-2 text-sm font-medium text-admin-ink">Statistik (3 angka di bawah tombol)</p>
                            <div class="grid gap-3 sm:grid-cols-3">
                                @foreach ($headerStats as $i => $stat)
                                    <div class="space-y-2 rounded-lg border border-admin-border p-3">
                                        <input
                                            type="text" maxlength="12" placeholder="500+"
                                            wire:model="headerStats.{{ $i }}.value"
                                            class="w-full rounded-lg border border-admin-border bg-admin-surface px-2.5 py-2 text-sm font-semibold text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                        >
                                        <input
                                            type="text" maxlength="30" placeholder="Pelanggan Puas"
                                            wire:model="headerStats.{{ $i }}.label"
                                            class="w-full rounded-lg border border-admin-border bg-admin-surface px-2.5 py-2 text-xs text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                        >
                                        @error("headerStats.{$i}.value")<p class="text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                                        @error("headerStats.{$i}.label")<p class="text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            wire:loading.attr="disabled" wire:target="saveHeader"
                            class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition-all duration-200 hover:bg-admin-accent-strong active:scale-[0.99] disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="saveHeader" class="flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan
                            </span>
                            <span wire:loading wire:target="saveHeader" class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch animate-spin"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            @else
                <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-admin-border px-5 py-16 text-center">
                    <i class="fa-solid fa-pen-to-square mb-3 text-2xl text-admin-ink-soft"></i>
                    <p class="text-sm font-medium text-admin-ink">
                        Bagian ini masih dalam pengembangan.
                    </p>
                    <p class="mt-1 text-xs text-admin-ink-soft">
                        Akan ditambahkan bertahap, sama seperti Header.
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- ================= MODAL CROP FOTO HEADER ================= --}}
    {{--
        x-teleport memindahkan modal ini jadi anak langsung <body> saat dirender.
        Sama persis seperti logoCropper (Pengaturan), thumbnailCropper (Produk)
        & categoryCoverCropper (Kategori), supaya "fixed inset-0" benar-benar
        relatif ke viewport.
    --}}
    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-999 flex items-center justify-center bg-black/60 p-4"
            style="display: none;"
        >
            <div
                x-show="open"
                x-transition.scale.origin.center
                @click.outside="cancelCrop()"
                class="w-full max-w-md rounded-2xl bg-admin-surface p-6 shadow-2xl"
            >
                <h4 class="mb-1 text-sm font-semibold text-admin-ink">Sesuaikan Foto Header</h4>
                <p class="mb-4 text-xs text-admin-ink-soft">Geser gambar untuk memindah, gunakan slider untuk zoom. Hasil crop mengikuti proporsi foto di section Header (10:9).</p>

                <div
                    x-ref="viewport"
                    class="relative mx-auto aspect-10/9 w-full max-w-80 cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none"
                    x-on:pointerdown="startDrag($event)"
                    x-on:pointermove="onDrag($event)"
                    x-on:pointerup="endDrag()"
                    x-on:pointerleave="endDrag()"
                >
                    <img
                        x-ref="cropImg"
                        :src="rawImage"
                        x-on:load="onImgLoad($event)"
                        draggable="false"
                        class="absolute left-0 top-0 max-w-none origin-top-left select-none"
                        :style="`width:${natW * scale}px; height:${natH * scale}px; transform: translate(${posX}px, ${posY}px);`"
                    >
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <i class="fa-solid fa-magnifying-glass-minus text-xs text-admin-ink-soft"></i>
                    <input
                        type="range" min="0" max="100" x-model.number="zoomPercent"
                        x-on:input="applyZoom()"
                        class="h-1.5 w-full cursor-pointer appearance-none rounded-full bg-admin-border accent-admin-accent"
                    >
                    <i class="fa-solid fa-magnifying-glass-plus text-xs text-admin-ink-soft"></i>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" x-on:click="cancelCrop()" class="rounded-full border border-admin-border px-4 py-2 text-xs font-semibold text-admin-ink-soft transition hover:bg-admin-cream">
                        Batal
                    </button>
                    <button type="button" x-on:click="confirmCrop()" class="rounded-full bg-admin-accent px-4 py-2 text-xs font-semibold text-white transition hover:bg-admin-accent-strong">
                        Gunakan Foto Ini
                    </button>
                </div>

                <canvas x-ref="cropCanvas" class="hidden"></canvas>
            </div>
        </div>
    </template>
</div>

@script
<script>
    // Sama persis strukturnya dengan categoryCoverCropper (kategori-form)
    // & thumbnailCropper (produk-form) -- cuma nama & rasio yang beda
    // (10:9, mengikuti frame foto di partials/frontend/hero.blade.php).
    Alpine.data('heroImageCropper', (existingPreviewUrl) => ({
        open: false,
        rawImage: null,
        previewUrl: existingPreviewUrl || null,
        natW: 0,
        natH: 0,
        scale: 1,
        minScale: 1,
        maxScale: 1,
        zoomPercent: 0,
        posX: 0,
        posY: 0,
        dragging: false,
        dragStartX: 0,
        dragStartY: 0,
        startPosX: 0,
        startPosY: 0,

        ASPECT_W: 10,
        ASPECT_H: 9,

        viewW: 0,
        viewH: 0,

        OUT_W: 1000,
        OUT_H: 900,

        init() {
            this.$watch('open', (isOpen) => {
                document.body.style.overflow = isOpen ? 'hidden' : '';
            });

            window.addEventListener('resize', () => {
                if (!this.open || !this.$refs.viewport || this.natW === 0) return;

                const rect = this.$refs.viewport.getBoundingClientRect();
                this.viewW = rect.width;
                this.viewH = rect.height;
                this.clampPos();
            });
        },

        onFileChange(e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = () => {
                this.rawImage = reader.result;
                this.open = true;
            };
            reader.readAsDataURL(file);
        },

        onImgLoad(e) {
            this.natW = e.target.naturalWidth;
            this.natH = e.target.naturalHeight;

            this.$nextTick(() => {
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.viewW = rect.width;
                this.viewH = rect.height;

                this.minScale = Math.max(this.viewW / this.natW, this.viewH / this.natH);
                this.maxScale = this.minScale * 3;
                this.scale = this.minScale;
                this.zoomPercent = 0;

                this.posX = (this.viewW - this.natW * this.scale) / 2;
                this.posY = (this.viewH - this.natH * this.scale) / 2;
            });
        },

        clampPos() {
            const w = this.natW * this.scale;
            const h = this.natH * this.scale;
            this.posX = Math.min(0, Math.max(this.viewW - w, this.posX));
            this.posY = Math.min(0, Math.max(this.viewH - h, this.posY));
        },

        applyZoom() {
            this.scale = this.minScale + (this.maxScale - this.minScale) * (this.zoomPercent / 100);
            this.clampPos();
        },

        startDrag(e) {
            this.dragging = true;
            this.dragStartX = e.clientX;
            this.dragStartY = e.clientY;
            this.startPosX = this.posX;
            this.startPosY = this.posY;
        },

        onDrag(e) {
            if (!this.dragging) return;
            this.posX = this.startPosX + (e.clientX - this.dragStartX);
            this.posY = this.startPosY + (e.clientY - this.dragStartY);
            this.clampPos();
        },

        endDrag() {
            this.dragging = false;
        },

        confirmCrop() {
            const canvas = this.$refs.cropCanvas;
            canvas.width = this.OUT_W;
            canvas.height = this.OUT_H;
            const ctx = canvas.getContext('2d');

            const sx = -this.posX / this.scale;
            const sy = -this.posY / this.scale;
            const sWidth = this.viewW / this.scale;
            const sHeight = this.viewH / this.scale;

            ctx.clearRect(0, 0, this.OUT_W, this.OUT_H);
            ctx.drawImage(this.$refs.cropImg, sx, sy, sWidth, sHeight, 0, 0, this.OUT_W, this.OUT_H);

            const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
            this.previewUrl = dataUrl;
            this.$wire.set('headerImageCroppedBase64', dataUrl);
            this.closeModal();
        },

        cancelCrop() {
            this.closeModal();
        },

        closeModal() {
            this.open = false;
            this.rawImage = null;
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        },
    }));
</script>
@endscript

'@

Write-Host "[3/4] Menulis ulang edit-web.blade.php (form Header -> Hero, kontrol foto pakai cropper)..." -ForegroundColor Yellow
Set-Content -Path "resources\views\pages\admin\edit-web.blade.php" -Value $editWeb -Encoding UTF8 -NoNewline
Write-Host "      OK" -ForegroundColor Green
Write-Host ""

$homeSectionModel = @'
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

'@
$migration = @'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('section_key')->unique();
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_sections');
    }
};

'@

Write-Host "[4/4] Memastikan HomeSection model & migration ada..." -ForegroundColor Yellow
if (-not (Test-Path "app\Models\HomeSection.php")) {
    Set-Content -Path "app\Models\HomeSection.php" -Value $homeSectionModel -Encoding UTF8 -NoNewline
    Write-Host "      OK - app/Models/HomeSection.php dibuat." -ForegroundColor Green
} else {
    Write-Host "      Sudah ada, dilewati." -ForegroundColor Gray
}

$migrationPath = "database\migrations\2026_08_31_090000_create_home_sections_table.php"
if (-not (Test-Path $migrationPath)) {
    Set-Content -Path $migrationPath -Value $migration -Encoding UTF8 -NoNewline
    Write-Host "      OK - migration home_sections dibuat." -ForegroundColor Green
} else {
    Write-Host "      Sudah ada, dilewati." -ForegroundColor Gray
}
Write-Host ""

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Selesai." -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host "Langkah selanjutnya:" -ForegroundColor White
Write-Host "  1. php artisan migrate        (kalau tabel home_sections belum pernah dibuat)" -ForegroundColor White
Write-Host "  2. php artisan serve          (kalau belum jalan)" -ForegroundColor White
Write-Host "  3. Buka /adminmode -> Edit Web -> Header, coba ganti teks/warna/foto." -ForegroundColor White
Write-Host "  4. Cek Beranda: navbar harus normal lagi, section di bawahnya ikut perubahan Header." -ForegroundColor White
