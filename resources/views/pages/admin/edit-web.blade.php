<?php

use App\Models\HomeSection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin-panel')] #[Title('Edit Web')] class extends Component
{
    // WithFileUploads dipakai KHUSUS untuk upload video "Kenapa Pilih Kami"
    // langsung dari perangkat admin (lihat $keahlianVideoUpload di bawah).
    // Foto-foto lain di halaman ini tetap pakai alur base64 dari cropper
    // Alpine (tidak disentuh) -- jadi trait ini tidak mengubah perilaku upload manapun yang sudah ada.
    use WithFileUploads;

    // State & aksi gradasi "Warna Frame" (tombol Gradasi di tiap section) --
    // lihat app/Support/HasFrameGradients.php.
    use \App\Support\HasFrameGradients;

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
        ['key' => 'faq', 'label' => 'FAQ', 'icon' => 'fa-circle-question', 'ready' => true],
        ['key' => 'lokasi', 'label' => 'Lokasi', 'icon' => 'fa-location-dot', 'ready' => true],
    ];

    public string $activeSection = 'header';

    /**
     * Daftar section untuk grup "Tentang Kami". Kartunya sendiri sudah
     * bisa diklik (lihat $groups di bawah), tapi tiap section di sini
     * SENGAJA masih `ready => false` semua -- formnya akan diaktifkan
     * satu per satu menyusul, sama seperti pola $sections (Beranda) di atas.
     */
    public array $tentangKamiSections = [
        ['key' => 'profil-toko', 'label' => 'Tentang Kami', 'icon' => 'fa-store', 'ready' => true],
        ['key' => 'tentang-kami-2', 'label' => 'Tentang Kami 2', 'icon' => 'fa-couch', 'ready' => true],
        ['key' => 'sejarah', 'label' => 'Sejarah', 'icon' => 'fa-clock-rotate-left', 'ready' => true],
        ['key' => 'nilai-kami', 'label' => 'Nilai Kami', 'icon' => 'fa-gem', 'ready' => true],
        ['key' => 'keberlanjutan', 'label' => 'Keberlanjutan', 'icon' => 'fa-leaf', 'ready' => false],
        ['key' => 'karier', 'label' => 'Karier', 'icon' => 'fa-briefcase', 'ready' => false],
    ];

    /**
     * Section Hero halaman "Tentang Kami" (frontend: /profil, section
     * paling atas -- lihat resources/views/pages/frontend/profil.blade.php
     * bagian A). Yang bisa diedit: label kecil di atas judul, judul
     * (2 baris), paragraf, dan foto kanan. Tombol "Lihat Produk" &
     * "Hubungi Kami" TIDAK diedit di sini (link & tulisan tetap, sama
     * seperti tombol CTA Header Beranda).
     */
    public string $profilHeroEyebrow = 'Tentang Kami';

    public string $profilHeroHeadingLine1 = 'Mewujudkan Ruang';

    public string $profilHeroHeadingLine2 = 'yang Punya Cerita.';

    public string $profilHeroDescription = '';

    public ?string $profilHeroFotoCroppedBase64 = null;

    public ?string $profilHeroFotoPathLama = null;

    /**
     * Daftar section untuk grup "Dokumentasi". Cuma 1 section (hero teks
     * saja, tidak ada sub-halaman lain seperti Sejarah/Pengrajin dst di
     * "Tentang Kami"), jadi langsung `ready => true`.
     */
    public array $dokumentasiSections = [
        ['key' => 'dokumentasi', 'label' => 'Dokumentasi', 'icon' => 'fa-images', 'ready' => true],
    ];

    /**
     * Hero halaman "Dokumentasi" (frontend: /dokumentasi -- lihat
     * resources/views/pages/frontend/booking.blade.php). Yang bisa diedit:
     * judul, subjudul (italic), dan deskripsi. Kolom kanan sengaja
     * dikosongkan di halaman itu, jadi tidak ada foto untuk diedit di sini.
     */
    public string $dokumentasiJudul = 'Dokumentasi';

    public string $dokumentasiSubjudul = 'Jejak karya dan proses kerja Karya Ide Edi';

    public string $dokumentasiDeskripsi = '';

    /**
     * Media video kolom kanan hero Dokumentasi. Pengisiannya SAMA PERSIS
     * dengan "Kenapa Pilih Kami" di Beranda: video dari tautan (YouTube,
     * TikTok, Instagram, Facebook, Google Drive, atau link file video
     * langsung) ATAU upload video dari perangkat -- lihat
     * App\Models\HomeSection::classifyVideoUrl() dan $keahlianVideoUpload
     * di atas untuk pola yang sama.
     */
    public string $dokumentasiMediaType = 'video_url';

    public string $dokumentasiVideoUrl = '';

    public $dokumentasiVideoUpload = null;

    public ?string $dokumentasiVideoPathLama = null;

    /**
     * Halaman apa saja yang bisa dikelola dari "Edit Web", ditampilkan
     * sebagai KARTU PILIHAN dulu (bukan langsung sidebar tab). Klik kartu
     * yang `ready` => baru masuk ke daftar section (sidebar + form)
     * halaman itu, lihat $activeGroup & selectGroup() di bawah.
     *
     * "Tentang Kami" kartunya sudah `ready` (bisa diklik), TAPI semua
     * section di dalamnya ($tentangKamiSections di atas) masih
     * `ready => false` -- jadi begitu masuk, sidebar tampil tapi setiap
     * item masih berlabel "Segera" dan tidak bisa diklik, sampai
     * diaktifkan satu per satu menyusul.
     */
    public array $groups = [
        [
            'key' => 'beranda',
            'label' => 'Beranda',
            'icon' => 'fa-house',
            'description' => 'Header, Keunggulan, Sejak Berdiri, Produk Unggulan, Kategori Produk, Testimoni, Kenapa Pilih Kami, FAQ, Lokasi.',
            'ready' => true,
        ],
        [
            'key' => 'tentang-kami',
            'label' => 'Tentang Kami',
            'icon' => 'fa-circle-info',
            'description' => 'Tentang Kami, Sejarah, Nilai Kami, Keberlanjutan, Karier.',
            'ready' => true,
        ],
        [
            'key' => 'dokumentasi',
            'label' => 'Dokumentasi',
            'icon' => 'fa-images',
            'description' => 'Judul, subjudul, dan deskripsi halaman Dokumentasi.',
            'ready' => true,
        ],
    ];

    /** Kartu mana yang lagi aktif di "Edit Web". 'menu' = tampil grid kartu pilihan. */
    public string $activeGroup = 'menu';

    /** Pindah antara grid kartu pilihan ('menu') dan halaman yang dipilih. Kartu yang belum `ready` diabaikan. */
    public function selectGroup(string $key): void
    {
        if ($key !== 'menu') {
            $group = collect($this->groups)->firstWhere('key', $key);

            if (! $group || ! $group['ready']) {
                return;
            }
        }

        $this->activeGroup = $key;

        // Reset activeSection ke default grup yang baru dipilih, supaya
        // sidebar & panel tidak "nyangkut" di section grup sebelumnya.
        $this->activeSection = match ($key) {
            'tentang-kami' => $this->tentangKamiSections[0]['key'],
            'dokumentasi' => $this->dokumentasiSections[0]['key'],
            'beranda' => 'header',
            default => $this->activeSection,
        };
    }

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
     * Palet warna rekomendasi untuk: Header (section paling atas Beranda).
     * SATU daftar yang sama untuk SEMUA pilihan warna di Edit Web -- ubah/tambah
     * warna di app/Support/ColorPalette.php, jangan bikin daftar sendiri di sini.
     *
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public array $headerBgPresets = \App\Support\ColorPalette::PRESETS;

    /**
     * Override warna teks (judul/deskripsi/statistik/divider) header secara
     * manual. Bawaan (false) tetap pakai kontras otomatis dari $heroBgColor
     * (lihat contrastTextColors() di partials/frontend/hero.blade.php) --
     * tidak mengubah tampilan situs yang sudah berjalan kalau admin tidak
     * menyentuh opsi ini sama sekali.
     */
    public bool $headerUseCustomTextColor = false;

    /** @var 'black'|'white' */
    public string $headerTextColor = 'black';

    /**
     * Cahaya (glow) opsional di belakang judul/deskripsi header, supaya
     * teks tetap kebaca kalau foto latar terlalu terang/ramai. Mati
     * secara bawaan.
     */
    public bool $headerTextGlowEnabled = false;

    /** @var 'black'|'white' */
    public string $headerTextGlowColor = 'white';

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
     * Palet warna rekomendasi untuk: Sejak Berdiri.
     * SATU daftar yang sama untuk SEMUA pilihan warna di Edit Web -- ubah/tambah
     * warna di app/Support/ColorPalette.php, jangan bikin daftar sendiri di sini.
     *
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public array $missionBgPresets = \App\Support\ColorPalette::PRESETS;

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
     * Palet warna rekomendasi untuk: Produk Unggulan.
     * SATU daftar yang sama untuk SEMUA pilihan warna di Edit Web -- ubah/tambah
     * warna di app/Support/ColorPalette.php, jangan bikin daftar sendiri di sini.
     *
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public array $produkUnggulanBgPresets = \App\Support\ColorPalette::PRESETS;

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
     * Palet warna rekomendasi untuk: Kategori Produk.
     * SATU daftar yang sama untuk SEMUA pilihan warna di Edit Web -- ubah/tambah
     * warna di app/Support/ColorPalette.php, jangan bikin daftar sendiri di sini.
     *
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public array $kategoriBgPresets = \App\Support\ColorPalette::PRESETS;

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
     * Palet warna rekomendasi untuk: Testimoni Pelanggan (warna frame section).
     * SATU daftar yang sama untuk SEMUA pilihan warna di Edit Web -- ubah/tambah
     * warna di app/Support/ColorPalette.php, jangan bikin daftar sendiri di sini.
     *
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public array $testimoniBgPresets = \App\Support\ColorPalette::PRESETS;

    /**
     * Palet warna rekomendasi untuk: Testimoni Pelanggan (warna kartu Top 1/2/3).
     * SATU daftar yang sama untuk SEMUA pilihan warna di Edit Web -- ubah/tambah
     * warna di app/Support/ColorPalette.php, jangan bikin daftar sendiri di sini.
     *
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public array $testimoniCardPresets = \App\Support\ColorPalette::PRESETS;

    // ================= KENAPA PILIH KAMI (section Expertise) =================

    public string $keahlianBadgeText = '';

    public string $keahlianTitle = '';

    public string $keahlianDescription = '';

    /** @var array<int, string> Selalu tepat 2 item (checklist di bawah deskripsi). */
    public array $keahlianChecklist = [];

    /**
     * Path gambar sampul/poster yang dulu tersimpan lewat tab "Foto" (tab ini
     * sudah dihapus -- section ini sekarang khusus video). Nilai lama tetap
     * dipertahankan apa adanya supaya tidak menghapus data yang sudah ada,
     * dan tetap dipakai di partials/frontend/expertise.blade.php sebagai
     * poster video / fallback kalau video belum diisi sama sekali.
     */
    public ?string $keahlianImagePathLama = null;

    /**
     * Jenis media yang tampil di panel kiri section ini:
     * - 'video_url'      : video dari tautan (YouTube, TikTok, Instagram,
     *                       Facebook, Google Drive, atau link video langsung).
     * - 'video_upload'   : video yang diunggah langsung dari perangkat admin.
     *
     * Section ini khusus video (bukan foto statis) -- nilai video_url &
     * video_path TETAP disimpan meski media_type sedang bukan salah
     * satunya, supaya admin gonta-ganti tab tidak kehilangan data yang
     * sudah pernah diisi/diunggah sebelumnya.
     */
    public string $keahlianMediaType = 'video_url';

    public string $keahlianVideoUrl = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $keahlianVideoUpload = null;

    public ?string $keahlianVideoPathLama = null;

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
            'media_type' => 'video_url',
            'video_url' => null,
            'video_path' => null,
        ];
    }

    // ================= FAQ (section FAQ) =================

    /** @var array<int, array{q: string, a: string}> Selalu tepat 6 item (accordion pertanyaan & jawaban). */
    public array $faqItems = [];

    private function faqDefaults(): array
    {
        return [
            'items' => [
                [
                    'q' => 'Bagaimana cara memesan produk di '.\App\Models\Setting::current()->site_name.'?',
                    'a' => 'Pilih produk yang kamu suka di halaman Produk, lalu hubungi admin lewat tombol WhatsApp untuk konsultasi ukuran, bahan, dan harga. Pesanan baru tercatat resmi setelah kesepakatan dikonfirmasi lewat WhatsApp — lihat halaman Booking untuk alur lengkapnya.',
                ],
                [
                    'q' => 'Apakah bisa pesan furniture custom sesuai ukuran ruangan saya?',
                    'a' => 'Bisa. Sampaikan ukuran, bahan, dan kebutuhan khususmu ke admin lewat WhatsApp saat proses booking — kami sesuaikan sebelum pesanan diproses.',
                ],
                [
                    'q' => 'Berapa lama proses pengerjaan dan pengirimannya?',
                    'a' => 'Estimasi waktu pengerjaan dan biaya ongkir menyesuaikan lokasi serta ukuran pesanan, dan akan diinfokan admin sebelum pesanan difinalkan lewat WhatsApp.',
                ],
                [
                    'q' => 'Bagaimana kalau produk yang sampai cacat atau tidak sesuai?',
                    'a' => 'Kami kasih garansi retur 30 hari sejak barang diterima. Kerusakan akibat cacat produksi kami tanggung — cukup kirim foto/video kondisi barang lewat WhatsApp untuk klaim.',
                ],
                [
                    'q' => 'Bagaimana cara memantau status pesanan saya?',
                    'a' => 'Setelah pesanan dikonfirmasi admin, kamu akan mendapat link tracking pribadi yang bisa dibuka kapan saja di halaman Lacak Pesanan.',
                ],
                [
                    'q' => 'Apakah saya bisa lihat detail dan foto produk sebelum booking?',
                    'a' => 'Tentu — buka halaman Produk untuk melihat foto, kategori, dan harga tiap produk sebelum menghubungi admin untuk melanjutkan pemesanan.',
                ],
            ],
        ];
    }

    // ================= LOKASI (section "Kunjungi Kami") =================

    /**
     * Section Lokasi = section paling bawah Beranda, tepat sebelum Footer
     * (lihat partials/frontend/lokasi.blade.php). Yang bisa diedit di sini:
     * teks kiri (label, judul 2 baris, paragraf) dan tautan Google Maps.
     *
     * Alamat yang tampil di teks TETAP ikut Admin > Pengaturan (tidak
     * diedit dari sini) -- begitu juga tulisan pada ILUSTRASI peta
     * (gambarnya SVG dekoratif, bukan embed Google Maps asli), sudah
     * tidak bisa diubah manual karena sepenuhnya mengikuti tautan Google
     * Maps yang admin masukkan (satu paket, otomatis).
     *
     * Tautan Maps SENGAJA boleh dikosongkan. Kalau dikosongkan, frontend
     * otomatis membangun tautan dari nama toko + alamat -- persis
     * perilaku sebelum section ini bisa diedit.
     */
    public string $lokasiEyebrow = 'Kunjungi Kami';

    public string $lokasiHeadingLine1 = 'Datang Langsung ke';

    public string $lokasiHeadingLine2 = 'Workshop Kami';

    public string $lokasiDescription = '';

    public string $lokasiMapsUrl = '';

    public bool $lokasiUseCustomBg = false;

    public string $lokasiBgColor = '#FFFFFF';

    /**
     * Palet warna rekomendasi untuk: Lokasi.
     * SATU daftar yang sama untuk SEMUA pilihan warna di Edit Web -- ubah/tambah
     * warna di app/Support/ColorPalette.php, jangan bikin daftar sendiri di sini.
     *
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public array $lokasiBgPresets = \App\Support\ColorPalette::PRESETS;

    private function lokasiDefaults(): array
    {
        return [
            'bg_color' => null,
            'eyebrow' => 'Kunjungi Kami',
            'heading_line1' => 'Datang Langsung ke',
            'heading_line2' => 'Workshop Kami',
            'description' => 'Lihat langsung kualitas material dan proses pembuatan furnitur '.\App\Models\Setting::current()->site_name.' sebelum memutuskan pesan. Kami dengan senang hati menyambut kunjungan Anda.',
            'maps_url' => null,
        ];
    }

    private function headerDefaults(): array
    {
        return [
            'bg_color' => null,
            'text_color' => null,
            'text_glow' => null,
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

    private function profilHeroDefaults(): array
    {
        return [
            'eyebrow' => 'Tentang Kami',
            'heading_line1' => 'Mewujudkan Ruang',
            'heading_line2' => 'yang Punya Cerita.',
            'description' => 'Karya Ide Edi menghadirkan furnitur yang dibuat dengan teliti untuk melengkapi ruang Anda \u2014 bukan sekadar mengisinya. Setiap karya dirancang untuk nyaman digunakan sekaligus enak dipandang, untuk rumah maupun ruang kerja.',
            'image_path' => null,
        ];
    }

    // ================= SEJARAH (halaman Tentang Kami) =================

    /**
     * Section "Sejarah" halaman Tentang Kami (frontend: /profil, blok
     * id="sejarah" tepat setelah section "Lebih dari sekadar furniture" --
     * lihat resources/views/pages/frontend/profil.blade.php). Yang bisa
     * diedit: warna frame (warna polos + gradasi opsional), foto, dan isi
     * teks (label kecil, judul 2 baris, paragraf). Disimpan di home_sections
     * dengan section_key 'sejarah'.
     */
    public string $sejarahEyebrow = 'Sejarah Kami';

    public string $sejarahHeadingLine1 = 'Perjalanan Kami';

    public string $sejarahHeadingLine2 = 'dari Awal Hingga Kini.';

    public string $sejarahDescription = '';

    /** Hasil crop rasio 4:5 (data URL base64 JPEG). Null = foto tidak diganti. */
    public ?string $sejarahFotoCroppedBase64 = null;

    public ?string $sejarahFotoPathLama = null;

    public bool $sejarahUseCustomBg = false;

    public string $sejarahBgColor = '#FFFFFF';

    /**
     * Palet warna rekomendasi untuk: Sejarah.
     * SATU daftar yang sama untuk SEMUA pilihan warna di Edit Web -- ubah/tambah
     * warna di app/Support/ColorPalette.php, jangan bikin daftar sendiri di sini.
     *
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public array $sejarahBgPresets = \App\Support\ColorPalette::PRESETS;

    /**
     * Nilai bawaan HARUS sama dengan yang ada di
     * resources/views/pages/frontend/profil.blade.php (blok id="sejarah").
     */
    private function sejarahDefaults(): array
    {
        $siteName = \App\Models\Setting::current()->site_name;

        return [
            'bg_color' => null,
            'eyebrow' => 'Sejarah Kami',
            'heading_line1' => 'Perjalanan Kami',
            'heading_line2' => 'dari Awal Hingga Kini.',
            'description' => $siteName." terus tumbuh dari satu karya ke karya berikutnya, dibuat dengan tangan dan dengan perhatian pada setiap detail.\n\nSetiap pesanan menjadi kesempatan untuk belajar, menjaga mutu, dan membuat furnitur yang benar-benar dipakai dan disukai pelanggan.",
            'image_path' => null,
        ];
    }

    // ================= TENTANG KAMI 2 (halaman Tentang Kami) =================

    /**
     * Section ke-2 halaman Tentang Kami (frontend: /profil, foto di kiri + teks
     * "Lebih dari sekadar furniture." di kanan, tepat di bawah Hero -- lihat
     * resources/views/pages/frontend/profil.blade.php, bagian B). Yang bisa
     * diedit: warna frame (warna polos + gradasi opsional), foto, dan isi teks
     * (label kecil, judul, paragraf). Disimpan di home_sections dengan
     * section_key 'tentang-kami-2'. Tampilan bawaan SAMA dengan sebelum
     * section ini bisa diedit.
     */
    public string $tentangKami2Eyebrow = 'Tentang Kami';

    public string $tentangKami2Heading = 'Lebih dari sekadar furniture.';

    public string $tentangKami2Description = '';

    /** Hasil crop rasio 4:5 (data URL base64 JPEG). Null = foto tidak diganti. */
    public ?string $tentangKami2FotoCroppedBase64 = null;

    public ?string $tentangKami2FotoPathLama = null;

    public bool $tentangKami2UseCustomBg = false;

    /** Kira-kira warna bawaan section ini (krem sangat lembut) -- hanya awalan color picker. */
    public string $tentangKami2BgColor = '#FBF8F3';

    /**
     * Palet warna rekomendasi untuk: Tentang Kami 2.
     * SATU daftar yang sama untuk SEMUA pilihan warna di Edit Web -- ubah/tambah
     * warna di app/Support/ColorPalette.php, jangan bikin daftar sendiri di sini.
     *
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public array $tentangKami2BgPresets = \App\Support\ColorPalette::PRESETS;

    /**
     * Nilai bawaan HARUS sama dengan yang ada di
     * resources/views/pages/frontend/profil.blade.php (bagian B).
     */
    private function tentangKami2Defaults(): array
    {
        $siteName = \App\Models\Setting::current()->site_name;

        return [
            'bg_color' => null,
            'eyebrow' => 'Tentang Kami',
            'heading' => 'Lebih dari sekadar furniture.',
            'description' => $siteName." adalah toko furniture yang menghadirkan produk untuk membantu Anda menciptakan ruang yang nyaman, fungsional, dan punya karakter. Kami percaya furnitur yang baik bukan cuma soal bentuk \u{2014} tapi juga soal bagaimana ia membuat ruang terasa lebih hidup untuk dipakai sehari-hari.\n\nDari kebutuhan rumah tangga sampai ruang kerja, setiap produk kami pilih dan siapkan dengan memperhatikan kualitas bahan, kenyamanan pemakaian, dan kejelasan informasi \u{2014} supaya Anda bisa memutuskan dengan tenang.",
            'image_path' => null,
        ];
    }

    // ================= NILAI KAMI (halaman Tentang Kami) =================

    /**
     * Section "Nilai Kami" halaman Tentang Kami (frontend: /profil, blok
     * "C. NILAI / KEUNGGULAN" -- judul di tengah + 4 kartu). Yang bisa
     * diedit: warna frame (warna polos + gradasi opsional), foto tiap kartu,
     * dan isi teks (label kecil, judul, serta judul + deskripsi tiap kartu).
     * Nomor (01-04) & ikon kartu tetap. Disimpan di home_sections dengan
     * section_key 'nilai-kami'.
     */
    public string $nilaiKamiEyebrow = 'Nilai Kami';

    public string $nilaiKamiHeading = 'Yang kami utamakan di setiap karya.';

    /** @var array<int, array{title: string, desc: string}> Selalu tepat 4 kartu. */
    public array $nilaiKamiItems = [];

    /** Hasil crop rasio 4:3 (data URL base64 JPEG) per kartu. Null = foto kartu itu tidak diganti. */
    public array $nilaiKamiFotoCropped = [null, null, null, null];

    /** Path foto tersimpan per kartu. Null = kartu memakai foto produk otomatis (seperti sebelum bisa diedit). */
    public array $nilaiKamiFotoPathLama = [null, null, null, null];

    public bool $nilaiKamiUseCustomBg = false;

    /** Kira-kira warna bawaan section ini (krem) -- hanya awalan color picker. */
    public string $nilaiKamiBgColor = '#F4EDE0';

    /**
     * Palet warna rekomendasi untuk: Nilai Kami.
     * SATU daftar yang sama untuk SEMUA pilihan warna di Edit Web -- ubah/tambah
     * warna di app/Support/ColorPalette.php, jangan bikin daftar sendiri di sini.
     *
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public array $nilaiKamiBgPresets = \App\Support\ColorPalette::PRESETS;

    /**
     * Nilai bawaan HARUS sama dengan yang ada di
     * resources/views/pages/frontend/profil.blade.php (bagian C).
     */
    private function nilaiKamiDefaults(): array
    {
        return [
            'bg_color' => null,
            'eyebrow' => 'Nilai Kami',
            'heading' => 'Yang kami utamakan di setiap karya.',
            'items' => [
                ['title' => 'Kualitas Terpilih', 'desc' => 'Produk dipilih dengan mempertimbangkan kualitas dan fungsi.', 'image_path' => null],
                ['title' => 'Desain Berkarakter', 'desc' => 'Furniture yang dirancang untuk melengkapi berbagai gaya ruang.', 'image_path' => null],
                ['title' => 'Pelayanan Terpercaya', 'desc' => 'Memberikan pengalaman belanja yang nyaman dan jelas.', 'image_path' => null],
                ['title' => 'Untuk Setiap Ruang', 'desc' => 'Pilihan furniture untuk kebutuhan rumah maupun ruang kerja.', 'image_path' => null],
            ],
        ];
    }

    private function dokumentasiDefaults(): array
    {
        return [
            'judul' => 'Dokumentasi',
            'subjudul' => 'Jejak karya dan proses kerja Karya Ide Edi',
            'deskripsi' => 'Kumpulan foto dan video hasil pekerjaan serta proses pembuatan furnitur Karya Ide Edi, sebagai gambaran kualitas dan ketelitian kami di setiap karya.',
            'media_type' => 'video_url',
            'video_url' => null,
            'video_path' => null,
        ];
    }

    public function mount(): void
    {
        $defaults = $this->headerDefaults();
        $data = HomeSection::dataFor('header', $defaults);

        $this->headerUseCustomBg = filled($data['bg_color']);
        $this->headerBgColor = $data['bg_color'] ?: $this->headerBgColor;

        $this->headerUseCustomTextColor = filled($data['text_color']);
        $this->headerTextColor = $data['text_color'] ?: $this->headerTextColor;

        $this->headerTextGlowEnabled = filled($data['text_glow']);
        $this->headerTextGlowColor = $data['text_glow'] ?: $this->headerTextGlowColor;

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
        $this->keahlianMediaType = in_array($keahlianData['media_type'] ?? null, ['video_url', 'video_upload'], true)
            ? $keahlianData['media_type']
            : 'video_url';
        $this->keahlianVideoUrl = $keahlianData['video_url'] ?? '';
        $this->keahlianVideoPathLama = $keahlianData['video_path'] ?? null;

        $faqDefaults = $this->faqDefaults();
        $faqData = HomeSection::dataFor('faq', $faqDefaults);
        $this->faqItems = $faqData['items'];

        $lokasiDefaults = $this->lokasiDefaults();
        $lokasiData = HomeSection::dataFor('lokasi', $lokasiDefaults);

        $this->lokasiUseCustomBg = filled($lokasiData['bg_color']);
        $this->lokasiBgColor = $lokasiData['bg_color'] ?: $this->lokasiBgColor;
        $this->lokasiEyebrow = $lokasiData['eyebrow'];
        $this->lokasiHeadingLine1 = $lokasiData['heading_line1'];
        $this->lokasiHeadingLine2 = $lokasiData['heading_line2'];
        $this->lokasiDescription = $lokasiData['description'];
        $this->lokasiMapsUrl = (string) ($lokasiData['maps_url'] ?? '');

        $profilHeroDefaults = $this->profilHeroDefaults();
        $profilHeroData = HomeSection::dataFor('profil-toko', $profilHeroDefaults);
        $this->profilHeroEyebrow = $profilHeroData['eyebrow'];
        $this->profilHeroHeadingLine1 = $profilHeroData['heading_line1'];
        $this->profilHeroHeadingLine2 = $profilHeroData['heading_line2'];
        $this->profilHeroDescription = $profilHeroData['description'];
        $this->profilHeroFotoPathLama = $profilHeroData['image_path'];

        $sejarahData = HomeSection::dataFor('sejarah', $this->sejarahDefaults());
        $this->sejarahUseCustomBg = filled($sejarahData['bg_color']);
        $this->sejarahBgColor = $sejarahData['bg_color'] ?: $this->sejarahBgColor;
        $this->sejarahEyebrow = $sejarahData['eyebrow'];
        $this->sejarahHeadingLine1 = $sejarahData['heading_line1'];
        $this->sejarahHeadingLine2 = $sejarahData['heading_line2'];
        $this->sejarahDescription = $sejarahData['description'];
        $this->sejarahFotoPathLama = $sejarahData['image_path'];

        $tentangKami2Data = HomeSection::dataFor('tentang-kami-2', $this->tentangKami2Defaults());
        $this->tentangKami2UseCustomBg = filled($tentangKami2Data['bg_color']);
        $this->tentangKami2BgColor = $tentangKami2Data['bg_color'] ?: $this->tentangKami2BgColor;
        $this->tentangKami2Eyebrow = $tentangKami2Data['eyebrow'];
        $this->tentangKami2Heading = $tentangKami2Data['heading'];
        $this->tentangKami2Description = $tentangKami2Data['description'];
        $this->tentangKami2FotoPathLama = $tentangKami2Data['image_path'];

        $nilaiKamiDefaults = $this->nilaiKamiDefaults();
        $nilaiKamiData = HomeSection::dataFor('nilai-kami', $nilaiKamiDefaults);
        $this->nilaiKamiUseCustomBg = filled($nilaiKamiData['bg_color']);
        $this->nilaiKamiBgColor = $nilaiKamiData['bg_color'] ?: $this->nilaiKamiBgColor;
        $this->nilaiKamiEyebrow = $nilaiKamiData['eyebrow'];
        $this->nilaiKamiHeading = $nilaiKamiData['heading'];

        foreach (range(0, 3) as $i) {
            $item = $nilaiKamiData['items'][$i] ?? $nilaiKamiDefaults['items'][$i];

            $this->nilaiKamiItems[$i] = [
                'title' => (string) ($item['title'] ?? $nilaiKamiDefaults['items'][$i]['title']),
                'desc' => (string) ($item['desc'] ?? $nilaiKamiDefaults['items'][$i]['desc']),
            ];
            $this->nilaiKamiFotoPathLama[$i] = $item['image_path'] ?? null;
        }

        $dokumentasiDefaults = $this->dokumentasiDefaults();
        $dokumentasiData = HomeSection::dataFor('dokumentasi', $dokumentasiDefaults);
        $this->dokumentasiJudul = $dokumentasiData['judul'];
        $this->dokumentasiSubjudul = $dokumentasiData['subjudul'];
        $this->dokumentasiDeskripsi = $dokumentasiData['deskripsi'];
        $this->dokumentasiMediaType = in_array($dokumentasiData['media_type'] ?? null, ['video_url', 'video_upload'], true)
            ? $dokumentasiData['media_type']
            : 'video_url';
        $this->dokumentasiVideoUrl = $dokumentasiData['video_url'] ?? '';
        $this->dokumentasiVideoPathLama = $dokumentasiData['video_path'] ?? null;

        // Gradasi "Warna Frame" tiap section (kalau belum pernah diatur -> mati, frame polos).
        $this->loadFrameGradient('mission', $missionData['bg_gradient'] ?? null);
        $this->loadFrameGradient('produkUnggulan', $produkUnggulanData['bg_gradient'] ?? null);
        $this->loadFrameGradient('kategori', $kategoriData['bg_gradient'] ?? null);
        $this->loadFrameGradient('testimoni', $testimoniData['bg_gradient'] ?? null);
        $this->loadFrameGradient('lokasi', $lokasiData['bg_gradient'] ?? null);
        $this->loadFrameGradient('sejarah', $sejarahData['bg_gradient'] ?? null);
        $this->loadFrameGradient('tentangKami2', $tentangKami2Data['bg_gradient'] ?? null);
        $this->loadFrameGradient('nilaiKami', $nilaiKamiData['bg_gradient'] ?? null);
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

    public function selectLokasiBgPreset(string $hex): void
    {
        $this->lokasiUseCustomBg = true;
        $this->lokasiBgColor = $hex;
    }

    public function selectSejarahBgPreset(string $hex): void
    {
        $this->sejarahUseCustomBg = true;
        $this->sejarahBgColor = $hex;
    }

    public function selectTentangKami2BgPreset(string $hex): void
    {
        $this->tentangKami2UseCustomBg = true;
        $this->tentangKami2BgColor = $hex;
    }

    public function selectNilaiKamiBgPreset(string $hex): void
    {
        $this->nilaiKamiUseCustomBg = true;
        $this->nilaiKamiBgColor = $hex;
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

    /** Foto kanan Hero "Tentang Kami". Fallback ke kursi.png, sama seperti foto ini di frontend sebelum pernah diedit admin. */
    public function getProfilHeroFotoPreviewUrlProperty(): string
    {
        if ($this->profilHeroFotoCroppedBase64) {
            return $this->profilHeroFotoCroppedBase64;
        }

        return $this->profilHeroFotoPathLama
            ? Storage::disk('public')->url($this->profilHeroFotoPathLama)
            : asset('images/admin-login/kursi.png');
    }

    /** Foto section Sejarah. Fallback ke kursi.png, sama seperti tampilan frontend sebelum pernah diedit admin. */
    public function getSejarahFotoPreviewUrlProperty(): string
    {
        if ($this->sejarahFotoCroppedBase64) {
            return $this->sejarahFotoCroppedBase64;
        }

        return $this->sejarahFotoPathLama
            ? Storage::disk('public')->url($this->sejarahFotoPathLama)
            : asset('images/admin-login/kursi.png');
    }

    /** Foto section Tentang Kami 2. Fallback ke hero.png, sama seperti tampilan frontend sebelum pernah diedit admin. */
    public function getTentangKami2FotoPreviewUrlProperty(): string
    {
        if ($this->tentangKami2FotoCroppedBase64) {
            return $this->tentangKami2FotoCroppedBase64;
        }

        return $this->tentangKami2FotoPathLama
            ? Storage::disk('public')->url($this->tentangKami2FotoPathLama)
            : asset('images/admin-login/hero.png');
    }

    /**
     * Foto 4 kartu Nilai Kami untuk pratinjau. Urutan prioritas SAMA dengan
     * tampilan frontend: foto upload admin -> foto produk aktif (bawaan lama)
     * -> null (tampil ikon polos).
     *
     * @return array<int, string|null>
     */
    public function getNilaiKamiFotoPreviewUrlsProperty(): array
    {
        $produk = \App\Models\Product::query()
            ->where('status', 'aktif')
            ->whereNotNull('thumbnail')
            ->orderByDesc('featured')
            ->orderByDesc('created_at')
            ->take(4)
            ->get()
            ->values();

        $urls = [];

        foreach (range(0, 3) as $i) {
            if (filled($this->nilaiKamiFotoCropped[$i] ?? null)) {
                $urls[$i] = $this->nilaiKamiFotoCropped[$i];

                continue;
            }

            if (filled($this->nilaiKamiFotoPathLama[$i] ?? null)) {
                $urls[$i] = Storage::disk('public')->url($this->nilaiKamiFotoPathLama[$i]);

                continue;
            }

            $item = $produk->get($i);
            $urls[$i] = ($item && Storage::disk('public')->exists($item->thumbnail))
                ? Storage::disk('public')->url($item->thumbnail)
                : null;
        }

        return $urls;
    }

    /** URL video yang sudah tersimpan (hasil upload dari perangkat). Null kalau belum pernah upload apapun. */
    public function getKeahlianVideoPreviewUrlProperty(): ?string
    {
        return $this->keahlianVideoPathLama
            ? Storage::disk('public')->url($this->keahlianVideoPathLama)
            : null;
    }

    /** URL video Dokumentasi yang sudah tersimpan (hasil upload dari perangkat). Null kalau belum pernah upload apapun. */
    public function getDokumentasiVideoPreviewUrlProperty(): ?string
    {
        return $this->dokumentasiVideoPathLama
            ? Storage::disk('public')->url($this->dokumentasiVideoPathLama)
            : null;
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
            'headerTextColor' => ['required', 'string', 'in:black,white'],
            'headerTextGlowColor' => ['required', 'string', 'in:black,white'],
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
                'text_color' => $this->headerUseCustomTextColor ? $validated['headerTextColor'] : null,
                'text_glow' => $this->headerTextGlowEnabled ? $validated['headerTextGlowColor'] : null,
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
            ...$this->frameGradientRules('mission'),
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
                'bg_gradient' => $this->frameGradientPayload('mission'),
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
            ...$this->frameGradientRules('produkUnggulan'),
        ]);

        HomeSection::forSection('produk-unggulan')->update([
            'data' => [
                'bg_color' => $this->produkUnggulanUseCustomBg ? $validated['produkUnggulanBgColor'] : null,
                'bg_gradient' => $this->frameGradientPayload('produkUnggulan'),
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveKategori(): void
    {
        $validated = $this->validate([
            'kategoriBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ...$this->frameGradientRules('kategori'),
        ]);

        HomeSection::forSection('kategori')->update([
            'data' => [
                'bg_color' => $this->kategoriUseCustomBg ? $validated['kategoriBgColor'] : null,
                'bg_gradient' => $this->frameGradientPayload('kategori'),
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveTestimoni(): void
    {
        $validated = $this->validate([
            'testimoniBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ...$this->frameGradientRules('testimoni'),
            'testimoniCardColor.1' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'testimoniCardColor.2' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'testimoniCardColor.3' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        HomeSection::forSection('testimoni')->update([
            'data' => [
                'bg_color' => $this->testimoniUseCustomBg ? $validated['testimoniBgColor'] : null,
                'bg_gradient' => $this->frameGradientPayload('testimoni'),
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
        $rules = [
            'keahlianBadgeText' => ['required', 'string', 'max:40'],
            'keahlianTitle' => ['required', 'string', 'max:60'],
            'keahlianDescription' => ['required', 'string', 'max:400'],
            'keahlianChecklist' => ['required', 'array', 'size:2'],
            'keahlianChecklist.*' => ['required', 'string', 'max:150'],
            'keahlianMediaType' => ['required', 'in:video_url,video_upload'],
            'keahlianVideoUrl' => ['nullable', 'url', 'max:2048'],
            'keahlianVideoUpload' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime', 'max:51200'],
        ];

        // Tautan wajib diisi HANYA kalau tab "Video (Tautan URL)" yang aktif.
        if ($this->keahlianMediaType === 'video_url') {
            $rules['keahlianVideoUrl'] = ['required', 'url', 'max:2048'];
        }

        // File upload wajib diisi HANYA kalau tab "Upload dari Perangkat" aktif
        // DAN belum pernah ada video tersimpan sebelumnya (sama pola dengan foto:
        // ganti foto/video itu opsional selama yang lama masih ada).
        if ($this->keahlianMediaType === 'video_upload' && ! $this->keahlianVideoPathLama) {
            $rules['keahlianVideoUpload'] = ['required', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime', 'max:51200'];
        }

        $validated = $this->validate($rules, [
            'keahlianBadgeText.required' => 'Label kecil wajib diisi.',
            'keahlianTitle.required' => 'Judul wajib diisi.',
            'keahlianDescription.required' => 'Deskripsi wajib diisi.',
            'keahlianChecklist.*.required' => 'Poin checklist wajib diisi.',
            'keahlianVideoUrl.required' => 'Tautan video wajib diisi.',
            'keahlianVideoUrl.url' => 'Tautan video tidak valid (harus diawali http:// atau https://).',
            'keahlianVideoUpload.required' => 'Pilih file video dari perangkat terlebih dahulu.',
            'keahlianVideoUpload.mimetypes' => 'Format video harus MP4, WebM, MOV, atau OGG.',
            'keahlianVideoUpload.max' => 'Ukuran video maksimal 50MB.',
        ]);

        // Path gambar sampul lama (dari tab "Foto" yang sudah dihapus) tetap
        // dipertahankan apa adanya -- masih dipakai sebagai poster video /
        // fallback di partials/frontend/expertise.blade.php.
        $imagePath = $this->keahlianImagePathLama;

        // Video baru diunggah dari perangkat -- ganti file lama (kalau ada) dengan yang baru.
        if ($this->keahlianMediaType === 'video_upload' && $this->keahlianVideoUpload) {
            if ($this->keahlianVideoPathLama) {
                Storage::disk('public')->delete($this->keahlianVideoPathLama);
            }

            $extension = $this->keahlianVideoUpload->getClientOriginalExtension() ?: 'mp4';
            $this->keahlianVideoPathLama = $this->keahlianVideoUpload->storeAs(
                'home-sections',
                'keahlian-'.Str::uuid().'.'.$extension,
                'public'
            );
            $this->keahlianVideoUpload = null;
        }

        HomeSection::forSection('keahlian')->update([
            'data' => [
                'badge_text' => $validated['keahlianBadgeText'],
                'title' => $validated['keahlianTitle'],
                'description' => $validated['keahlianDescription'],
                'checklist' => $validated['keahlianChecklist'],
                'image_path' => $imagePath,
                'media_type' => $this->keahlianMediaType,
                'video_url' => $this->keahlianVideoUrl !== '' ? $this->keahlianVideoUrl : null,
                'video_path' => $this->keahlianVideoPathLama,
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveFaq(): void
    {
        $validated = $this->validate([
            'faqItems' => ['required', 'array', 'size:6'],
            'faqItems.*.q' => ['required', 'string', 'max:150'],
            'faqItems.*.a' => ['required', 'string', 'max:500'],
        ], [
            'faqItems.*.q.required' => 'Pertanyaan wajib diisi.',
            'faqItems.*.a.required' => 'Jawaban wajib diisi.',
        ]);

        HomeSection::forSection('faq')->update([
            'data' => [
                'items' => $validated['faqItems'],
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    /**
     * Simpan section Lokasi ("Kunjungi Kami") di Beranda.
     *
     * Alamat & tautan Google Maps boleh kosong -- yang kosong disimpan
     * sebagai null, dan frontend (partials/frontend/lokasi.blade.php)
     * otomatis balik memakai data dari Admin > Pengaturan seperti semula.
     */
    public function saveLokasi(): void
    {
        $rules = [
            'lokasiEyebrow' => ['required', 'string', 'max:40'],
            'lokasiHeadingLine1' => ['required', 'string', 'max:60'],
            'lokasiHeadingLine2' => ['required', 'string', 'max:60'],
            'lokasiDescription' => ['required', 'string', 'max:500'],
            ...$this->frameGradientRules('lokasi'),
        ];

        // Tautan Maps opsional. Aturan `url` sengaja baru dipasang kalau
        // kolomnya memang diisi -- kalau dikosongkan admin, string kosong
        // tidak boleh sampai gagal validasi (nanti jatuh ke tautan otomatis).
        if (trim($this->lokasiMapsUrl) !== '') {
            $rules['lokasiMapsUrl'] = ['url', 'max:2048'];
        }

        $validated = $this->validate($rules, [
            'lokasiEyebrow.required' => 'Label kecil wajib diisi.',
            'lokasiHeadingLine1.required' => 'Judul (baris 1) wajib diisi.',
            'lokasiHeadingLine2.required' => 'Judul (baris 2) wajib diisi.',
            'lokasiDescription.required' => 'Deskripsi wajib diisi.',
            'lokasiMapsUrl.url' => 'Tautan Google Maps tidak valid (harus diawali http:// atau https://).',
        ]);

        HomeSection::forSection('lokasi')->update([
            'data' => [
                'bg_color' => $this->lokasiUseCustomBg ? $this->lokasiBgColor : null,
                'bg_gradient' => $this->frameGradientPayload('lokasi'),
                'eyebrow' => $validated['lokasiEyebrow'],
                'heading_line1' => $validated['lokasiHeadingLine1'],
                'heading_line2' => $validated['lokasiHeadingLine2'],
                'description' => $validated['lokasiDescription'],
                'maps_url' => trim($this->lokasiMapsUrl) !== '' ? trim($this->lokasiMapsUrl) : null,
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveProfilHero(): void
    {
        $validated = $this->validate([
            'profilHeroEyebrow' => ['required', 'string', 'max:40'],
            'profilHeroHeadingLine1' => ['required', 'string', 'max:60'],
            'profilHeroHeadingLine2' => ['required', 'string', 'max:60'],
            'profilHeroDescription' => ['required', 'string', 'max:500'],
            'profilHeroFotoCroppedBase64' => ['nullable', 'string'],
        ], [
            'profilHeroEyebrow.required' => 'Label wajib diisi.',
            'profilHeroHeadingLine1.required' => 'Judul (baris 1) wajib diisi.',
            'profilHeroHeadingLine2.required' => 'Judul (baris 2) wajib diisi.',
            'profilHeroDescription.required' => 'Deskripsi wajib diisi.',
        ]);

        $imagePath = $this->profilHeroFotoPathLama;

        if ($this->profilHeroFotoCroppedBase64) {
            $binary = $this->decodeBase64Image($this->profilHeroFotoCroppedBase64);

            if ($binary !== null) {
                if ($this->profilHeroFotoPathLama) {
                    Storage::disk('public')->delete($this->profilHeroFotoPathLama);
                }

                $imagePath = 'home-sections/profil-hero-'.Str::uuid().'.jpg';
                Storage::disk('public')->put($imagePath, $binary);
                $this->profilHeroFotoPathLama = $imagePath;
                $this->profilHeroFotoCroppedBase64 = null;
            }
        }

        HomeSection::forSection('profil-toko')->update([
            'data' => [
                'eyebrow' => $validated['profilHeroEyebrow'],
                'heading_line1' => $validated['profilHeroHeadingLine1'],
                'heading_line2' => $validated['profilHeroHeadingLine2'],
                'description' => $validated['profilHeroDescription'],
                'image_path' => $imagePath,
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveSejarah(): void
    {
        $validated = $this->validate([
            'sejarahBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ...$this->frameGradientRules('sejarah'),
            'sejarahEyebrow' => ['required', 'string', 'max:40'],
            'sejarahHeadingLine1' => ['required', 'string', 'max:60'],
            'sejarahHeadingLine2' => ['required', 'string', 'max:60'],
            'sejarahDescription' => ['required', 'string', 'max:1500'],
            'sejarahFotoCroppedBase64' => ['nullable', 'string'],
        ], [
            'sejarahEyebrow.required' => 'Label kecil wajib diisi.',
            'sejarahHeadingLine1.required' => 'Judul (baris 1) wajib diisi.',
            'sejarahHeadingLine2.required' => 'Judul (baris 2) wajib diisi.',
            'sejarahDescription.required' => 'Paragraf wajib diisi.',
        ]);

        $imagePath = $this->sejarahFotoPathLama;

        if ($this->sejarahFotoCroppedBase64) {
            $binary = $this->decodeBase64Image($this->sejarahFotoCroppedBase64);

            if ($binary !== null) {
                if ($this->sejarahFotoPathLama) {
                    Storage::disk('public')->delete($this->sejarahFotoPathLama);
                }

                $imagePath = 'home-sections/sejarah-'.Str::uuid().'.jpg';
                Storage::disk('public')->put($imagePath, $binary);
                $this->sejarahFotoPathLama = $imagePath;
                $this->sejarahFotoCroppedBase64 = null;
            }
        }

        HomeSection::forSection('sejarah')->update([
            'data' => [
                'bg_color' => $this->sejarahUseCustomBg ? $validated['sejarahBgColor'] : null,
                'bg_gradient' => $this->frameGradientPayload('sejarah'),
                'eyebrow' => $validated['sejarahEyebrow'],
                'heading_line1' => $validated['sejarahHeadingLine1'],
                'heading_line2' => $validated['sejarahHeadingLine2'],
                'description' => $validated['sejarahDescription'],
                'image_path' => $imagePath,
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveTentangKami2(): void
    {
        $validated = $this->validate([
            'tentangKami2BgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ...$this->frameGradientRules('tentangKami2'),
            'tentangKami2Eyebrow' => ['required', 'string', 'max:40'],
            'tentangKami2Heading' => ['required', 'string', 'max:80'],
            'tentangKami2Description' => ['required', 'string', 'max:1200'],
            'tentangKami2FotoCroppedBase64' => ['nullable', 'string'],
        ], [
            'tentangKami2Eyebrow.required' => 'Label kecil wajib diisi.',
            'tentangKami2Heading.required' => 'Judul wajib diisi.',
            'tentangKami2Description.required' => 'Paragraf wajib diisi.',
        ]);

        $imagePath = $this->tentangKami2FotoPathLama;

        if ($this->tentangKami2FotoCroppedBase64) {
            $binary = $this->decodeBase64Image($this->tentangKami2FotoCroppedBase64);

            if ($binary !== null) {
                if ($this->tentangKami2FotoPathLama) {
                    Storage::disk('public')->delete($this->tentangKami2FotoPathLama);
                }

                $imagePath = 'home-sections/tentang-kami-2-'.Str::uuid().'.jpg';
                Storage::disk('public')->put($imagePath, $binary);
                $this->tentangKami2FotoPathLama = $imagePath;
                $this->tentangKami2FotoCroppedBase64 = null;
            }
        }

        HomeSection::forSection('tentang-kami-2')->update([
            'data' => [
                'bg_color' => $this->tentangKami2UseCustomBg ? $validated['tentangKami2BgColor'] : null,
                'bg_gradient' => $this->frameGradientPayload('tentangKami2'),
                'eyebrow' => $validated['tentangKami2Eyebrow'],
                'heading' => $validated['tentangKami2Heading'],
                'description' => $validated['tentangKami2Description'],
                'image_path' => $imagePath,
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveNilaiKami(): void
    {
        $validated = $this->validate([
            'nilaiKamiBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ...$this->frameGradientRules('nilaiKami'),
            'nilaiKamiEyebrow' => ['required', 'string', 'max:40'],
            'nilaiKamiHeading' => ['required', 'string', 'max:80'],
            'nilaiKamiItems' => ['required', 'array', 'size:4'],
            'nilaiKamiItems.*.title' => ['required', 'string', 'max:40'],
            'nilaiKamiItems.*.desc' => ['required', 'string', 'max:160'],
            'nilaiKamiFotoCropped.*' => ['nullable', 'string'],
        ], [
            'nilaiKamiEyebrow.required' => 'Label kecil wajib diisi.',
            'nilaiKamiHeading.required' => 'Judul wajib diisi.',
            'nilaiKamiItems.*.title.required' => 'Judul kartu wajib diisi.',
            'nilaiKamiItems.*.desc.required' => 'Deskripsi kartu wajib diisi.',
        ]);

        $items = [];

        foreach (range(0, 3) as $i) {
            $imagePath = $this->nilaiKamiFotoPathLama[$i] ?? null;
            $cropped = $this->nilaiKamiFotoCropped[$i] ?? null;

            if ($cropped) {
                $binary = $this->decodeBase64Image($cropped);

                if ($binary !== null) {
                    if ($imagePath) {
                        Storage::disk('public')->delete($imagePath);
                    }

                    $imagePath = 'home-sections/nilai-kami-'.($i + 1).'-'.Str::uuid().'.jpg';
                    Storage::disk('public')->put($imagePath, $binary);
                    $this->nilaiKamiFotoPathLama[$i] = $imagePath;
                    $this->nilaiKamiFotoCropped[$i] = null;
                }
            }

            $items[] = [
                'title' => $validated['nilaiKamiItems'][$i]['title'],
                'desc' => $validated['nilaiKamiItems'][$i]['desc'],
                'image_path' => $imagePath,
            ];
        }

        HomeSection::forSection('nilai-kami')->update([
            'data' => [
                'bg_color' => $this->nilaiKamiUseCustomBg ? $validated['nilaiKamiBgColor'] : null,
                'bg_gradient' => $this->frameGradientPayload('nilaiKami'),
                'eyebrow' => $validated['nilaiKamiEyebrow'],
                'heading' => $validated['nilaiKamiHeading'],
                'items' => $items,
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveDokumentasi(): void
    {
        $rules = [
            'dokumentasiJudul' => ['required', 'string', 'max:60'],
            'dokumentasiSubjudul' => ['required', 'string', 'max:100'],
            'dokumentasiDeskripsi' => ['required', 'string', 'max:500'],
            'dokumentasiMediaType' => ['required', 'in:video_url,video_upload'],
            'dokumentasiVideoUrl' => ['nullable', 'url', 'max:2048'],
            'dokumentasiVideoUpload' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime', 'max:51200'],
        ];

        // Tautan wajib diisi HANYA kalau tab "Video (Tautan URL)" yang aktif.
        if ($this->dokumentasiMediaType === 'video_url') {
            $rules['dokumentasiVideoUrl'] = ['required', 'url', 'max:2048'];
        }

        // File upload wajib diisi HANYA kalau tab "Upload dari Perangkat" aktif
        // DAN belum pernah ada video tersimpan sebelumnya (sama pola dengan
        // saveKeahlian(): ganti video itu opsional selama yang lama masih ada).
        if ($this->dokumentasiMediaType === 'video_upload' && ! $this->dokumentasiVideoPathLama) {
            $rules['dokumentasiVideoUpload'] = ['required', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime', 'max:51200'];
        }

        $validated = $this->validate($rules, [
            'dokumentasiJudul.required' => 'Judul wajib diisi.',
            'dokumentasiSubjudul.required' => 'Subjudul wajib diisi.',
            'dokumentasiDeskripsi.required' => 'Deskripsi wajib diisi.',
            'dokumentasiVideoUrl.required' => 'Tautan video wajib diisi.',
            'dokumentasiVideoUrl.url' => 'Tautan video tidak valid (harus diawali http:// atau https://).',
            'dokumentasiVideoUpload.required' => 'Pilih file video dari perangkat terlebih dahulu.',
            'dokumentasiVideoUpload.mimetypes' => 'Format video harus MP4, WebM, MOV, atau OGG.',
            'dokumentasiVideoUpload.max' => 'Ukuran video maksimal 50MB.',
        ]);

        // Video baru diunggah dari perangkat -- ganti file lama (kalau ada) dengan yang baru.
        if ($this->dokumentasiMediaType === 'video_upload' && $this->dokumentasiVideoUpload) {
            if ($this->dokumentasiVideoPathLama) {
                Storage::disk('public')->delete($this->dokumentasiVideoPathLama);
            }

            $extension = $this->dokumentasiVideoUpload->getClientOriginalExtension() ?: 'mp4';
            $this->dokumentasiVideoPathLama = $this->dokumentasiVideoUpload->storeAs(
                'home-sections',
                'dokumentasi-'.Str::uuid().'.'.$extension,
                'public'
            );
            $this->dokumentasiVideoUpload = null;
        }

        HomeSection::forSection('dokumentasi')->update([
            'data' => [
                'judul' => $validated['dokumentasiJudul'],
                'subjudul' => $validated['dokumentasiSubjudul'],
                'deskripsi' => $validated['dokumentasiDeskripsi'],
                'media_type' => $this->dokumentasiMediaType,
                'video_url' => $this->dokumentasiVideoUrl !== '' ? $this->dokumentasiVideoUrl : null,
                'video_path' => $this->dokumentasiVideoPathLama,
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

    @if ($activeGroup === 'menu')

        {{-- ================= PILIH HALAMAN (kartu) ================= --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ($groups as $group)
                <button
                    type="button"
                    wire:click="selectGroup('{{ $group['key'] }}')"
                    @if(! $group['ready']) disabled @endif
                    class="flex items-start gap-4 rounded-2xl border border-admin-border bg-admin-surface p-5 text-left transition
                    {{ $group['ready']
                        ? 'hover:border-admin-accent hover:shadow-sm'
                        : 'cursor-not-allowed opacity-60' }}"
                >
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-admin-cream text-admin-ink">
                        <i class="fa-solid {{ $group['icon'] }} text-lg"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-2">
                            <span class="font-display text-base font-semibold text-admin-ink">{{ $group['label'] }}</span>
                            @unless ($group['ready'])
                                <span class="rounded-full bg-admin-cream px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-admin-ink-soft">
                                    Segera
                                </span>
                            @endunless
                        </span>
                        <span class="mt-0.5 block text-xs text-admin-ink-soft">
                            {{ $group['description'] }}
                        </span>
                    </span>
                    @if ($group['ready'])
                        <i class="fa-solid fa-chevron-right mt-1 shrink-0 text-xs text-admin-ink-soft"></i>
                    @endif
                </button>
            @endforeach
        </div>

    @else

        <button
            type="button"
            wire:click="selectGroup('menu')"
            class="mb-2 inline-flex items-center gap-2 text-sm font-medium text-admin-ink-soft transition hover:text-admin-ink"
        >
            <i class="fa-solid fa-arrow-left text-xs"></i>
            Kembali ke Edit Web
        </button>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[260px_1fr]">

        {{-- ================= DAFTAR SECTION ================= --}}
        @php
            $activeGroupSections = match ($activeGroup) {
                'tentang-kami' => $tentangKamiSections,
                'dokumentasi' => $dokumentasiSections,
                default => $sections,
            };
            $activeGroupLabel = match ($activeGroup) {
                'tentang-kami' => 'Tentang Kami',
                'dokumentasi' => 'Dokumentasi',
                default => 'Beranda',
            };
        @endphp
        <div class="flex flex-col gap-2 rounded-2xl border border-admin-border bg-admin-surface p-2">
            <p class="px-2 pt-1 text-[11px] font-semibold uppercase tracking-wide text-admin-ink-soft">
                {{ $activeGroupLabel }}
            </p>
            <div class="flex gap-2 overflow-x-auto lg:flex-col lg:overflow-visible">
                @foreach ($activeGroupSections as $section)
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
                            <div class="relative shrink-0" style="width: 12rem;">
                                <img :src="previewUrl" alt="Preview foto header" class="rounded-2xl object-cover ring-4 ring-admin-cream" style="width: 12rem; aspect-ratio: 19 / 10;">

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

                    {{--
                        WARNA TEKS -- override manual (opsional) dari kontras otomatis
                        di atas. Bawaan TETAP "Otomatis" supaya situs yang sudah
                        berjalan tidak berubah tampilannya kalau admin tidak
                        menyentuh opsi ini.
                    --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Teks</p>

                        <label class="flex items-center gap-2 text-sm text-admin-ink">
                            <input type="checkbox" wire:model.live="headerUseCustomTextColor" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                            Pakai warna teks manual (judul, deskripsi, statistik)
                        </label>

                        <div class="flex flex-wrap gap-2.5 {{ $headerUseCustomTextColor ? '' : 'pointer-events-none opacity-40' }}">
                            @foreach (['black' => 'Hitam', 'white' => 'Putih'] as $value => $label)
                                <button
                                    type="button"
                                    wire:click="$set('headerTextColor', '{{ $value }}')"
                                    @disabled(! $headerUseCustomTextColor)
                                    class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition
                                    {{ $headerTextColor === $value
                                        ? 'border-admin-accent bg-admin-accent/10 text-admin-ink'
                                        : 'border-admin-border text-admin-ink-soft hover:border-admin-accent/60' }}"
                                >
                                    <span class="h-4 w-4 rounded-full border border-admin-border" style="background: {{ $value === 'black' ? '#1A1208' : '#FFFFFF' }};"></span>
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        <p class="text-xs text-admin-ink-soft">
                            Kalau tidak dicentang, warna teks tetap otomatis mengikuti kontras warna latar di atas.
                        </p>

                        <div class="border-t border-admin-border pt-4">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="headerTextGlowEnabled" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Beri cahaya (glow) di belakang teks
                            </label>

                            <div class="mt-3 flex flex-wrap gap-2.5 {{ $headerTextGlowEnabled ? '' : 'pointer-events-none opacity-40' }}">
                                @foreach (['black' => 'Hitam', 'white' => 'Putih'] as $value => $label)
                                    <button
                                        type="button"
                                        wire:click="$set('headerTextGlowColor', '{{ $value }}')"
                                        @disabled(! $headerTextGlowEnabled)
                                        class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition
                                        {{ $headerTextGlowColor === $value
                                            ? 'border-admin-accent bg-admin-accent/10 text-admin-ink'
                                            : 'border-admin-border text-admin-ink-soft hover:border-admin-accent/60' }}"
                                    >
                                        <span class="h-4 w-4 rounded-full border border-admin-border" style="background: {{ $value === 'black' ? '#1A1208' : '#FFFFFF' }};"></span>
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>

                            <p class="mt-2 text-xs text-admin-ink-soft">
                                Cahaya lembut di belakang tiap baris judul, deskripsi, dan statistik (masing-masing punya cahaya sendiri) -- berguna kalau foto latar terlalu ramai/terang sehingga teks susah dibaca.
                            </p>
                        </div>
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

                    @include('partials.admin.frame-gradient', [
                        'key' => 'mission',
                        'gradient' => $missionGradient,
                    ])

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

                    @include('partials.admin.frame-gradient', [
                        'key' => 'produkUnggulan',
                        'gradient' => $produkUnggulanGradient,
                    ])

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

                    @include('partials.admin.frame-gradient', [
                        'key' => 'kategori',
                        'gradient' => $kategoriGradient,
                    ])

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

                    @include('partials.admin.frame-gradient', [
                        'key' => 'testimoni',
                        'gradient' => $testimoniGradient,
                    ])

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

                    {{-- MEDIA (Foto / Video) --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Media panel kiri</p>
                            <p class="mt-1 text-xs text-admin-ink-soft">
                                Section ini khusus video (bukan foto statis). Pilih salah satu: video dari
                                tautan (YouTube, TikTok, Instagram, Facebook, Google Drive, atau link video
                                langsung), atau upload video dari perangkat. Video otomatis diputar dengan
                                suara begitu pengunjung scroll sampai bagian ini, dan suaranya meredup pelan
                                saat mereka melewatinya.
                            </p>
                        </div>

                        {{-- Tab pemilih jenis media --}}
                        <div class="inline-flex w-full flex-wrap gap-2 rounded-lg bg-admin-cream p-1 sm:w-auto">
                            @foreach ([
                                'video_url' => ['icon' => 'fa-link', 'label' => 'Video (Tautan URL)'],
                                'video_upload' => ['icon' => 'fa-upload', 'label' => 'Video (Upload Perangkat)'],
                            ] as $mediaKey => $mediaMeta)
                                <button
                                    type="button"
                                    wire:click="$set('keahlianMediaType', '{{ $mediaKey }}')"
                                    class="flex items-center gap-2 rounded-md px-3 py-2 text-xs font-semibold transition {{ $keahlianMediaType === $mediaKey ? 'bg-admin-panel text-white shadow-sm' : 'text-admin-ink-soft hover:bg-white' }}"
                                >
                                    <i class="fa-solid {{ $mediaMeta['icon'] }} text-xs"></i>
                                    {{ $mediaMeta['label'] }}
                                </button>
                            @endforeach
                        </div>
                        @error('keahlianMediaType')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror

                    @if ($keahlianMediaType === 'video_url')
                        <div class="space-y-3">
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">Tautan video</label>
                            <input
                                type="url" wire:model="keahlianVideoUrl"
                                placeholder="https://youtube.com/watch?v=... , https://drive.google.com/file/d/... , dst."
                                class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                            >
                            @error('keahlianVideoUrl')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            <p class="text-xs text-admin-ink-soft">
                                Tautan yang didukung: YouTube, TikTok, Instagram, Facebook, Google Drive (pakai
                                link "Bagikan" biasa), atau tautan file video langsung (.mp4/.webm/.mov/.ogg).
                                Untuk YouTube, video otomatis diputar dengan suara &amp; meredup sendiri saat
                                dilewati. Untuk platform lain (TikTok/Instagram/Facebook/Google Drive), video
                                tetap tampil dan baru dimuat saat pengunjung sampai di bagian ini, tapi
                                putar-otomatis-bersuara &amp; efek redup mengikuti aturan pemutar bawaan
                                masing-masing platform tsb -- di luar kendali kita.
                            </p>
                            @if ($keahlianVideoUrl)
                                <a
                                    href="{{ $keahlianVideoUrl }}" target="_blank" rel="noopener"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-admin-accent hover:underline"
                                >
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> Buka tautan ini untuk memastikan
                                </a>
                            @endif
                        </div>
                    @endif

                    @if ($keahlianMediaType === 'video_upload')
                        <div class="space-y-3">
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">File video dari perangkat</label>
                            <input
                                type="file" wire:model="keahlianVideoUpload" accept="video/mp4,video/webm,video/ogg,video/quicktime"
                                class="block w-full text-sm text-admin-ink file:mr-3 file:rounded-full file:border-0 file:bg-admin-accent file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-admin-accent-strong"
                            >
                            <div wire:loading wire:target="keahlianVideoUpload" class="text-xs text-admin-ink-soft">
                                <i class="fa-solid fa-circle-notch animate-spin"></i> Mengunggah video...
                            </div>
                            @error('keahlianVideoUpload')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            <p class="text-xs text-admin-ink-soft">Format MP4/WebM/MOV/OGG, maksimal 50MB.</p>

                            @if ($this->keahlianVideoPreviewUrl)
                                <a
                                    href="{{ $this->keahlianVideoPreviewUrl }}" target="_blank" rel="noopener"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-admin-accent hover:underline"
                                >
                                    <i class="fa-solid fa-circle-play text-[10px]"></i> Lihat video yang sedang tersimpan
                                </a>
                            @else
                                <p class="text-xs text-admin-ink-soft">Belum ada video yang diunggah.</p>
                            @endif
                        </div>
                    @endif
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
            @elseif ($activeSection === 'faq')
                <form wire:submit="saveFaq" class="space-y-6">

                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-circle-question text-admin-accent"></i>
                            FAQ (section ke-8 Beranda, tepat sebelum Footer)
                        </h3>
                        <p class="text-xs text-admin-ink-soft">
                            Enam pasang pertanyaan &amp; jawaban yang tampil sebagai accordion. Tombol
                            "Hubungi admin lewat WhatsApp" di bawahnya tidak diedit di sini -- nomor diambil
                            otomatis dari Pengaturan.
                        </p>
                    </div>

                    <div class="space-y-3">
                        @foreach ($faqItems as $i => $faqItem)
                            <div class="space-y-2 rounded-xl border border-admin-border p-4">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">
                                    Pertanyaan {{ $i + 1 }}
                                </label>
                                <input
                                    type="text" maxlength="150" placeholder="Pertanyaan"
                                    wire:model="faqItems.{{ $i }}.q"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-2.5 py-2 text-sm font-semibold text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error("faqItems.{$i}.q")<p class="text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror

                                <textarea
                                    rows="2" maxlength="500" placeholder="Jawaban"
                                    wire:model="faqItems.{{ $i }}.a"
                                    class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-2.5 py-2 text-xs text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                ></textarea>
                                @error("faqItems.{$i}.a")<p class="text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            wire:loading.attr="disabled" wire:target="saveFaq"
                            class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition-all duration-200 hover:bg-admin-accent-strong active:scale-[0.99] disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="saveFaq" class="flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan
                            </span>
                            <span wire:loading wire:target="saveFaq" class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch animate-spin"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            @elseif ($activeSection === 'lokasi')
                <form wire:submit="saveLokasi" class="space-y-6">

                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-location-dot text-admin-accent"></i>
                            Lokasi (section ke-9 Beranda, tepat sebelum Footer)
                        </h3>
                        <p class="text-xs text-admin-ink-soft">
                            Section "Kunjungi Kami" -- teks di kiri dan ilustrasi peta di kanan. Tombol
                            "Hubungi via WhatsApp" tidak diedit di sini (nomornya otomatis dari Pengaturan,
                            dan tombolnya hilang sendiri kalau nomor WhatsApp masih kosong).
                        </p>
                    </div>

                    {{-- TEKS --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Teks</p>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-admin-ink-soft">Label kecil (di atas judul)</label>
                            <input
                                type="text" maxlength="40" placeholder="Kunjungi Kami"
                                wire:model="lokasiEyebrow"
                                class="w-full rounded-lg border border-admin-border bg-admin-surface px-2.5 py-2 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                            >
                            @error('lokasiEyebrow')<p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-admin-ink-soft">Judul baris 1</label>
                                <input
                                    type="text" maxlength="60" placeholder="Datang Langsung ke"
                                    wire:model="lokasiHeadingLine1"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-2.5 py-2 text-sm font-semibold text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('lokasiHeadingLine1')<p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-admin-ink-soft">Judul baris 2</label>
                                <input
                                    type="text" maxlength="60" placeholder="Workshop Kami"
                                    wire:model="lokasiHeadingLine2"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-2.5 py-2 text-sm font-semibold text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('lokasiHeadingLine2')<p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-admin-ink-soft">Deskripsi</label>
                            <textarea
                                rows="3" maxlength="500"
                                wire:model="lokasiDescription"
                                class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-2.5 py-2 text-xs text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                            ></textarea>
                            @error('lokasiDescription')<p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- GOOGLE MAPS --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Google Maps</p>
                            <p class="mt-1 text-[11px] text-admin-ink-soft">
                                Cukup tempel tautan Google Maps toko -- alamat yang tampil di teks dan tulisan
                                pada ilustrasi peta di sebelah kanan otomatis mengikuti (satu paket), tidak perlu
                                diatur satu-satu lagi.
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-admin-ink-soft">Tautan Google Maps (tombol "Lihat di Google Maps")</label>
                            <input
                                type="text" maxlength="2048" placeholder="https://maps.app.goo.gl/..."
                                wire:model="lokasiMapsUrl"
                                class="w-full rounded-lg border border-admin-border bg-admin-surface px-2.5 py-2 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                            >
                            @error('lokasiMapsUrl')<p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                            <p class="mt-1 text-[11px] text-admin-ink-soft">
                                Tempel tautan hasil "Bagikan" di Google Maps supaya titiknya benar-benar akurat.
                                Dikosongkan = tautan dibuat otomatis dari nama toko + alamat di Admin &gt; Pengaturan
                                (perilaku lama).
                            </p>
                        </div>
                    </div>

                    {{-- WARNA --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Frame (Latar Section)</p>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="lokasiUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna latar khusus
                            </label>
                            <input
                                type="color" wire:model="lokasiBgColor"
                                @disabled(! $lokasiUseCustomBg)
                                class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:cursor-not-allowed disabled:opacity-40"
                            >
                        </div>

                        <div>
                            <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-admin-ink-soft">Atau pilih dari rekomendasi</p>
                            <div class="flex flex-wrap gap-2.5">
                                @foreach ($lokasiBgPresets as $preset)
                                    <button
                                        type="button"
                                        wire:click="selectLokasiBgPreset('{{ $preset['value'] }}')"
                                        title="{{ $preset['label'] }}"
                                        class="group flex flex-col items-center gap-1"
                                    >
                                        <span
                                            class="flex h-9 w-9 items-center justify-center rounded-full shadow-sm ring-1 ring-inset ring-white/40 transition duration-200 group-hover:scale-110 group-hover:shadow-md
                                            {{ $lokasiUseCustomBg && strtoupper($lokasiBgColor) === $preset['value']
                                                ? 'border-2 border-admin-accent ring-2 ring-admin-accent ring-offset-2 ring-offset-admin-surface'
                                                : 'border border-admin-border group-hover:border-admin-accent/60' }}"
                                            style="background: linear-gradient(135deg, color-mix(in oklab, {{ $preset['value'] }} 100%, white 30%), {{ $preset['value'] }} 55%, color-mix(in oklab, {{ $preset['value'] }} 100%, black 16%));"
                                        >
                                            @if ($lokasiUseCustomBg && strtoupper($lokasiBgColor) === $preset['value'])
                                                <i class="fa-solid fa-check text-xs drop-shadow-sm" style="color: {{ $preset['check'] }};"></i>
                                            @endif
                                        </span>
                                        <span class="max-w-14 truncate text-[10px] text-admin-ink-soft">{{ $preset['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <p class="text-xs text-admin-ink-soft">
                            Kalau tidak dicentang, section ini tetap putih seperti bawaan. Warna judul, paragraf,
                            dan alamat otomatis menyesuaikan terang/gelap mengikuti warna latar supaya tetap kebaca.
                        </p>
                    </div>

                    @include('partials.admin.frame-gradient', [
                        'key' => 'lokasi',
                        'gradient' => $lokasiGradient,
                    ])

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            wire:loading.attr="disabled" wire:target="saveLokasi"
                            class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition-all duration-200 hover:bg-admin-accent-strong active:scale-[0.99] disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="saveLokasi" class="flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan
                            </span>
                            <span wire:loading wire:target="saveLokasi" class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch animate-spin"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            @elseif ($activeSection === 'profil-toko')
                <form wire:submit="saveProfilHero" class="space-y-6">

                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-store text-admin-accent"></i>
                            Tentang Kami (section paling atas halaman Tentang Kami)
                        </h3>
                        <p class="text-xs text-admin-ink-soft">
                            Ini section tepat di bawah navbar di halaman "Tentang Kami" -- label kecil,
                            judul besar, paragraf, dan foto kanan. Tombol "Lihat Produk" &amp; "Hubungi Kami"
                            tidak bisa diubah dari sini (link &amp; tulisannya tetap).
                        </p>
                    </div>

                    {{-- FOTO --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4" x-data="profilHeroFotoCropper(@js($this->profilHeroFotoPreviewUrl))">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Foto</p>

                        <div class="flex flex-col items-center gap-5 sm:flex-row">
                            <div class="relative w-32 shrink-0">
                                <img :src="previewUrl" alt="Preview foto Tentang Kami" class="aspect-10/9 w-32 rounded-2xl object-cover ring-4 ring-admin-cream">

                                <label
                                    for="profil_hero_foto_input"
                                    class="absolute -bottom-1 -right-1 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-admin-accent text-white shadow-sm ring-2 ring-admin-surface transition hover:bg-admin-accent-strong"
                                    title="Ganti foto"
                                >
                                    <i class="fa-solid fa-camera text-xs"></i>
                                </label>
                                <input x-ref="fileInput" id="profil_hero_foto_input" type="file" accept="image/*" class="hidden" x-on:change="onFileChange($event)">
                            </div>

                            <div class="text-center sm:text-left">
                                <p class="text-sm font-medium text-admin-ink">Klik ikon kamera untuk ganti foto</p>
                                <p class="mt-1 text-xs text-admin-ink-soft">
                                    Setelah pilih foto, geser untuk memindah posisi &amp; pakai slider untuk zoom --
                                    sama seperti mengatur foto Header Beranda.
                                </p>
                            </div>
                        </div>

                        <template x-teleport="body">
                            <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-999 flex items-center justify-center bg-black/60 p-4" style="display: none;">
                                <div x-show="open" x-transition.scale.origin.center @click.outside="cancelCrop()" class="w-full max-w-md rounded-2xl bg-admin-surface p-6 shadow-2xl">
                                    <h4 class="mb-1 text-sm font-semibold text-admin-ink">Sesuaikan Foto</h4>
                                    <p class="mb-4 text-xs text-admin-ink-soft">Geser gambar untuk memindah, gunakan slider untuk zoom. Rasio 10:9 (mengikuti bingkai foto di halaman).</p>
                                    <div x-ref="viewport" class="relative mx-auto aspect-10/9 w-full max-w-64 cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none" x-on:pointerdown="startDrag($event)" x-on:pointermove="onDrag($event)" x-on:pointerup="endDrag()" x-on:pointerleave="endDrag()">
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
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Label kecil (di atas judul)</label>
                                <input
                                    type="text" maxlength="40" wire:model="profilHeroEyebrow"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('profilHeroEyebrow')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul (baris 1)</label>
                                <input
                                    type="text" maxlength="60" wire:model="profilHeroHeadingLine1"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('profilHeroHeadingLine1')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul (baris 2)</label>
                                <input
                                    type="text" maxlength="60" wire:model="profilHeroHeadingLine2"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('profilHeroHeadingLine2')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Paragraf</label>
                                <textarea
                                    rows="4" maxlength="500" wire:model="profilHeroDescription"
                                    class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                ></textarea>
                                @error('profilHeroDescription')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            wire:loading.attr="disabled" wire:target="saveProfilHero"
                            class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition-all duration-200 hover:bg-admin-accent-strong active:scale-[0.99] disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="saveProfilHero" class="flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan
                            </span>
                            <span wire:loading wire:target="saveProfilHero" class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch animate-spin"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            @elseif ($activeSection === 'sejarah')
                <form wire:submit="saveSejarah" class="space-y-6">

                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-clock-rotate-left text-admin-accent"></i>
                            Sejarah (section di halaman Tentang Kami)
                        </h3>
                        <p class="text-xs text-admin-ink-soft">
                            Section "Sejarah Kami" di halaman "Tentang Kami", tepat setelah bagian foto &amp; teks
                            "Lebih dari sekadar furniture" -- teks di kiri, foto di kanan. Yang bisa diubah: warna
                            frame, foto, dan isi teks.
                        </p>
                    </div>

                    {{-- 1. WARNA FRAME --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Frame (Latar Section)</p>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="sejarahUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna latar khusus
                            </label>
                            <input
                                type="color" wire:model="sejarahBgColor"
                                @disabled(! $sejarahUseCustomBg)
                                class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:cursor-not-allowed disabled:opacity-40"
                            >
                        </div>

                        <div>
                            <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-admin-ink-soft">Atau pilih dari rekomendasi</p>
                            <div class="flex flex-wrap gap-2.5">
                                @foreach ($sejarahBgPresets as $preset)
                                    <button
                                        type="button"
                                        wire:click="selectSejarahBgPreset('{{ $preset['value'] }}')"
                                        title="{{ $preset['label'] }}"
                                        class="group flex flex-col items-center gap-1"
                                    >
                                        <span
                                            class="flex h-9 w-9 items-center justify-center rounded-full shadow-sm ring-1 ring-inset ring-white/40 transition duration-200 group-hover:scale-110 group-hover:shadow-md
                                            {{ $sejarahUseCustomBg && strtoupper($sejarahBgColor) === $preset['value']
                                                ? 'border-2 border-admin-accent ring-2 ring-admin-accent ring-offset-2 ring-offset-admin-surface'
                                                : 'border border-admin-border group-hover:border-admin-accent/60' }}"
                                            style="background: linear-gradient(135deg, color-mix(in oklab, {{ $preset['value'] }} 100%, white 30%), {{ $preset['value'] }} 55%, color-mix(in oklab, {{ $preset['value'] }} 100%, black 16%));"
                                        >
                                            @if ($sejarahUseCustomBg && strtoupper($sejarahBgColor) === $preset['value'])
                                                <i class="fa-solid fa-check text-xs drop-shadow-sm" style="color: {{ $preset['check'] }};"></i>
                                            @endif
                                        </span>
                                        <span class="max-w-14 truncate text-[10px] text-admin-ink-soft">{{ $preset['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <p class="text-xs text-admin-ink-soft">
                            Kalau tidak dicentang, section ini tetap putih seperti bawaan. Warna label, judul, dan
                            paragraf otomatis menyesuaikan terang/gelap mengikuti warna latar supaya tetap kebaca.
                        </p>
                    </div>

                    @include('partials.admin.frame-gradient', [
                        'key' => 'sejarah',
                        'gradient' => $sejarahGradient,
                    ])

                    {{-- 2. FOTO --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4" x-data="sejarahFotoCropper(@js($this->sejarahFotoPreviewUrl))">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Foto</p>

                        <div class="flex flex-col items-center gap-5 sm:flex-row">
                            <div class="relative w-32 shrink-0">
                                <img :src="previewUrl" alt="Preview foto Sejarah" class="aspect-4/5 w-32 rounded-2xl object-cover bg-admin-cream ring-4 ring-admin-cream">

                                <label
                                    for="sejarah_foto_input"
                                    class="absolute -bottom-1 -right-1 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-admin-accent text-white shadow-sm ring-2 ring-admin-surface transition hover:bg-admin-accent-strong"
                                    title="Ganti foto"
                                >
                                    <i class="fa-solid fa-camera text-xs"></i>
                                </label>
                                <input x-ref="fileInput" id="sejarah_foto_input" type="file" accept="image/*" class="hidden" x-on:change="onFileChange($event)">
                            </div>

                            <div class="text-center sm:text-left">
                                <p class="text-sm font-medium text-admin-ink">Klik ikon kamera untuk ganti foto</p>
                                <p class="mt-1 text-xs text-admin-ink-soft">
                                    Setelah pilih foto, geser untuk memindah posisi &amp; pakai slider untuk zoom --
                                    sama seperti mengatur foto Header Beranda.
                                </p>
                            </div>
                        </div>

                        <template x-teleport="body">
                            <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-999 flex items-center justify-center bg-black/60 p-4" style="display: none;">
                                <div x-show="open" x-transition.scale.origin.center @click.outside="cancelCrop()" class="w-full max-w-md rounded-2xl bg-admin-surface p-6 shadow-2xl">
                                    <h4 class="mb-1 text-sm font-semibold text-admin-ink">Sesuaikan Foto</h4>
                                    <p class="mb-4 text-xs text-admin-ink-soft">Geser gambar untuk memindah, gunakan slider untuk zoom. Rasio 4:5 (mengikuti bingkai foto di halaman).</p>
                                    <div x-ref="viewport" class="relative mx-auto aspect-4/5 w-full max-w-64 cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none" x-on:pointerdown="startDrag($event)" x-on:pointermove="onDrag($event)" x-on:pointerup="endDrag()" x-on:pointerleave="endDrag()">
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

                    {{-- 3. ISI TEKS --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Isi Teks</p>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Label kecil (di atas judul)</label>
                                <input
                                    type="text" maxlength="40" wire:model="sejarahEyebrow"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('sejarahEyebrow')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul (baris 1)</label>
                                <input
                                    type="text" maxlength="60" wire:model="sejarahHeadingLine1"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('sejarahHeadingLine1')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul (baris 2)</label>
                                <input
                                    type="text" maxlength="60" wire:model="sejarahHeadingLine2"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('sejarahHeadingLine2')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Paragraf</label>
                                <p class="mb-1.5 text-xs text-admin-ink-soft">Beri satu baris kosong untuk memisahkan paragraf.</p>
                                <textarea
                                    rows="8" maxlength="1500" wire:model="sejarahDescription"
                                    class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                ></textarea>
                                @error('sejarahDescription')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            wire:loading.attr="disabled" wire:target="saveSejarah"
                            class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition-all duration-200 hover:bg-admin-accent-strong active:scale-[0.99] disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="saveSejarah" class="flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan
                            </span>
                            <span wire:loading wire:target="saveSejarah" class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch animate-spin"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            @elseif ($activeSection === 'tentang-kami-2')
                <form wire:submit="saveTentangKami2" class="space-y-6">

                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-couch text-admin-accent"></i>
                            Tentang Kami 2 (section ke-2 halaman Tentang Kami)
                        </h3>
                        <p class="text-xs text-admin-ink-soft">
                            Section "Lebih dari sekadar furniture" di halaman "Tentang Kami", tepat di bawah section
                            paling atas -- foto di kiri, teks di kanan. Yang bisa diubah: warna frame, foto, dan isi teks.
                        </p>
                    </div>

                    {{-- 1. WARNA FRAME --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Frame (Latar Section)</p>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="tentangKami2UseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna latar khusus
                            </label>
                            <input
                                type="color" wire:model="tentangKami2BgColor"
                                @disabled(! $tentangKami2UseCustomBg)
                                class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:cursor-not-allowed disabled:opacity-40"
                            >
                        </div>

                        <div>
                            <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-admin-ink-soft">Atau pilih dari rekomendasi</p>
                            <div class="flex flex-wrap gap-2.5">
                                @foreach ($tentangKami2BgPresets as $preset)
                                    <button
                                        type="button"
                                        wire:click="selectTentangKami2BgPreset('{{ $preset['value'] }}')"
                                        title="{{ $preset['label'] }}"
                                        class="group flex flex-col items-center gap-1"
                                    >
                                        <span
                                            class="flex h-9 w-9 items-center justify-center rounded-full shadow-sm ring-1 ring-inset ring-white/40 transition duration-200 group-hover:scale-110 group-hover:shadow-md
                                            {{ $tentangKami2UseCustomBg && strtoupper($tentangKami2BgColor) === $preset['value']
                                                ? 'border-2 border-admin-accent ring-2 ring-admin-accent ring-offset-2 ring-offset-admin-surface'
                                                : 'border border-admin-border group-hover:border-admin-accent/60' }}"
                                            style="background: linear-gradient(135deg, color-mix(in oklab, {{ $preset['value'] }} 100%, white 30%), {{ $preset['value'] }} 55%, color-mix(in oklab, {{ $preset['value'] }} 100%, black 16%));"
                                        >
                                            @if ($tentangKami2UseCustomBg && strtoupper($tentangKami2BgColor) === $preset['value'])
                                                <i class="fa-solid fa-check text-xs drop-shadow-sm" style="color: {{ $preset['check'] }};"></i>
                                            @endif
                                        </span>
                                        <span class="max-w-14 truncate text-[10px] text-admin-ink-soft">{{ $preset['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <p class="text-xs text-admin-ink-soft">
                            Kalau tidak dicentang, section ini tetap krem lembut seperti bawaan. Warna label, judul, dan
                            paragraf otomatis menyesuaikan terang/gelap mengikuti warna latar supaya tetap kebaca.
                        </p>
                    </div>

                    @include('partials.admin.frame-gradient', [
                        'key' => 'tentangKami2',
                        'gradient' => $tentangKami2Gradient,
                    ])

                    {{-- 2. FOTO --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4" x-data="tentangKami2FotoCropper(@js($this->tentangKami2FotoPreviewUrl))">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Foto</p>

                        <div class="flex flex-col items-center gap-5 sm:flex-row">
                            <div class="relative w-32 shrink-0">
                                <img :src="previewUrl" alt="Preview foto Tentang Kami 2" class="aspect-4/5 w-32 rounded-2xl object-cover bg-admin-cream ring-4 ring-admin-cream">

                                <label
                                    for="tentang_kami_2_foto_input"
                                    class="absolute -bottom-1 -right-1 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-admin-accent text-white shadow-sm ring-2 ring-admin-surface transition hover:bg-admin-accent-strong"
                                    title="Ganti foto"
                                >
                                    <i class="fa-solid fa-camera text-xs"></i>
                                </label>
                                <input x-ref="fileInput" id="tentang_kami_2_foto_input" type="file" accept="image/*" class="hidden" x-on:change="onFileChange($event)">
                            </div>

                            <div class="text-center sm:text-left">
                                <p class="text-sm font-medium text-admin-ink">Klik ikon kamera untuk ganti foto</p>
                                <p class="mt-1 text-xs text-admin-ink-soft">
                                    Setelah pilih foto, geser untuk memindah posisi &amp; pakai slider untuk zoom --
                                    sama seperti mengatur foto Header Beranda.
                                </p>
                            </div>
                        </div>

                        <template x-teleport="body">
                            <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-999 flex items-center justify-center bg-black/60 p-4" style="display: none;">
                                <div x-show="open" x-transition.scale.origin.center @click.outside="cancelCrop()" class="w-full max-w-md rounded-2xl bg-admin-surface p-6 shadow-2xl">
                                    <h4 class="mb-1 text-sm font-semibold text-admin-ink">Sesuaikan Foto</h4>
                                    <p class="mb-4 text-xs text-admin-ink-soft">Geser gambar untuk memindah, gunakan slider untuk zoom. Rasio 4:5 (mengikuti bingkai foto di halaman).</p>
                                    <div x-ref="viewport" class="relative mx-auto aspect-4/5 w-full max-w-64 cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none" x-on:pointerdown="startDrag($event)" x-on:pointermove="onDrag($event)" x-on:pointerup="endDrag()" x-on:pointerleave="endDrag()">
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

                    {{-- 3. ISI TEKS --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Isi Teks</p>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Label kecil (di atas judul)</label>
                                <input
                                    type="text" maxlength="40" wire:model="tentangKami2Eyebrow"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('tentangKami2Eyebrow')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul</label>
                                <input
                                    type="text" maxlength="80" wire:model="tentangKami2Heading"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('tentangKami2Heading')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Paragraf</label>
                                <p class="mb-1.5 text-xs text-admin-ink-soft">Beri satu baris kosong untuk memisahkan paragraf.</p>
                                <textarea
                                    rows="8" maxlength="1200" wire:model="tentangKami2Description"
                                    class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                ></textarea>
                                @error('tentangKami2Description')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            wire:loading.attr="disabled" wire:target="saveTentangKami2"
                            class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition-all duration-200 hover:bg-admin-accent-strong active:scale-[0.99] disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="saveTentangKami2" class="flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan
                            </span>
                            <span wire:loading wire:target="saveTentangKami2" class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch animate-spin"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            @elseif ($activeSection === 'nilai-kami')
                <form wire:submit="saveNilaiKami" class="space-y-6">

                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-gem text-admin-accent"></i>
                            Nilai Kami (section di halaman Tentang Kami)
                        </h3>
                        <p class="text-xs text-admin-ink-soft">
                            Section "Nilai Kami" di halaman "Tentang Kami" -- judul di tengah dan 4 kartu di bawahnya.
                            Yang bisa diubah: warna frame, foto tiap kartu, dan isi teks. Nomor (01-04) dan ikon kartu tetap.
                        </p>
                    </div>

                    {{-- 1. WARNA FRAME --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Frame (Latar Section)</p>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="nilaiKamiUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna latar khusus
                            </label>
                            <input
                                type="color" wire:model="nilaiKamiBgColor"
                                @disabled(! $nilaiKamiUseCustomBg)
                                class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:cursor-not-allowed disabled:opacity-40"
                            >
                        </div>

                        <div>
                            <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-admin-ink-soft">Atau pilih dari rekomendasi</p>
                            <div class="flex flex-wrap gap-2.5">
                                @foreach ($nilaiKamiBgPresets as $preset)
                                    <button
                                        type="button"
                                        wire:click="selectNilaiKamiBgPreset('{{ $preset['value'] }}')"
                                        title="{{ $preset['label'] }}"
                                        class="group flex flex-col items-center gap-1"
                                    >
                                        <span
                                            class="flex h-9 w-9 items-center justify-center rounded-full shadow-sm ring-1 ring-inset ring-white/40 transition duration-200 group-hover:scale-110 group-hover:shadow-md
                                            {{ $nilaiKamiUseCustomBg && strtoupper($nilaiKamiBgColor) === $preset['value']
                                                ? 'border-2 border-admin-accent ring-2 ring-admin-accent ring-offset-2 ring-offset-admin-surface'
                                                : 'border border-admin-border group-hover:border-admin-accent/60' }}"
                                            style="background: linear-gradient(135deg, color-mix(in oklab, {{ $preset['value'] }} 100%, white 30%), {{ $preset['value'] }} 55%, color-mix(in oklab, {{ $preset['value'] }} 100%, black 16%));"
                                        >
                                            @if ($nilaiKamiUseCustomBg && strtoupper($nilaiKamiBgColor) === $preset['value'])
                                                <i class="fa-solid fa-check text-xs drop-shadow-sm" style="color: {{ $preset['check'] }};"></i>
                                            @endif
                                        </span>
                                        <span class="max-w-14 truncate text-[10px] text-admin-ink-soft">{{ $preset['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <p class="text-xs text-admin-ink-soft">
                            Kalau tidak dicentang, section ini tetap krem seperti bawaan. Warna label dan judul
                            otomatis menyesuaikan terang/gelap mengikuti warna latar supaya tetap kebaca.
                            Kartunya sendiri tetap putih.
                        </p>
                    </div>

                    @include('partials.admin.frame-gradient', [
                        'key' => 'nilaiKami',
                        'gradient' => $nilaiKamiGradient,
                    ])

                    {{-- 2. FOTO --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4" x-data="nilaiKamiFotoCropper(@js($this->nilaiKamiFotoPreviewUrls))">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Foto</p>
                            <p class="mt-1 text-xs text-admin-ink-soft">
                                Satu foto untuk tiap kartu. Klik ikon kamera, lalu geser untuk memindah posisi &amp; pakai slider
                                untuk zoom (rasio 4:3, mengikuti bingkai foto kartu). Kartu yang fotonya belum pernah diganti
                                tetap memakai foto produk otomatis seperti sebelumnya.
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ([0, 1, 2, 3] as $i)
                                <div class="flex items-center gap-4 rounded-xl border border-admin-border p-3">
                                    <div class="relative w-32 shrink-0">
                                        <div class="aspect-4/3 w-32 overflow-hidden rounded-xl bg-admin-cream ring-4 ring-admin-cream">
                                            <img x-show="previews[{{ $i }}]" :src="previews[{{ $i }}]" alt="Preview foto kartu {{ $i + 1 }}" class="h-full w-full object-cover">
                                            <div x-show="! previews[{{ $i }}]" class="flex h-full w-full items-center justify-center text-admin-ink-soft/60">
                                                <i class="fa-solid fa-image text-xl"></i>
                                            </div>
                                        </div>

                                        <label
                                            for="nilai_kami_foto_input_{{ $i }}"
                                            class="absolute -bottom-1 -right-1 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-admin-accent text-white shadow-sm ring-2 ring-admin-surface transition hover:bg-admin-accent-strong"
                                            title="Ganti foto kartu {{ $i + 1 }}"
                                        >
                                            <i class="fa-solid fa-camera text-xs"></i>
                                        </label>
                                        <input id="nilai_kami_foto_input_{{ $i }}" type="file" accept="image/*" class="hidden" x-on:change="onFileChange($event, {{ $i }})">
                                    </div>

                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-admin-ink">Kartu {{ $i + 1 }}</p>
                                        <p class="mt-0.5 truncate text-xs text-admin-ink-soft">{{ $nilaiKamiItems[$i]['title'] ?? '' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <template x-teleport="body">
                            <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-999 flex items-center justify-center bg-black/60 p-4" style="display: none;">
                                <div x-show="open" x-transition.scale.origin.center @click.outside="cancelCrop()" class="w-full max-w-md rounded-2xl bg-admin-surface p-6 shadow-2xl">
                                    <h4 class="mb-1 text-sm font-semibold text-admin-ink">Sesuaikan Foto</h4>
                                    <p class="mb-4 text-xs text-admin-ink-soft">Geser gambar untuk memindah, gunakan slider untuk zoom. Rasio 4:3 (mengikuti bingkai foto kartu di halaman).</p>
                                    <div x-ref="viewport" class="relative mx-auto aspect-4/3 w-full cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none" x-on:pointerdown="startDrag($event)" x-on:pointermove="onDrag($event)" x-on:pointerup="endDrag()" x-on:pointerleave="endDrag()">
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

                    {{-- 3. ISI TEKS --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Isi Teks</p>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Label kecil (di atas judul)</label>
                                <input
                                    type="text" maxlength="40" wire:model="nilaiKamiEyebrow"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('nilaiKamiEyebrow')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul</label>
                                <input
                                    type="text" maxlength="80" wire:model="nilaiKamiHeading"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('nilaiKamiHeading')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ([0, 1, 2, 3] as $i)
                                <div class="space-y-3 rounded-xl border border-admin-border p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Kartu {{ $i + 1 }}</p>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul kartu</label>
                                        <input
                                            type="text" maxlength="40" wire:model="nilaiKamiItems.{{ $i }}.title"
                                            class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                        >
                                        @error('nilaiKamiItems.'.$i.'.title')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-admin-ink">Deskripsi kartu</label>
                                        <textarea
                                            rows="3" maxlength="160" wire:model="nilaiKamiItems.{{ $i }}.desc"
                                            class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20 resize-none"
                                        ></textarea>
                                        @error('nilaiKamiItems.'.$i.'.desc')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            wire:loading.attr="disabled" wire:target="saveNilaiKami"
                            class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition-all duration-200 hover:bg-admin-accent-strong active:scale-[0.99] disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="saveNilaiKami" class="flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan
                            </span>
                            <span wire:loading wire:target="saveNilaiKami" class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch animate-spin"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            @elseif ($activeSection === 'dokumentasi')
                <form wire:submit="saveDokumentasi" class="space-y-6">

                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-images text-admin-accent"></i>
                            Dokumentasi (section paling atas halaman Dokumentasi)
                        </h3>
                        <p class="text-xs text-admin-ink-soft">
                            Judul, subjudul, dan deskripsi yang tampil di bagian atas halaman
                            "Dokumentasi". Kolom kanan halaman itu sengaja dikosongkan, jadi tidak
                            ada foto untuk diedit di sini.
                        </p>
                    </div>

                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Isi Teks</p>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul</label>
                                <input
                                    type="text" maxlength="60" wire:model="dokumentasiJudul"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('dokumentasiJudul')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Subjudul</label>
                                <input
                                    type="text" maxlength="100" wire:model="dokumentasiSubjudul"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('dokumentasiSubjudul')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Deskripsi</label>
                                <textarea
                                    rows="4" maxlength="500" wire:model="dokumentasiDeskripsi"
                                    class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                ></textarea>
                                @error('dokumentasiDeskripsi')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- MEDIA (Video kolom kanan) --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Video kolom kanan</p>
                            <p class="mt-1 text-xs text-admin-ink-soft">
                                Mengisi ruang kosong di kanan hero Dokumentasi. Pilih salah satu: video
                                dari tautan (YouTube, TikTok, Instagram, Facebook, Google Drive, atau
                                link video langsung), atau upload video dari perangkat -- sama seperti
                                pengisian video "Kenapa Pilih Kami" di Beranda.
                            </p>
                        </div>

                        {{-- Tab pemilih jenis media --}}
                        <div class="inline-flex w-full flex-wrap gap-2 rounded-lg bg-admin-cream p-1 sm:w-auto">
                            @foreach ([
                                'video_url' => ['icon' => 'fa-link', 'label' => 'Video (Tautan URL)'],
                                'video_upload' => ['icon' => 'fa-upload', 'label' => 'Video (Upload Perangkat)'],
                            ] as $mediaKey => $mediaMeta)
                                <button
                                    type="button"
                                    wire:click="$set('dokumentasiMediaType', '{{ $mediaKey }}')"
                                    class="flex items-center gap-2 rounded-md px-3 py-2 text-xs font-semibold transition {{ $dokumentasiMediaType === $mediaKey ? 'bg-admin-panel text-white shadow-sm' : 'text-admin-ink-soft hover:bg-white' }}"
                                >
                                    <i class="fa-solid {{ $mediaMeta['icon'] }} text-xs"></i>
                                    {{ $mediaMeta['label'] }}
                                </button>
                            @endforeach
                        </div>
                        @error('dokumentasiMediaType')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror

                    @if ($dokumentasiMediaType === 'video_url')
                        <div class="space-y-3">
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">Tautan video</label>
                            <input
                                type="url" wire:model="dokumentasiVideoUrl"
                                placeholder="https://youtube.com/watch?v=... , https://drive.google.com/file/d/... , dst."
                                class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                            >
                            @error('dokumentasiVideoUrl')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            <p class="text-xs text-admin-ink-soft">
                                Tautan yang didukung: YouTube, TikTok, Instagram, Facebook, Google Drive (pakai
                                link "Bagikan" biasa), atau tautan file video langsung (.mp4/.webm/.mov/.ogg).
                            </p>
                            @if ($dokumentasiVideoUrl)
                                <a
                                    href="{{ $dokumentasiVideoUrl }}" target="_blank" rel="noopener"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-admin-accent hover:underline"
                                >
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> Buka tautan ini untuk memastikan
                                </a>
                            @endif
                        </div>
                    @endif

                    @if ($dokumentasiMediaType === 'video_upload')
                        <div class="space-y-3">
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">File video dari perangkat</label>
                            <input
                                type="file" wire:model="dokumentasiVideoUpload" accept="video/mp4,video/webm,video/ogg,video/quicktime"
                                class="block w-full text-sm text-admin-ink file:mr-3 file:rounded-full file:border-0 file:bg-admin-accent file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-admin-accent-strong"
                            >
                            <div wire:loading wire:target="dokumentasiVideoUpload" class="text-xs text-admin-ink-soft">
                                <i class="fa-solid fa-circle-notch animate-spin"></i> Mengunggah video...
                            </div>
                            @error('dokumentasiVideoUpload')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            <p class="text-xs text-admin-ink-soft">Format MP4/WebM/MOV/OGG, maksimal 50MB.</p>

                            @if ($this->dokumentasiVideoPreviewUrl)
                                <a
                                    href="{{ $this->dokumentasiVideoPreviewUrl }}" target="_blank" rel="noopener"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-admin-accent hover:underline"
                                >
                                    <i class="fa-solid fa-circle-play text-[10px]"></i> Lihat video yang sedang tersimpan
                                </a>
                            @else
                                <p class="text-xs text-admin-ink-soft">Belum ada video yang diunggah.</p>
                            @endif
                        </div>
                    @endif
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            wire:loading.attr="disabled" wire:target="saveDokumentasi"
                            class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition-all duration-200 hover:bg-admin-accent-strong active:scale-[0.99] disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="saveDokumentasi" class="flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan
                            </span>
                            <span wire:loading wire:target="saveDokumentasi" class="flex items-center gap-2">
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

    @endif

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
                <p class="mb-4 text-xs text-admin-ink-soft">Geser gambar untuk memindah, gunakan slider untuk zoom. Hasil crop mengikuti proporsi tampilan foto di hero beranda (19:10).</p>

                <div
                    x-ref="viewport"
                    class="relative mx-auto w-full cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none" style="aspect-ratio: 19 / 10;"
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
    // (19:10, mengikuti tampilan foto latar di partials/frontend/hero.blade.php).
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

        ASPECT_W: 19,
        ASPECT_H: 10,

        viewW: 0,
        viewH: 0,

        OUT_W: 1900,
        OUT_H: 1000,

        init() {
            this.$watch('open', (isOpen) => {
                document.body.style.overflow = isOpen ? 'hidden' : '';
            });

            window.addEventListener('resize', () => {
                if (!this.open || !this.$refs.viewport || this.natW === 0) return;

                this.viewW = this.$refs.viewport.offsetWidth;
                this.viewH = this.$refs.viewport.offsetHeight;
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
                this.viewW = this.$refs.viewport.offsetWidth;
                this.viewH = this.$refs.viewport.offsetHeight;

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

                this.viewW = this.$refs.viewport.offsetWidth;
                this.viewH = this.$refs.viewport.offsetHeight;
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
                this.viewW = this.$refs.viewport.offsetWidth;
                this.viewH = this.$refs.viewport.offsetHeight;

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

                this.viewW = this.$refs.viewport.offsetWidth;
                this.viewH = this.$refs.viewport.offsetHeight;
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
                this.viewW = this.$refs.viewport.offsetWidth;
                this.viewH = this.$refs.viewport.offsetHeight;

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

                this.viewW = this.$refs.viewport.offsetWidth;
                this.viewH = this.$refs.viewport.offsetHeight;
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
                this.viewW = this.$refs.viewport.offsetWidth;
                this.viewH = this.$refs.viewport.offsetHeight;

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

    // Sama persis strukturnya dengan missionFotoBesarCropper di atas -- cuma
    // rasio (10:9, mengikuti bingkai foto kanan Hero "Tentang Kami") dan
    // target property yang beda.
    Alpine.data('profilHeroFotoCropper', (existingPreviewUrl) => ({
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

                this.viewW = this.$refs.viewport.offsetWidth;
                this.viewH = this.$refs.viewport.offsetHeight;
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
                this.viewW = this.$refs.viewport.offsetWidth;
                this.viewH = this.$refs.viewport.offsetHeight;

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
            this.$wire.set('profilHeroFotoCroppedBase64', dataUrl);
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

    // Sama persis strukturnya dengan profilHeroFotoCropper di atas -- cuma
    // rasio (4:5, mengikuti bingkai foto section Sejarah) dan target property yang beda.
    Alpine.data('sejarahFotoCropper', (existingPreviewUrl) => ({
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

        ASPECT_W: 4,
        ASPECT_H: 5,

        viewW: 0,
        viewH: 0,

        OUT_W: 800,
        OUT_H: 1000,

        init() {
            this.$watch('open', (isOpen) => {
                document.body.style.overflow = isOpen ? 'hidden' : '';
            });

            window.addEventListener('resize', () => {
                if (!this.open || !this.$refs.viewport || this.natW === 0) return;

                this.viewW = this.$refs.viewport.offsetWidth;
                this.viewH = this.$refs.viewport.offsetHeight;
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
                this.viewW = this.$refs.viewport.offsetWidth;
                this.viewH = this.$refs.viewport.offsetHeight;

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
            this.$wire.set('sejarahFotoCroppedBase64', dataUrl);
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

    // Sama persis strukturnya dengan sejarahFotoCropper di atas -- cuma
    // rasio (4:5, mengikuti bingkai foto section Tentang Kami 2) dan target property yang beda.
    Alpine.data('tentangKami2FotoCropper', (existingPreviewUrl) => ({
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

        ASPECT_W: 4,
        ASPECT_H: 5,

        viewW: 0,
        viewH: 0,

        OUT_W: 800,
        OUT_H: 1000,

        init() {
            this.$watch('open', (isOpen) => {
                document.body.style.overflow = isOpen ? 'hidden' : '';
            });

            window.addEventListener('resize', () => {
                if (!this.open || !this.$refs.viewport || this.natW === 0) return;

                this.viewW = this.$refs.viewport.offsetWidth;
                this.viewH = this.$refs.viewport.offsetHeight;
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
                this.viewW = this.$refs.viewport.offsetWidth;
                this.viewH = this.$refs.viewport.offsetHeight;

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
            this.$wire.set('tentangKami2FotoCroppedBase64', dataUrl);
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

    // Cropper foto kartu section Nilai Kami: satu modal dipakai bersama 4 kartu
    // (indeks kartu dikirim lewat onFileChange). Strukturnya sama dengan
    // sejarahFotoCropper -- bedanya rasio 4:3 (mengikuti bingkai foto kartu)
    // dan hasilnya dikirim ke nilaiKamiFotoCropped.{indeks}.
    Alpine.data('nilaiKamiFotoCropper', (existingPreviewUrls) => ({
        open: false,
        rawImage: null,
        previews: existingPreviewUrls || [],
        activeIndex: 0,
        activeInput: null,
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

        ASPECT_W: 4,
        ASPECT_H: 3,

        viewW: 0,
        viewH: 0,

        OUT_W: 800,
        OUT_H: 600,

        init() {
            this.$watch('open', (isOpen) => {
                document.body.style.overflow = isOpen ? 'hidden' : '';
            });

            window.addEventListener('resize', () => {
                if (!this.open || !this.$refs.viewport || this.natW === 0) return;

                this.viewW = this.$refs.viewport.offsetWidth;
                this.viewH = this.$refs.viewport.offsetHeight;
                this.clampPos();
            });
        },

        onFileChange(e, index) {
            const file = e.target.files[0];
            if (!file) return;

            this.activeIndex = index;
            this.activeInput = e.target;

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
                this.viewW = this.$refs.viewport.offsetWidth;
                this.viewH = this.$refs.viewport.offsetHeight;

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
            this.previews[this.activeIndex] = dataUrl;
            this.$wire.set('nilaiKamiFotoCropped.' + this.activeIndex, dataUrl);
            this.closeModal();
        },

        cancelCrop() {
            this.closeModal();
        },

        closeModal() {
            this.open = false;
            this.rawImage = null;
            if (this.activeInput) this.activeInput.value = '';
        },
    }));
</script>
@endscript