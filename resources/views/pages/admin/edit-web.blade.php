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
        ['key' => 'keunggulan', 'label' => 'Keunggulan', 'icon' => 'fa-star', 'ready' => true],
        ['key' => 'sejak-berdiri', 'label' => 'Sejak Berdiri', 'icon' => 'fa-calendar-day', 'ready' => true],
        ['key' => 'produk-unggulan', 'label' => 'Produk Unggulan', 'icon' => 'fa-layer-group', 'ready' => true],
        ['key' => 'kategori', 'label' => 'Kategori Produk', 'icon' => 'fa-th-large', 'ready' => true],
        ['key' => 'testimoni', 'label' => 'Testimoni Pelanggan', 'icon' => 'fa-quote-left', 'ready' => true],
        ['key' => 'keahlian', 'label' => 'Kenapa Pilih Kami', 'icon' => 'fa-award', 'ready' => true],
        ['key' => 'faq', 'label' => 'FAQ', 'icon' => 'fa-circle-question', 'ready' => false],
        ['key' => 'footer', 'label' => 'Footer', 'icon' => 'fa-shoe-prints', 'ready' => false],
    ];

    public string $activeSection = 'header';

    // ================= HEADER (section Hero) =================

    public bool $headerUseCustomBg = false;

    public string $headerBgColor = '#F9F7F2';

    public string $headerHeadlinePrefix = 'Furnitur';

    public string $headerTagline = 'Menghidupkan Setiap Sudut';

    public string $headerDescription = '';

    /** @var array<int, array{value: string, label: string}> Selalu tepat 3 item (dt/dd statistik di bawah CTA). */
    public array $headerStats = [];

    /** Hasil crop (drag + zoom custom, sama persis seperti logoCropper/thumbnailCropper/categoryCoverCropper), dikirim sebagai data URL base64 (JPEG). Null = gambar tidak diganti. */
    public ?string $headerImageCroppedBase64 = null;

    public ?string $headerImagePathLama = null;

    /**
     * Palet warna latar yang direkomendasikan -- dipilih tangan supaya
     * hasilnya konsisten dengan gaya toko (bukan random) dan kontrasnya
     * sudah pasti aman lewat contrastTextColors() di hero.blade.php.
     * Admin tetap bisa pakai color picker di sampingnya untuk warna bebas.
     *
     * @var array<int, array{label: string, value: string}>
     */
    public array $headerBgPresets = [
        ['label' => 'Krem Hangat (Default)', 'value' => '#F9F7F2', 'check' => '#3D2B1F'],
        ['label' => 'Putih Gading', 'value' => '#FFFDF8', 'check' => '#3D2B1F'],
        ['label' => 'Beige Pasir', 'value' => '#EFE3D0', 'check' => '#3D2B1F'],
        ['label' => 'Sage Hijau Lembut', 'value' => '#DCE3CE', 'check' => '#3D2B1F'],
        ['label' => 'Terracotta', 'value' => '#C97B5A', 'check' => '#FFFFFF'],
        ['label' => 'Coklat Kayu Tua', 'value' => '#3D2B1F', 'check' => '#FFFFFF'],
        ['label' => 'Navy Malam', 'value' => '#22303F', 'check' => '#FFFFFF'],
        ['label' => 'Hitam Elegan', 'value' => '#1A1A1A', 'check' => '#FFFFFF'],
    ];

    // ================= KEUNGGULAN (section Features) =================

    /**
     * Pilihan ikon yang boleh dipakai admin -- dibatasi (bukan input bebas)
     * supaya nama class FontAwesome yang tersimpan selalu valid & konsisten
     * dengan gaya toko, sama semangatnya dengan $headerBgPresets di atas.
     *
     * @var array<int, string>
     */
    public array $keunggulanIconOptions = [
        'fa-truck-fast', 'fa-comment-dots', 'fa-headset', 'fa-shield-halved',
        'fa-gem', 'fa-leaf', 'fa-hand-holding-heart', 'fa-screwdriver-wrench',
        'fa-star', 'fa-thumbs-up', 'fa-box-open', 'fa-clock',
    ];

    /** @var array<int, array{icon: string, title: string, desc: string}> Selalu tepat 3 item (kolom di bawah Header). */
    public array $keunggulanItems = [];

    private function keunggulanDefaults(): array
    {
        return [
            'items' => [
                ['icon' => 'fa-truck-fast', 'title' => 'Custom Sesuai Pesanan', 'desc' => 'Bebas request sesuai kebutuhan Anda'],
                ['icon' => 'fa-comment-dots', 'title' => 'Konsultasi via WhatsApp', 'desc' => 'Tanya produk, harga, dan booking langsung'],
                ['icon' => 'fa-headset', 'title' => 'Pembayaran Fleksibel', 'desc' => 'Berbagai pilihan pembayaran yang aman'],
            ],
        ];
    }

    // ================= SEJAK BERDIRI (section Mission) =================

    public bool $missionUseCustomBg = false;

    public string $missionBgColor = '#FEEDD8';

    public string $missionTitle = '';

    public string $missionDescription = '';

    /** @var array<int, array{icon: string, title: string, desc: string}> Selalu tepat 3 item (poin unggulan). */
    public array $missionPoints = [];

    public string $missionStatValue = '';

    public string $missionStatLabel = '';

    /** Hasil crop foto besar (kolase kiri, rasio 3:4), dikirim sebagai data URL base64. Null = gambar tidak diganti. */
    public ?string $missionImageBesarCroppedBase64 = null;

    public ?string $missionImageBesarPathLama = null;

    /** Hasil crop foto kecil (kolase kanan atas, rasio 1:1), dikirim sebagai data URL base64. Null = gambar tidak diganti. */
    public ?string $missionImageKecilCroppedBase64 = null;

    public ?string $missionImageKecilPathLama = null;

    /** Hasil crop foto latar kartu angka penghargaan (rasio 1:1), OPSIONAL -- null berarti kartu tetap pakai warna solid. */
    public ?string $missionStatBgCroppedBase64 = null;

    public ?string $missionStatBgPathLama = null;

    /**
     * Palet warna latar rekomendasi untuk section ini -- sama semangatnya
     * dengan $headerBgPresets, cuma preset pertama disesuaikan jadi warna
     * bawaan section ini (krem persik #FEEDD8), bukan krem hangat Header.
     *
     * @var array<int, array{label: string, value: string}>
     */
    public array $missionBgPresets = [
        ['label' => 'Krem Persik (Default)', 'value' => '#FEEDD8', 'check' => '#4B3A26'],
        ['label' => 'Putih Gading', 'value' => '#FFFDF8', 'check' => '#3D2B1F'],
        ['label' => 'Beige Pasir', 'value' => '#EFE3D0', 'check' => '#3D2B1F'],
        ['label' => 'Sage Hijau Lembut', 'value' => '#DCE3CE', 'check' => '#3D2B1F'],
        ['label' => 'Terracotta', 'value' => '#C97B5A', 'check' => '#FFFFFF'],
        ['label' => 'Coklat Kayu Tua', 'value' => '#3D2B1F', 'check' => '#FFFFFF'],
        ['label' => 'Navy Malam', 'value' => '#22303F', 'check' => '#FFFFFF'],
        ['label' => 'Hitam Elegan', 'value' => '#1A1A1A', 'check' => '#FFFFFF'],
    ];

    /**
     * Pilihan ikon poin unggulan -- sama pola pembatasannya dengan
     * $keunggulanIconOptions.
     *
     * @var array<int, string>
     */
    public array $missionIconOptions = [
        'fa-leaf', 'fa-hammer', 'fa-rotate-left', 'fa-tree',
        'fa-award', 'fa-shield-halved', 'fa-gem', 'fa-hand-holding-heart',
        'fa-recycle', 'fa-screwdriver-wrench', 'fa-clock', 'fa-star',
    ];

    private function missionDefaults(): array
    {
        return [
            'bg_color' => null,
            'title' => 'Setiap Furnitur Memiliki Cerita di Baliknya',
            'description' => 'Berawal dari tangan seorang pengrajin yang mencintai kayu, Karya Ide Edi lahir untuk menghadirkan furnitur berkualitas yang dibuat dengan teliti, tanpa mengorbankan kualitas maupun kelestarian bahan. Kini, misi kami adalah membantu Anda menciptakan ruang yang mencerminkan kepribadian Anda, dengan kenyamanan yang bertahan bertahun-tahun.',
            'points' => [
                ['icon' => 'fa-leaf', 'title' => 'Bahan Baku Berkelanjutan', 'desc' => 'Setiap kayu bersertifikat dari hutan yang dikelola secara bertanggung jawab.'],
                ['icon' => 'fa-hammer', 'title' => 'Pengrajin Ahli', 'desc' => 'Detail akhir dikerjakan tangan oleh pengrajin berpengalaman puluhan tahun.'],
                ['icon' => 'fa-rotate-left', 'title' => 'Garansi Retur 30 Hari', 'desc' => 'Tidak sesuai ekspektasi? Kami jemput dan proses pengembalian tanpa ribet.'],
            ],
            'stat_value' => '15 thn',
            'stat_label' => 'Penghargaan Karya Terbaik',
            'image_path_besar' => null,
            'image_path_kecil' => null,
            'stat_bg_image' => null,
        ];
    }

    // ================= PRODUK UNGGULAN (section Products) =================

    /**
     * Section ini isinya produk asli dari database (lihat
     * partials/frontend/products.blade.php) -- bukan teks/foto statis,
     * jadi cuma warna frame (latar) yang bisa diedit di sini, beda dengan
     * Header/Sejak Berdiri yang juga punya teks & foto.
     */
    public bool $produkUnggulanUseCustomBg = false;

    public string $produkUnggulanBgColor = '#FFFFFF';

    /**
     * Palet warna latar rekomendasi untuk section ini -- sama pola dengan
     * $headerBgPresets & $missionBgPresets, preset pertama disesuaikan
     * jadi putih (warna bawaan section ini).
     *
     * @var array<int, array{label: string, value: string}>
     */
    public array $produkUnggulanBgPresets = [
        ['label' => 'Putih (Default)', 'value' => '#FFFFFF', 'check' => '#3D2B1F'],
        ['label' => 'Krem Hangat', 'value' => '#F9F7F2', 'check' => '#3D2B1F'],
        ['label' => 'Beige Pasir', 'value' => '#EFE3D0', 'check' => '#3D2B1F'],
        ['label' => 'Sage Hijau Lembut', 'value' => '#DCE3CE', 'check' => '#3D2B1F'],
        ['label' => 'Terracotta', 'value' => '#C97B5A', 'check' => '#FFFFFF'],
        ['label' => 'Coklat Kayu Tua', 'value' => '#3D2B1F', 'check' => '#FFFFFF'],
        ['label' => 'Navy Malam', 'value' => '#22303F', 'check' => '#FFFFFF'],
        ['label' => 'Hitam Elegan', 'value' => '#1A1A1A', 'check' => '#FFFFFF'],
    ];

    // ================= KATEGORI PRODUK (section Categories) =================

    /**
     * Sama pola dengan Produk Unggulan: isinya kategori asli dari database
     * (lihat partials/frontend/categories.blade.php) -- bukan teks/foto
     * statis, jadi cuma warna frame (latar) section-nya yang bisa diedit
     * di sini.
     */
    public bool $kategoriUseCustomBg = false;

    public string $kategoriBgColor = '#FEEDD8';

    /**
     * Palet warna latar rekomendasi untuk section ini -- sama pola dengan
     * $headerBgPresets, $missionBgPresets & $produkUnggulanBgPresets,
     * preset pertama disesuaikan jadi peach lembut (warna bawaan section
     * ini sebelum bisa diedit).
     *
     * @var array<int, array{label: string, value: string}>
     */
    public array $kategoriBgPresets = [
        ['label' => 'Peach Lembut (Default)', 'value' => '#FEEDD8', 'check' => '#3D2B1F'],
        ['label' => 'Krem Hangat', 'value' => '#F9F7F2', 'check' => '#3D2B1F'],
        ['label' => 'Putih', 'value' => '#FFFFFF', 'check' => '#3D2B1F'],
        ['label' => 'Beige Pasir', 'value' => '#EFE3D0', 'check' => '#3D2B1F'],
        ['label' => 'Sage Hijau Lembut', 'value' => '#DCE3CE', 'check' => '#3D2B1F'],
        ['label' => 'Terracotta', 'value' => '#C97B5A', 'check' => '#FFFFFF'],
        ['label' => 'Coklat Kayu Tua', 'value' => '#3D2B1F', 'check' => '#FFFFFF'],
        ['label' => 'Navy Malam', 'value' => '#22303F', 'check' => '#FFFFFF'],
        ['label' => 'Hitam Elegan', 'value' => '#1A1A1A', 'check' => '#FFFFFF'],
    ];

    // ================= TESTIMONI PELANGGAN (section Testimonials) =================

    /**
     * Sama pola dengan Produk Unggulan & Kategori Produk: isinya testimoni
     * asli dari database (lihat partials/frontend/testimonials.blade.php --
     * cuma maksimal 3 testimoni yang ditandai "Tampilkan di Beranda" oleh
     * admin lewat menu Testimoni). Ada DUA warna yang bisa diedit di sini:
     * 1. Warna Frame = latar section-nya sendiri.
     * 2. Warna Top 3 Komentar = warna kartu testimoni per posisi (Top 1,
     *    Top 2, Top 3) -- masing-masing opsional & independen satu sama
     *    lain. Kalau salah satu posisi tidak dicentang, kartu di posisi
     *    itu fallback ke pola bawaan (bergantian gelap/krem berdasarkan
     *    posisi, seperti sebelumnya). Teks tiap kartu custom otomatis
     *    menyesuaikan kontras (terang/gelap) sesuai warna yang dipilih.
     */
    public bool $testimoniUseCustomBg = false;

    public string $testimoniBgColor = '#FAF8F4';

    /**
     * Keyed 1-3 (Top 1, Top 2, Top 3), masing-masing independen.
     *
     * @var array<int, bool>
     */
    public array $testimoniUseCustomCardColor = [1 => false, 2 => false, 3 => false];

    /**
     * @var array<int, string>
     */
    public array $testimoniCardColor = [1 => '#2A1B12', 2 => '#2A1B12', 3 => '#2A1B12'];

    /**
     * @var array<int, array{label: string, value: string}>
     */
    public array $testimoniBgPresets = [
        ['label' => 'Krem Kanvas (Default)', 'value' => '#FAF8F4', 'check' => '#3D2B1F'],
        ['label' => 'Putih Gading', 'value' => '#FFFDF8', 'check' => '#3D2B1F'],
        ['label' => 'Beige Pasir', 'value' => '#EFE3D0', 'check' => '#3D2B1F'],
        ['label' => 'Sage Hijau Lembut', 'value' => '#DCE3CE', 'check' => '#3D2B1F'],
        ['label' => 'Terracotta', 'value' => '#C97B5A', 'check' => '#FFFFFF'],
        ['label' => 'Coklat Kayu Tua', 'value' => '#3D2B1F', 'check' => '#FFFFFF'],
        ['label' => 'Navy Malam', 'value' => '#22303F', 'check' => '#FFFFFF'],
        ['label' => 'Hitam Elegan', 'value' => '#1A1A1A', 'check' => '#FFFFFF'],
    ];

    /**
     * Preset pertama disesuaikan jadi coklat tua (warna kartu gelap bawaan
     * section ini), bukan krem, karena mayoritas kartu default-nya gelap.
     *
     * @var array<int, array{label: string, value: string}>
     */
    public array $testimoniCardPresets = [
        ['label' => 'Coklat Tua (Default)', 'value' => '#2A1B12', 'check' => '#FFFFFF'],
        ['label' => 'Krem Hangat', 'value' => '#F4EDE0', 'check' => '#3D2B1F'],
        ['label' => 'Putih', 'value' => '#FFFFFF', 'check' => '#3D2B1F'],
        ['label' => 'Sage Hijau Lembut', 'value' => '#DCE3CE', 'check' => '#3D2B1F'],
        ['label' => 'Terracotta', 'value' => '#C97B5A', 'check' => '#FFFFFF'],
        ['label' => 'Navy Malam', 'value' => '#22303F', 'check' => '#FFFFFF'],
        ['label' => 'Hitam Elegan', 'value' => '#1A1A1A', 'check' => '#FFFFFF'],
    ];

    // ================= KENAPA PILIH KAMI (section Expertise) =================

    public string $keahlianBadgeText = '';

    public string $keahlianTitle = '';

    public string $keahlianDescription = '';

    /** @var array<int, string> Selalu tepat 2 item (checklist di bawah deskripsi). */
    public array $keahlianChecklist = [];

    /** Hasil crop foto (drag + zoom, sama pola dengan headerImageCroppedBase64), dikirim sebagai data URL base64 (JPEG). Null = gambar tidak diganti. */
    public ?string $keahlianImageCroppedBase64 = null;

    public ?string $keahlianImagePathLama = null;

    private function keahlianDefaults(): array
    {
        return [
            'badge_text' => 'Kenapa Pilih Kami',
            'title' => 'Kualitas yang Bisa Anda Percaya',
            'description' => 'Setiap furnitur kami dibuat dari material pilihan dan dikerjakan dengan tangan secara teliti, menghasilkan produk yang kokoh, nyaman, dan tahan lama untuk mengisi rumah Anda.',
            'checklist' => [
                'Material kayu pilihan yang sudah melalui proses seleksi ketat.',
                'Dikerjakan pengrajin berpengalaman dengan standar rapi dan presisi.',
            ],
            'image_path' => null,
        ];
    }

    private function headerDefaults(): array
    {
        return [
            'bg_color' => null,
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

        $this->headerHeadlinePrefix = $data['headline_prefix'];
        $this->headerTagline = $data['tagline'];
        $this->headerDescription = $data['description'];
        $this->headerStats = $data['stats'];
        $this->headerImagePathLama = $data['image_path'];

        $keunggulanDefaults = $this->keunggulanDefaults();
        $keunggulanData = HomeSection::dataFor('keunggulan', $keunggulanDefaults);
        $this->keunggulanItems = $keunggulanData['items'];

        $missionDefaults = $this->missionDefaults();
        $missionData = HomeSection::dataFor('sejak-berdiri', $missionDefaults);

        $this->missionUseCustomBg = filled($missionData['bg_color']);
        $this->missionBgColor = $missionData['bg_color'] ?: $this->missionBgColor;
        $this->missionTitle = $missionData['title'];
        $this->missionDescription = $missionData['description'];
        $this->missionPoints = $missionData['points'];
        $this->missionStatValue = $missionData['stat_value'];
        $this->missionStatLabel = $missionData['stat_label'];
        $this->missionImageBesarPathLama = $missionData['image_path_besar'];
        $this->missionImageKecilPathLama = $missionData['image_path_kecil'];
        $this->missionStatBgPathLama = $missionData['stat_bg_image'];

        $produkUnggulanData = HomeSection::dataFor('produk-unggulan', ['bg_color' => null]);
        $this->produkUnggulanUseCustomBg = filled($produkUnggulanData['bg_color']);
        $this->produkUnggulanBgColor = $produkUnggulanData['bg_color'] ?: $this->produkUnggulanBgColor;

        $kategoriData = HomeSection::dataFor('kategori', ['bg_color' => null]);
        $this->kategoriUseCustomBg = filled($kategoriData['bg_color']);
        $this->kategoriBgColor = $kategoriData['bg_color'] ?: $this->kategoriBgColor;

        $testimoniData = HomeSection::dataFor('testimoni', [
            'bg_color' => null,
            'card_color' => null, // skema lama (1 warna utk ketiga kartu) -- dipakai cuma buat migrasi di bawah
            'card_colors' => [1 => null, 2 => null, 3 => null],
        ]);
        $this->testimoniUseCustomBg = filled($testimoniData['bg_color']);
        $this->testimoniBgColor = $testimoniData['bg_color'] ?: $this->testimoniBgColor;

        $testimoniCardColors = $testimoniData['card_colors'] ?? [1 => null, 2 => null, 3 => null];

        // Migrasi data lama: dulu 1 warna dipakai utk ketiga kartu sekaligus.
        // Kalau skema baru (per-rank) belum pernah disimpan sama sekali tapi
        // ada data lama, isi ketiga rank dengan warna lama itu sebagai awalan.
        if (filled($testimoniData['card_color']) && ! array_filter($testimoniCardColors)) {
            $testimoniCardColors = [1 => $testimoniData['card_color'], 2 => $testimoniData['card_color'], 3 => $testimoniData['card_color']];
        }

        foreach ([1, 2, 3] as $rank) {
            $this->testimoniUseCustomCardColor[$rank] = filled($testimoniCardColors[$rank] ?? null);
            $this->testimoniCardColor[$rank] = $testimoniCardColors[$rank] ?: $this->testimoniCardColor[$rank];
        }

        $keahlianDefaults = $this->keahlianDefaults();
        $keahlianData = HomeSection::dataFor('keahlian', $keahlianDefaults);

        $this->keahlianBadgeText = $keahlianData['badge_text'];
        $this->keahlianTitle = $keahlianData['title'];
        $this->keahlianDescription = $keahlianData['description'];
        $this->keahlianChecklist = $keahlianData['checklist'];
        $this->keahlianImagePathLama = $keahlianData['image_path'];
    }

    public function selectSection(string $key): void
    {
        $this->activeSection = $key;
    }

    /**
     * Dipanggil pas admin klik salah satu swatch preset di bawah color
     * picker. Otomatis mencentang "Pakai warna latar khusus" juga --
     * admin gak perlu centang manual dulu baru pilih preset.
     */
    public function selectHeaderBgPreset(string $hex): void
    {
        $this->headerUseCustomBg = true;
        $this->headerBgColor = $hex;
    }

    public function selectMissionBgPreset(string $hex): void
    {
        $this->missionUseCustomBg = true;
        $this->missionBgColor = $hex;
    }

    public function selectProdukUnggulanBgPreset(string $hex): void
    {
        $this->produkUnggulanUseCustomBg = true;
        $this->produkUnggulanBgColor = $hex;
    }

    public function selectKategoriBgPreset(string $hex): void
    {
        $this->kategoriUseCustomBg = true;
        $this->kategoriBgColor = $hex;
    }

    public function selectTestimoniBgPreset(string $hex): void
    {
        $this->testimoniUseCustomBg = true;
        $this->testimoniBgColor = $hex;
    }

    public function selectTestimoniCardPreset(int $rank, string $hex): void
    {
        $this->testimoniUseCustomCardColor[$rank] = true;
        $this->testimoniCardColor[$rank] = $hex;
    }

    /**
     * Foto kolase besar (kiri, rasio 3:4). Sebelum admin pernah mengganti,
     * pakai foto stok Pexels yang sama dengan yang tampil di Beranda --
     * lihat komentar identik di partials/frontend/mission.blade.php.
     */
    public function getMissionImageBesarPreviewUrlProperty(): string
    {
        if ($this->missionImageBesarCroppedBase64) {
            return $this->missionImageBesarCroppedBase64;
        }

        return $this->missionImageBesarPathLama
            ? Storage::disk('public')->url($this->missionImageBesarPathLama)
            : 'https://images.pexels.com/photos/28513061/pexels-photo-28513061.jpeg?auto=compress&cs=tinysrgb&w=1200';
    }

    /** Foto kolase kecil (kanan atas, rasio 1:1). Sama pola fallback-nya dengan foto besar di atas. */
    public function getMissionImageKecilPreviewUrlProperty(): string
    {
        if ($this->missionImageKecilCroppedBase64) {
            return $this->missionImageKecilCroppedBase64;
        }

        return $this->missionImageKecilPathLama
            ? Storage::disk('public')->url($this->missionImageKecilPathLama)
            : 'https://images.pexels.com/photos/82256/pexels-photo-82256.jpeg?auto=compress&cs=tinysrgb&w=800';
    }

    /**
     * Foto latar kartu angka penghargaan -- OPSIONAL, beda dari foto besar/kecil
     * di atas. Kalau admin belum pernah upload, preview di form ini pakai
     * gambar kursi.png cuma sebagai placeholder visual (tidak pernah tampil
     * di Beranda -- di Beranda yang dipakai tetap warna solid, lihat null-check
     * $missionStatBgUrl di partials/frontend/mission.blade.php).
     */
    public function getMissionStatBgPreviewUrlProperty(): string
    {
        if ($this->missionStatBgCroppedBase64) {
            return $this->missionStatBgCroppedBase64;
        }

        return $this->missionStatBgPathLama
            ? Storage::disk('public')->url($this->missionStatBgPathLama)
            : asset('images/admin-login/kursi.png');
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

    /** Sama pola fallback-nya dengan foto Header di atas -- lihat komentar identik di partials/frontend/expertise.blade.php. */
    public function getKeahlianImagePreviewUrlProperty(): string
    {
        if ($this->keahlianImageCroppedBase64) {
            return $this->keahlianImageCroppedBase64;
        }

        return $this->keahlianImagePathLama
            ? Storage::disk('public')->url($this->keahlianImagePathLama)
            : asset('images/admin-login/kursi.png');
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
                'headline_prefix' => $validated['headerHeadlinePrefix'],
                'tagline' => $validated['headerTagline'],
                'description' => $validated['headerDescription'],
                'stats' => $validated['headerStats'],
                'image_path' => $imagePath,
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveKeunggulan(): void
    {
        $validated = $this->validate([
            'keunggulanItems' => ['required', 'array', 'size:3'],
            'keunggulanItems.*.icon' => ['required', 'string', 'in:'.implode(',', $this->keunggulanIconOptions)],
            'keunggulanItems.*.title' => ['required', 'string', 'max:60'],
            'keunggulanItems.*.desc' => ['required', 'string', 'max:120'],
        ], [
            'keunggulanItems.*.title.required' => 'Judul wajib diisi.',
            'keunggulanItems.*.desc.required' => 'Deskripsi wajib diisi.',
        ]);

        HomeSection::forSection('keunggulan')->update([
            'data' => [
                'items' => $validated['keunggulanItems'],
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveMission(): void
    {
        $validated = $this->validate([
            'missionBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'missionTitle' => ['required', 'string', 'max:80'],
            'missionDescription' => ['required', 'string', 'max:600'],
            'missionPoints' => ['required', 'array', 'size:3'],
            'missionPoints.*.icon' => ['required', 'string', 'in:'.implode(',', $this->missionIconOptions)],
            'missionPoints.*.title' => ['required', 'string', 'max:60'],
            'missionPoints.*.desc' => ['required', 'string', 'max:150'],
            'missionStatValue' => ['required', 'string', 'max:12'],
            'missionStatLabel' => ['required', 'string', 'max:40'],
            'missionImageBesarCroppedBase64' => ['nullable', 'string'],
            'missionImageKecilCroppedBase64' => ['nullable', 'string'],
            'missionStatBgCroppedBase64' => ['nullable', 'string'],
        ], [
            'missionTitle.required' => 'Judul wajib diisi.',
            'missionDescription.required' => 'Deskripsi wajib diisi.',
            'missionPoints.*.title.required' => 'Judul poin wajib diisi.',
            'missionPoints.*.desc.required' => 'Deskripsi poin wajib diisi.',
            'missionStatValue.required' => 'Angka statistik wajib diisi.',
            'missionStatLabel.required' => 'Label statistik wajib diisi.',
        ]);

        $imagePathBesar = $this->missionImageBesarPathLama;

        if ($this->missionImageBesarCroppedBase64) {
            $binary = $this->decodeBase64Image($this->missionImageBesarCroppedBase64);

            if ($binary !== null) {
                if ($this->missionImageBesarPathLama) {
                    Storage::disk('public')->delete($this->missionImageBesarPathLama);
                }

                $imagePathBesar = 'home-sections/mission-besar-'.Str::uuid().'.jpg';
                Storage::disk('public')->put($imagePathBesar, $binary);
                $this->missionImageBesarPathLama = $imagePathBesar;
                $this->missionImageBesarCroppedBase64 = null;
            }
        }

        $imagePathKecil = $this->missionImageKecilPathLama;

        if ($this->missionImageKecilCroppedBase64) {
            $binary = $this->decodeBase64Image($this->missionImageKecilCroppedBase64);

            if ($binary !== null) {
                if ($this->missionImageKecilPathLama) {
                    Storage::disk('public')->delete($this->missionImageKecilPathLama);
                }

                $imagePathKecil = 'home-sections/mission-kecil-'.Str::uuid().'.jpg';
                Storage::disk('public')->put($imagePathKecil, $binary);
                $this->missionImageKecilPathLama = $imagePathKecil;
                $this->missionImageKecilCroppedBase64 = null;
            }
        }

        $imagePathStatBg = $this->missionStatBgPathLama;

        if ($this->missionStatBgCroppedBase64) {
            $binary = $this->decodeBase64Image($this->missionStatBgCroppedBase64);

            if ($binary !== null) {
                if ($this->missionStatBgPathLama) {
                    Storage::disk('public')->delete($this->missionStatBgPathLama);
                }

                $imagePathStatBg = 'home-sections/mission-statbg-'.Str::uuid().'.jpg';
                Storage::disk('public')->put($imagePathStatBg, $binary);
                $this->missionStatBgPathLama = $imagePathStatBg;
                $this->missionStatBgCroppedBase64 = null;
            }
        }

        HomeSection::forSection('sejak-berdiri')->update([
            'data' => [
                'bg_color' => $this->missionUseCustomBg ? $validated['missionBgColor'] : null,
                'title' => $validated['missionTitle'],
                'description' => $validated['missionDescription'],
                'points' => $validated['missionPoints'],
                'stat_value' => $validated['missionStatValue'],
                'stat_label' => $validated['missionStatLabel'],
                'image_path_besar' => $imagePathBesar,
                'image_path_kecil' => $imagePathKecil,
                'stat_bg_image' => $imagePathStatBg,
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveProdukUnggulan(): void
    {
        $validated = $this->validate([
            'produkUnggulanBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        HomeSection::forSection('produk-unggulan')->update([
            'data' => [
                'bg_color' => $this->produkUnggulanUseCustomBg ? $validated['produkUnggulanBgColor'] : null,
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveKategori(): void
    {
        $validated = $this->validate([
            'kategoriBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        HomeSection::forSection('kategori')->update([
            'data' => [
                'bg_color' => $this->kategoriUseCustomBg ? $validated['kategoriBgColor'] : null,
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveTestimoni(): void
    {
        $validated = $this->validate([
            'testimoniBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'testimoniCardColor.1' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'testimoniCardColor.2' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'testimoniCardColor.3' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        HomeSection::forSection('testimoni')->update([
            'data' => [
                'bg_color' => $this->testimoniUseCustomBg ? $validated['testimoniBgColor'] : null,
                'card_color' => null, // skema lama sudah tidak dipakai lagi, dikosongkan
                'card_colors' => [
                    1 => $this->testimoniUseCustomCardColor[1] ? $validated['testimoniCardColor'][1] : null,
                    2 => $this->testimoniUseCustomCardColor[2] ? $validated['testimoniCardColor'][2] : null,
                    3 => $this->testimoniUseCustomCardColor[3] ? $validated['testimoniCardColor'][3] : null,
                ],
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveKeahlian(): void
    {
        $validated = $this->validate([
            'keahlianBadgeText' => ['required', 'string', 'max:40'],
            'keahlianTitle' => ['required', 'string', 'max:60'],
            'keahlianDescription' => ['required', 'string', 'max:400'],
            'keahlianChecklist' => ['required', 'array', 'size:2'],
            'keahlianChecklist.*' => ['required', 'string', 'max:150'],
            'keahlianImageCroppedBase64' => ['nullable', 'string'],
        ], [
            'keahlianBadgeText.required' => 'Label kecil wajib diisi.',
            'keahlianTitle.required' => 'Judul wajib diisi.',
            'keahlianDescription.required' => 'Deskripsi wajib diisi.',
            'keahlianChecklist.*.required' => 'Poin checklist wajib diisi.',
        ]);

        $imagePath = $this->keahlianImagePathLama;

        if ($this->keahlianImageCroppedBase64) {
            $binary = $this->decodeBase64Image($this->keahlianImageCroppedBase64);

            if ($binary !== null) {
                if ($this->keahlianImagePathLama) {
                    Storage::disk('public')->delete($this->keahlianImagePathLama);
                }

                $imagePath = 'home-sections/keahlian-'.Str::uuid().'.jpg';
                Storage::disk('public')->put($imagePath, $binary);
                $this->keahlianImagePathLama = $imagePath;
                $this->keahlianImageCroppedBase64 = null;
            }
        }

        HomeSection::forSection('keahlian')->update([
            'data' => [
                'badge_text' => $validated['keahlianBadgeText'],
                'title' => $validated['keahlianTitle'],
                'description' => $validated['keahlianDescription'],
                'checklist' => $validated['keahlianChecklist'],
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

                    {{--
                        WARNA -- cuma SATU pilihan: warna latar/frame section ini.
                        Warna judul, deskripsi, dan angka statistik BUKAN input
                        terpisah -- otomatis dihitung dari kontras warna latar yang
                        dipilih (lihat helper contrastTextColors() di
                        partials/frontend/hero.blade.php), supaya tidak pernah
                        "bertabrakan" (teks gelap di atas latar gelap, dst).
                        wire:model.live dipakai supaya checkbox langsung jadi
                        source of truth di server begitu ditoggle (pola yang sama
                        dengan pesanan.blade.php / produk-index filter kategori).
                    --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna</p>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="headerUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna latar khusus
                            </label>
                            <input
                                type="color" wire:model="headerBgColor"
                                @disabled(! $headerUseCustomBg)
                                class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:cursor-not-allowed disabled:opacity-40"
                            >
                        </div>

                        {{-- Preset warna rekomendasi -- klik langsung pakai warnanya + otomatis centang checkbox di atas. --}}
                        <div>
                            <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-admin-ink-soft">Atau pilih dari rekomendasi</p>
                            <div class="flex flex-wrap gap-2.5">
                                @foreach ($headerBgPresets as $preset)
                                    <button
                                        type="button"
                                        wire:click="selectHeaderBgPreset('{{ $preset['value'] }}')"
                                        title="{{ $preset['label'] }}"
                                        class="group flex flex-col items-center gap-1"
                                    >
                                        {{--
                                            Swatch dikasih gradien halus (color-mix, pola yang sama
                                            dipakai di resources/css/app.css untuk scrollbar) +
                                            ring highlight tipis supaya ada dimensi/kilau, tidak
                                            flat/polos satu warna rata seperti sebelumnya.
                                        --}}
                                        <span
                                            class="flex h-9 w-9 items-center justify-center rounded-full shadow-sm ring-1 ring-inset ring-white/40 transition duration-200 group-hover:scale-110 group-hover:shadow-md
                                            {{ $headerUseCustomBg && strtoupper($headerBgColor) === $preset['value']
                                                ? 'border-2 border-admin-accent ring-2 ring-admin-accent ring-offset-2 ring-offset-admin-surface'
                                                : 'border border-admin-border group-hover:border-admin-accent/60' }}"
                                            style="background: linear-gradient(135deg, color-mix(in oklab, {{ $preset['value'] }} 100%, white 30%), {{ $preset['value'] }} 55%, color-mix(in oklab, {{ $preset['value'] }} 100%, black 16%));"
                                        >
                                            @if ($headerUseCustomBg && strtoupper($headerBgColor) === $preset['value'])
                                                <i class="fa-solid fa-check text-xs drop-shadow-sm" style="color: {{ $preset['check'] }};"></i>
                                            @endif
                                        </span>
                                        <span class="max-w-14 truncate text-[10px] text-admin-ink-soft">{{ $preset['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <p class="text-xs text-admin-ink-soft">
                            Kalau tidak dicentang, section ini ikut warna tema default (bukan warna admin panel).
                            Warna judul, deskripsi, dan angka statistik tidak diatur terpisah -- otomatis
                            menyesuaikan (terang/gelap) mengikuti warna latar yang dipilih di sini, supaya
                            tetap kebaca dan tidak bertabrakan.
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
            @elseif ($activeSection === 'keunggulan')
                <form wire:submit="saveKeunggulan" class="space-y-6">

                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-star text-admin-accent"></i>
                            Keunggulan (3 kolom tepat di bawah Header)
                        </h3>
                        <p class="text-xs text-admin-ink-soft">
                            Tiga poin singkat yang tampil berdampingan di bawah Header. Tiap poin punya ikon,
                            judul singkat, dan deskripsi satu baris.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        @foreach ($keunggulanItems as $i => $item)
                            <div class="space-y-3 rounded-xl border border-admin-border p-4">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-admin-cream">
                                        <i class="fa-solid {{ $item['icon'] }} text-sm text-admin-ink"></i>
                                    </span>
                                    <select
                                        wire:model="keunggulanItems.{{ $i }}.icon"
                                        class="w-full rounded-lg border border-admin-border bg-admin-surface px-2.5 py-2 text-xs text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                    >
                                        @foreach ($keunggulanIconOptions as $iconOption)
                                            <option value="{{ $iconOption }}">{{ $iconOption }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error("keunggulanItems.{$i}.icon")<p class="text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror

                                <input
                                    type="text" maxlength="60" placeholder="Judul singkat"
                                    wire:model="keunggulanItems.{{ $i }}.title"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-2.5 py-2 text-sm font-semibold text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error("keunggulanItems.{$i}.title")<p class="text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror

                                <textarea
                                    rows="2" maxlength="120" placeholder="Deskripsi singkat"
                                    wire:model="keunggulanItems.{{ $i }}.desc"
                                    class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-2.5 py-2 text-xs text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                ></textarea>
                                @error("keunggulanItems.{$i}.desc")<p class="text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            wire:loading.attr="disabled" wire:target="saveKeunggulan"
                            class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition-all duration-200 hover:bg-admin-accent-strong active:scale-[0.99] disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="saveKeunggulan" class="flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan
                            </span>
                            <span wire:loading wire:target="saveKeunggulan" class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch animate-spin"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            @elseif ($activeSection === 'sejak-berdiri')
                <form wire:submit="saveMission" class="space-y-6">

                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-calendar-day text-admin-accent"></i>
                            Sejak Berdiri (section ke-3 Beranda)
                        </h3>
                        <p class="text-xs text-admin-ink-soft">
                            Section "Tentang Kami" tepat di bawah Keunggulan -- 2 foto kolase + kartu angka
                            di kiri, judul, paragraf, 3 poin unggulan, dan angka penghargaan di kanan.
                            Tombol &amp; link "Lihat Profil Kami" tidak bisa diubah dari sini (selalu menuju
                            halaman Profil).
                        </p>
                    </div>

                    {{-- FOTO --}}
                    <div class="space-y-5 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Foto</p>

                        <div class="grid gap-5 sm:grid-cols-3">
                            {{-- Foto besar (kiri, rasio 3:4) --}}
                            <div class="flex flex-col items-center gap-3 text-center" x-data="missionFotoBesarCropper(@js($this->missionImageBesarPreviewUrl))">
                                <div class="relative w-28 shrink-0">
                                    <img :src="previewUrl" alt="Preview foto besar Sejak Berdiri" class="aspect-3/4 w-28 rounded-2xl object-cover ring-4 ring-admin-cream">
                                    <label
                                        for="mission_image_besar_input"
                                        class="absolute -bottom-1 -right-1 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-admin-accent text-white shadow-sm ring-2 ring-admin-surface transition hover:bg-admin-accent-strong"
                                        title="Ganti foto"
                                    >
                                        <i class="fa-solid fa-camera text-xs"></i>
                                    </label>
                                    <input x-ref="fileInput" id="mission_image_besar_input" type="file" accept="image/*" class="hidden" x-on:change="onFileChange($event)">
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-admin-ink">Foto besar (kiri)</p>
                                    <p class="mt-1 text-xs text-admin-ink-soft">Klik ikon kamera untuk ganti, lalu geser &amp; zoom.</p>
                                </div>

                                <template x-teleport="body">
                                    <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-999 flex items-center justify-center bg-black/60 p-4" style="display: none;">
                                        <div x-show="open" x-transition.scale.origin.center @click.outside="cancelCrop()" class="w-full max-w-md rounded-2xl bg-admin-surface p-6 shadow-2xl">
                                            <h4 class="mb-1 text-sm font-semibold text-admin-ink">Sesuaikan Foto Besar</h4>
                                            <p class="mb-4 text-xs text-admin-ink-soft">Geser gambar untuk memindah, gunakan slider untuk zoom. Rasio 3:4 (mengikuti kolase kiri).</p>
                                            <div x-ref="viewport" class="relative mx-auto aspect-3/4 w-full max-w-64 cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none" x-on:pointerdown="startDrag($event)" x-on:pointermove="onDrag($event)" x-on:pointerup="endDrag()" x-on:pointerleave="endDrag()">
                                                <img x-ref="cropImg" :src="rawImage" x-on:load="onImgLoad($event)" draggable="false" class="absolute left-0 top-0 max-w-none origin-top-left select-none" :style="`width:${natW * scale}px; height:${natH * scale}px; transform: translate(${posX}px, ${posY}px);`">
                                            </div>
                                            <div class="mt-4 flex items-center gap-3">
                                                <i class="fa-solid fa-magnifying-glass-minus text-xs text-admin-ink-soft"></i>
                                                <input type="range" min="0" max="100" x-model.number="zoomPercent" x-on:input="applyZoom()" class="h-1.5 w-full cursor-pointer appearance-none rounded-full bg-admin-border accent-admin-accent">
                                                <i class="fa-solid fa-magnifying-glass-plus text-xs text-admin-ink-soft"></i>
                                            </div>
                                            <div class="mt-5 flex justify-end gap-2">
                                                <button type="button" x-on:click="cancelCrop()" class="rounded-full border border-admin-border px-4 py-2 text-xs font-semibold text-admin-ink-soft transition hover:bg-admin-cream">Batal</button>
                                                <button type="button" x-on:click="confirmCrop()" class="rounded-full bg-admin-accent px-4 py-2 text-xs font-semibold text-white transition hover:bg-admin-accent-strong">Gunakan Foto Ini</button>
                                            </div>
                                            <canvas x-ref="cropCanvas" class="hidden"></canvas>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Foto kecil (kanan atas, rasio 1:1) --}}
                            <div class="flex flex-col items-center gap-3 text-center" x-data="missionFotoKecilCropper(@js($this->missionImageKecilPreviewUrl))">
                                <div class="relative w-28 shrink-0">
                                    <img :src="previewUrl" alt="Preview foto kecil Sejak Berdiri" class="aspect-square w-28 rounded-2xl object-cover ring-4 ring-admin-cream">
                                    <label
                                        for="mission_image_kecil_input"
                                        class="absolute -bottom-1 -right-1 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-admin-accent text-white shadow-sm ring-2 ring-admin-surface transition hover:bg-admin-accent-strong"
                                        title="Ganti foto"
                                    >
                                        <i class="fa-solid fa-camera text-xs"></i>
                                    </label>
                                    <input x-ref="fileInput" id="mission_image_kecil_input" type="file" accept="image/*" class="hidden" x-on:change="onFileChange($event)">
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-admin-ink">Foto kecil (kanan atas)</p>
                                    <p class="mt-1 text-xs text-admin-ink-soft">Klik ikon kamera untuk ganti, lalu geser &amp; zoom.</p>
                                </div>

                                <template x-teleport="body">
                                    <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-999 flex items-center justify-center bg-black/60 p-4" style="display: none;">
                                        <div x-show="open" x-transition.scale.origin.center @click.outside="cancelCrop()" class="w-full max-w-md rounded-2xl bg-admin-surface p-6 shadow-2xl">
                                            <h4 class="mb-1 text-sm font-semibold text-admin-ink">Sesuaikan Foto Kecil</h4>
                                            <p class="mb-4 text-xs text-admin-ink-soft">Geser gambar untuk memindah, gunakan slider untuk zoom. Rasio 1:1 (mengikuti kolase kanan atas).</p>
                                            <div x-ref="viewport" class="relative mx-auto aspect-square w-full max-w-64 cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none" x-on:pointerdown="startDrag($event)" x-on:pointermove="onDrag($event)" x-on:pointerup="endDrag()" x-on:pointerleave="endDrag()">
                                                <img x-ref="cropImg" :src="rawImage" x-on:load="onImgLoad($event)" draggable="false" class="absolute left-0 top-0 max-w-none origin-top-left select-none" :style="`width:${natW * scale}px; height:${natH * scale}px; transform: translate(${posX}px, ${posY}px);`">
                                            </div>
                                            <div class="mt-4 flex items-center gap-3">
                                                <i class="fa-solid fa-magnifying-glass-minus text-xs text-admin-ink-soft"></i>
                                                <input type="range" min="0" max="100" x-model.number="zoomPercent" x-on:input="applyZoom()" class="h-1.5 w-full cursor-pointer appearance-none rounded-full bg-admin-border accent-admin-accent">
                                                <i class="fa-solid fa-magnifying-glass-plus text-xs text-admin-ink-soft"></i>
                                            </div>
                                            <div class="mt-5 flex justify-end gap-2">
                                                <button type="button" x-on:click="cancelCrop()" class="rounded-full border border-admin-border px-4 py-2 text-xs font-semibold text-admin-ink-soft transition hover:bg-admin-cream">Batal</button>
                                                <button type="button" x-on:click="confirmCrop()" class="rounded-full bg-admin-accent px-4 py-2 text-xs font-semibold text-white transition hover:bg-admin-accent-strong">Gunakan Foto Ini</button>
                                            </div>
                                            <canvas x-ref="cropCanvas" class="hidden"></canvas>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Foto latar kartu angka penghargaan (rasio 3:5, OPSIONAL -- disamakan dengan tinggi sisa kolom kanan di kolase mission.blade.php) --}}
                            <div class="flex flex-col items-center gap-3 text-center" x-data="missionStatBgCropper(@js($this->missionStatBgPreviewUrl))">
                                <div class="relative w-28 shrink-0">
                                    <img :src="previewUrl" alt="Preview foto latar kartu angka" class="aspect-3/5 w-28 rounded-2xl object-cover ring-4 ring-admin-cream">
                                    <label
                                        for="mission_stat_bg_input"
                                        class="absolute -bottom-1 -right-1 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-admin-accent text-white shadow-sm ring-2 ring-admin-surface transition hover:bg-admin-accent-strong"
                                        title="Ganti foto"
                                    >
                                        <i class="fa-solid fa-camera text-xs"></i>
                                    </label>
                                    <input x-ref="fileInput" id="mission_stat_bg_input" type="file" accept="image/*" class="hidden" x-on:change="onFileChange($event)">
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-admin-ink">Latar kartu angka</p>
                                    <p class="mt-1 text-xs text-admin-ink-soft">Opsional -- kalau tidak diisi, kartu tetap pakai warna solid.</p>
                                </div>

                                <template x-teleport="body">
                                    <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-999 flex items-center justify-center bg-black/60 p-4" style="display: none;">
                                        <div x-show="open" x-transition.scale.origin.center @click.outside="cancelCrop()" class="w-full max-w-md rounded-2xl bg-admin-surface p-6 shadow-2xl">
                                            <h4 class="mb-1 text-sm font-semibold text-admin-ink">Sesuaikan Foto Latar Kartu Angka</h4>
                                            <p class="mb-4 text-xs text-admin-ink-soft">Geser gambar untuk memindah, gunakan slider untuk zoom. Rasio 3:5 (mengikuti tinggi sisa kolom kanan kolase), akan tampil dengan overlay gelap tipis supaya angka &amp; label tetap kebaca.</p>
                                            <div x-ref="viewport" class="relative mx-auto aspect-3/5 w-full max-w-64 cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none" x-on:pointerdown="startDrag($event)" x-on:pointermove="onDrag($event)" x-on:pointerup="endDrag()" x-on:pointerleave="endDrag()">
                                                <img x-ref="cropImg" :src="rawImage" x-on:load="onImgLoad($event)" draggable="false" class="absolute left-0 top-0 max-w-none origin-top-left select-none" :style="`width:${natW * scale}px; height:${natH * scale}px; transform: translate(${posX}px, ${posY}px);`">
                                            </div>
                                            <div class="mt-4 flex items-center gap-3">
                                                <i class="fa-solid fa-magnifying-glass-minus text-xs text-admin-ink-soft"></i>
                                                <input type="range" min="0" max="100" x-model.number="zoomPercent" x-on:input="applyZoom()" class="h-1.5 w-full cursor-pointer appearance-none rounded-full bg-admin-border accent-admin-accent">
                                                <i class="fa-solid fa-magnifying-glass-plus text-xs text-admin-ink-soft"></i>
                                            </div>
                                            <div class="mt-5 flex justify-end gap-2">
                                                <button type="button" x-on:click="cancelCrop()" class="rounded-full border border-admin-border px-4 py-2 text-xs font-semibold text-admin-ink-soft transition hover:bg-admin-cream">Batal</button>
                                                <button type="button" x-on:click="confirmCrop()" class="rounded-full bg-admin-accent px-4 py-2 text-xs font-semibold text-white transition hover:bg-admin-accent-strong">Gunakan Foto Ini</button>
                                            </div>
                                            <canvas x-ref="cropCanvas" class="hidden"></canvas>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                </div>

                    {{-- WARNA --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Frame (Latar Section)</p>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="missionUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna latar khusus
                            </label>
                            <input
                                type="color" wire:model="missionBgColor"
                                @disabled(! $missionUseCustomBg)
                                class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:cursor-not-allowed disabled:opacity-40"
                            >
                        </div>

                        <div>
                            <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-admin-ink-soft">Atau pilih dari rekomendasi</p>
                            <div class="flex flex-wrap gap-2.5">
                                @foreach ($missionBgPresets as $preset)
                                    <button
                                        type="button"
                                        wire:click="selectMissionBgPreset('{{ $preset['value'] }}')"
                                        title="{{ $preset['label'] }}"
                                        class="group flex flex-col items-center gap-1"
                                    >
                                        <span
                                            class="flex h-9 w-9 items-center justify-center rounded-full shadow-sm ring-1 ring-inset ring-white/40 transition duration-200 group-hover:scale-110 group-hover:shadow-md
                                            {{ $missionUseCustomBg && strtoupper($missionBgColor) === $preset['value']
                                                ? 'border-2 border-admin-accent ring-2 ring-admin-accent ring-offset-2 ring-offset-admin-surface'
                                                : 'border border-admin-border group-hover:border-admin-accent/60' }}"
                                            style="background: linear-gradient(135deg, color-mix(in oklab, {{ $preset['value'] }} 100%, white 30%), {{ $preset['value'] }} 55%, color-mix(in oklab, {{ $preset['value'] }} 100%, black 16%));"
                                        >
                                            @if ($missionUseCustomBg && strtoupper($missionBgColor) === $preset['value'])
                                                <i class="fa-solid fa-check text-xs drop-shadow-sm" style="color: {{ $preset['check'] }};"></i>
                                            @endif
                                        </span>
                                        <span class="max-w-14 truncate text-[10px] text-admin-ink-soft">{{ $preset['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <p class="text-xs text-admin-ink-soft">
                            Kalau tidak dicentang, section ini ikut warna krem persik bawaan. Warna judul,
                            paragraf, ikon poin, dan kartu angka otomatis menyesuaikan (terang/gelap)
                            mengikuti warna latar yang dipilih, supaya tetap kebaca.
                        </p>
                    </div>

                    {{-- ISI TEKS --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Isi Teks</p>

                        <div class="grid gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul</label>
                                <input
                                    type="text" maxlength="80" wire:model="missionTitle"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('missionTitle')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Paragraf</label>
                                <textarea
                                    rows="4" maxlength="600" wire:model="missionDescription"
                                    class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                ></textarea>
                                @error('missionDescription')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <p class="mb-2 text-sm font-medium text-admin-ink">3 Poin Unggulan</p>
                            <div class="grid gap-4 sm:grid-cols-3">
                                @foreach ($missionPoints as $i => $point)
                                    <div class="space-y-3 rounded-xl border border-admin-border p-4">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-admin-cream">
                                                <i class="fa-solid {{ $point['icon'] }} text-sm text-admin-ink"></i>
                                            </span>
                                            <select
                                                wire:model="missionPoints.{{ $i }}.icon"
                                                class="w-full rounded-lg border border-admin-border bg-admin-surface px-2.5 py-2 text-xs text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                            >
                                                @foreach ($missionIconOptions as $iconOption)
                                                    <option value="{{ $iconOption }}">{{ $iconOption }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error("missionPoints.{$i}.icon")<p class="text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror

                                        <input
                                            type="text" maxlength="60" placeholder="Judul poin"
                                            wire:model="missionPoints.{{ $i }}.title"
                                            class="w-full rounded-lg border border-admin-border bg-admin-surface px-2.5 py-2 text-sm font-semibold text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                        >
                                        @error("missionPoints.{$i}.title")<p class="text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror

                                        <textarea
                                            rows="2" maxlength="150" placeholder="Deskripsi poin"
                                            wire:model="missionPoints.{{ $i }}.desc"
                                            class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-2.5 py-2 text-xs text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                        ></textarea>
                                        @error("missionPoints.{$i}.desc")<p class="text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <p class="mb-2 text-sm font-medium text-admin-ink">Kartu Angka (di kolase foto)</p>
                            <div class="grid max-w-sm gap-3 sm:grid-cols-2">
                                <div>
                                    <input
                                        type="text" maxlength="12" placeholder="15 thn"
                                        wire:model="missionStatValue"
                                        class="w-full rounded-lg border border-admin-border bg-admin-surface px-2.5 py-2 text-sm font-semibold text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                    >
                                    @error('missionStatValue')<p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <input
                                        type="text" maxlength="40" placeholder="Penghargaan Karya Terbaik"
                                        wire:model="missionStatLabel"
                                        class="w-full rounded-lg border border-admin-border bg-admin-surface px-2.5 py-2 text-xs text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                    >
                                    @error('missionStatLabel')<p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            wire:loading.attr="disabled" wire:target="saveMission"
                            class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition-all duration-200 hover:bg-admin-accent-strong active:scale-[0.99] disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="saveMission" class="flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan
                            </span>
                            <span wire:loading wire:target="saveMission" class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch animate-spin"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            @elseif ($activeSection === 'produk-unggulan')
                <form wire:submit="saveProdukUnggulan" class="space-y-6">

                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-layer-group text-admin-accent"></i>
                            Produk Unggulan (section ke-4 Beranda)
                        </h3>
                        <p class="text-xs text-admin-ink-soft">
                            Section ini menampilkan produk asli dari menu Produk (maksimal 3 produk terbaru
                            berstatus aktif) -- judul, foto, kategori, dan harga produk ambil otomatis dari
                            situ, jadi tidak diedit di sini. Yang bisa diatur cuma warna latar section-nya.
                        </p>
                    </div>

                    {{-- WARNA --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Frame (Latar Section)</p>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="produkUnggulanUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna latar khusus
                            </label>
                            <input
                                type="color" wire:model="produkUnggulanBgColor"
                                @disabled(! $produkUnggulanUseCustomBg)
                                class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:cursor-not-allowed disabled:opacity-40"
                            >
                        </div>

                        <div>
                            <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-admin-ink-soft">Atau pilih dari rekomendasi</p>
                            <div class="flex flex-wrap gap-2.5">
                                @foreach ($produkUnggulanBgPresets as $preset)
                                    <button
                                        type="button"
                                        wire:click="selectProdukUnggulanBgPreset('{{ $preset['value'] }}')"
                                        title="{{ $preset['label'] }}"
                                        class="group flex flex-col items-center gap-1"
                                    >
                                        <span
                                            class="flex h-9 w-9 items-center justify-center rounded-full shadow-sm ring-1 ring-inset ring-white/40 transition duration-200 group-hover:scale-110 group-hover:shadow-md
                                            {{ $produkUnggulanUseCustomBg && strtoupper($produkUnggulanBgColor) === $preset['value']
                                                ? 'border-2 border-admin-accent ring-2 ring-admin-accent ring-offset-2 ring-offset-admin-surface'
                                                : 'border border-admin-border group-hover:border-admin-accent/60' }}"
                                            style="background: linear-gradient(135deg, color-mix(in oklab, {{ $preset['value'] }} 100%, white 30%), {{ $preset['value'] }} 55%, color-mix(in oklab, {{ $preset['value'] }} 100%, black 16%));"
                                        >
                                            @if ($produkUnggulanUseCustomBg && strtoupper($produkUnggulanBgColor) === $preset['value'])
                                                <i class="fa-solid fa-check text-xs drop-shadow-sm" style="color: {{ $preset['check'] }};"></i>
                                            @endif
                                        </span>
                                        <span class="max-w-14 truncate text-[10px] text-admin-ink-soft">{{ $preset['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <p class="text-xs text-admin-ink-soft">
                            Kalau tidak dicentang, section ini ikut warna putih bawaan. Warna judul & link
                            "Lihat Semua Produk" otomatis menyesuaikan (terang/gelap) mengikuti warna latar
                            yang dipilih, supaya tetap kebaca. Kartu produk (thumbnail, nama, harga) tetap
                            berwarna krem terang seperti biasa, tidak ikut berubah.
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            wire:loading.attr="disabled" wire:target="saveProdukUnggulan"
                            class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition-all duration-200 hover:bg-admin-accent-strong active:scale-[0.99] disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="saveProdukUnggulan" class="flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan
                            </span>
                            <span wire:loading wire:target="saveProdukUnggulan" class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch animate-spin"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            @elseif ($activeSection === 'kategori')
                <form wire:submit="saveKategori" class="space-y-6">

                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-th-large text-admin-accent"></i>
                            Kategori Produk (section ke-5 Beranda)
                        </h3>
                        <p class="text-xs text-admin-ink-soft">
                            Section ini menampilkan 4 kategori teratas dari menu Kategori (foto, nama, dan
                            jumlah produk ambil otomatis dari situ, urutan mengikuti drag & drop), jadi tidak
                            diedit di sini. Yang bisa diatur cuma warna latar section-nya.
                        </p>
                    </div>

                    {{-- WARNA --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Frame (Latar Section)</p>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="kategoriUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna latar khusus
                            </label>
                            <input
                                type="color" wire:model="kategoriBgColor"
                                @disabled(! $kategoriUseCustomBg)
                                class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:cursor-not-allowed disabled:opacity-40"
                            >
                        </div>

                        <div>
                            <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-admin-ink-soft">Atau pilih dari rekomendasi</p>
                            <div class="flex flex-wrap gap-2.5">
                                @foreach ($kategoriBgPresets as $preset)
                                    <button
                                        type="button"
                                        wire:click="selectKategoriBgPreset('{{ $preset['value'] }}')"
                                        title="{{ $preset['label'] }}"
                                        class="group flex flex-col items-center gap-1"
                                    >
                                        <span
                                            class="flex h-9 w-9 items-center justify-center rounded-full shadow-sm ring-1 ring-inset ring-white/40 transition duration-200 group-hover:scale-110 group-hover:shadow-md
                                            {{ $kategoriUseCustomBg && strtoupper($kategoriBgColor) === $preset['value']
                                                ? 'border-2 border-admin-accent ring-2 ring-admin-accent ring-offset-2 ring-offset-admin-surface'
                                                : 'border border-admin-border group-hover:border-admin-accent/60' }}"
                                            style="background: linear-gradient(135deg, color-mix(in oklab, {{ $preset['value'] }} 100%, white 30%), {{ $preset['value'] }} 55%, color-mix(in oklab, {{ $preset['value'] }} 100%, black 16%));"
                                        >
                                            @if ($kategoriUseCustomBg && strtoupper($kategoriBgColor) === $preset['value'])
                                                <i class="fa-solid fa-check text-xs drop-shadow-sm" style="color: {{ $preset['check'] }};"></i>
                                            @endif
                                        </span>
                                        <span class="max-w-14 truncate text-[10px] text-admin-ink-soft">{{ $preset['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <p class="text-xs text-admin-ink-soft">
                            Kalau tidak dicentang, section ini ikut warna peach lembut bawaan. Warna judul,
                            nama kategori & jumlah produk otomatis menyesuaikan (terang/gelap) mengikuti
                            warna latar yang dipilih, supaya tetap kebaca -- teks-teks ini langsung di atas
                            warna latar (tidak ada kartu putih di belakangnya seperti section Produk Unggulan).
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            wire:loading.attr="disabled" wire:target="saveKategori"
                            class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition-all duration-200 hover:bg-admin-accent-strong active:scale-[0.99] disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="saveKategori" class="flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan
                            </span>
                            <span wire:loading wire:target="saveKategori" class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch animate-spin"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            @elseif ($activeSection === 'testimoni')
                <form wire:submit="saveTestimoni" class="space-y-6">

                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-quote-left text-admin-accent"></i>
                            Testimoni Pelanggan (section ke-6 Beranda)
                        </h3>
                        <p class="text-xs text-admin-ink-soft">
                            Section ini menampilkan maksimal 3 testimoni yang ditandai "Tampilkan di Beranda"
                            lewat menu Testimoni (nama, foto, rating, dan komentar ambil otomatis dari situ,
                            jadi tidak diedit di sini). Yang bisa diatur cuma warna latar section dan warna
                            tiap kartu Top 3 Komentar (Top 1, Top 2, Top 3, bisa beda-beda).
                        </p>
                    </div>

                    {{-- WARNA FRAME (LATAR SECTION) --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Frame (Latar Section)</p>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="testimoniUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna latar khusus
                            </label>
                            <input
                                type="color" wire:model="testimoniBgColor"
                                @disabled(! $testimoniUseCustomBg)
                                class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:cursor-not-allowed disabled:opacity-40"
                            >
                        </div>

                        <div>
                            <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-admin-ink-soft">Atau pilih dari rekomendasi</p>
                            <div class="flex flex-wrap gap-2.5">
                                @foreach ($testimoniBgPresets as $preset)
                                    <button
                                        type="button"
                                        wire:click="selectTestimoniBgPreset('{{ $preset['value'] }}')"
                                        title="{{ $preset['label'] }}"
                                        class="group flex flex-col items-center gap-1"
                                    >
                                        <span
                                            class="flex h-9 w-9 items-center justify-center rounded-full shadow-sm ring-1 ring-inset ring-white/40 transition duration-200 group-hover:scale-110 group-hover:shadow-md
                                            {{ $testimoniUseCustomBg && strtoupper($testimoniBgColor) === $preset['value']
                                                ? 'border-2 border-admin-accent ring-2 ring-admin-accent ring-offset-2 ring-offset-admin-surface'
                                                : 'border border-admin-border group-hover:border-admin-accent/60' }}"
                                            style="background: linear-gradient(135deg, color-mix(in oklab, {{ $preset['value'] }} 100%, white 30%), {{ $preset['value'] }} 55%, color-mix(in oklab, {{ $preset['value'] }} 100%, black 16%));"
                                        >
                                            @if ($testimoniUseCustomBg && strtoupper($testimoniBgColor) === $preset['value'])
                                                <i class="fa-solid fa-check text-xs drop-shadow-sm" style="color: {{ $preset['check'] }};"></i>
                                            @endif
                                        </span>
                                        <span class="max-w-14 truncate text-[10px] text-admin-ink-soft">{{ $preset['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <p class="text-xs text-admin-ink-soft">
                            Kalau tidak dicentang, section ini ikut warna krem kanvas bawaan.
                        </p>
                    </div>

                    {{-- WARNA TOP 3 KOMENTAR (KARTU TESTIMONI) --}}
                    {{-- Tiap posisi (Top 1/2/3) independen -- boleh isi salah satu, semua, atau tidak
                    sama sekali. Posisi yang tidak dicentang otomatis fallback ke pola bawaan
                    (bergantian gelap/krem berdasarkan posisi kartu). --}}
                    @php
                        $testimoniCardRankLabels = [1 => 'Top 1', 2 => 'Top 2', 3 => 'Top 3'];
                    @endphp
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Top 3 Komentar (Kartu Testimoni)</p>
                            <p class="mt-1 text-xs text-admin-ink-soft">
                                Atur warna kartu per posisi. Posisi yang tidak dicentang ikut warna
                                bergantian gelap/krem seperti biasa -- warna nama, komentar, dan badge
                                produk otomatis menyesuaikan (terang/gelap) di kartu yang dicentang.
                            </p>
                        </div>

                        <div class="space-y-5 divide-y divide-admin-border">
                            @foreach ($testimoniCardRankLabels as $rank => $rankLabel)
                                <div class="{{ $loop->first ? '' : 'pt-5' }} space-y-3">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <label class="flex items-center gap-2 text-sm text-admin-ink">
                                            <input type="checkbox" wire:model.live="testimoniUseCustomCardColor.{{ $rank }}" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                            <span class="rounded-full bg-admin-cream px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-admin-ink-soft">{{ $rankLabel }}</span>
                                            Pakai warna kartu khusus
                                        </label>
                                        <input
                                            type="color" wire:model="testimoniCardColor.{{ $rank }}"
                                            @disabled(! $testimoniUseCustomCardColor[$rank])
                                            class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:cursor-not-allowed disabled:opacity-40"
                                        >
                                    </div>

                                    <div>
                                        <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-admin-ink-soft">Atau pilih dari rekomendasi</p>
                                        <div class="flex flex-wrap gap-2.5">
                                            @foreach ($testimoniCardPresets as $preset)
                                                <button
                                                    type="button"
                                                    wire:click="selectTestimoniCardPreset({{ $rank }}, '{{ $preset['value'] }}')"
                                                    title="{{ $preset['label'] }}"
                                                    class="group flex flex-col items-center gap-1"
                                                >
                                                    <span
                                                        class="flex h-9 w-9 items-center justify-center rounded-full shadow-sm ring-1 ring-inset ring-white/40 transition duration-200 group-hover:scale-110 group-hover:shadow-md
                                                        {{ $testimoniUseCustomCardColor[$rank] && strtoupper($testimoniCardColor[$rank]) === $preset['value']
                                                            ? 'border-2 border-admin-accent ring-2 ring-admin-accent ring-offset-2 ring-offset-admin-surface'
                                                            : 'border border-admin-border group-hover:border-admin-accent/60' }}"
                                                        style="background: linear-gradient(135deg, color-mix(in oklab, {{ $preset['value'] }} 100%, white 30%), {{ $preset['value'] }} 55%, color-mix(in oklab, {{ $preset['value'] }} 100%, black 16%));"
                                                    >
                                                        @if ($testimoniUseCustomCardColor[$rank] && strtoupper($testimoniCardColor[$rank]) === $preset['value'])
                                                            <i class="fa-solid fa-check text-xs drop-shadow-sm" style="color: {{ $preset['check'] }};"></i>
                                                        @endif
                                                    </span>
                                                    <span class="max-w-14 truncate text-[10px] text-admin-ink-soft">{{ $preset['label'] }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            wire:loading.attr="disabled" wire:target="saveTestimoni"
                            class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition-all duration-200 hover:bg-admin-accent-strong active:scale-[0.99] disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="saveTestimoni" class="flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan
                            </span>
                            <span wire:loading wire:target="saveTestimoni" class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch animate-spin"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            @elseif ($activeSection === 'keahlian')
                <form wire:submit="saveKeahlian" class="space-y-6">

                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-award text-admin-accent"></i>
                            Kenapa Pilih Kami (section ke-7 Beranda)
                        </h3>
                        <p class="text-xs text-admin-ink-soft">
                            Section split foto kiri / teks kanan, tepat setelah Testimoni Pelanggan. Ikon sosial
                            di bawah checklist tidak diedit di sini (Instagram, TikTok, Facebook selalu tetap,
                            WhatsApp ambil otomatis dari Pengaturan).
                        </p>
                    </div>

                    {{-- FOTO --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4" x-data="keahlianFotoCropper(@js($this->keahlianImagePreviewUrl))">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Foto</p>

                        <div class="flex flex-col items-center gap-5 sm:flex-row">
                            <div class="relative w-32 shrink-0">
                                <img :src="previewUrl" alt="Preview foto Kenapa Pilih Kami" class="aspect-10/9 w-32 rounded-2xl object-cover ring-4 ring-admin-cream">

                                <label
                                    for="keahlian_image_input"
                                    class="absolute -bottom-1 -right-1 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-admin-accent text-white shadow-sm ring-2 ring-admin-surface transition hover:bg-admin-accent-strong"
                                    title="Ganti foto"
                                >
                                    <i class="fa-solid fa-camera text-xs"></i>
                                </label>
                                <input x-ref="fileInput" id="keahlian_image_input" type="file" accept="image/*" class="hidden" x-on:change="onFileChange($event)">
                            </div>

                            <div class="text-center sm:text-left">
                                <p class="text-sm font-medium text-admin-ink">Klik ikon kamera untuk ganti foto</p>
                                <p class="mt-1 text-xs text-admin-ink-soft">
                                    Sebaiknya foto workshop, pengrajin, atau proses pembuatan -- geser untuk
                                    memindah posisi &amp; pakai slider untuk zoom, sama seperti foto lainnya.
                                </p>
                            </div>
                        </div>

                        <template x-teleport="body">
                            <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-999 flex items-center justify-center bg-black/60 p-4" style="display: none;">
                                <div x-show="open" x-transition.scale.origin.center @click.outside="cancelCrop()" class="w-full max-w-md rounded-2xl bg-admin-surface p-6 shadow-2xl">
                                    <h4 class="mb-1 text-sm font-semibold text-admin-ink">Sesuaikan Foto Kenapa Pilih Kami</h4>
                                    <p class="mb-4 text-xs text-admin-ink-soft">Geser gambar untuk memindah, gunakan slider untuk zoom. Rasio 10:9.</p>
                                    <div x-ref="viewport" class="relative mx-auto aspect-10/9 w-full max-w-80 cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none" x-on:pointerdown="startDrag($event)" x-on:pointermove="onDrag($event)" x-on:pointerup="endDrag()" x-on:pointerleave="endDrag()">
                                        <img x-ref="cropImg" :src="rawImage" x-on:load="onImgLoad($event)" draggable="false" class="absolute left-0 top-0 max-w-none origin-top-left select-none" :style="`width:${natW * scale}px; height:${natH * scale}px; transform: translate(${posX}px, ${posY}px);`">
                                    </div>
                                    <div class="mt-4 flex items-center gap-3">
                                        <i class="fa-solid fa-magnifying-glass-minus text-xs text-admin-ink-soft"></i>
                                        <input type="range" min="0" max="100" x-model.number="zoomPercent" x-on:input="applyZoom()" class="h-1.5 w-full cursor-pointer appearance-none rounded-full bg-admin-border accent-admin-accent">
                                        <i class="fa-solid fa-magnifying-glass-plus text-xs text-admin-ink-soft"></i>
                                    </div>
                                    <div class="mt-5 flex justify-end gap-2">
                                        <button type="button" x-on:click="cancelCrop()" class="rounded-full border border-admin-border px-4 py-2 text-xs font-semibold text-admin-ink-soft transition hover:bg-admin-cream">Batal</button>
                                        <button type="button" x-on:click="confirmCrop()" class="rounded-full bg-admin-accent px-4 py-2 text-xs font-semibold text-white transition hover:bg-admin-accent-strong">Gunakan Foto Ini</button>
                                    </div>
                                    <canvas x-ref="cropCanvas" class="hidden"></canvas>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- ISI TEKS --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Isi Teks</p>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Label kecil (di atas judul)</label>
                                <input
                                    type="text" maxlength="40" wire:model="keahlianBadgeText"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('keahlianBadgeText')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul</label>
                                <input
                                    type="text" maxlength="60" wire:model="keahlianTitle"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('keahlianTitle')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Deskripsi</label>
                                <textarea
                                    rows="3" maxlength="400" wire:model="keahlianDescription"
                                    class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                ></textarea>
                                @error('keahlianDescription')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <p class="mb-2 text-sm font-medium text-admin-ink">Checklist (2 poin di bawah deskripsi)</p>
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ($keahlianChecklist as $i => $point)
                                    <div class="space-y-2 rounded-lg border border-admin-border p-3">
                                        <textarea
                                            rows="2" maxlength="150" placeholder="Poin ke-{{ $i + 1 }}"
                                            wire:model="keahlianChecklist.{{ $i }}"
                                            class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-2.5 py-2 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                        ></textarea>
                                        @error("keahlianChecklist.{$i}")<p class="text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            wire:loading.attr="disabled" wire:target="saveKeahlian"
                            class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition-all duration-200 hover:bg-admin-accent-strong active:scale-[0.99] disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="saveKeahlian" class="flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan
                            </span>
                            <span wire:loading wire:target="saveKeahlian" class="flex items-center gap-2">
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

    // Sama persis strukturnya dengan heroImageCropper di atas -- rasio sama
    // (10:9) tapi target property ($wire.set) beda, buat foto section
    // "Kenapa Pilih Kami".
    Alpine.data('keahlianFotoCropper', (existingPreviewUrl) => ({
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
            this.$wire.set('keahlianImageCroppedBase64', dataUrl);
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

    // Sama persis strukturnya dengan heroImageCropper di atas -- cuma rasio
    // (3:4, mengikuti kolase foto besar di partials/frontend/mission.blade.php)
    // dan target property ($wire.set) yang beda.
    Alpine.data('missionFotoBesarCropper', (existingPreviewUrl) => ({
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

        ASPECT_W: 3,
        ASPECT_H: 4,

        viewW: 0,
        viewH: 0,

        OUT_W: 900,
        OUT_H: 1200,

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
            this.$wire.set('missionImageBesarCroppedBase64', dataUrl);
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

    // Sama persis strukturnya dengan missionFotoBesarCropper di atas -- cuma
    // rasio (1:1, mengikuti kolase foto kecil) dan target property yang beda.
    Alpine.data('missionFotoKecilCropper', (existingPreviewUrl) => ({
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

        ASPECT_W: 1,
        ASPECT_H: 1,

        viewW: 0,
        viewH: 0,

        OUT_W: 900,
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
            this.$wire.set('missionImageKecilCroppedBase64', dataUrl);
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

    Alpine.data('missionStatBgCropper', (existingPreviewUrl) => ({
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

        ASPECT_W: 3,
        ASPECT_H: 5,

        viewW: 0,
        viewH: 0,

        OUT_W: 900,
        OUT_H: 1500,

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
            this.$wire.set('missionStatBgCroppedBase64', dataUrl);
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