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
        ['key' => 'why-choose-us', 'label' => 'Why Choose Us', 'icon' => 'fa-leaf', 'ready' => true],
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
     * Daftar section untuk grup "Produk" (frontend: /produk, halaman
     * katalog -- lihat resources/views/pages/frontend/produk-index.blade.php).
     * Baru "Warna" yang diaktifkan dulu, sisanya menyusul bertahap sama
     * seperti pola grup lain di atas.
     */
    public array $produkSections = [
        ['key' => 'warna', 'label' => 'Warna', 'icon' => 'fa-palette', 'ready' => true],
    ];

    /**
     * Warna latar halaman katalog Produk (section di bawah judul
     * "Produk" -- yang berisi Filter Options & daftar produk). Data
     * tersimpan di home_sections, section_key 'produk'. Bawaan
     * (belum diganti admin) = #FEEDD8, PERSIS sama dengan warna yang
     * sebelumnya hardcode di produk-index.blade.php supaya tampilan
     * tidak berubah sebelum admin menyimpan warna baru.
     */
    public bool $produkWarnaUseCustomBg = false;

    public string $produkWarnaBgColor = '#FEEDD8';

    public array $produkWarnaBgPresets = \App\Support\ColorPalette::PRESETS;

    /**
     * Warna latar section PALING ATAS halaman Produk -- bagian breadcrumb
     * yang menampilkan judul besar "Produk" & "Home / Produk", TEPAT DI
     * BAWAH navbar. Section terpisah dari Filter Options & daftar produk
     * di atas (2 warna berbeda, lihat produk-index.blade.php). Bawaan
     * (belum diganti admin) = #F6F9F6, PERSIS sama dengan warna yang
     * sebelumnya hardcode.
     */
    public bool $produkWarnaHeroUseCustomBg = false;

    public string $produkWarnaHeroBgColor = '#F6F9F6';

    public array $produkWarnaHeroBgPresets = \App\Support\ColorPalette::PRESETS;

    /**
     * Daftar section untuk grup "Dokumentasi". Cuma 1 section (hero teks
     * saja, tidak ada sub-halaman lain seperti Sejarah/Pengrajin dst di
     * "Tentang Kami"), jadi langsung `ready => true`.
     */
    public array $dokumentasiSections = [
        ['key' => 'dokumentasi', 'label' => 'Hero', 'icon' => 'fa-image', 'ready' => true],
        ['key' => 'dokumentasi-3', 'label' => 'Galeri Video', 'icon' => 'fa-video', 'ready' => true],
        ['key' => 'dokumentasi-foto', 'label' => 'Galeri Foto', 'icon' => 'fa-images', 'ready' => true],
    ];

    /**
     * Daftar section untuk grup "Our Craftsmen" (frontend: /pengrajin-kami --
     * lihat resources/views/pages/frontend/pengrajin.blade.php). Kartunya
     * sudah `ready` (bisa diklik, lihat $groups di bawah). Hero sudah aktif
     * (`ready => true`); Daftar Pengrajin & CTA masih `ready => false` --
     * sidebar tampil begitu masuk, tapi kedua item itu masih berlabel
     * "Segera" dan tidak bisa diklik, sampai diaktifkan menyusul satu per
     * satu (pola sama persis dengan $tentangKamiSections waktu pertama kali
     * dibuat).
     */
    /** Dua section halaman Sustainability: Hero + Prinsip Sustainability. */
    public array $sustainabilitySections = [
        ['key' => 'sustainability-hero', 'label' => 'Hero', 'icon' => 'fa-leaf', 'ready' => true],
        ['key' => 'sustainability-points', 'label' => 'Prinsip Sustainability', 'icon' => 'fa-seedling', 'ready' => true],
    ];
    /** Privacy Policy: Hero + 6 bagian + Kontak. */
    public array $privacySections = [
        ['key' => 'privacy-hero', 'label' => 'Hero', 'icon' => 'fa-lock', 'ready' => true],
        ['key' => 'privacy-information', 'label' => '1. Informasi yang Dikumpulkan', 'icon' => 'fa-database', 'ready' => true],
        ['key' => 'privacy-usage', 'label' => '2. Penggunaan Informasi', 'icon' => 'fa-gears', 'ready' => true],
        ['key' => 'privacy-security', 'label' => '3. Penyimpanan & Keamanan', 'icon' => 'fa-shield-halved', 'ready' => true],
        ['key' => 'privacy-sharing', 'label' => '4. Berbagi Informasi', 'icon' => 'fa-people-arrows', 'ready' => true],
        ['key' => 'privacy-rights', 'label' => '5. Hak atas Data Pribadi', 'icon' => 'fa-user-shield', 'ready' => true],
        ['key' => 'privacy-changes', 'label' => '6. Perubahan Kebijakan', 'icon' => 'fa-rotate', 'ready' => true],
        ['key' => 'privacy-contact', 'label' => 'Kontak', 'icon' => 'fa-envelope-open-text', 'ready' => true],
    ];

    /** Cookies: Hero + Ringkasan + Rincian Kategori + Pengaturan Browser + Kontak. */
    public array $cookiesSections = [
        ['key' => 'cookies-hero', 'label' => 'Hero', 'icon' => 'fa-cookie-bite', 'ready' => true],
        ['key' => 'cookies-summary', 'label' => 'Ringkasan', 'icon' => 'fa-circle-check', 'ready' => true],
        ['key' => 'cookies-categories', 'label' => 'Rincian per Kategori', 'icon' => 'fa-layer-group', 'ready' => true],
        ['key' => 'cookies-browser', 'label' => 'Mengatur Penyimpanan', 'icon' => 'fa-sliders', 'ready' => true],
        ['key' => 'cookies-contact', 'label' => 'Kontak', 'icon' => 'fa-comments', 'ready' => true],
    ];
    /** Terms of Service: Hero + 9 bagian ketentuan + Kontak. */
    public array $termsSections = [
        ['key' => 'terms-hero', 'label' => 'Hero', 'icon' => 'fa-file-contract', 'ready' => true],
        ['key' => 'terms-acceptance', 'label' => '1. Penerimaan Ketentuan', 'icon' => 'fa-file-signature', 'ready' => true],
        ['key' => 'terms-custom-order', 'label' => '2. Pemesanan Custom', 'icon' => 'fa-ruler-combined', 'ready' => true],
        ['key' => 'terms-payment', 'label' => '3. Harga & Pembayaran', 'icon' => 'fa-tags', 'ready' => true],
        ['key' => 'terms-delivery', 'label' => '4. Pengerjaan & Pengiriman', 'icon' => 'fa-truck-fast', 'ready' => true],
        ['key' => 'terms-cancellation', 'label' => '5. Pembatalan & Perubahan', 'icon' => 'fa-ban', 'ready' => true],
        ['key' => 'terms-warranty', 'label' => '6. Garansi & Pengembalian', 'icon' => 'fa-shield-heart', 'ready' => true],
        ['key' => 'terms-copyright', 'label' => '7. Hak Cipta & Konten', 'icon' => 'fa-copyright', 'ready' => true],
        ['key' => 'terms-liability', 'label' => '8. Batasan Tanggung Jawab', 'icon' => 'fa-scale-balanced', 'ready' => true],
        ['key' => 'terms-changes', 'label' => '9. Perubahan Ketentuan', 'icon' => 'fa-rotate', 'ready' => true],
        ['key' => 'terms-contact', 'label' => 'Kontak', 'icon' => 'fa-handshake', 'ready' => true],
    ];
    public array $ourCraftsmenSections = [
        ['key' => 'our-craftsmen-hero', 'label' => 'Hero', 'icon' => 'fa-image', 'ready' => true],
        ['key' => 'our-craftsmen-daftar', 'label' => 'Pemilik & Founder', 'icon' => 'fa-user-tie', 'ready' => true],
        ['key' => 'our-craftsmen-cta', 'label' => 'Mulai dari Sebuah Ide', 'icon' => 'fa-lightbulb', 'ready' => true],
    ];

    /**
     * Section Hero halaman "Our Craftsmen" (frontend: /pengrajin-kami --
     * lihat resources/views/pages/frontend/pengrajin.blade.php bagian
     * HERO). Yang bisa diedit: label kecil (eyebrow), judul, paragraf,
     * dan warna frame (latar section). Sama seperti Sejak Berdiri/Nilai
     * Kami dkk: warna judul & paragraf BUKAN field terpisah -- otomatis
     * dihitung dari kontras warna latar yang dipilih (lihat
     * $contrastOurCraftsmenHeroColors di pengrajin.blade.php), supaya
     * teks tidak pernah "bertabrakan" dengan warna latar apa pun.
     */
    // ================= SUSTAINABILITY =================

    public bool $sustainabilityHeroUseCustomBg = false;
    public string $sustainabilityHeroBgColor = '#F9F7F2';
    public string $sustainabilityHeroEyebrow = 'Sustainability';
    public string $sustainabilityHeroHeading = 'Kualitas yang Dibuat untuk Bertahan';
    public string $sustainabilityHeroDescription = 'Bagi kami, cara paling nyata untuk mengurangi limbah adalah membuat furnitur yang benar-benar awet dan tidak perlu cepat diganti â€” dikerjakan sesuai pesanan, dari bahan yang dipilih dengan hati-hati.';
    public array $sustainabilityHeroBgPresets = \App\Support\ColorPalette::PRESETS;

    public bool $sustainabilityPointsUseCustomBg = false;
    public string $sustainabilityPointsBgColor = '#FFFFFF';
    public array $sustainabilityPoints = [];
    public string $sustainabilityNote = 'Catatan: halaman ini menjelaskan prinsip kerja kami secara umum. Kami akan memperbarui halaman ini kalau ke depannya ada praktik atau sertifikasi keberlanjutan yang lebih spesifik untuk ditampilkan.';
    public array $sustainabilityPointsBgPresets = \App\Support\ColorPalette::PRESETS;
    public array $sustainabilityIconOptions = [
        'fa-tree', 'fa-ruler-combined', 'fa-hammer', 'fa-couch',
        'fa-leaf', 'fa-seedling', 'fa-recycle', 'fa-screwdriver-wrench',
    ];

    private function sustainabilityHeroDefaults(): array
    {
        return [
            'bg_color' => null,
            'eyebrow' => 'Sustainability',
            'heading' => 'Kualitas yang Dibuat untuk Bertahan',
            'description' => 'Bagi kami, cara paling nyata untuk mengurangi limbah adalah membuat furnitur yang benar-benar awet dan tidak perlu cepat diganti â€” dikerjakan sesuai pesanan, dari bahan yang dipilih dengan hati-hati.',
        ];
    }

    private function sustainabilityPointsDefaults(): array
    {
        return [
            'bg_color' => null,
            'items' => [
                ['icon' => 'fa-tree', 'title' => 'Bahan Baku Pilihan', 'text' => 'Setiap kayu diseleksi manual sebelum masuk proses produksi, supaya hasil akhirnya kuat dan awet dipakai bertahun-tahun.'],
                ['icon' => 'fa-ruler-combined', 'title' => 'Dibuat Sesuai Pesanan', 'text' => 'Produk custom dikerjakan sesuai ukuran & kebutuhan pemesan â€” mengurangi kelebihan stok dan sisa bahan yang terbuang percuma.'],
                ['icon' => 'fa-hammer', 'title' => 'Dikerjakan Tangan, Bukan Massal', 'text' => 'Diproses langsung oleh tukang kayu berpengalaman, bukan produksi pabrik â€” sehingga tiap detail bisa diperiksa satu per satu.'],
                ['icon' => 'fa-couch', 'title' => 'Furnitur untuk Jangka Panjang', 'text' => 'Kami merancang furnitur yang tahan lama secara struktur, bukan sekadar tampilan â€” supaya lebih jarang perlu diganti.'],
            ],
            'note' => 'Catatan: halaman ini menjelaskan prinsip kerja kami secara umum. Kami akan memperbarui halaman ini kalau ke depannya ada praktik atau sertifikasi keberlanjutan yang lebih spesifik untuk ditampilkan.',
        ];
    }
    // ================= PRIVACY POLICY =================

    public string $privacyHeroEyebrow = 'Privacy Policy';
    public string $privacyHeroHeading = 'Kebijakan Privasi';
    public string $privacyHeroDescription = 'Privasi Anda penting bagi kami. Halaman ini menjelaskan secara transparan informasi apa yang kami kumpulkan, mengapa kami membutuhkannya, dan bagaimana kami menjaganya.';
    public string $privacyHeroUpdatedDate = '2026-08-01';

    public array $privacyContent = [];

    public string $privacyContactHeading = 'Ada Pertanyaan Soal Privasi Anda?';
    public string $privacyContactDescription = 'Hubungi kami kapan saja - kami siap menjelaskan bagaimana data Anda dikelola.';
    public string $privacyContactButtonLabel = 'Hubungi via WhatsApp';

    private function privacyHeroDefaults(): array
    {
        return [
            'eyebrow' => 'Privacy Policy',
            'heading' => 'Kebijakan Privasi',
            'description' => 'Privasi Anda penting bagi kami. Halaman ini menjelaskan secara transparan informasi apa yang kami kumpulkan, mengapa kami membutuhkannya, dan bagaimana kami menjaganya.',
            'updated_date' => '2026-08-01',
        ];
    }

    private function privacyContentDefaults(): array
    {
        return [
            'information' => [
                'title' => '1. Informasi yang Kami Kumpulkan',
                'intro' => 'Kami hanya mengumpulkan informasi yang benar-benar Anda berikan sendiri saat berinteraksi dengan kami, antara lain:',
                'bullets' => [
                    'Nama, nomor telepon/WhatsApp, dan alamat pengiriman - saat Anda mengisi formulir booking custom furniture.',
                    'Pesan atau lampiran gambar referensi - saat Anda menghubungi kami melalui WhatsApp.',
                    'Riwayat produk yang dilihat - digunakan hanya untuk menampilkan rekomendasi produk serupa, tersimpan sementara di server.',
                    'Data favorit & keranjang belanja - tersimpan lokal di penyimpanan browser perangkat Anda sendiri, bukan di server kami, dan tidak memerlukan akun/login.',
                ],
                'outro' => '',
            ],
            'usage' => [
                'title' => '2. Bagaimana Kami Menggunakan Informasi Anda',
                'intro' => 'Informasi yang Anda berikan digunakan semata-mata untuk:',
                'bullets' => [
                    'Memproses dan mengonfirmasi pesanan custom furniture Anda.',
                    'Menghubungi Anda kembali terkait status pesanan melalui WhatsApp.',
                    'Menampilkan status pesanan pada halaman Lacak Pesanan menggunakan tautan unik milik Anda.',
                    'Meningkatkan kualitas katalog produk berdasarkan produk yang paling sering dilihat pembeli (secara agregat, tanpa mengidentifikasi individu).',
                ],
                'outro' => 'Kami tidak pernah menjual, menyewakan, atau memperdagangkan data pribadi Anda kepada pihak ketiga mana pun untuk kepentingan pemasaran.',
            ],
            'security' => [
                'title' => '3. Penyimpanan & Keamanan Data',
                'intro' => 'Data pesanan disimpan pada sistem basis data yang hanya dapat diakses oleh admin toko melalui panel administrasi yang dilindungi kata sandi. Kami menerapkan langkah keamanan yang wajar untuk mencegah akses, perubahan, atau pengungkapan data tanpa izin.',
                'bullets' => [],
                'outro' => 'Meski begitu, tidak ada metode transmisi data melalui internet yang 100% aman sepenuhnya. Kami senantiasa berupaya menjaga data Anda dengan sebaik mungkin.',
            ],
            'sharing' => [
                'title' => '4. Berbagi Informasi dengan Pihak Ketiga',
                'intro' => 'Kami hanya membagikan informasi yang diperlukan kepada:',
                'bullets' => [
                    'Layanan WhatsApp - sebagai kanal komunikasi utama yang Anda pilih sendiri untuk bernegosiasi dan konfirmasi pesanan.',
                    'Mitra pengiriman/ekspedisi - sebatas nama & alamat, semata-mata untuk keperluan pengantaran barang pesanan Anda.',
                ],
                'outro' => 'Kami tidak membagikan data Anda kepada pengiklan atau pihak ketiga lain di luar kebutuhan operasional di atas.',
            ],
            'rights' => [
                'title' => '5. Hak Anda atas Data Pribadi',
                'intro' => 'Anda berhak untuk:',
                'bullets' => [
                    'Meminta salinan data pribadi yang kami simpan terkait pesanan Anda.',
                    'Meminta koreksi apabila terdapat data yang keliru.',
                    'Meminta penghapusan data setelah pesanan selesai diproses, selama tidak melanggar kewajiban hukum/pembukuan yang berlaku.',
                ],
                'outro' => 'Untuk menggunakan hak-hak tersebut, silakan hubungi kami melalui kontak pada bagian akhir halaman ini.',
            ],
            'changes' => [
                'title' => '6. Perubahan Kebijakan Ini',
                'intro' => 'Kebijakan Privasi ini dapat kami perbarui sewaktu-waktu mengikuti perkembangan layanan. Tanggal pembaruan terakhir selalu tercantum di bagian atas halaman ini. Kami menganjurkan Anda meninjau halaman ini secara berkala.',
                'bullets' => [],
                'outro' => '',
            ],
        ];
    }

    private function privacyContactDefaults(): array
    {
        return [
            'heading' => 'Ada Pertanyaan Soal Privasi Anda?',
            'description' => 'Hubungi kami kapan saja - kami siap menjelaskan bagaimana data Anda dikelola.',
            'button_label' => 'Hubungi via WhatsApp',
        ];
    }

    // ================= COOKIES =================

    public string $cookiesHeroEyebrow = 'Cookies';
    public string $cookiesHeroHeading = 'Kebijakan Cookie';
    public string $cookiesHeroDescription = 'Kami menjaga situs ini seringan dan sesederhana mungkin. Berikut rincian lengkap dan jujur soal data apa saja yang tersimpan di perangkat Anda saat berkunjung ke Karya Ide Edi.';
    public string $cookiesHeroUpdatedDate = '2026-08-01';

    public string $cookiesSummaryLabel = 'Singkatnya:';
    public string $cookiesSummaryText = 'kami tidak memasang cookie pelacak iklan atau analitik pihak ketiga. Yang kami simpan di perangkat Anda hanya hal-hal yang membuat pengalaman belanja lebih nyaman - seperti daftar favorit dan keranjang Anda sendiri.';

    public string $cookiesCategoriesHeading = 'Rincian per Kategori';
    public string $cookiesCategoriesDescription = 'Kategori esensial & preferensi lokal tidak dapat kami hindari karena menjadi dasar fungsi belanja di situs ini. Sisanya, secara sadar tidak kami gunakan.';
    public array $cookiesCategoryItems = [];
    public string $cookiesStatusAlways = 'Selalu Aktif';
    public string $cookiesStatusLocal = 'Tersimpan di Perangkat Anda';
    public string $cookiesStatusNone = 'Tidak Digunakan';

    public string $cookiesBrowserHeading = 'Mengatur Penyimpanan di Browser Anda';
    public string $cookiesBrowserText = 'Karena favorit dan keranjang tersimpan secara lokal di browser Anda, Anda bisa menghapusnya kapan saja melalui pengaturan "Clear browsing data" / "Hapus data situs" pada browser yang Anda gunakan. Menghapus data ini hanya akan mengosongkan favorit & keranjang di perangkat tersebut - tidak memengaruhi pesanan yang sudah dikonfirmasi.';

    public string $cookiesContactHeading = 'Masih Ada yang Ingin Ditanyakan?';
    public string $cookiesContactDescription = 'Kami senang menjelaskan lebih lanjut soal bagaimana situs ini bekerja.';
    public string $cookiesContactButtonLabel = 'Hubungi via WhatsApp';

    private function cookiesHeroDefaults(): array
    {
        return [
            'eyebrow' => 'Cookies',
            'heading' => 'Kebijakan Cookie',
            'description' => 'Kami menjaga situs ini seringan dan sesederhana mungkin. Berikut rincian lengkap dan jujur soal data apa saja yang tersimpan di perangkat Anda saat berkunjung ke Karya Ide Edi.',
            'updated_date' => '2026-08-01',
        ];
    }

    private function cookiesSummaryDefaults(): array
    {
        return [
            'label' => 'Singkatnya:',
            'text' => 'kami tidak memasang cookie pelacak iklan atau analitik pihak ketiga. Yang kami simpan di perangkat Anda hanya hal-hal yang membuat pengalaman belanja lebih nyaman - seperti daftar favorit dan keranjang Anda sendiri.',
        ];
    }

    private function cookiesCategoriesDefaults(): array
    {
        return [
            'heading' => 'Rincian per Kategori',
            'description' => 'Kategori esensial & preferensi lokal tidak dapat kami hindari karena menjadi dasar fungsi belanja di situs ini. Sisanya, secara sadar tidak kami gunakan.',
            'items' => [
                ['name' => 'Esensial', 'desc' => 'Diperlukan agar situs berfungsi dengan baik - misalnya menjaga sesi login admin tetap aktif dan keamanan formulir. Tidak dapat dinonaktifkan karena situs tidak akan berjalan normal tanpanya.'],
                ['name' => 'Preferensi Lokal', 'desc' => 'Favorit dan Keranjang belanja Anda disimpan di localStorage browser perangkat ini (bukan cookie, dan bukan di server kami) agar tetap tersimpan saat Anda kembali berkunjung - tanpa perlu membuat akun.'],
                ['name' => 'Tautan Lacak Pesanan', 'desc' => 'Setelah pesanan dibuat, token pelacakan pesanan Anda didaftarkan pada perangkat ini supaya halaman Lacak Pesanan bisa langsung menampilkan riwayat pesanan Anda tanpa harus login.'],
                ['name' => 'Analitik Pihak Ketiga', 'desc' => 'Kami tidak memasang Google Analytics, Meta Pixel, atau alat pelacak iklan pihak ketiga mana pun di situs ini.'],
                ['name' => 'Iklan & Retargeting', 'desc' => 'Kami tidak membagikan data kunjungan Anda kepada jaringan iklan, dan tidak menampilkan iklan pihak ketiga di situs ini.'],
            ],
            'status_labels' => [
                'always' => 'Selalu Aktif',
                'local' => 'Tersimpan di Perangkat Anda',
                'none' => 'Tidak Digunakan',
            ],
        ];
    }

    private function cookiesBrowserDefaults(): array
    {
        return [
            'heading' => 'Mengatur Penyimpanan di Browser Anda',
            'text' => 'Karena favorit dan keranjang tersimpan secara lokal di browser Anda, Anda bisa menghapusnya kapan saja melalui pengaturan "Clear browsing data" / "Hapus data situs" pada browser yang Anda gunakan. Menghapus data ini hanya akan mengosongkan favorit & keranjang di perangkat tersebut - tidak memengaruhi pesanan yang sudah dikonfirmasi.',
        ];
    }

    private function cookiesContactDefaults(): array
    {
        return [
            'heading' => 'Masih Ada yang Ingin Ditanyakan?',
            'description' => 'Kami senang menjelaskan lebih lanjut soal bagaimana situs ini bekerja.',
            'button_label' => 'Hubungi via WhatsApp',
        ];
    }
    // ================= TERMS OF SERVICE =================

    public string $termsHeroEyebrow = 'Terms of Service';
    public string $termsHeroHeading = 'Ketentuan Layanan';
    public string $termsHeroDescription = 'Ketentuan berikut mengatur hubungan Anda dengan Karya Ide Edi - mulai dari proses pemesanan custom furniture hingga pengiriman ke tangan Anda.';
    public string $termsHeroUpdatedDate = '2026-08-01';

    public array $termsContent = [];

    public string $termsContactHeading = 'Butuh Penjelasan Lebih Lanjut?';
    public string $termsContactDescription = 'Tim kami siap membantu menjelaskan ketentuan pemesanan sebelum Anda melanjutkan booking.';
    public string $termsContactButtonLabel = 'Hubungi via WhatsApp';

    private function termsHeroDefaults(): array
    {
        return [
            'eyebrow' => 'Terms of Service',
            'heading' => 'Ketentuan Layanan',
            'description' => 'Ketentuan berikut mengatur hubungan Anda dengan Karya Ide Edi - mulai dari proses pemesanan custom furniture hingga pengiriman ke tangan Anda.',
            'updated_date' => '2026-08-01',
        ];
    }

    private function termsContentDefaults(): array
    {
        return [
            'acceptance' => [
                'title' => '1. Penerimaan Ketentuan',
                'text' => 'Dengan mengakses dan menggunakan situs Karya Ide Edi, melihat katalog produk, atau mengirimkan formulir booking custom furniture, Anda dianggap telah membaca, memahami, dan menyetujui seluruh ketentuan yang tercantum dalam halaman ini.',
            ],
            'custom-order' => [
                'title' => '2. Proses Pemesanan Custom',
                'text' => 'Setiap produk pada dasarnya bersifat custom-made (dibuat sesuai pesanan), sehingga:',
                'bullets' => [
                    'Pengiriman formulir booking bukan konfirmasi final - pesanan baru dianggap sah setelah dikonfirmasi kedua belah pihak melalui WhatsApp.',
                    'Spesifikasi (ukuran, bahan, warna, deskripsi custom) yang Anda kirimkan menjadi acuan utama proses produksi.',
                    'Perubahan spesifikasi setelah produksi dimulai dapat memengaruhi waktu pengerjaan dan biaya tambahan, dan akan didiskusikan terlebih dahulu.',
                ],
            ],
            'payment' => [
                'title' => '3. Harga & Pembayaran',
                'text' => 'Harga yang tercantum di katalog merupakan estimasi awal dan dapat berubah menyesuaikan kompleksitas custom, bahan, dan ukuran akhir yang disepakati saat negosiasi via WhatsApp. Skema pembayaran (termasuk uang muka/DP bila berlaku) akan diinformasikan dan disepakati bersama sebelum produksi dimulai - kami tidak memproses pembayaran otomatis melalui situs ini.',
            ],
            'delivery' => [
                'title' => '4. Waktu Pengerjaan & Pengiriman',
                'text' => 'Estimasi waktu pengerjaan disampaikan saat konfirmasi pesanan dan dapat bervariasi tergantung tingkat kesulitan desain serta antrian produksi yang sedang berjalan. Status terkini dapat dipantau kapan saja melalui halaman Lacak Pesanan menggunakan tautan unik yang diberikan untuk setiap pesanan.',
            ],
            'cancellation' => [
                'title' => '5. Pembatalan & Perubahan Pesanan',
                'text' => 'Pembatalan sebelum proses produksi dimulai dapat diajukan melalui WhatsApp dan akan diproses sesuai kesepakatan terkait pengembalian uang muka (bila ada). Setelah produksi berjalan, pembatalan menjadi lebih terbatas mengingat bahan dan waktu kerja yang sudah dialokasikan khusus untuk pesanan Anda - kondisi ini akan dijelaskan secara terbuka saat negosiasi.',
            ],
            'warranty' => [
                'title' => '6. Garansi & Pengembalian',
                'text' => 'Ketentuan lengkap mengenai garansi produk dan kebijakan retur diatur secara khusus pada halaman Profil, bagian Garansi & Pengiriman, agar informasinya selalu konsisten dan mudah ditemukan di satu tempat.',
                'link_labels' => [
                    'Lihat ketentuan Garansi',
                    'Lihat ketentuan Pengiriman & Retur',
                ],
            ],
            'copyright' => [
                'title' => '7. Hak Cipta & Konten Situs',
                'text' => 'Seluruh nama merek, logo, foto produk, dan konten pada situs ini adalah milik Karya Ide Edi dan dilindungi hak cipta. Penggunaan, penyalinan, atau reproduksi konten tanpa izin tertulis tidak diperkenankan.',
            ],
            'liability' => [
                'title' => '8. Batasan Tanggung Jawab',
                'text' => 'Kami berupaya menampilkan informasi produk dan harga seakurat mungkin, namun variasi kecil pada warna atau tekstur material alami (kayu) adalah hal wajar dan bukan merupakan cacat produk. Kami tidak bertanggung jawab atas keterlambatan yang disebabkan oleh faktor di luar kendali kami, seperti kendala pihak ekspedisi.',
            ],
            'changes' => [
                'title' => '9. Perubahan Ketentuan',
                'text' => 'Ketentuan Layanan ini dapat diperbarui sewaktu-waktu untuk menyesuaikan perkembangan layanan kami. Tanggal pembaruan terakhir selalu tercantum pada bagian atas halaman ini.',
            ],
        ];
    }

    private function termsContactDefaults(): array
    {
        return [
            'heading' => 'Butuh Penjelasan Lebih Lanjut?',
            'description' => 'Tim kami siap membantu menjelaskan ketentuan pemesanan sebelum Anda melanjutkan booking.',
            'button_label' => 'Hubungi via WhatsApp',
        ];
    }
    public bool $ourCraftsmenHeroUseCustomBg = false;

    public string $ourCraftsmenHeroBgColor = '#F9F7F2';

    public string $ourCraftsmenHeroEyebrow = 'Our Craftsmen';

    public string $ourCraftsmenHeroHeading = 'Tangan-Tangan di Balik Setiap Produk';

    public string $ourCraftsmenHeroDescription = 'Setiap produk Karya Ide Edi dibuat langsung oleh tukang kayu berpengalaman — bukan produksi massal. Berikut sebagian dari tim yang mengerjakan pesanan Anda dari awal sampai jadi.';

    /** @var array<int, array{label: string, value: string, check: string}> */
    public array $ourCraftsmenHeroBgPresets = \App\Support\ColorPalette::PRESETS;

    /**
     * @return array{bg_color: null, eyebrow: string, heading: string, description: string}
     */
    private function ourCraftsmenHeroDefaults(): array
    {
        return [
            'bg_color' => null,
            'eyebrow' => 'Our Craftsmen',
            'heading' => 'Tangan-Tangan di Balik Setiap Produk',
            'description' => 'Setiap produk Karya Ide Edi dibuat langsung oleh tukang kayu berpengalaman — bukan produksi massal. Berikut sebagian dari tim yang mengerjakan pesanan Anda dari awal sampai jadi.',
        ];
    }


    // ================= OUR CRAFTSMEN â€” PROFIL PEMILIK =================

    public bool $ourCraftsmenOwnerUseCustomBg = false;

    public string $ourCraftsmenOwnerBgColor = '#F8F5F0';

    public array $ourCraftsmenOwnerBgPresets = \App\Support\ColorPalette::PRESETS;

    public string $ourCraftsmenOwnerEyebrow = 'Pemilik & Founder';

    public string $ourCraftsmenOwnerName = 'Pemilik Karya Ide Edi';

    public string $ourCraftsmenOwnerRole = 'Founder & Creative Director';

    public string $ourCraftsmenOwnerHeading = 'Berawal dari ketelitian, tumbuh menjadi karya yang dipercaya.';

    public string $ourCraftsmenOwnerDescription = 'Karya Ide Edi dibangun dengan perhatian pada detail, fungsi, dan karakter setiap ruang. Setiap pesanan dikerjakan dengan pendekatan personal agar furnitur tidak hanya mengisi ruang, tetapi benar-benar menjadi bagian dari cerita pemiliknya.';

    public string $ourCraftsmenOwnerQuote = 'Bagi kami, furnitur yang baik bukan sekadar terlihat indah. Ia harus terasa tepat untuk orang dan ruang yang menggunakannya.';

    public ?string $ourCraftsmenOwnerPhotoPathLama = null;

    /** Hasil crop portrait 4:5. Foto baru WAJIB melewati cropper sebelum disimpan. */
    public ?string $ourCraftsmenOwnerPhotoCroppedBase64 = null;

    public $ourCraftsmenOwnerPhotoUpload = null;

    /** @var array<int, array{value:string,label:string}> */
    public array $ourCraftsmenOwnerStats = [
        ['value' => 'Custom', 'label' => 'Dibuat sesuai kebutuhan'],
        ['value' => 'Detail', 'label' => 'Fokus pada kerapian'],
        ['value' => 'Jepara', 'label' => 'Berbasis karya lokal'],
    ];

    private function ourCraftsmenOwnerDefaults(): array
    {
        return [
            'eyebrow' => 'Pemilik & Founder',
            'name' => 'Pemilik Karya Ide Edi',
            'role' => 'Founder & Creative Director',
            'heading' => 'Berawal dari ketelitian, tumbuh menjadi karya yang dipercaya.',
            'description' => 'Karya Ide Edi dibangun dengan perhatian pada detail, fungsi, dan karakter setiap ruang. Setiap pesanan dikerjakan dengan pendekatan personal agar furnitur tidak hanya mengisi ruang, tetapi benar-benar menjadi bagian dari cerita pemiliknya.',
            'quote' => 'Bagi kami, furnitur yang baik bukan sekadar terlihat indah. Ia harus terasa tepat untuk orang dan ruang yang menggunakannya.',
            'photo_path' => null,
            'stats' => [
                ['value' => 'Custom', 'label' => 'Dibuat sesuai kebutuhan'],
                ['value' => 'Detail', 'label' => 'Fokus pada kerapian'],
                ['value' => 'Jepara', 'label' => 'Berbasis karya lokal'],
            ],
        ];
    }

    // ================= OUR CRAFTSMEN â€” CTA =================

    public bool $ourCraftsmenCtaUseCustomBg = false;

    public string $ourCraftsmenCtaBgColor = '#21140D';

    public array $ourCraftsmenCtaBgPresets = \App\Support\ColorPalette::PRESETS;

    public string $ourCraftsmenCtaEyebrow = 'Mulai dari Sebuah Ide';

    public string $ourCraftsmenCtaHeading = 'Punya ide furnitur custom?';

    public string $ourCraftsmenCtaDescription = 'Ceritakan ukuran, fungsi, gaya, atau referensi yang Anda inginkan. Kami siap membantu menerjemahkannya menjadi furnitur yang sesuai dengan ruang Anda.';

    public string $ourCraftsmenCtaButtonText = 'Konsultasi via WhatsApp';

    private function ourCraftsmenCtaDefaults(): array
    {
        return [
            'eyebrow' => 'Mulai dari Sebuah Ide',
            'heading' => 'Punya ide furnitur custom?',
            'description' => 'Ceritakan ukuran, fungsi, gaya, atau referensi yang Anda inginkan. Kami siap membantu menerjemahkannya menjadi furnitur yang sesuai dengan ruang Anda.',
            'button_text' => 'Konsultasi via WhatsApp',
        ];
    }
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
     * Galeri "Dokumentasi" (foto & video jejak karya) yang tampil DI BAWAH
     * hero teks+video di atas -- lihat resources/views/pages/frontend/booking.blade.php,
     * bagian "GALERI DOKUMENTASI". Disimpan di section_key 'dokumentasi' yang
     * SAMA dengan hero (cuma nambah key 'galeri'), tidak mengubah data hero.
     *
     * Pola slot TETAP (SAMA seperti $existingAdditionalImages di
     * produk-form.blade.php), bukan daftar dinamis -- lebih sederhana &
     * konsisten dengan konvensi yang sudah ada di project ini.
     */
    public const DOKUMENTASI_GALERI_SLOTS = 6;

    /** Tipe media tiap slot: 'foto' atau 'video'. */
    public array $dokumentasiGaleriTipe = ['foto', 'foto', 'foto', 'foto', 'foto', 'foto'];

    /** Keterangan singkat tiap slot (opsional, ditampilkan kecil di galeri). */
    public array $dokumentasiGaleriKeterangan = ['', '', '', '', '', ''];

    /** Path foto/video yang SUDAH tersimpan per slot. Null = slot belum diisi. */
    public array $dokumentasiGaleriPathLama = [null, null, null, null, null, null];

    /** File baru yang di-upload admin tapi belum disimpan, per slot (Livewire TemporaryUploadedFile). */
    public array $dokumentasiGaleriUploadBaru = [null, null, null, null, null, null];

    /**
     * ================= SECTION TAMBAHAN HALAMAN DOKUMENTASI =================
     *
     * Ini SECTION BARU yang ditaruh DI BAWAH hero + galeri di atas -- hero dan
     * galeri TIDAK diubah sedikit pun oleh fitur ini. Lihat bagian "SECTION
     * TAMBAHAN: DOKUMENTASI 3" di
     * resources/views/pages/frontend/booking.blade.php.
     *
     *   Dokumentasi 3 -> kartu foto/video bertumpuk miring 3D
     *                    (section_key 'dokumentasi-3')
     *
     * TIDAK ADA BATAS JUMLAH foto/video di section ini -- admin bisa
     * menambah (tombol "Tambah") atau menghapus item sebanyak yang mau.
     * Karena itu setiap item DIKUNCI DENGAN KEY STRING (UUID), bukan indeks
     * angka biasa: index bisa berubah kalau ada item yang dihapus di
     * tengah, sedangkan key UUID selalu tetap sama untuk item yang sama --
     * ini supaya wire:model & upload file per item tidak pernah nyasar ke
     * item lain, dan wire:key di blade bisa dipasang stabil per baris.
     *
     * $dokNKeys menyimpan URUTAN item (array of string), sedangkan properti
     * lain ($dokNTipe, $dokNKeterangan, dst) adalah array asosiatif yang
     * di-index oleh key yang sama.
     */

    // ---- Dokumentasi 3 (3D Tilt Card Stack) ----

    public string $dok3Judul = 'Arsip Pilihan';

    public string $dok3Subjudul = 'Kartu Karya';

    public string $dok3Deskripsi = '';

    /** Urutan kartu, berisi key UUID. */
    public array $dok3Keys = [];

    public array $dok3Tipe = [];

    public array $dok3Keterangan = [];

    public array $dok3PathLama = [];

    public array $dok3UploadBaru = [];

    // ---- Galeri Foto Dokumentasi (khusus FOTO) ----
    public string $dokFotoJudul = 'Momen Karya dalam Bingkai';
    public string $dokFotoSubjudul = 'Galeri Foto';
    public string $dokFotoDeskripsi = 'Dokumentasi visual yang menampilkan proses, detail pengerjaan, hingga hasil akhir furnitur secara lebih dekat, bersih, dan profesional.';
    public array $dokFotoKeys = [];
    public array $dokFotoTipe = [];
    public array $dokFotoKeterangan = [];
    public array $dokFotoPathLama = [];
    public array $dokFotoUploadBaru = [];


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
            'description' => 'Tentang Kami, Sejarah, Nilai Kami, Why Choose Us.',
            'ready' => true,
        ],
        [
            'key' => 'produk',
            'label' => 'Produk',
            'icon' => 'fa-box-open',
            'description' => 'Warna.',
            'ready' => true,
        ],
        [
            'key' => 'dokumentasi',
            'label' => 'Dokumentasi',
            'icon' => 'fa-images',
            'description' => 'Hero, Galeri Video, Galeri Foto, dan Pita Foto & Video.',
            'ready' => true,
        ],
        [
            'key' => 'sustainability',
            'label' => 'Sustainability',
            'icon' => 'fa-leaf',
            'description' => 'Hero dan Prinsip Sustainability.',
            'ready' => true,
        ],        [
            'key' => 'privacy-policy',
            'label' => 'Privacy Policy',
            'icon' => 'fa-user-shield',
            'description' => 'Hero, 6 bagian kebijakan privasi, dan Kontak.',
            'ready' => true,
        ],
        [
            'key' => 'cookies',
            'label' => 'Cookies',
            'icon' => 'fa-cookie-bite',
            'description' => 'Hero, Ringkasan, Rincian per Kategori, Pengaturan Browser, dan Kontak.',
            'ready' => true,
        ],
        [
            'key' => 'terms-of-service',
            'label' => 'Terms of Service',
            'icon' => 'fa-file-contract',
            'description' => 'Hero, 9 bagian ketentuan layanan, dan Kontak.',
            'ready' => true,
        ],
        [
            'key' => 'our-craftsmen',
            'label' => 'Our Craftsmen',
            'icon' => 'fa-user-gear',
            'description' => 'Hero, Pemilik & Founder, Mulai dari Sebuah Ide.',
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
            'produk' => $this->produkSections[0]['key'],
            'dokumentasi' => $this->dokumentasiSections[0]['key'],
            'sustainability' => $this->sustainabilitySections[0]['key'],
            'privacy-policy' => $this->privacySections[0]['key'],
            'cookies' => $this->cookiesSections[0]['key'],
            'terms-of-service' => $this->termsSections[0]['key'],
            'our-craftsmen' => $this->ourCraftsmenSections[0]['key'],
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

    // ================= WHY CHOOSE US (halaman Tentang Kami) =================

    /**
     * Section "Why Choose Us" halaman Tentang Kami (frontend: /profil, blok
     * "D. WHY CHOOSE US" -- section gelap paling bawah: teks di kiri, 5
     * kartu poin di kanan; lihat resources/views/pages/frontend/profil.blade.php).
     * Yang bisa diedit: warna frame (warna polos + gradasi opsional), isi
     * teks (label kecil, judul, paragraf, teks tiap kartu), dan ikon tiap
     * kartu. Disimpan di home_sections dengan section_key 'why-choose-us-profil'.
     */
    public string $whyChooseUsEyebrow = 'Why Choose Us';

    public string $whyChooseUsHeading = '';

    public string $whyChooseUsDescription = '';

    /** @var array<int, array{icon: string, text: string}> Selalu tepat 5 kartu. */
    public array $whyChooseUsItems = [];

    public bool $whyChooseUsUseCustomBg = false;

    /** Warna bawaan section ini (cokelat gelap) -- hanya awalan color picker. */
    public string $whyChooseUsBgColor = '#221B14';

    /**
     * Palet warna rekomendasi untuk: Why Choose Us.
     * SATU daftar yang sama untuk SEMUA pilihan warna di Edit Web -- ubah/tambah
     * warna di app/Support/ColorPalette.php, jangan bikin daftar sendiri di sini.
     *
     * @var array<int, array{label: string, value: string, check: string}>
     */
    public array $whyChooseUsBgPresets = \App\Support\ColorPalette::PRESETS;

    /**
     * Pilihan ikon kartu -- dibatasi (bukan input bebas) supaya nama class
     * FontAwesome yang tersimpan selalu valid, sama semangatnya dengan
     * $keunggulanIconOptions. Dikunci (Locked) supaya daftar ini tidak bisa
     * diubah dari browser.
     *
     * @var array<int, string>
     */
    #[\Livewire\Attributes\Locked]
    public array $whyChooseUsIconOptions = [
        'fa-layer-group', 'fa-circle-info', 'fa-cart-shopping', 'fa-headset',
        'fa-couch', 'fa-gem', 'fa-award', 'fa-medal',
        'fa-star', 'fa-heart', 'fa-hand-holding-heart', 'fa-handshake',
        'fa-thumbs-up', 'fa-circle-check', 'fa-shield-halved', 'fa-truck-fast',
        'fa-comment-dots', 'fa-clock', 'fa-house', 'fa-tree',
        'fa-hammer', 'fa-ruler-combined', 'fa-screwdriver-wrench', 'fa-box-open',
    ];

    /**
     * Nilai bawaan HARUS sama dengan yang ada di
     * resources/views/pages/frontend/profil.blade.php (bagian D).
     */
    private function whyChooseUsDefaults(): array
    {
        $siteName = \App\Models\Setting::current()->site_name;

        return [
            'bg_color' => null,
            'eyebrow' => 'Why Choose Us',
            'heading' => 'Kenapa memilih '.$siteName.'?',
            'description' => "Kami ingin proses memilih furniture terasa mudah dan tenang \u{2014} dari melihat produk sampai memutuskan yang paling cocok untuk ruang Anda.",
            'items' => [
                ['icon' => 'fa-layer-group', 'text' => 'Produk pilihan'],
                ['icon' => 'fa-circle-info', 'text' => 'Informasi produk yang jelas'],
                ['icon' => 'fa-cart-shopping', 'text' => 'Proses pemesanan mudah'],
                ['icon' => 'fa-headset', 'text' => 'Dukungan pelanggan'],
                ['icon' => 'fa-couch', 'text' => 'Pengalaman belanja yang nyaman'],
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
            'galeri' => [],
        ];
    }

    private function dokumentasi3Defaults(): array
    {
        return [
            'judul' => 'Galeri Video',
            'subjudul' => 'Dokumentasi Proses & Hasil',
            'deskripsi' => 'Kumpulan video proses kerja dan hasil akhir furnitur Karya Ide Edi. Tampil rapi seperti katalog, maksimal 4 video per baris lalu lanjut otomatis ke bawah.',
            'items' => [],
        ];
    }

    private function dokumentasiFotoDefaults(): array
    {
        return [
            'judul' => 'Momen Karya dalam Bingkai',
            'subjudul' => 'Galeri Foto',
            'deskripsi' => 'Dokumentasi visual yang menampilkan proses, detail pengerjaan, hingga hasil akhir furnitur secara lebih dekat, bersih, dan profesional.',
            'items' => [],
        ];
    }

    /**
     * Isi bawaan galeri Dokumentasi -- HANYA dipakai kalau admin belum
     * pernah menyimpan galeri sama sekali (pertama kali fitur ini aktif).
     * Sengaja memakai foto produk & video "Kenapa Pilih Kami" yang SUDAH
     * ada di database, supaya galeri tidak kosong melompong tanpa perlu
     * menambah file baru apa pun ke storage. Begitu admin menyimpan galeri
     * lewat form (walau cuma ubah keterangan), isi bawaan ini tidak
     * dipakai lagi -- yang tersimpan di home_sections jadi acuan seterusnya.
     */
    private function dokumentasiGaleriDefaults(): array
    {
        $items = \App\Models\Product::query()
            ->whereNotNull('thumbnail')
            ->latest()
            ->limit(self::DOKUMENTASI_GALERI_SLOTS)
            ->get(['nama', 'thumbnail'])
            ->map(fn ($produk) => [
                'tipe' => 'foto',
                'path' => $produk->thumbnail,
                'keterangan' => $produk->nama,
            ])
            ->values()
            ->all();


        return $items;
    }

    /**
     * Isi properti dinamis ($dok{prefix}Keys/Tipe/Keterangan/PathLama) dari
     * array item yang tersimpan di database, dengan MEMBUAT KEY UUID BARU
     * untuk tiap item -- key ini tidak perlu sama dengan sesi sebelumnya,
     * cuma perlu stabil SELAMA request/komponen ini hidup (dipakai untuk
     * wire:model & wire:key). $prefix contohnya 'dok1', 'dok2', 'dok3'.
     */
    private function muatItemDinamis(string $prefix, array $items): void
    {
        $keysProp = "{$prefix}Keys";
        $tipeProp = "{$prefix}Tipe";
        $ketProp = "{$prefix}Keterangan";
        $pathProp = "{$prefix}PathLama";

        $this->$keysProp = [];
        $this->$tipeProp = [];
        $this->$ketProp = [];
        $this->$pathProp = [];

        foreach ($items as $item) {
            if (! is_array($item) || empty($item['path'])) {
                continue;
            }

            $key = (string) Str::uuid();

            $this->$keysProp[] = $key;
            $this->{$tipeProp}[$key] = in_array($item['tipe'] ?? null, ['foto', 'video'], true) ? $item['tipe'] : 'foto';
            $this->{$ketProp}[$key] = (string) ($item['keterangan'] ?? '');
            $this->{$pathProp}[$key] = $item['path'];
        }
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
        // Data lama sempat punya media_type "photo" walaupun video upload
        // sudah tersimpan. Kalau itu terjadi, utamakan video_path yang memang
        // milik section "keahlian" agar video kembali tampil di Beranda.
        $this->keahlianMediaType = in_array($keahlianData['media_type'] ?? null, ['video_url', 'video_upload'], true)
            ? $keahlianData['media_type']
            : (filled($keahlianData['video_path'] ?? null) ? 'video_upload' : 'video_url');
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

        $whyChooseUsDefaults = $this->whyChooseUsDefaults();
        $whyChooseUsData = HomeSection::dataFor('why-choose-us-profil', $whyChooseUsDefaults);
        $this->whyChooseUsUseCustomBg = filled($whyChooseUsData['bg_color']);
        $this->whyChooseUsBgColor = $whyChooseUsData['bg_color'] ?: $this->whyChooseUsBgColor;
        $this->whyChooseUsEyebrow = (string) $whyChooseUsData['eyebrow'];
        $this->whyChooseUsHeading = (string) $whyChooseUsData['heading'];
        $this->whyChooseUsDescription = (string) $whyChooseUsData['description'];

        foreach (range(0, 4) as $i) {
            $item = $whyChooseUsData['items'][$i] ?? $whyChooseUsDefaults['items'][$i];
            $icon = (string) ($item['icon'] ?? '');

            $this->whyChooseUsItems[$i] = [
                'icon' => in_array($icon, $this->whyChooseUsIconOptions, true) ? $icon : $whyChooseUsDefaults['items'][$i]['icon'],
                'text' => (string) ($item['text'] ?? $whyChooseUsDefaults['items'][$i]['text']),
            ];
        }

        $dokumentasiDefaults = $this->dokumentasiDefaults();
        $dokumentasiData = HomeSection::dataFor('dokumentasi', $dokumentasiDefaults);
        $this->dokumentasiJudul = $dokumentasiData['judul'];
        $this->dokumentasiSubjudul = $dokumentasiData['subjudul'];
        $this->dokumentasiDeskripsi = $dokumentasiData['deskripsi'];
        // Sama seperti "Kenapa Pilih Kami": kalau ada data lama tanpa
        // media_type yang valid tetapi video upload milik Dokumentasi sudah
        // tersimpan, jangan salah mengarahkannya ke tab URL.
        $this->dokumentasiMediaType = in_array($dokumentasiData['media_type'] ?? null, ['video_url', 'video_upload'], true)
            ? $dokumentasiData['media_type']
            : (filled($dokumentasiData['video_path'] ?? null) ? 'video_upload' : 'video_url');
        $this->dokumentasiVideoUrl = $dokumentasiData['video_url'] ?? '';
        $this->dokumentasiVideoPathLama = $dokumentasiData['video_path'] ?? null;

        $dokumentasiGaleriTersimpan = $dokumentasiData['galeri'] ?? [];
        $dokumentasiGaleriTersimpan = is_array($dokumentasiGaleriTersimpan) && $dokumentasiGaleriTersimpan !== []
            ? $dokumentasiGaleriTersimpan
            : $this->dokumentasiGaleriDefaults();

        foreach (range(0, self::DOKUMENTASI_GALERI_SLOTS - 1) as $i) {
            $item = $dokumentasiGaleriTersimpan[$i] ?? null;
            $this->dokumentasiGaleriTipe[$i] = in_array($item['tipe'] ?? null, ['foto', 'video'], true) ? $item['tipe'] : 'foto';
            $this->dokumentasiGaleriKeterangan[$i] = (string) ($item['keterangan'] ?? '');
            $this->dokumentasiGaleriPathLama[$i] = $item['path'] ?? null;
        }

        // ---- section tambahan halaman Dokumentasi (jumlah item BEBAS,
        //      lihat catatan "TIDAK ADA BATAS JUMLAH" di atas properti) ----
        $dok3Data = HomeSection::dataFor('dokumentasi-3', $this->dokumentasi3Defaults());
        $this->dok3Judul = (string) $dok3Data['judul'];
        $this->dok3Subjudul = (string) $dok3Data['subjudul'];
        $this->dok3Deskripsi = (string) $dok3Data['deskripsi'];
        $this->muatItemDinamis('dok3', $dok3Data['items'] ?? []);

        $dokFotoData = HomeSection::dataFor('dokumentasi-foto', $this->dokumentasiFotoDefaults());
        $this->dokFotoJudul = (string) ($dokFotoData['judul'] ?? $this->dokFotoJudul);
        $this->dokFotoSubjudul = (string) ($dokFotoData['subjudul'] ?? $this->dokFotoSubjudul);
        $this->dokFotoDeskripsi = (string) ($dokFotoData['deskripsi'] ?? $this->dokFotoDeskripsi);
        $dokFotoItems = is_array($dokFotoData['items'] ?? null) ? $dokFotoData['items'] : [];
        if ($dokFotoItems === []) {
            $dokFotoItems = collect($dokumentasiData['galeri'] ?? [])->filter(fn ($item) => is_array($item) && ($item['tipe'] ?? null) === 'foto')->values()->all();
        }
        $this->muatItemDinamis('dokFoto', $dokFotoItems);
        foreach ($this->dokFotoKeys as $key) { $this->dokFotoTipe[$key] = 'foto'; }


        $produkWarnaData = HomeSection::dataFor('produk', ['bg_color' => null, 'bg_color_hero' => null]);
        $this->produkWarnaUseCustomBg = filled($produkWarnaData['bg_color']);
        $this->produkWarnaBgColor = $produkWarnaData['bg_color'] ?: $this->produkWarnaBgColor;
        $this->produkWarnaHeroUseCustomBg = filled($produkWarnaData['bg_color_hero']);
        $this->produkWarnaHeroBgColor = $produkWarnaData['bg_color_hero'] ?: $this->produkWarnaHeroBgColor;

        // Gradasi "Warna Frame" tiap section (kalau belum pernah diatur -> mati, frame polos).
        $this->loadFrameGradient('mission', $missionData['bg_gradient'] ?? null);
        $this->loadFrameGradient('produkUnggulan', $produkUnggulanData['bg_gradient'] ?? null);
        $this->loadFrameGradient('kategori', $kategoriData['bg_gradient'] ?? null);
        $this->loadFrameGradient('testimoni', $testimoniData['bg_gradient'] ?? null);
        $this->loadFrameGradient('lokasi', $lokasiData['bg_gradient'] ?? null);
        $this->loadFrameGradient('sejarah', $sejarahData['bg_gradient'] ?? null);
        $this->loadFrameGradient('tentangKami2', $tentangKami2Data['bg_gradient'] ?? null);
        $this->loadFrameGradient('nilaiKami', $nilaiKamiData['bg_gradient'] ?? null);
        $this->loadFrameGradient('whyChooseUs', $whyChooseUsData['bg_gradient'] ?? null);

        $sustainHeroData = HomeSection::dataFor('sustainability-hero', $this->sustainabilityHeroDefaults());
        $this->sustainabilityHeroUseCustomBg = filled($sustainHeroData['bg_color'] ?? null);
        $this->sustainabilityHeroBgColor = $sustainHeroData['bg_color'] ?: $this->sustainabilityHeroBgColor;
        $this->sustainabilityHeroEyebrow = (string) $sustainHeroData['eyebrow'];
        $this->sustainabilityHeroHeading = (string) $sustainHeroData['heading'];
        $this->sustainabilityHeroDescription = (string) $sustainHeroData['description'];

        $sustainPointsData = HomeSection::dataFor('sustainability-points', $this->sustainabilityPointsDefaults());
        $this->sustainabilityPointsUseCustomBg = filled($sustainPointsData['bg_color'] ?? null);
        $this->sustainabilityPointsBgColor = $sustainPointsData['bg_color'] ?: $this->sustainabilityPointsBgColor;
        $this->sustainabilityPoints = array_values(is_array($sustainPointsData['items'] ?? null) ? $sustainPointsData['items'] : $this->sustainabilityPointsDefaults()['items']);
        $this->sustainabilityNote = (string) ($sustainPointsData['note'] ?? $this->sustainabilityPointsDefaults()['note']);
        $privacyHeroData = HomeSection::dataFor('privacy-hero', $this->privacyHeroDefaults());
        $this->privacyHeroEyebrow = (string) ($privacyHeroData['eyebrow'] ?? $this->privacyHeroEyebrow);
        $this->privacyHeroHeading = (string) ($privacyHeroData['heading'] ?? $this->privacyHeroHeading);
        $this->privacyHeroDescription = (string) ($privacyHeroData['description'] ?? $this->privacyHeroDescription);
        $this->privacyHeroUpdatedDate = (string) ($privacyHeroData['updated_date'] ?? $this->privacyHeroUpdatedDate);

        $this->privacyContent = [];
        foreach ($this->privacyContentDefaults() as $privacyKey => $privacyDefault) {
            $privacyData = HomeSection::dataFor('privacy-'.$privacyKey, $privacyDefault);

            $this->privacyContent[$privacyKey] = [
                'title' => (string) ($privacyData['title'] ?? $privacyDefault['title']),
                'intro' => (string) ($privacyData['intro'] ?? $privacyDefault['intro']),
                'bullets' => array_values(is_array($privacyData['bullets'] ?? null) ? $privacyData['bullets'] : $privacyDefault['bullets']),
                'outro' => (string) ($privacyData['outro'] ?? $privacyDefault['outro']),
            ];
        }

        $privacyContactData = HomeSection::dataFor('privacy-contact', $this->privacyContactDefaults());
        $this->privacyContactHeading = (string) ($privacyContactData['heading'] ?? $this->privacyContactHeading);
        $this->privacyContactDescription = (string) ($privacyContactData['description'] ?? $this->privacyContactDescription);
        $this->privacyContactButtonLabel = (string) ($privacyContactData['button_label'] ?? $this->privacyContactButtonLabel);

        $cookiesHeroData = HomeSection::dataFor('cookies-hero', $this->cookiesHeroDefaults());
        $this->cookiesHeroEyebrow = (string) ($cookiesHeroData['eyebrow'] ?? $this->cookiesHeroEyebrow);
        $this->cookiesHeroHeading = (string) ($cookiesHeroData['heading'] ?? $this->cookiesHeroHeading);
        $this->cookiesHeroDescription = (string) ($cookiesHeroData['description'] ?? $this->cookiesHeroDescription);
        $this->cookiesHeroUpdatedDate = (string) ($cookiesHeroData['updated_date'] ?? $this->cookiesHeroUpdatedDate);

        $cookiesSummaryData = HomeSection::dataFor('cookies-summary', $this->cookiesSummaryDefaults());
        $this->cookiesSummaryLabel = (string) ($cookiesSummaryData['label'] ?? $this->cookiesSummaryLabel);
        $this->cookiesSummaryText = (string) ($cookiesSummaryData['text'] ?? $this->cookiesSummaryText);

        $cookiesCategoriesData = HomeSection::dataFor('cookies-categories', $this->cookiesCategoriesDefaults());
        $this->cookiesCategoriesHeading = (string) ($cookiesCategoriesData['heading'] ?? $this->cookiesCategoriesHeading);
        $this->cookiesCategoriesDescription = (string) ($cookiesCategoriesData['description'] ?? $this->cookiesCategoriesDescription);
        $this->cookiesCategoryItems = array_values(is_array($cookiesCategoriesData['items'] ?? null) ? $cookiesCategoriesData['items'] : $this->cookiesCategoriesDefaults()['items']);
        $cookiesStatusLabels = is_array($cookiesCategoriesData['status_labels'] ?? null) ? $cookiesCategoriesData['status_labels'] : $this->cookiesCategoriesDefaults()['status_labels'];
        $this->cookiesStatusAlways = (string) ($cookiesStatusLabels['always'] ?? $this->cookiesStatusAlways);
        $this->cookiesStatusLocal = (string) ($cookiesStatusLabels['local'] ?? $this->cookiesStatusLocal);
        $this->cookiesStatusNone = (string) ($cookiesStatusLabels['none'] ?? $this->cookiesStatusNone);

        $cookiesBrowserData = HomeSection::dataFor('cookies-browser', $this->cookiesBrowserDefaults());
        $this->cookiesBrowserHeading = (string) ($cookiesBrowserData['heading'] ?? $this->cookiesBrowserHeading);
        $this->cookiesBrowserText = (string) ($cookiesBrowserData['text'] ?? $this->cookiesBrowserText);

        $cookiesContactData = HomeSection::dataFor('cookies-contact', $this->cookiesContactDefaults());
        $this->cookiesContactHeading = (string) ($cookiesContactData['heading'] ?? $this->cookiesContactHeading);
        $this->cookiesContactDescription = (string) ($cookiesContactData['description'] ?? $this->cookiesContactDescription);
        $this->cookiesContactButtonLabel = (string) ($cookiesContactData['button_label'] ?? $this->cookiesContactButtonLabel);
        $termsHeroData = HomeSection::dataFor('terms-hero', $this->termsHeroDefaults());
        $this->termsHeroEyebrow = (string) ($termsHeroData['eyebrow'] ?? $this->termsHeroEyebrow);
        $this->termsHeroHeading = (string) ($termsHeroData['heading'] ?? $this->termsHeroHeading);
        $this->termsHeroDescription = (string) ($termsHeroData['description'] ?? $this->termsHeroDescription);
        $this->termsHeroUpdatedDate = (string) ($termsHeroData['updated_date'] ?? $this->termsHeroUpdatedDate);

        $this->termsContent = [];
        foreach ($this->termsContentDefaults() as $termsKey => $termsDefault) {
            $termsData = HomeSection::dataFor('terms-'.$termsKey, $termsDefault);

            $this->termsContent[$termsKey] = [
                'title' => (string) ($termsData['title'] ?? $termsDefault['title']),
                'text' => (string) ($termsData['text'] ?? $termsDefault['text']),
            ];

            if (isset($termsDefault['bullets'])) {
                $this->termsContent[$termsKey]['bullets'] = array_values(
                    is_array($termsData['bullets'] ?? null) ? $termsData['bullets'] : $termsDefault['bullets']
                );
            }

            if (isset($termsDefault['link_labels'])) {
                $this->termsContent[$termsKey]['link_labels'] = array_values(
                    is_array($termsData['link_labels'] ?? null) ? $termsData['link_labels'] : $termsDefault['link_labels']
                );
            }
        }

        $termsContactData = HomeSection::dataFor('terms-contact', $this->termsContactDefaults());
        $this->termsContactHeading = (string) ($termsContactData['heading'] ?? $this->termsContactHeading);
        $this->termsContactDescription = (string) ($termsContactData['description'] ?? $this->termsContactDescription);
        $this->termsContactButtonLabel = (string) ($termsContactData['button_label'] ?? $this->termsContactButtonLabel);
        $ourCraftsmenHeroDefaults = $this->ourCraftsmenHeroDefaults();
        $ourCraftsmenHeroData = HomeSection::dataFor('our-craftsmen-hero', $ourCraftsmenHeroDefaults);
        $this->ourCraftsmenHeroUseCustomBg = filled($ourCraftsmenHeroData['bg_color']);
        $this->ourCraftsmenHeroBgColor = $ourCraftsmenHeroData['bg_color'] ?: $this->ourCraftsmenHeroBgColor;
        $this->ourCraftsmenHeroEyebrow = $ourCraftsmenHeroData['eyebrow'];
        $this->ourCraftsmenHeroHeading = $ourCraftsmenHeroData['heading'];
        $this->ourCraftsmenHeroDescription = $ourCraftsmenHeroData['description'];
        $this->loadFrameGradient('ourCraftsmenHero', $ourCraftsmenHeroData['bg_gradient'] ?? null);

        $ourCraftsmenOwnerData = HomeSection::dataFor('our-craftsmen-daftar', $this->ourCraftsmenOwnerDefaults());
        $this->ourCraftsmenOwnerUseCustomBg = filled($ourCraftsmenOwnerData['bg_color'] ?? null);
        $this->ourCraftsmenOwnerBgColor = $ourCraftsmenOwnerData['bg_color'] ?? $this->ourCraftsmenOwnerBgColor;
        $this->ourCraftsmenOwnerEyebrow = (string) ($ourCraftsmenOwnerData['eyebrow'] ?? $this->ourCraftsmenOwnerEyebrow);
        $this->ourCraftsmenOwnerName = (string) ($ourCraftsmenOwnerData['name'] ?? $this->ourCraftsmenOwnerName);
        $this->ourCraftsmenOwnerRole = (string) ($ourCraftsmenOwnerData['role'] ?? $this->ourCraftsmenOwnerRole);
        $this->ourCraftsmenOwnerHeading = (string) ($ourCraftsmenOwnerData['heading'] ?? $this->ourCraftsmenOwnerHeading);
        $this->ourCraftsmenOwnerDescription = (string) ($ourCraftsmenOwnerData['description'] ?? $this->ourCraftsmenOwnerDescription);
        $this->ourCraftsmenOwnerQuote = (string) ($ourCraftsmenOwnerData['quote'] ?? $this->ourCraftsmenOwnerQuote);
        $this->ourCraftsmenOwnerPhotoPathLama = $ourCraftsmenOwnerData['photo_path'] ?? null;
        $ownerStats = is_array($ourCraftsmenOwnerData['stats'] ?? null) ? array_values($ourCraftsmenOwnerData['stats']) : [];
        $this->ourCraftsmenOwnerStats = count($ownerStats) === 3 ? $ownerStats : $this->ourCraftsmenOwnerStats;

        $ourCraftsmenCtaData = HomeSection::dataFor('our-craftsmen-cta', $this->ourCraftsmenCtaDefaults());
        $this->ourCraftsmenCtaUseCustomBg = filled($ourCraftsmenCtaData['bg_color'] ?? null);
        $this->ourCraftsmenCtaBgColor = $ourCraftsmenCtaData['bg_color'] ?? $this->ourCraftsmenCtaBgColor;
        $this->ourCraftsmenCtaEyebrow = (string) ($ourCraftsmenCtaData['eyebrow'] ?? $this->ourCraftsmenCtaEyebrow);
        $this->ourCraftsmenCtaHeading = (string) ($ourCraftsmenCtaData['heading'] ?? $this->ourCraftsmenCtaHeading);
        $this->ourCraftsmenCtaDescription = (string) ($ourCraftsmenCtaData['description'] ?? $this->ourCraftsmenCtaDescription);
        $this->ourCraftsmenCtaButtonText = (string) ($ourCraftsmenCtaData['button_text'] ?? $this->ourCraftsmenCtaButtonText);
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

    public function selectSustainabilityHeroBgPreset(string $hex): void
    {
        $this->sustainabilityHeroUseCustomBg = true;
        $this->sustainabilityHeroBgColor = $hex;
    }

    public function selectSustainabilityPointsBgPreset(string $hex): void
    {
        $this->sustainabilityPointsUseCustomBg = true;
        $this->sustainabilityPointsBgColor = $hex;
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

    public function selectOurCraftsmenHeroBgPreset(string $hex): void
    {
        $this->ourCraftsmenHeroUseCustomBg = true;
        $this->ourCraftsmenHeroBgColor = $hex;
    }

    public function selectWhyChooseUsBgPreset(string $hex): void
    {
        $this->whyChooseUsUseCustomBg = true;
        $this->whyChooseUsBgColor = $hex;
    }

    /**
     * Pilih ikon sebuah kartu Why Choose Us. Hanya ikon dari daftar
     * $whyChooseUsIconOptions yang diterima.
     */
    public function selectWhyChooseUsIcon(int $index, string $icon): void
    {
        if (! isset($this->whyChooseUsItems[$index]) || ! in_array($icon, $this->whyChooseUsIconOptions, true)) {
            return;
        }

        $this->whyChooseUsItems[$index]['icon'] = $icon;
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

    /** Jumlah slot galeri Dokumentasi -- dipakai di blade (konstanta class tidak diakses langsung dari blade, ikut konvensi getMaxAdditionalPhotosProperty() di produk-form.blade.php). */
    public function getDokumentasiGaleriSlotsProperty(): int
    {
        return self::DOKUMENTASI_GALERI_SLOTS;
    }

    /** URL preview tiap slot galeri Dokumentasi (foto/video yang sudah tersimpan). Null = slot kosong. */
    public function getDokumentasiGaleriPreviewUrlsProperty(): array
    {
        return collect($this->dokumentasiGaleriPathLama)
            ->map(fn (?string $path) => $path ? Storage::disk('public')->url($path) : null)
            ->all();
    }

    /** Hapus isi slot galeri tertentu (upload baru yang belum disimpan, ATAU file lama yang sudah tersimpan). */
    public function removeDokumentasiGaleriSlot(int $slot): void
    {
        if (! array_key_exists($slot, $this->dokumentasiGaleriPathLama)) {
            return;
        }

        $this->dokumentasiGaleriUploadBaru[$slot] = null;

        if ($this->dokumentasiGaleriPathLama[$slot]) {
            Storage::disk('public')->delete($this->dokumentasiGaleriPathLama[$slot]);
        }

        $this->dokumentasiGaleriPathLama[$slot] = null;
        $this->dokumentasiGaleriKeterangan[$slot] = '';
    }

    /**
     * URL preview tiap item yang SUDAH tersimpan, per section Dokumentasi 3
     * -- di-index oleh key UUID yang sama dengan $dok3Keys, bukan angka,
     * karena jumlah item di sini TIDAK DIBATASI (lihat catatan di atas
     * properti $dok3Keys dkk).
     */

    /** URL preview tiap item kartu 3D yang sudah tersimpan, di-index oleh key UUID-nya. */
    public function getDok3PreviewUrlsProperty(): array
    {
        return collect($this->dok3PathLama)
            ->map(fn (?string $path) => $path ? Storage::disk('public')->url($path) : null)
            ->all();
    }

    /** Tambah 1 item kartu 3D baru (kosong) -- TIDAK ADA BATAS jumlahnya. */
    public function addDok3Item(): void
    {
        $key = (string) Str::uuid();

        $this->dok3Keys[] = $key;
        $this->dok3Tipe[$key] = 'video';
        $this->dok3Keterangan[$key] = '';
        $this->dok3PathLama[$key] = null;
        $this->dok3UploadBaru[$key] = null;
    }

    /** Hapus 1 item kartu 3D (upload baru yang belum disimpan ATAU file lama), lalu buang key-nya dari urutan. */
    public function removeDok3Item(string $key): void
    {
        if (! in_array($key, $this->dok3Keys, true)) {
            return;
        }

        if ($this->dok3PathLama[$key] ?? null) {
            Storage::disk('public')->delete($this->dok3PathLama[$key]);
        }

        $this->dok3Keys = array_values(array_diff($this->dok3Keys, [$key]));
        unset($this->dok3Tipe[$key], $this->dok3Keterangan[$key], $this->dok3PathLama[$key], $this->dok3UploadBaru[$key]);
    }

    public function getDokFotoPreviewUrlsProperty(): array
    {
        return collect($this->dokFotoPathLama)->map(fn (?string $path) => $path ? Storage::disk('public')->url($path) : null)->all();
    }

    public function addDokFotoItem(): void
    {
        $key = (string) Str::uuid();
        $this->dokFotoKeys[] = $key;
        $this->dokFotoTipe[$key] = 'foto';
        $this->dokFotoKeterangan[$key] = '';
        $this->dokFotoPathLama[$key] = null;
        $this->dokFotoUploadBaru[$key] = null;
    }

    public function removeDokFotoItem(string $key): void
    {
        if (! in_array($key, $this->dokFotoKeys, true)) return;
        if ($this->dokFotoPathLama[$key] ?? null) Storage::disk('public')->delete($this->dokFotoPathLama[$key]);
        $this->dokFotoKeys = array_values(array_diff($this->dokFotoKeys, [$key]));
        unset($this->dokFotoTipe[$key], $this->dokFotoKeterangan[$key], $this->dokFotoPathLama[$key], $this->dokFotoUploadBaru[$key]);
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

    public function saveSustainabilityHero(): void
    {
        $validated = $this->validate([
            'sustainabilityHeroBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sustainabilityHeroEyebrow' => ['required', 'string', 'max:40'],
            'sustainabilityHeroHeading' => ['required', 'string', 'max:100'],
            'sustainabilityHeroDescription' => ['required', 'string', 'max:500'],
        ]);

        HomeSection::forSection('sustainability-hero')->update([
            'data' => [
                'bg_color' => $this->sustainabilityHeroUseCustomBg ? $validated['sustainabilityHeroBgColor'] : null,
                'eyebrow' => $validated['sustainabilityHeroEyebrow'],
                'heading' => $validated['sustainabilityHeroHeading'],
                'description' => $validated['sustainabilityHeroDescription'],
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveSustainabilityPoints(): void
    {
        $validated = $this->validate([
            'sustainabilityPointsBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sustainabilityPoints' => ['required', 'array', 'size:4'],
            'sustainabilityPoints.*.icon' => ['required', 'string', 'in:'.implode(',', $this->sustainabilityIconOptions)],
            'sustainabilityPoints.*.title' => ['required', 'string', 'max:80'],
            'sustainabilityPoints.*.text' => ['required', 'string', 'max:300'],
            'sustainabilityNote' => ['required', 'string', 'max:500'],
        ]);

        HomeSection::forSection('sustainability-points')->update([
            'data' => [
                'bg_color' => $this->sustainabilityPointsUseCustomBg ? $validated['sustainabilityPointsBgColor'] : null,
                'items' => array_values($validated['sustainabilityPoints']),
                'note' => $validated['sustainabilityNote'],
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }
    public function savePrivacyHero(): void
    {
        $validated = $this->validate([
            'privacyHeroEyebrow' => ['required', 'string', 'max:60'],
            'privacyHeroHeading' => ['required', 'string', 'max:120'],
            'privacyHeroDescription' => ['required', 'string', 'max:700'],
            'privacyHeroUpdatedDate' => ['required', 'date'],
        ]);

        HomeSection::forSection('privacy-hero')->update([
            'data' => [
                'eyebrow' => $validated['privacyHeroEyebrow'],
                'heading' => $validated['privacyHeroHeading'],
                'description' => $validated['privacyHeroDescription'],
                'updated_date' => $validated['privacyHeroUpdatedDate'],
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function savePrivacySection(string $key): void
    {
        $defaults = $this->privacyContentDefaults();

        if (!isset($defaults[$key])) {
            return;
        }

        $rules = [
            "privacyContent.$key.title" => ['required', 'string', 'max:160'],
            "privacyContent.$key.intro" => ['required', 'string', 'max:2500'],
            "privacyContent.$key.outro" => ['nullable', 'string', 'max:2500'],
        ];

        if (count($defaults[$key]['bullets']) > 0) {
            $rules["privacyContent.$key.bullets"] = ['required', 'array', 'size:'.count($defaults[$key]['bullets'])];
            $rules["privacyContent.$key.bullets.*"] = ['required', 'string', 'max:1000'];
        }

        $this->validate($rules);

        HomeSection::forSection('privacy-'.$key)->update([
            'data' => [
                'title' => trim((string) $this->privacyContent[$key]['title']),
                'intro' => trim((string) $this->privacyContent[$key]['intro']),
                'bullets' => array_values($this->privacyContent[$key]['bullets'] ?? []),
                'outro' => trim((string) ($this->privacyContent[$key]['outro'] ?? '')),
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function savePrivacyContact(): void
    {
        $validated = $this->validate([
            'privacyContactHeading' => ['required', 'string', 'max:140'],
            'privacyContactDescription' => ['required', 'string', 'max:600'],
            'privacyContactButtonLabel' => ['required', 'string', 'max:80'],
        ]);

        HomeSection::forSection('privacy-contact')->update([
            'data' => [
                'heading' => $validated['privacyContactHeading'],
                'description' => $validated['privacyContactDescription'],
                'button_label' => $validated['privacyContactButtonLabel'],
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveCookiesHero(): void
    {
        $validated = $this->validate([
            'cookiesHeroEyebrow' => ['required', 'string', 'max:60'],
            'cookiesHeroHeading' => ['required', 'string', 'max:120'],
            'cookiesHeroDescription' => ['required', 'string', 'max:700'],
            'cookiesHeroUpdatedDate' => ['required', 'date'],
        ]);

        HomeSection::forSection('cookies-hero')->update([
            'data' => [
                'eyebrow' => $validated['cookiesHeroEyebrow'],
                'heading' => $validated['cookiesHeroHeading'],
                'description' => $validated['cookiesHeroDescription'],
                'updated_date' => $validated['cookiesHeroUpdatedDate'],
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveCookiesSummary(): void
    {
        $validated = $this->validate([
            'cookiesSummaryLabel' => ['required', 'string', 'max:80'],
            'cookiesSummaryText' => ['required', 'string', 'max:1200'],
        ]);

        HomeSection::forSection('cookies-summary')->update([
            'data' => [
                'label' => $validated['cookiesSummaryLabel'],
                'text' => $validated['cookiesSummaryText'],
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveCookiesCategories(): void
    {
        $this->validate([
            'cookiesCategoriesHeading' => ['required', 'string', 'max:120'],
            'cookiesCategoriesDescription' => ['required', 'string', 'max:800'],
            'cookiesCategoryItems' => ['required', 'array', 'size:5'],
            'cookiesCategoryItems.*.name' => ['required', 'string', 'max:100'],
            'cookiesCategoryItems.*.desc' => ['required', 'string', 'max:1200'],
            'cookiesStatusAlways' => ['required', 'string', 'max:80'],
            'cookiesStatusLocal' => ['required', 'string', 'max:80'],
            'cookiesStatusNone' => ['required', 'string', 'max:80'],
        ]);

        HomeSection::forSection('cookies-categories')->update([
            'data' => [
                'heading' => $this->cookiesCategoriesHeading,
                'description' => $this->cookiesCategoriesDescription,
                'items' => array_values($this->cookiesCategoryItems),
                'status_labels' => [
                    'always' => $this->cookiesStatusAlways,
                    'local' => $this->cookiesStatusLocal,
                    'none' => $this->cookiesStatusNone,
                ],
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveCookiesBrowser(): void
    {
        $validated = $this->validate([
            'cookiesBrowserHeading' => ['required', 'string', 'max:140'],
            'cookiesBrowserText' => ['required', 'string', 'max:1800'],
        ]);

        HomeSection::forSection('cookies-browser')->update([
            'data' => [
                'heading' => $validated['cookiesBrowserHeading'],
                'text' => $validated['cookiesBrowserText'],
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveCookiesContact(): void
    {
        $validated = $this->validate([
            'cookiesContactHeading' => ['required', 'string', 'max:140'],
            'cookiesContactDescription' => ['required', 'string', 'max:600'],
            'cookiesContactButtonLabel' => ['required', 'string', 'max:80'],
        ]);

        HomeSection::forSection('cookies-contact')->update([
            'data' => [
                'heading' => $validated['cookiesContactHeading'],
                'description' => $validated['cookiesContactDescription'],
                'button_label' => $validated['cookiesContactButtonLabel'],
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }
    public function saveTermsHero(): void
    {
        $validated = $this->validate([
            'termsHeroEyebrow' => ['required', 'string', 'max:60'],
            'termsHeroHeading' => ['required', 'string', 'max:120'],
            'termsHeroDescription' => ['required', 'string', 'max:700'],
            'termsHeroUpdatedDate' => ['required', 'date'],
        ]);

        HomeSection::forSection('terms-hero')->update([
            'data' => [
                'eyebrow' => $validated['termsHeroEyebrow'],
                'heading' => $validated['termsHeroHeading'],
                'description' => $validated['termsHeroDescription'],
                'updated_date' => $validated['termsHeroUpdatedDate'],
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveTermsSection(string $key): void
    {
        $defaults = $this->termsContentDefaults();

        if (! isset($defaults[$key])) {
            return;
        }

        $rules = [
            "termsContent.$key.title" => ['required', 'string', 'max:140'],
            "termsContent.$key.text" => ['required', 'string', 'max:2500'],
        ];

        if (isset($defaults[$key]['bullets'])) {
            $rules["termsContent.$key.bullets"] = ['required', 'array', 'size:'.count($defaults[$key]['bullets'])];
            $rules["termsContent.$key.bullets.*"] = ['required', 'string', 'max:800'];
        }

        if (isset($defaults[$key]['link_labels'])) {
            $rules["termsContent.$key.link_labels"] = ['required', 'array', 'size:'.count($defaults[$key]['link_labels'])];
            $rules["termsContent.$key.link_labels.*"] = ['required', 'string', 'max:120'];
        }

        $this->validate($rules);

        $payload = [
            'title' => trim((string) $this->termsContent[$key]['title']),
            'text' => trim((string) $this->termsContent[$key]['text']),
        ];

        if (isset($defaults[$key]['bullets'])) {
            $payload['bullets'] = array_values($this->termsContent[$key]['bullets']);
        }

        if (isset($defaults[$key]['link_labels'])) {
            $payload['link_labels'] = array_values($this->termsContent[$key]['link_labels']);
        }

        HomeSection::forSection('terms-'.$key)->update([
            'data' => $payload,
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveTermsContact(): void
    {
        $validated = $this->validate([
            'termsContactHeading' => ['required', 'string', 'max:120'],
            'termsContactDescription' => ['required', 'string', 'max:500'],
            'termsContactButtonLabel' => ['required', 'string', 'max:80'],
        ]);

        HomeSection::forSection('terms-contact')->update([
            'data' => [
                'heading' => $validated['termsContactHeading'],
                'description' => $validated['termsContactDescription'],
                'button_label' => $validated['termsContactButtonLabel'],
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }
    public function saveOurCraftsmenHero(): void
    {
        $validated = $this->validate([
            'ourCraftsmenHeroBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ...$this->frameGradientRules('ourCraftsmenHero'),
            'ourCraftsmenHeroEyebrow' => ['required', 'string', 'max:40'],
            'ourCraftsmenHeroHeading' => ['required', 'string', 'max:100'],
            'ourCraftsmenHeroDescription' => ['required', 'string', 'max:400'],
        ], [
            'ourCraftsmenHeroEyebrow.required' => 'Label kecil wajib diisi.',
            'ourCraftsmenHeroHeading.required' => 'Judul wajib diisi.',
            'ourCraftsmenHeroDescription.required' => 'Paragraf wajib diisi.',
        ]);

        HomeSection::forSection('our-craftsmen-hero')->update([
            'data' => [
                'bg_color' => $this->ourCraftsmenHeroUseCustomBg ? $validated['ourCraftsmenHeroBgColor'] : null,
                'bg_gradient' => $this->frameGradientPayload('ourCraftsmenHero'),
                'eyebrow' => $validated['ourCraftsmenHeroEyebrow'],
                'heading' => $validated['ourCraftsmenHeroHeading'],
                'description' => $validated['ourCraftsmenHeroDescription'],
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function getOurCraftsmenOwnerPhotoPreviewUrlProperty(): ?string
    {
        if ($this->ourCraftsmenOwnerPhotoCroppedBase64) {
            return $this->ourCraftsmenOwnerPhotoCroppedBase64;
        }

        return $this->ourCraftsmenOwnerPhotoPathLama
            ? Storage::disk('public')->url($this->ourCraftsmenOwnerPhotoPathLama)
            : null;
    }

    public function removeOurCraftsmenOwnerPhoto(): void
    {
        $this->ourCraftsmenOwnerPhotoUpload = null;
        $this->ourCraftsmenOwnerPhotoCroppedBase64 = null;

        if ($this->ourCraftsmenOwnerPhotoPathLama) {
            Storage::disk('public')->delete($this->ourCraftsmenOwnerPhotoPathLama);
            $this->ourCraftsmenOwnerPhotoPathLama = null;
        }
    }

    public function selectOurCraftsmenOwnerBgPreset(string $hex): void
    {
        $this->ourCraftsmenOwnerUseCustomBg = true;
        $this->ourCraftsmenOwnerBgColor = $hex;
    }

    public function selectOurCraftsmenCtaBgPreset(string $hex): void
    {
        $this->ourCraftsmenCtaUseCustomBg = true;
        $this->ourCraftsmenCtaBgColor = $hex;
    }
    public function saveOurCraftsmenOwner(): void
    {
        $validated = $this->validate([
            'ourCraftsmenOwnerBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'ourCraftsmenOwnerEyebrow' => ['required', 'string', 'max:50'],
            'ourCraftsmenOwnerName' => ['required', 'string', 'max:80'],
            'ourCraftsmenOwnerRole' => ['required', 'string', 'max:100'],
            'ourCraftsmenOwnerHeading' => ['required', 'string', 'max:140'],
            'ourCraftsmenOwnerDescription' => ['required', 'string', 'max:800'],
            'ourCraftsmenOwnerQuote' => ['nullable', 'string', 'max:350'],
            'ourCraftsmenOwnerPhotoCroppedBase64' => ['nullable', 'string'],
            'ourCraftsmenOwnerStats' => ['required', 'array', 'size:3'],
            'ourCraftsmenOwnerStats.*.value' => ['required', 'string', 'max:30'],
            'ourCraftsmenOwnerStats.*.label' => ['required', 'string', 'max:60'],
        ]);

        $imagePath = $this->ourCraftsmenOwnerPhotoPathLama;

        if ($this->ourCraftsmenOwnerPhotoCroppedBase64) {
            $binary = $this->decodeBase64Image($this->ourCraftsmenOwnerPhotoCroppedBase64);

            if ($binary !== null) {
                if ($this->ourCraftsmenOwnerPhotoPathLama) {
                    Storage::disk('public')->delete($this->ourCraftsmenOwnerPhotoPathLama);
                }

                $imagePath = 'home-sections/our-craftsmen-owner-'.Str::uuid().'.jpg';
                Storage::disk('public')->put($imagePath, $binary);
                $this->ourCraftsmenOwnerPhotoPathLama = $imagePath;
                $this->ourCraftsmenOwnerPhotoCroppedBase64 = null;
                $this->ourCraftsmenOwnerPhotoUpload = null;
            }
        }

        HomeSection::forSection('our-craftsmen-daftar')->update([
            'data' => [
                'bg_color' => $this->ourCraftsmenOwnerUseCustomBg ? strtoupper($validated['ourCraftsmenOwnerBgColor']) : null,
                'eyebrow' => $validated['ourCraftsmenOwnerEyebrow'],
                'name' => $validated['ourCraftsmenOwnerName'],
                'role' => $validated['ourCraftsmenOwnerRole'],
                'heading' => $validated['ourCraftsmenOwnerHeading'],
                'description' => $validated['ourCraftsmenOwnerDescription'],
                'quote' => $validated['ourCraftsmenOwnerQuote'] !== '' ? $validated['ourCraftsmenOwnerQuote'] : null,
                'photo_path' => $imagePath,
                'stats' => array_values($validated['ourCraftsmenOwnerStats']),
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveOurCraftsmenCta(): void
    {
        $validated = $this->validate([
            'ourCraftsmenCtaBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'ourCraftsmenCtaEyebrow' => ['required', 'string', 'max:50'],
            'ourCraftsmenCtaHeading' => ['required', 'string', 'max:120'],
            'ourCraftsmenCtaDescription' => ['required', 'string', 'max:500'],
            'ourCraftsmenCtaButtonText' => ['required', 'string', 'max:50'],
        ]);

        HomeSection::forSection('our-craftsmen-cta')->update([
            'data' => [
                'bg_color' => $this->ourCraftsmenCtaUseCustomBg ? strtoupper($validated['ourCraftsmenCtaBgColor']) : null,
                'eyebrow' => $validated['ourCraftsmenCtaEyebrow'],
                'heading' => $validated['ourCraftsmenCtaHeading'],
                'description' => $validated['ourCraftsmenCtaDescription'],
                'button_text' => $validated['ourCraftsmenCtaButtonText'],
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

    public function saveWhyChooseUs(): void
    {
        $validated = $this->validate([
            'whyChooseUsBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ...$this->frameGradientRules('whyChooseUs'),
            'whyChooseUsEyebrow' => ['required', 'string', 'max:40'],
            'whyChooseUsHeading' => ['required', 'string', 'max:100'],
            'whyChooseUsDescription' => ['required', 'string', 'max:300'],
            'whyChooseUsItems' => ['required', 'array', 'size:5'],
            'whyChooseUsItems.*.icon' => ['required', 'string', 'in:'.implode(',', $this->whyChooseUsIconOptions)],
            'whyChooseUsItems.*.text' => ['required', 'string', 'max:60'],
        ], [
            'whyChooseUsEyebrow.required' => 'Label kecil wajib diisi.',
            'whyChooseUsHeading.required' => 'Judul wajib diisi.',
            'whyChooseUsDescription.required' => 'Paragraf wajib diisi.',
            'whyChooseUsItems.*.icon.required' => 'Ikon kartu wajib dipilih.',
            'whyChooseUsItems.*.icon.in' => 'Ikon kartu tidak valid, pilih dari daftar yang tersedia.',
            'whyChooseUsItems.*.text.required' => 'Teks kartu wajib diisi.',
        ]);

        $items = [];

        foreach (range(0, 4) as $i) {
            $items[] = [
                'icon' => $validated['whyChooseUsItems'][$i]['icon'],
                'text' => $validated['whyChooseUsItems'][$i]['text'],
            ];
        }

        HomeSection::forSection('why-choose-us-profil')->update([
            'data' => [
                'bg_color' => $this->whyChooseUsUseCustomBg ? $validated['whyChooseUsBgColor'] : null,
                'bg_gradient' => $this->frameGradientPayload('whyChooseUs'),
                'eyebrow' => $validated['whyChooseUsEyebrow'],
                'heading' => $validated['whyChooseUsHeading'],
                'description' => $validated['whyChooseUsDescription'],
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

        if ($this->dokumentasiMediaType === 'video_url') {
            $rules['dokumentasiVideoUrl'] = ['required', 'url', 'max:2048'];
        }
        if ($this->dokumentasiMediaType === 'video_upload' && ! $this->dokumentasiVideoPathLama) {
            $rules['dokumentasiVideoUpload'] = ['required', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime', 'max:51200'];
        }

        $validated = $this->validate($rules);

        if ($this->dokumentasiMediaType === 'video_upload' && $this->dokumentasiVideoUpload) {
            if ($this->dokumentasiVideoPathLama) Storage::disk('public')->delete($this->dokumentasiVideoPathLama);
            $extension = $this->dokumentasiVideoUpload->getClientOriginalExtension() ?: 'mp4';
            $this->dokumentasiVideoPathLama = $this->dokumentasiVideoUpload->storeAs('home-sections', 'dokumentasi-'.Str::uuid().'.'.$extension, 'public');
            $this->dokumentasiVideoUpload = null;
        }

        // Pertahankan data galeri lama sebagai fallback/migrasi aman, tapi tidak lagi diedit di tab Hero.
        $existing = HomeSection::dataFor('dokumentasi', $this->dokumentasiDefaults());

        HomeSection::forSection('dokumentasi')->update(['data' => [
            'judul' => $validated['dokumentasiJudul'],
            'subjudul' => $validated['dokumentasiSubjudul'],
            'deskripsi' => $validated['dokumentasiDeskripsi'],
            'media_type' => $this->dokumentasiMediaType,
            'video_url' => $this->dokumentasiVideoUrl !== '' ? $this->dokumentasiVideoUrl : null,
            'video_path' => $this->dokumentasiVideoPathLama,
            'galeri' => is_array($existing['galeri'] ?? null) ? $existing['galeri'] : [],
        ]]);

        session()->flash('edit-web-tersimpan', true);
    }
    /**
     * Simpan Dokumentasi 3 (section_key 'dokumentasi-3'). Jumlah item
     * BEBAS -- makanya validasi & penyimpanan mengikuti urutan key di
     * $dok3Keys (bukan range(0, N-1) seperti pola slot tetap), lalu
     * ditulis ulang sebagai array berurutan (values()) ke home_sections.
     */
    public function saveDokumentasi3(): void
    {
        $rules = [
            'dok3Judul' => ['required', 'string', 'max:60'],
            'dok3Subjudul' => ['required', 'string', 'max:100'],
            'dok3Deskripsi' => ['required', 'string', 'max:500'],
        ];

        foreach ($this->dok3Keys as $key) {
            $rules["dok3Tipe.$key"] = ['required', 'in:video'];
            $rules["dok3Keterangan.$key"] = ['nullable', 'string', 'max:80'];
            $rules["dok3UploadBaru.$key"] = ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime', 'max:51200'];
        }

        $validated = $this->validate($rules, [
            'dok3Judul.required' => 'Judul wajib diisi.',
            'dok3Subjudul.required' => 'Subjudul wajib diisi.',
            'dok3Deskripsi.required' => 'Deskripsi wajib diisi.',
        ]);

        foreach ($this->dok3Keys as $key) {
            $upload = $this->dok3UploadBaru[$key] ?? null;

            if (! $upload) {
                continue;
            }

            if ($this->dok3PathLama[$key] ?? null) {
                Storage::disk('public')->delete($this->dok3PathLama[$key]);
            }

            $extension = $upload->getClientOriginalExtension() ?: 'mp4';
            $this->dok3PathLama[$key] = $upload->storeAs(
                'home-sections',
                'dokumentasi3-'.Str::uuid().'.'.$extension,
                'public'
            );
            $this->dok3UploadBaru[$key] = null;
        }

        // Item TANPA file tersimpan (belum pernah diisi) tidak ikut disimpan --
        // urutan yang tersisa mengikuti $dok3Keys, dirapikan ulang lewat values().
        $items = collect($this->dok3Keys)
            ->map(fn ($key) => ($this->dok3PathLama[$key] ?? null) ? [
                'tipe' => $this->dok3Tipe[$key],
                'path' => $this->dok3PathLama[$key],
                'keterangan' => trim((string) $this->dok3Keterangan[$key]),
            ] : null)
            ->filter()
            ->values()
            ->all();

        HomeSection::forSection('dokumentasi-3')->update([
            'data' => [
                'judul' => $validated['dok3Judul'],
                'subjudul' => $validated['dok3Subjudul'],
                'deskripsi' => $validated['dok3Deskripsi'],
                'items' => $items,
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveDokumentasiFoto(): void
    {
        $rules = [
            'dokFotoJudul' => ['required', 'string', 'max:80'],
            'dokFotoSubjudul' => ['required', 'string', 'max:100'],
            'dokFotoDeskripsi' => ['required', 'string', 'max:500'],
        ];
        foreach ($this->dokFotoKeys as $key) {
            $rules["dokFotoKeterangan.$key"] = ['nullable', 'string', 'max:80'];
            $rules["dokFotoUploadBaru.$key"] = ['nullable', 'image', 'max:8192'];
        }
        $validated = $this->validate($rules);
        foreach ($this->dokFotoKeys as $key) {
            $upload = $this->dokFotoUploadBaru[$key] ?? null;
            if (! $upload) continue;
            if ($this->dokFotoPathLama[$key] ?? null) Storage::disk('public')->delete($this->dokFotoPathLama[$key]);
            $ext = $upload->getClientOriginalExtension() ?: 'jpg';
            $this->dokFotoPathLama[$key] = $upload->storeAs('home-sections', 'dokumentasi-foto-'.Str::uuid().'.'.$ext, 'public');
            $this->dokFotoUploadBaru[$key] = null;
            $this->dokFotoTipe[$key] = 'foto';
        }
        $items = collect($this->dokFotoKeys)->map(fn ($key) => ($this->dokFotoPathLama[$key] ?? null) ? [
            'tipe' => 'foto', 'path' => $this->dokFotoPathLama[$key], 'keterangan' => trim((string) ($this->dokFotoKeterangan[$key] ?? '')),
        ] : null)->filter()->values()->all();
        HomeSection::forSection('dokumentasi-foto')->update(['data' => [
            'judul' => $validated['dokFotoJudul'], 'subjudul' => $validated['dokFotoSubjudul'], 'deskripsi' => $validated['dokFotoDeskripsi'], 'items' => $items,
        ]]);
        session()->flash('edit-web-tersimpan', true);
    }


    /** Klik salah satu swatch preset -> langsung pakai warnanya + otomatis centang "warna kustom". */
    public function selectProdukWarnaBgPreset(string $hex): void
    {
        $this->produkWarnaUseCustomBg = true;
        $this->produkWarnaBgColor = $hex;
    }

    /** Sama seperti selectProdukWarnaBgPreset(), tapi untuk section hero/breadcrumb (warna ke-2). */
    public function selectProdukWarnaHeroBgPreset(string $hex): void
    {
        $this->produkWarnaHeroUseCustomBg = true;
        $this->produkWarnaHeroBgColor = $hex;
    }

    public function saveProdukWarna(): void
    {
        $validated = $this->validate([
            'produkWarnaBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'produkWarnaHeroBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        HomeSection::forSection('produk')->update([
            'data' => [
                'bg_color' => $this->produkWarnaUseCustomBg ? $validated['produkWarnaBgColor'] : null,
                'bg_color_hero' => $this->produkWarnaHeroUseCustomBg ? $validated['produkWarnaHeroBgColor'] : null,
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
                'produk' => $produkSections,
                'dokumentasi' => $dokumentasiSections,
                'sustainability' => $sustainabilitySections,
                'privacy-policy' => $privacySections,
                'cookies' => $cookiesSections,
                'terms-of-service' => $termsSections,
                'our-craftsmen' => $ourCraftsmenSections,
                default => $sections,
            };
            $activeGroupLabel = match ($activeGroup) {
                'tentang-kami' => 'Tentang Kami',
                'produk' => 'Produk',
                'dokumentasi' => 'Dokumentasi',
                'sustainability' => 'Sustainability',
                'privacy-policy' => 'Privacy Policy',
                'cookies' => 'Cookies',
                'terms-of-service' => 'Terms of Service',
                'our-craftsmen' => 'Our Craftsmen',
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
                                            <div x-ref="viewport" class="relative mx-auto aspect-3/4 w-full max-w-64 cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none" x-on:pointerdown="startDrag($event)" x-on:pointermove="onDrag($event)" x-on:pointerup="endDrag($event)" x-on:pointercancel="endDrag($event)" x-on:pointerleave="endDrag($event)" x-on:wheel.prevent="onWheel($event)">
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
                                            <div x-ref="viewport" class="relative mx-auto aspect-square w-full max-w-64 cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none" x-on:pointerdown="startDrag($event)" x-on:pointermove="onDrag($event)" x-on:pointerup="endDrag($event)" x-on:pointercancel="endDrag($event)" x-on:pointerleave="endDrag($event)" x-on:wheel.prevent="onWheel($event)">
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
                                            <div x-ref="viewport" class="relative mx-auto aspect-3/5 w-full max-w-64 cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none" x-on:pointerdown="startDrag($event)" x-on:pointermove="onDrag($event)" x-on:pointerup="endDrag($event)" x-on:pointercancel="endDrag($event)" x-on:pointerleave="endDrag($event)" x-on:wheel.prevent="onWheel($event)">
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
                                    <div x-ref="viewport" class="relative mx-auto aspect-10/9 w-full max-w-64 cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none" x-on:pointerdown="startDrag($event)" x-on:pointermove="onDrag($event)" x-on:pointerup="endDrag($event)" x-on:pointercancel="endDrag($event)" x-on:pointerleave="endDrag($event)" x-on:wheel.prevent="onWheel($event)">
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
                                    <div x-ref="viewport" class="relative mx-auto aspect-4/5 w-full max-w-64 cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none" x-on:pointerdown="startDrag($event)" x-on:pointermove="onDrag($event)" x-on:pointerup="endDrag($event)" x-on:pointercancel="endDrag($event)" x-on:pointerleave="endDrag($event)" x-on:wheel.prevent="onWheel($event)">
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
                                    <div x-ref="viewport" class="relative mx-auto aspect-4/5 w-full max-w-64 cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none" x-on:pointerdown="startDrag($event)" x-on:pointermove="onDrag($event)" x-on:pointerup="endDrag($event)" x-on:pointercancel="endDrag($event)" x-on:pointerleave="endDrag($event)" x-on:wheel.prevent="onWheel($event)">
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
                    <div wire:ignore class="space-y-4 rounded-xl border border-admin-border p-4" x-data="nilaiKamiFotoCropper(@js($this->nilaiKamiFotoPreviewUrls))">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Foto</p>
                            <p class="mt-1 text-xs text-admin-ink-soft">
                                Satu foto untuk tiap kartu. Klik ikon kamera, lalu geser untuk memindah posisi &amp; pakai slider
                                untuk zoom (rasio 4:3, mengikuti bingkai foto kartu). Foto baru tampil di halaman setelah klik Simpan. Kartu yang fotonya belum pernah diganti
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
                                        <p class="mt-0.5 truncate text-xs text-admin-ink-soft" x-text="$wire.nilaiKamiItems?.[{{ $i }}]?.title ?? ''"></p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <template x-teleport="body">
                            <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-999 flex items-center justify-center bg-black/60 p-4" style="display: none;">
                                <div x-show="open" x-transition.scale.origin.center @click.outside="cancelCrop()" class="w-full max-w-md rounded-2xl bg-admin-surface p-6 shadow-2xl">
                                    <h4 class="mb-1 text-sm font-semibold text-admin-ink">Sesuaikan Foto</h4>
                                    <p class="mb-4 text-xs text-admin-ink-soft">Geser gambar untuk memindah (di touchpad: geser 2 jari), zoom dengan slider, cubit 2 jari, atau Ctrl + scroll. Rasio 4:3 (mengikuti bingkai foto kartu di halaman).</p>
                                    <div x-ref="viewport" class="relative mx-auto aspect-4/3 w-full cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none" x-on:pointerdown="startDrag($event)" x-on:pointermove="onDrag($event)" x-on:pointerup="endDrag($event)" x-on:pointercancel="endDrag($event)" x-on:pointerleave="endDrag($event)" x-on:wheel.prevent="onWheel($event)">
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
            @elseif ($activeSection === 'why-choose-us')
                <form wire:submit="saveWhyChooseUs" class="space-y-6">

                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-leaf text-admin-accent"></i>
                            Why Choose Us (section di halaman Tentang Kami)
                        </h3>
                        <p class="text-xs text-admin-ink-soft">
                            Section gelap "Why Choose Us" di bagian paling bawah halaman "Tentang Kami" -- teks di kiri
                            dan 5 kartu poin di kanan. Yang bisa diubah: warna frame, isi teks, dan ikon tiap kartu.
                        </p>
                    </div>

                    {{-- 1. WARNA FRAME --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Frame (Latar Section)</p>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="whyChooseUsUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna latar khusus
                            </label>
                            <input
                                type="color" wire:model="whyChooseUsBgColor"
                                @disabled(! $whyChooseUsUseCustomBg)
                                class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:cursor-not-allowed disabled:opacity-40"
                            >
                        </div>

                        <div>
                            <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-admin-ink-soft">Atau pilih dari rekomendasi</p>
                            <div class="flex flex-wrap gap-2.5">
                                @foreach ($whyChooseUsBgPresets as $preset)
                                    <button
                                        type="button"
                                        wire:click="selectWhyChooseUsBgPreset('{{ $preset['value'] }}')"
                                        title="{{ $preset['label'] }}"
                                        class="group flex flex-col items-center gap-1"
                                    >
                                        <span
                                            class="flex h-9 w-9 items-center justify-center rounded-full shadow-sm ring-1 ring-inset ring-white/40 transition duration-200 group-hover:scale-110 group-hover:shadow-md
                                            {{ $whyChooseUsUseCustomBg && strtoupper($whyChooseUsBgColor) === $preset['value']
                                                ? 'border-2 border-admin-accent ring-2 ring-admin-accent ring-offset-2 ring-offset-admin-surface'
                                                : 'border border-admin-border group-hover:border-admin-accent/60' }}"
                                            style="background: linear-gradient(135deg, color-mix(in oklab, {{ $preset['value'] }} 100%, white 30%), {{ $preset['value'] }} 55%, color-mix(in oklab, {{ $preset['value'] }} 100%, black 16%));"
                                        >
                                            @if ($whyChooseUsUseCustomBg && strtoupper($whyChooseUsBgColor) === $preset['value'])
                                                <i class="fa-solid fa-check text-xs drop-shadow-sm" style="color: {{ $preset['check'] }};"></i>
                                            @endif
                                        </span>
                                        <span class="max-w-14 truncate text-[10px] text-admin-ink-soft">{{ $preset['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <p class="text-xs text-admin-ink-soft">
                            Kalau tidak dicentang, section ini tetap cokelat gelap seperti bawaan. Warna teks dan kartu
                            otomatis menyesuaikan terang/gelap mengikuti warna latar supaya tetap kebaca.
                        </p>
                    </div>

                    @include('partials.admin.frame-gradient', [
                        'key' => 'whyChooseUs',
                        'gradient' => $whyChooseUsGradient,
                    ])

                    {{-- 2. ISI TEKS --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Isi Teks</p>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Label kecil (di atas judul)</label>
                                <input
                                    type="text" maxlength="40" wire:model="whyChooseUsEyebrow"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('whyChooseUsEyebrow')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul</label>
                                <input
                                    type="text" maxlength="100" wire:model="whyChooseUsHeading"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('whyChooseUsHeading')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">Paragraf (di bawah judul)</label>
                            <textarea
                                rows="3" maxlength="300" wire:model="whyChooseUsDescription"
                                class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20 resize-none"
                            ></textarea>
                            @error('whyChooseUsDescription')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ([0, 1, 2, 3, 4] as $i)
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-admin-ink">Teks kartu {{ $i + 1 }}</label>
                                    <input
                                        type="text" maxlength="60" wire:model="whyChooseUsItems.{{ $i }}.text"
                                        class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                    >
                                    @error('whyChooseUsItems.'.$i.'.text')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- 3. IKON (satu ikon per kartu) --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Ikon Kartu</p>

                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ($whyChooseUsItems as $i => $item)
                                <div class="space-y-3 rounded-xl border border-admin-border p-3">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-admin-cream">
                                            <i class="fa-solid {{ $item['icon'] }} text-sm text-admin-ink"></i>
                                        </span>
                                        <p class="truncate text-sm font-medium text-admin-ink">
                                            Kartu {{ $i + 1 }}@if (filled($item['text'])): {{ $item['text'] }}@endif
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($whyChooseUsIconOptions as $iconOption)
                                            <button
                                                type="button"
                                                wire:click="selectWhyChooseUsIcon({{ $i }}, '{{ $iconOption }}')"
                                                title="{{ $iconOption }}"
                                                aria-label="Pakai ikon {{ $iconOption }}"
                                                aria-pressed="{{ $item['icon'] === $iconOption ? 'true' : 'false' }}"
                                                class="flex h-9 w-9 items-center justify-center rounded-lg border text-xs transition {{ $item['icon'] === $iconOption ? 'border-admin-accent bg-admin-accent text-white' : 'border-admin-border text-admin-ink hover:border-admin-accent/60' }}"
                                            >
                                                <i class="fa-solid {{ $iconOption }}"></i>
                                            </button>
                                        @endforeach
                                    </div>
                                    @error('whyChooseUsItems.'.$i.'.icon')<p class="text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                        </div>

                        <p class="text-xs text-admin-ink-soft">
                            Klik salah satu ikon untuk memilihnya. Ikon baru tampil di halaman setelah klik Simpan.
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            wire:loading.attr="disabled" wire:target="saveWhyChooseUs"
                            class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition-all duration-200 hover:bg-admin-accent-strong active:scale-[0.99] disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="saveWhyChooseUs" class="flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan
                            </span>
                            <span wire:loading wire:target="saveWhyChooseUs" class="flex items-center gap-2">
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
                            Judul, subjudul, deskripsi, dan video yang tampil di bagian atas halaman
                            "Dokumentasi". Media di bagian ini hanya terhubung ke section Dokumentasi
                            dan tidak mengambil media dari section Beranda.
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
            @elseif ($activeSection === 'dokumentasi-3')
                <form wire:submit="saveDokumentasi3" class="space-y-6">
                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink"><i class="fa-solid fa-video text-admin-accent"></i> Galeri Video</h3>
                        <p class="text-xs text-admin-ink-soft">Khusus VIDEO. Setiap item yang diunggah di sini tampil pada grid video modern di halaman Dokumentasi. Jumlah video bebas; halaman otomatis menyusun maksimal 4 video per baris di desktop.</p>
                    </div>
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Teks Section</p>
                        <input type="text" maxlength="100" wire:model="dok3Subjudul" placeholder="Label kecil" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm">
                        <input type="text" maxlength="60" wire:model="dok3Judul" placeholder="Judul" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm">
                        <textarea rows="3" maxlength="500" wire:model="dok3Deskripsi" placeholder="Deskripsi" class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm"></textarea>
                    </div>
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Daftar Video</p><p class="mt-1 text-xs text-admin-ink-soft">Input hanya menerima MP4, WebM, MOV, atau OGG. Tidak ada input foto di bagian ini.</p></div><button type="button" wire:click="addDok3Item" class="rounded-full border border-admin-accent px-3.5 py-2 text-xs font-semibold text-admin-accent hover:bg-admin-accent hover:text-white"><i class="fa-solid fa-plus mr-1"></i> Tambah Video</button></div>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($dok3Keys as $urutan => $key)
                                <div wire:key="dok3-video-{{ $key }}" class="space-y-3 rounded-xl border border-admin-border bg-admin-surface p-3">
                                    <div class="flex items-center justify-between"><span class="text-xs font-semibold text-admin-ink">Video {{ $urutan + 1 }}</span><button type="button" wire:click="removeDok3Item('{{ $key }}')" class="h-8 w-8 rounded-full text-admin-danger hover:bg-admin-danger/10"><i class="fa-solid fa-trash text-xs"></i></button></div>
                                    @if ($this->dok3PreviewUrls[$key] ?? null)<video src="{{ $this->dok3PreviewUrls[$key] }}" class="aspect-video w-full rounded-lg bg-black object-cover" autoplay muted loop playsinline></video>@endif
                                    <input type="file" wire:model="dok3UploadBaru.{{ $key }}" accept="video/mp4,video/webm,video/ogg,video/quicktime" class="block w-full text-xs file:mr-2 file:rounded-full file:border-0 file:bg-admin-accent file:px-3 file:py-1.5 file:text-white">
                                    @error("dok3UploadBaru.$key")<p class="text-[11px] text-red-600">{{ $message }}</p>@enderror
                                    <input type="text" maxlength="80" wire:model="dok3Keterangan.{{ $key }}" placeholder="Judul/keterangan video" class="w-full rounded-md border border-admin-border bg-admin-surface px-2.5 py-2 text-xs">
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex justify-end"><button type="submit" class="rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white"><i class="fa-solid fa-floppy-disk mr-2"></i>Simpan Galeri Video</button></div>
                </form>
            @elseif ($activeSection === 'dokumentasi-foto')
                <form wire:submit="saveDokumentasiFoto" class="space-y-6">
                    <div><h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink"><i class="fa-solid fa-images text-admin-accent"></i> Galeri Foto</h3><p class="text-xs text-admin-ink-soft">Khusus FOTO. Foto yang diunggah di sini tampil sebagai bento/editorial gallery premium. Tidak ada input video di bagian ini.</p></div>
                    <div class="space-y-3 rounded-xl border border-admin-border p-4">
                        <input type="text" maxlength="100" wire:model="dokFotoSubjudul" placeholder="Label kecil" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm">
                        <input type="text" maxlength="80" wire:model="dokFotoJudul" placeholder="Judul" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm">
                        <textarea rows="3" maxlength="500" wire:model="dokFotoDeskripsi" placeholder="Deskripsi" class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm"></textarea>
                    </div>
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft" style="animation-duration:120s !important;">Daftar Foto</p><p class="mt-1 text-xs text-admin-ink-soft">Input hanya menerima file gambar. Susunan di frontend otomatis mengikuti layout bento yang sudah dibuat.</p></div><button type="button" wire:click="addDokFotoItem" class="rounded-full border border-admin-accent px-3.5 py-2 text-xs font-semibold text-admin-accent hover:bg-admin-accent hover:text-white"><i class="fa-solid fa-plus mr-1"></i> Tambah Foto</button></div>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($dokFotoKeys as $urutan => $key)
                                <div wire:key="dok-foto-{{ $key }}" class="space-y-3 rounded-xl border border-admin-border bg-admin-surface p-3">
                                    <div class="flex items-center justify-between"><span class="text-xs font-semibold text-admin-ink">Foto {{ $urutan + 1 }}</span><button type="button" wire:click="removeDokFotoItem('{{ $key }}')" class="h-8 w-8 rounded-full text-admin-danger hover:bg-admin-danger/10"><i class="fa-solid fa-trash text-xs"></i></button></div>
                                    @if ($this->dokFotoPreviewUrls[$key] ?? null)<img src="{{ $this->dokFotoPreviewUrls[$key] }}" alt="Foto {{ $urutan + 1 }}" class="aspect-video w-full rounded-lg object-cover">@endif
                                    <input type="file" wire:model="dokFotoUploadBaru.{{ $key }}" accept="image/*" class="block w-full text-xs file:mr-2 file:rounded-full file:border-0 file:bg-admin-accent file:px-3 file:py-1.5 file:text-white">
                                    @error("dokFotoUploadBaru.$key")<p class="text-[11px] text-red-600">{{ $message }}</p>@enderror
                                    <input type="text" maxlength="80" wire:model="dokFotoKeterangan.{{ $key }}" placeholder="Judul/keterangan foto" class="w-full rounded-md border border-admin-border bg-admin-surface px-2.5 py-2 text-xs">
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex justify-end"><button type="submit" class="rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white"><i class="fa-solid fa-floppy-disk mr-2"></i>Simpan Galeri Foto</button></div>
                </form>
            @elseif ($activeSection === 'warna')
                <form wire:submit="saveProdukWarna" class="space-y-6">

                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-palette text-admin-accent"></i>
                            Warna (halaman katalog Produk)
                        </h3>
                        <p class="text-xs text-admin-ink-soft">
                            Halaman "Produk" punya 2 section warna terpisah: bagian judul besar "Produk" +
                            breadcrumb (paling atas, tepat di bawah navbar), dan bagian Filter Options +
                            daftar kartu produk (di bawahnya). Navbar sendiri tidak ikut berubah dari sini.
                        </p>
                    </div>

                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">
                            Warna 1 -- Judul &amp; Breadcrumb (paling atas)
                        </p>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="produkWarnaHeroUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna latar khusus
                            </label>
                            <input
                                type="color" wire:model="produkWarnaHeroBgColor"
                                @disabled(! $produkWarnaHeroUseCustomBg)
                                class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:cursor-not-allowed disabled:opacity-40"
                            >
                        </div>

                        {{-- Preset warna rekomendasi -- klik langsung pakai warnanya + otomatis centang checkbox di atas. --}}
                        <div>
                            <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-admin-ink-soft">Atau pilih dari rekomendasi</p>
                            <div class="flex flex-wrap gap-2.5">
                                @foreach ($produkWarnaHeroBgPresets as $preset)
                                    <button
                                        type="button"
                                        wire:click="selectProdukWarnaHeroBgPreset('{{ $preset['value'] }}')"
                                        title="{{ $preset['label'] }}"
                                        class="group flex flex-col items-center gap-1"
                                    >
                                        <span
                                            class="flex h-9 w-9 items-center justify-center rounded-full shadow-sm ring-1 ring-inset ring-white/40 transition duration-200 group-hover:scale-110 group-hover:shadow-md
                                            {{ $produkWarnaHeroUseCustomBg && strtoupper($produkWarnaHeroBgColor) === $preset['value']
                                                ? 'border-2 border-admin-accent ring-2 ring-admin-accent ring-offset-2 ring-offset-admin-surface'
                                                : 'border border-admin-border group-hover:border-admin-accent/60' }}"
                                            style="background: linear-gradient(135deg, color-mix(in oklab, {{ $preset['value'] }} 100%, white 30%), {{ $preset['value'] }} 55%, color-mix(in oklab, {{ $preset['value'] }} 100%, black 16%));"
                                        >
                                            @if ($produkWarnaHeroUseCustomBg && strtoupper($produkWarnaHeroBgColor) === $preset['value'])
                                                <i class="fa-solid fa-check text-xs drop-shadow-sm" style="color: {{ $preset['check'] }};"></i>
                                            @endif
                                        </span>
                                        <span class="max-w-14 truncate text-[10px] text-admin-ink-soft">{{ $preset['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <p class="text-xs text-admin-ink-soft">
                            Kalau tidak dicentang, section ini pakai warna bawaan (putih kehijauan #F6F9F6)
                            seperti sebelumnya. Judul "Produk" berwarna gelap dan TIDAK otomatis menyesuaikan
                            -- pilih warna terang supaya tetap kebaca.
                        </p>
                    </div>

                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">
                            Warna 2 -- Filter Options &amp; Daftar Produk
                        </p>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="produkWarnaUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna latar khusus
                            </label>
                            <input
                                type="color" wire:model="produkWarnaBgColor"
                                @disabled(! $produkWarnaUseCustomBg)
                                class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:cursor-not-allowed disabled:opacity-40"
                            >
                        </div>

                        {{-- Preset warna rekomendasi -- klik langsung pakai warnanya + otomatis centang checkbox di atas. --}}
                        <div>
                            <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-admin-ink-soft">Atau pilih dari rekomendasi</p>
                            <div class="flex flex-wrap gap-2.5">
                                @foreach ($produkWarnaBgPresets as $preset)
                                    <button
                                        type="button"
                                        wire:click="selectProdukWarnaBgPreset('{{ $preset['value'] }}')"
                                        title="{{ $preset['label'] }}"
                                        class="group flex flex-col items-center gap-1"
                                    >
                                        <span
                                            class="flex h-9 w-9 items-center justify-center rounded-full shadow-sm ring-1 ring-inset ring-white/40 transition duration-200 group-hover:scale-110 group-hover:shadow-md
                                            {{ $produkWarnaUseCustomBg && strtoupper($produkWarnaBgColor) === $preset['value']
                                                ? 'border-2 border-admin-accent ring-2 ring-admin-accent ring-offset-2 ring-offset-admin-surface'
                                                : 'border border-admin-border group-hover:border-admin-accent/60' }}"
                                            style="background: linear-gradient(135deg, color-mix(in oklab, {{ $preset['value'] }} 100%, white 30%), {{ $preset['value'] }} 55%, color-mix(in oklab, {{ $preset['value'] }} 100%, black 16%));"
                                        >
                                            @if ($produkWarnaUseCustomBg && strtoupper($produkWarnaBgColor) === $preset['value'])
                                                <i class="fa-solid fa-check text-xs drop-shadow-sm" style="color: {{ $preset['check'] }};"></i>
                                            @endif
                                        </span>
                                        <span class="max-w-14 truncate text-[10px] text-admin-ink-soft">{{ $preset['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <p class="text-xs text-admin-ink-soft">
                            Kalau tidak dicentang, section ini pakai warna bawaan (krem #FEEDD8) seperti
                            sebelumnya. Teks &amp; tombol di section ini berwarna gelap dan TIDAK otomatis
                            menyesuaikan -- pilih warna terang supaya tetap kebaca.
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            wire:loading.attr="disabled" wire:target="saveProdukWarna"
                            class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition-all duration-200 hover:bg-admin-accent-strong active:scale-[0.99] disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="saveProdukWarna" class="flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan
                            </span>
                            <span wire:loading wire:target="saveProdukWarna" class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch animate-spin"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            @elseif ($activeSection === 'sustainability-hero')
                <form wire:submit="saveSustainabilityHero" class="space-y-6">
                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-leaf text-admin-accent"></i>
                            Hero Sustainability
                        </h3>
                        <p class="text-xs text-admin-ink-soft">Terhubung langsung ke section paling atas halaman Sustainability (/keberlanjutan).</p>
                    </div>

                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Backframe</p>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="sustainabilityHeroUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna latar khusus
                            </label>
                            <input type="color" wire:model="sustainabilityHeroBgColor" @disabled(! $sustainabilityHeroUseCustomBg) class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:opacity-40">
                        </div>
                        <div class="flex flex-wrap gap-2.5">
                            @foreach ($sustainabilityHeroBgPresets as $preset)
                                <button type="button" wire:click="selectSustainabilityHeroBgPreset('{{ $preset['value'] }}')" title="{{ $preset['label'] }}" class="flex h-9 w-9 items-center justify-center rounded-full border border-admin-border shadow-sm" style="background: {{ $preset['value'] }};">
                                    @if ($sustainabilityHeroUseCustomBg && strtoupper($sustainabilityHeroBgColor) === $preset['value'])<i class="fa-solid fa-check text-xs" style="color: {{ $preset['check'] }};"></i>@endif
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Isi Hero</p>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">Label kecil</label>
                            <input type="text" maxlength="40" wire:model="sustainabilityHeroEyebrow" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink">
                            @error('sustainabilityHeroEyebrow')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul</label>
                            <input type="text" maxlength="100" wire:model="sustainabilityHeroHeading" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink">
                            @error('sustainabilityHeroHeading')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">Deskripsi</label>
                            <textarea rows="5" maxlength="500" wire:model="sustainabilityHeroDescription" class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></textarea>
                            @error('sustainabilityHeroDescription')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="flex justify-end"><button type="submit" class="rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white"><i class="fa-solid fa-floppy-disk mr-2 text-xs"></i>Simpan Hero</button></div>
                </form>

            @elseif ($activeSection === 'sustainability-points')
                <form wire:submit="saveSustainabilityPoints" class="space-y-6">
                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-seedling text-admin-accent"></i>
                            Prinsip Sustainability
                        </h3>
                        <p class="text-xs text-admin-ink-soft">Mengatur empat kartu prinsip dan catatan di bawahnya. Semua field terhubung langsung ke halaman Sustainability.</p>
                    </div>

                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Backframe</p>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="sustainabilityPointsUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna latar khusus
                            </label>
                            <input type="color" wire:model="sustainabilityPointsBgColor" @disabled(! $sustainabilityPointsUseCustomBg) class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:opacity-40">
                        </div>
                        <div class="flex flex-wrap gap-2.5">
                            @foreach ($sustainabilityPointsBgPresets as $preset)
                                <button type="button" wire:click="selectSustainabilityPointsBgPreset('{{ $preset['value'] }}')" title="{{ $preset['label'] }}" class="flex h-9 w-9 items-center justify-center rounded-full border border-admin-border shadow-sm" style="background: {{ $preset['value'] }};">
                                    @if ($sustainabilityPointsUseCustomBg && strtoupper($sustainabilityPointsBgColor) === $preset['value'])<i class="fa-solid fa-check text-xs" style="color: {{ $preset['check'] }};"></i>@endif
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach ($sustainabilityPoints as $i => $point)
                            <div class="space-y-3 rounded-xl border border-admin-border p-4">
                                <div class="flex items-center justify-between"><p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Kartu {{ $i + 1 }}</p><i class="fa-solid {{ $point['icon'] ?? 'fa-leaf' }} text-admin-accent"></i></div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-admin-ink">Ikon</label>
                                    <select wire:model="sustainabilityPoints.{{ $i }}.icon" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink">
                                        @foreach ($sustainabilityIconOptions as $icon)<option value="{{ $icon }}">{{ str_replace('fa-', '', $icon) }}</option>@endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul</label>
                                    <input type="text" maxlength="80" wire:model="sustainabilityPoints.{{ $i }}.title" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-admin-ink">Deskripsi</label>
                                    <textarea rows="4" maxlength="300" wire:model="sustainabilityPoints.{{ $i }}.text" class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></textarea>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="rounded-xl border border-admin-border p-4">
                        <label class="mb-1.5 block text-sm font-medium text-admin-ink">Catatan bawah</label>
                        <textarea rows="4" maxlength="500" wire:model="sustainabilityNote" class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></textarea>
                        @error('sustainabilityNote')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end"><button type="submit" class="rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white"><i class="fa-solid fa-floppy-disk mr-2 text-xs"></i>Simpan Prinsip</button></div>
                </form>
            @elseif ($activeSection === 'privacy-hero')
                <form wire:submit="savePrivacyHero" class="space-y-6">
                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-lock text-admin-accent"></i>Hero Privacy Policy
                        </h3>
                        <p class="text-xs text-admin-ink-soft">Terhubung langsung ke hero halaman /kebijakan-privasi.</p>
                    </div>

                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Label kecil</label><input wire:model="privacyHeroEyebrow" type="text" maxlength="60" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul</label><input wire:model="privacyHeroHeading" type="text" maxlength="120" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Deskripsi</label><textarea wire:model="privacyHeroDescription" rows="5" maxlength="700" class="w-full resize-y rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></textarea></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Tanggal pembaruan terakhir</label><input wire:model="privacyHeroUpdatedDate" type="date" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink sm:max-w-xs"></div>
                    </div>

                    <div class="flex justify-end"><button type="submit" class="rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white"><i class="fa-solid fa-floppy-disk mr-2 text-xs"></i>Simpan Hero</button></div>
                </form>

            @elseif ($activeSection === 'privacy-contact')
                <form wire:submit="savePrivacyContact" class="space-y-6">
                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink"><i class="fa-solid fa-envelope-open-text text-admin-accent"></i>Kontak Privacy Policy</h3>
                        <p class="text-xs text-admin-ink-soft">Email, alamat, dan WhatsApp tetap mengikuti Pengaturan utama.</p>
                    </div>
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul</label><input wire:model="privacyContactHeading" type="text" maxlength="140" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Deskripsi</label><textarea wire:model="privacyContactDescription" rows="4" maxlength="600" class="w-full resize-y rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></textarea></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Tulisan tombol WhatsApp</label><input wire:model="privacyContactButtonLabel" type="text" maxlength="80" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></div>
                    </div>
                    <div class="flex justify-end"><button type="submit" class="rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white"><i class="fa-solid fa-floppy-disk mr-2 text-xs"></i>Simpan Kontak</button></div>
                </form>

            @elseif (str_starts_with($activeSection, 'privacy-'))
                @php
                    $privacyKey = str_replace('privacy-', '', $activeSection);
                    $privacyMeta = collect($privacySections)->firstWhere('key', $activeSection);
                @endphp
                <form wire:submit="savePrivacySection('{{ $privacyKey }}')" class="space-y-6">
                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid {{ $privacyMeta['icon'] ?? 'fa-file-lines' }} text-admin-accent"></i>
                            {{ $privacyMeta['label'] ?? 'Bagian Privacy Policy' }}
                        </h3>
                        <p class="text-xs text-admin-ink-soft">Judul di Daftar Isi halaman Privacy Policy ikut berubah otomatis.</p>
                    </div>

                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul bagian</label><input wire:model="privacyContent.{{ $privacyKey }}.title" type="text" maxlength="160" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Isi awal / paragraf utama</label><textarea wire:model="privacyContent.{{ $privacyKey }}.intro" rows="6" maxlength="2500" class="w-full resize-y rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm leading-relaxed text-admin-ink"></textarea></div>

                        @if (!empty($privacyContent[$privacyKey]['bullets']))
                            <div class="space-y-3 border-t border-admin-border pt-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Poin penjelas</p>
                                @foreach ($privacyContent[$privacyKey]['bullets'] as $bulletIndex => $bullet)
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-admin-ink">Poin {{ $bulletIndex + 1 }}</label>
                                        <textarea wire:model="privacyContent.{{ $privacyKey }}.bullets.{{ $bulletIndex }}" rows="3" maxlength="1000" class="w-full resize-y rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></textarea>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">Paragraf penutup <span class="text-admin-ink-soft">(boleh kosong)</span></label>
                            <textarea wire:model="privacyContent.{{ $privacyKey }}.outro" rows="4" maxlength="2500" class="w-full resize-y rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm leading-relaxed text-admin-ink"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end"><button type="submit" class="rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white"><i class="fa-solid fa-floppy-disk mr-2 text-xs"></i>Simpan Bagian</button></div>
                </form>

            @elseif ($activeSection === 'cookies-hero')
                <form wire:submit="saveCookiesHero" class="space-y-6">
                    <div><h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink"><i class="fa-solid fa-cookie-bite text-admin-accent"></i>Hero Cookies</h3><p class="text-xs text-admin-ink-soft">Terhubung langsung ke hero halaman /kebijakan-cookie.</p></div>
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Label kecil</label><input wire:model="cookiesHeroEyebrow" type="text" maxlength="60" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul</label><input wire:model="cookiesHeroHeading" type="text" maxlength="120" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Deskripsi</label><textarea wire:model="cookiesHeroDescription" rows="5" maxlength="700" class="w-full resize-y rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></textarea></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Tanggal pembaruan terakhir</label><input wire:model="cookiesHeroUpdatedDate" type="date" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink sm:max-w-xs"></div>
                    </div>
                    <div class="flex justify-end"><button type="submit" class="rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white"><i class="fa-solid fa-floppy-disk mr-2 text-xs"></i>Simpan Hero</button></div>
                </form>

            @elseif ($activeSection === 'cookies-summary')
                <form wire:submit="saveCookiesSummary" class="space-y-6">
                    <div><h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink"><i class="fa-solid fa-circle-check text-admin-accent"></i>Ringkasan Cookies</h3></div>
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Label tebal</label><input wire:model="cookiesSummaryLabel" type="text" maxlength="80" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Isi ringkasan</label><textarea wire:model="cookiesSummaryText" rows="5" maxlength="1200" class="w-full resize-y rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm leading-relaxed text-admin-ink"></textarea></div>
                    </div>
                    <div class="flex justify-end"><button type="submit" class="rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white"><i class="fa-solid fa-floppy-disk mr-2 text-xs"></i>Simpan Ringkasan</button></div>
                </form>

            @elseif ($activeSection === 'cookies-categories')
                <form wire:submit="saveCookiesCategories" class="space-y-6">
                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink"><i class="fa-solid fa-layer-group text-admin-accent"></i>Rincian per Kategori</h3>
                        <p class="text-xs text-admin-ink-soft">Lima kartu kategori mengikuti halaman Cookies yang sekarang. Ikon dan jenis status tetap dikunci agar arti teknisnya tidak berubah.</p>
                    </div>

                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul bagian</label><input wire:model="cookiesCategoriesHeading" type="text" maxlength="120" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Deskripsi bagian</label><textarea wire:model="cookiesCategoriesDescription" rows="4" maxlength="800" class="w-full resize-y rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></textarea></div>

                        <div class="grid gap-3 border-t border-admin-border pt-4 sm:grid-cols-3">
                            <div><label class="mb-1 block text-xs font-medium text-admin-ink">Status Esensial</label><input wire:model="cookiesStatusAlways" type="text" maxlength="80" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></div>
                            <div><label class="mb-1 block text-xs font-medium text-admin-ink">Status Lokal</label><input wire:model="cookiesStatusLocal" type="text" maxlength="80" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></div>
                            <div><label class="mb-1 block text-xs font-medium text-admin-ink">Status Tidak Dipakai</label><input wire:model="cookiesStatusNone" type="text" maxlength="80" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></div>
                        </div>

                        <div class="space-y-4 border-t border-admin-border pt-4">
                            @foreach ($cookiesCategoryItems as $cookieIndex => $cookie)
                                <div class="rounded-xl bg-admin-canvas p-4">
                                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-admin-accent">Kategori {{ $cookieIndex + 1 }}</p>
                                    <div><label class="mb-1 block text-xs font-medium text-admin-ink">Nama</label><input wire:model="cookiesCategoryItems.{{ $cookieIndex }}.name" type="text" maxlength="100" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></div>
                                    <div class="mt-3"><label class="mb-1 block text-xs font-medium text-admin-ink">Deskripsi</label><textarea wire:model="cookiesCategoryItems.{{ $cookieIndex }}.desc" rows="4" maxlength="1200" class="w-full resize-y rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm leading-relaxed text-admin-ink"></textarea></div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end"><button type="submit" class="rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white"><i class="fa-solid fa-floppy-disk mr-2 text-xs"></i>Simpan Kategori</button></div>
                </form>

            @elseif ($activeSection === 'cookies-browser')
                <form wire:submit="saveCookiesBrowser" class="space-y-6">
                    <div><h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink"><i class="fa-solid fa-sliders text-admin-accent"></i>Mengatur Penyimpanan di Browser</h3></div>
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul</label><input wire:model="cookiesBrowserHeading" type="text" maxlength="140" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Isi</label><textarea wire:model="cookiesBrowserText" rows="7" maxlength="1800" class="w-full resize-y rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm leading-relaxed text-admin-ink"></textarea></div>
                    </div>
                    <div class="flex justify-end"><button type="submit" class="rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white"><i class="fa-solid fa-floppy-disk mr-2 text-xs"></i>Simpan Bagian</button></div>
                </form>

            @elseif ($activeSection === 'cookies-contact')
                <form wire:submit="saveCookiesContact" class="space-y-6">
                    <div><h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink"><i class="fa-solid fa-comments text-admin-accent"></i>Kontak Cookies</h3><p class="text-xs text-admin-ink-soft">Email dan WhatsApp tetap mengikuti Pengaturan utama.</p></div>
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul</label><input wire:model="cookiesContactHeading" type="text" maxlength="140" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Deskripsi</label><textarea wire:model="cookiesContactDescription" rows="4" maxlength="600" class="w-full resize-y rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></textarea></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Tulisan tombol WhatsApp</label><input wire:model="cookiesContactButtonLabel" type="text" maxlength="80" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></div>
                    </div>
                    <div class="flex justify-end"><button type="submit" class="rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white"><i class="fa-solid fa-floppy-disk mr-2 text-xs"></i>Simpan Kontak</button></div>
                </form>
            @elseif ($activeSection === 'terms-hero')
                <form wire:submit="saveTermsHero" class="space-y-6">
                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-file-contract text-admin-accent"></i>
                            Hero Terms of Service
                        </h3>
                        <p class="text-xs text-admin-ink-soft">Terhubung langsung ke bagian paling atas halaman Terms of Service (/ketentuan-layanan).</p>
                    </div>

                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">Label kecil</label>
                            <input type="text" maxlength="60" wire:model="termsHeroEyebrow" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink">
                            @error('termsHeroEyebrow')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul</label>
                            <input type="text" maxlength="120" wire:model="termsHeroHeading" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink">
                            @error('termsHeroHeading')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">Deskripsi</label>
                            <textarea rows="5" maxlength="700" wire:model="termsHeroDescription" class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></textarea>
                            @error('termsHeroDescription')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">Tanggal pembaruan terakhir</label>
                            <input type="date" wire:model="termsHeroUpdatedDate" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink sm:max-w-xs">
                            @error('termsHeroUpdatedDate')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white">
                            <i class="fa-solid fa-floppy-disk mr-2 text-xs"></i>Simpan Hero
                        </button>
                    </div>
                </form>

            @elseif ($activeSection === 'terms-contact')
                <form wire:submit="saveTermsContact" class="space-y-6">
                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-handshake text-admin-accent"></i>
                            Kontak Terms of Service
                        </h3>
                        <p class="text-xs text-admin-ink-soft">Email dan nomor WhatsApp tetap mengikuti Pengaturan utama. Di sini Anda mengubah teks bagian kontaknya.</p>
                    </div>

                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul</label>
                            <input type="text" maxlength="120" wire:model="termsContactHeading" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink">
                            @error('termsContactHeading')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">Deskripsi</label>
                            <textarea rows="4" maxlength="500" wire:model="termsContactDescription" class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></textarea>
                            @error('termsContactDescription')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">Tulisan tombol WhatsApp</label>
                            <input type="text" maxlength="80" wire:model="termsContactButtonLabel" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink">
                            @error('termsContactButtonLabel')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white">
                            <i class="fa-solid fa-floppy-disk mr-2 text-xs"></i>Simpan Kontak
                        </button>
                    </div>
                </form>

            @elseif (str_starts_with($activeSection, 'terms-'))
                @php
                    $termsKey = str_replace('terms-', '', $activeSection);
                    $termsMeta = collect($termsSections)->firstWhere('key', $activeSection);
                @endphp

                <form wire:submit="saveTermsSection('{{ $termsKey }}')" class="space-y-6">
                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid {{ $termsMeta['icon'] ?? 'fa-file-lines' }} text-admin-accent"></i>
                            {{ $termsMeta['label'] ?? 'Bagian Terms of Service' }}
                        </h3>
                        <p class="text-xs text-admin-ink-soft">Perubahan tersambung langsung ke bagian yang sama pada halaman Terms of Service dan Daftar Isi ikut menyesuaikan judul.</p>
                    </div>

                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul bagian</label>
                            <input type="text" maxlength="140" wire:model="termsContent.{{ $termsKey }}.title" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink">
                            @error("termsContent.$termsKey.title")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">Isi utama</label>
                            <textarea rows="7" maxlength="2500" wire:model="termsContent.{{ $termsKey }}.text" class="w-full resize-y rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm leading-relaxed text-admin-ink"></textarea>
                            @error("termsContent.$termsKey.text")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        @if (isset($termsContent[$termsKey]['bullets']))
                            <div class="space-y-3 border-t border-admin-border pt-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Poin penjelas</p>
                                @foreach ($termsContent[$termsKey]['bullets'] as $bulletIndex => $bullet)
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-admin-ink">Poin {{ $bulletIndex + 1 }}</label>
                                        <textarea rows="3" maxlength="800" wire:model="termsContent.{{ $termsKey }}.bullets.{{ $bulletIndex }}" class="w-full resize-y rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></textarea>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if (isset($termsContent[$termsKey]['link_labels']))
                            <div class="space-y-3 border-t border-admin-border pt-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Tulisan tombol referensi</p>
                                <p class="text-xs text-admin-ink-soft">Tujuan link tetap diarahkan ke bagian Garansi dan Pengiriman pada halaman Profil agar tidak salah sambung.</p>
                                @foreach ($termsContent[$termsKey]['link_labels'] as $linkIndex => $linkLabel)
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-admin-ink">Tombol {{ $linkIndex + 1 }}</label>
                                        <input type="text" maxlength="120" wire:model="termsContent.{{ $termsKey }}.link_labels.{{ $linkIndex }}" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink">
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white">
                            <i class="fa-solid fa-floppy-disk mr-2 text-xs"></i>Simpan Bagian
                        </button>
                    </div>
                </form>
            @elseif ($activeSection === 'our-craftsmen-hero')
                <form wire:submit="saveOurCraftsmenHero" class="space-y-6">

                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-image text-admin-accent"></i>
                            Hero (halaman Our Craftsmen)
                        </h3>
                        <p class="text-xs text-admin-ink-soft">
                            Section paling atas halaman "Our Craftsmen" (/pengrajin-kami) -- label kecil,
                            judul, dan paragraf pembuka sebelum daftar pengrajin.
                        </p>
                    </div>

                    {{-- WARNA --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Frame (Latar Section)</p>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="ourCraftsmenHeroUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna latar khusus
                            </label>
                            <input
                                type="color" wire:model="ourCraftsmenHeroBgColor"
                                @disabled(! $ourCraftsmenHeroUseCustomBg)
                                class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:cursor-not-allowed disabled:opacity-40"
                            >
                        </div>

                        <div>
                            <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-admin-ink-soft">Atau pilih dari rekomendasi</p>
                            <div class="flex flex-wrap gap-2.5">
                                @foreach ($ourCraftsmenHeroBgPresets as $preset)
                                    <button
                                        type="button"
                                        wire:click="selectOurCraftsmenHeroBgPreset('{{ $preset['value'] }}')"
                                        title="{{ $preset['label'] }}"
                                        class="group flex flex-col items-center gap-1"
                                    >
                                        <span
                                            class="flex h-9 w-9 items-center justify-center rounded-full shadow-sm ring-1 ring-inset ring-white/40 transition duration-200 group-hover:scale-110 group-hover:shadow-md
                                            {{ $ourCraftsmenHeroUseCustomBg && strtoupper($ourCraftsmenHeroBgColor) === $preset['value']
                                                ? 'border-2 border-admin-accent ring-2 ring-admin-accent ring-offset-2 ring-offset-admin-surface'
                                                : 'border border-admin-border group-hover:border-admin-accent/60' }}"
                                            style="background: linear-gradient(135deg, color-mix(in oklab, {{ $preset['value'] }} 100%, white 30%), {{ $preset['value'] }} 55%, color-mix(in oklab, {{ $preset['value'] }} 100%, black 16%));"
                                        >
                                            @if ($ourCraftsmenHeroUseCustomBg && strtoupper($ourCraftsmenHeroBgColor) === $preset['value'])
                                                <i class="fa-solid fa-check text-xs drop-shadow-sm" style="color: {{ $preset['check'] }};"></i>
                                            @endif
                                        </span>
                                        <span class="max-w-14 truncate text-[10px] text-admin-ink-soft">{{ $preset['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <p class="text-xs text-admin-ink-soft">
                            Kalau tidak dicentang, section ini ikut warna krem bawaan. Warna judul dan
                            paragraf otomatis menyesuaikan (terang/gelap) mengikuti warna latar yang
                            dipilih, supaya tetap kebaca.
                        </p>
                    </div>

                    @include('partials.admin.frame-gradient', [
                        'key' => 'ourCraftsmenHero',
                        'gradient' => $ourCraftsmenHeroGradient,
                    ])

                    {{-- ISI TEKS --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Isi Teks</p>

                        <div class="grid gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Label kecil (eyebrow)</label>
                                <input
                                    type="text" maxlength="40" wire:model="ourCraftsmenHeroEyebrow"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('ourCraftsmenHeroEyebrow')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul</label>
                                <input
                                    type="text" maxlength="100" wire:model="ourCraftsmenHeroHeading"
                                    class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                >
                                @error('ourCraftsmenHeroHeading')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-admin-ink">Paragraf</label>
                                <textarea
                                    rows="4" maxlength="400" wire:model="ourCraftsmenHeroDescription"
                                    class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                                ></textarea>
                                @error('ourCraftsmenHeroDescription')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            wire:loading.attr="disabled" wire:target="saveOurCraftsmenHero"
                            class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition-all duration-200 hover:bg-admin-accent-strong active:scale-[0.99] disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="saveOurCraftsmenHero" class="flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan
                            </span>
                            <span wire:loading wire:target="saveOurCraftsmenHero" class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch animate-spin"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            @elseif ($activeSection === 'our-craftsmen-daftar')
                <form wire:submit="saveOurCraftsmenOwner" class="space-y-6">
                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-user-tie text-admin-accent"></i>
                            Pemilik & Founder
                        </h3>
                        <p class="text-xs text-admin-ink-soft">
                            Section editorial untuk memperkenalkan pemilik/founder secara profesional. Foto, identitas, cerita singkat, kutipan, dan 3 poin utama semuanya terhubung langsung ke halaman Our Craftsmen.
                        </p>
                    </div>

                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Backframe</p>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="ourCraftsmenOwnerUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna backframe khusus
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="color" wire:model="ourCraftsmenOwnerBgColor" @disabled(! $ourCraftsmenOwnerUseCustomBg) class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:opacity-40">
                                <span class="font-mono text-xs text-admin-ink-soft">{{ strtoupper($ourCraftsmenOwnerBgColor) }}</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2.5">
                            @foreach ($ourCraftsmenOwnerBgPresets as $preset)
                                <button type="button" wire:click="selectOurCraftsmenOwnerBgPreset('{{ $preset['value'] }}')" title="{{ $preset['label'] }}" class="h-9 w-9 rounded-full border border-admin-border shadow-sm transition hover:scale-110" style="background: {{ $preset['value'] }};"></button>
                            @endforeach
                        </div>
                        @error('ourCraftsmenOwnerBgColor')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-5 lg:grid-cols-[18rem_minmax(0,1fr)]">
                                                <div class="space-y-4 rounded-xl border border-admin-border p-4" x-data="ourCraftsmenOwnerPhotoCropper(@js($this->ourCraftsmenOwnerPhotoPreviewUrl))">
                            <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Foto Pemilik</p>

                            <div class="flex flex-col items-center gap-4">
                                <div class="relative w-full max-w-64">
                                    <div class="relative aspect-4/5 w-full overflow-hidden rounded-2xl bg-admin-cream ring-4 ring-admin-cream">
                                        <template x-if="previewUrl">
                                            <img :src="previewUrl" alt="Preview foto pemilik" class="h-full w-full object-cover">
                                        </template>
                                        <template x-if="!previewUrl">
                                            <div class="flex h-full w-full flex-col items-center justify-center gap-2 text-center text-admin-ink-soft">
                                                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-admin-surface shadow-sm"><i class="fa-solid fa-user-tie"></i></span>
                                                <span class="text-xs">Belum ada foto pemilik</span>
                                            </div>
                                        </template>
                                    </div>

                                    <label
                                        for="our_craftsmen_owner_photo_input"
                                        class="absolute -bottom-1 -right-1 flex h-9 w-9 cursor-pointer items-center justify-center rounded-full bg-admin-accent text-white shadow-sm ring-2 ring-admin-surface transition hover:bg-admin-accent-strong"
                                        title="Pilih dan crop foto"
                                    >
                                        <i class="fa-solid fa-camera text-xs"></i>
                                    </label>
                                    <input x-ref="fileInput" id="our_craftsmen_owner_photo_input" type="file" accept="image/*" class="hidden" x-on:change="onFileChange($event)">
                                </div>

                                <div class="w-full text-center">
                                    <p class="text-sm font-medium text-admin-ink">Klik ikon kamera untuk ganti foto</p>
                                    <p class="mt-1 text-xs leading-relaxed text-admin-ink-soft">Setelah memilih foto, crop wajib dilakukan. Geser gambar untuk mengatur posisi dan gunakan slider untuk zoom. Hasil akhir portrait 4:5.</p>
                                </div>

                                @if ($this->ourCraftsmenOwnerPhotoPreviewUrl)
                                    <button type="button" wire:click="removeOurCraftsmenOwnerPhoto" x-on:click="previewUrl = null" class="inline-flex items-center gap-2 text-xs font-semibold text-red-600 transition hover:text-red-700">
                                        <i class="fa-solid fa-trash"></i> Hapus foto
                                    </button>
                                @endif
                                @error('ourCraftsmenOwnerPhotoCroppedBase64')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <template x-teleport="body">
                                <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-999 flex items-center justify-center bg-black/60 p-4" style="display: none;">
                                    <div x-show="open" x-transition.scale.origin.center @click.outside="cancelCrop()" class="w-full max-w-md rounded-2xl bg-admin-surface p-6 shadow-2xl">
                                        <div class="mb-4">
                                            <h4 class="text-sm font-semibold text-admin-ink">Sesuaikan Foto Pemilik</h4>
                                            <p class="mt-1 text-xs text-admin-ink-soft">Geser foto untuk memindahkan posisi. Gunakan slider, scroll, atau cubit untuk zoom. Rasio akhir 4:5.</p>
                                        </div>

                                        <div x-ref="viewport" class="relative mx-auto aspect-4/5 w-full max-w-64 cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none" x-on:pointerdown="startDrag($event)" x-on:pointermove="onDrag($event)" x-on:pointerup="endDrag($event)" x-on:pointercancel="endDrag($event)" x-on:pointerleave="endDrag($event)" x-on:wheel.prevent="onWheel($event)">
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
<div class="space-y-4 rounded-xl border border-admin-border p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Identitas & Cerita</p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Label kecil</label><input type="text" maxlength="50" wire:model="ourCraftsmenOwnerEyebrow" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20">@error('ourCraftsmenOwnerEyebrow')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                                <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Nama pemilik</label><input type="text" maxlength="80" wire:model="ourCraftsmenOwnerName" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20">@error('ourCraftsmenOwnerName')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                                <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium text-admin-ink">Jabatan / peran</label><input type="text" maxlength="100" wire:model="ourCraftsmenOwnerRole" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20">@error('ourCraftsmenOwnerRole')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                                <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium text-admin-ink">Headline profil</label><input type="text" maxlength="140" wire:model="ourCraftsmenOwnerHeading" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20">@error('ourCraftsmenOwnerHeading')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                                <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium text-admin-ink">Cerita singkat</label><textarea rows="5" maxlength="800" wire:model="ourCraftsmenOwnerDescription" class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"></textarea>@error('ourCraftsmenOwnerDescription')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                                <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium text-admin-ink">Kutipan pemilik</label><textarea rows="3" maxlength="350" wire:model="ourCraftsmenOwnerQuote" class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"></textarea>@error('ourCraftsmenOwnerQuote')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-admin-border p-4">
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">3 Highlight Profil</p>
                        <p class="mb-4 text-xs text-admin-ink-soft">Contoh: â€œ10+ / Tahun pengalamanâ€, â€œCustom / Dibuat sesuai kebutuhanâ€, atau â€œJepara / Workshopâ€.</p>
                        <div class="grid gap-4 md:grid-cols-3">
                            @foreach ($ourCraftsmenOwnerStats as $i => $stat)
                                <div class="space-y-2 rounded-lg bg-admin-cream/50 p-3">
                                    <input type="text" maxlength="30" wire:model="ourCraftsmenOwnerStats.{{ $i }}.value" placeholder="Nilai" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2 text-sm font-semibold text-admin-ink focus:border-admin-accent focus:outline-none">
                                    <input type="text" maxlength="60" wire:model="ourCraftsmenOwnerStats.{{ $i }}.label" placeholder="Keterangan" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2 text-xs text-admin-ink focus:border-admin-accent focus:outline-none">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end"><button type="submit" wire:loading.attr="disabled" wire:target="saveOurCraftsmenOwner" class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition hover:bg-admin-accent-strong disabled:opacity-60"><span wire:loading.remove wire:target="saveOurCraftsmenOwner"><i class="fa-solid fa-floppy-disk mr-2 text-xs"></i>Simpan Pemilik & Founder</span><span wire:loading wire:target="saveOurCraftsmenOwner"><i class="fa-solid fa-circle-notch mr-2 animate-spin"></i>Menyimpan...</span></button></div>
                </form>
            @elseif ($activeSection === 'our-craftsmen-cta')
                <form wire:submit="saveOurCraftsmenCta" class="space-y-6">
                    <div><h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink"><i class="fa-solid fa-bullhorn text-admin-accent"></i>Mulai dari Sebuah Ide</h3><p class="text-xs text-admin-ink-soft">Bagian penutup halaman. Tombol otomatis menuju WhatsApp toko jika nomor WhatsApp tersedia di Pengaturan; jika belum, tombol menuju halaman booking.</p></div>
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Backframe</p>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="ourCraftsmenCtaUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna backframe khusus
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="color" wire:model="ourCraftsmenCtaBgColor" @disabled(! $ourCraftsmenCtaUseCustomBg) class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:opacity-40">
                                <span class="font-mono text-xs text-admin-ink-soft">{{ strtoupper($ourCraftsmenCtaBgColor) }}</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2.5">
                            @foreach ($ourCraftsmenCtaBgPresets as $preset)
                                <button type="button" wire:click="selectOurCraftsmenCtaBgPreset('{{ $preset['value'] }}')" title="{{ $preset['label'] }}" class="h-9 w-9 rounded-full border border-admin-border shadow-sm transition hover:scale-110" style="background: {{ $preset['value'] }};"></button>
                            @endforeach
                        </div>
                        @error('ourCraftsmenCtaBgColor')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Label kecil</label><input type="text" maxlength="50" wire:model="ourCraftsmenCtaEyebrow" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20">@error('ourCraftsmenCtaEyebrow')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul</label><input type="text" maxlength="120" wire:model="ourCraftsmenCtaHeading" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20">@error('ourCraftsmenCtaHeading')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Deskripsi</label><textarea rows="4" maxlength="500" wire:model="ourCraftsmenCtaDescription" class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"></textarea>@error('ourCraftsmenCtaDescription')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Teks tombol</label><input type="text" maxlength="50" wire:model="ourCraftsmenCtaButtonText" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20">@error('ourCraftsmenCtaButtonText')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    </div>
                    <div class="flex justify-end"><button type="submit" wire:loading.attr="disabled" wire:target="saveOurCraftsmenCta" class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition hover:bg-admin-accent-strong disabled:opacity-60"><span wire:loading.remove wire:target="saveOurCraftsmenCta"><i class="fa-solid fa-floppy-disk mr-2 text-xs"></i>Simpan CTA</span><span wire:loading wire:target="saveOurCraftsmenCta"><i class="fa-solid fa-circle-notch mr-2 animate-spin"></i>Menyimpan...</span></button></div>
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
                    x-on:pointerup="endDrag($event)"
                    x-on:pointercancel="endDrag($event)"
                    x-on:pointerleave="endDrag($event)"
                    x-on:wheel.prevent="onWheel($event)"
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

        // Gestur 2 jari: pointer aktif (id -> {x, y}) untuk cubit di layar sentuh.
        pointers: {},
        pinchStartDist: 0,
        pinchStartScale: 1,
        pinchLastMidX: 0,
        pinchLastMidY: 0,

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

        // Ubah zoom dengan titik (cx, cy) di dalam viewport tetap di tempatnya
        // (dipakai gestur cubit / Ctrl + scroll; slider tetap lewat applyZoom).
        zoomTo(newScale, cx, cy) {
            newScale = Math.min(this.maxScale, Math.max(this.minScale, newScale));
            const ratio = newScale / this.scale;

            this.posX = cx - (cx - this.posX) * ratio;
            this.posY = cy - (cy - this.posY) * ratio;
            this.scale = newScale;
            this.zoomPercent = this.maxScale > this.minScale
                ? ((newScale - this.minScale) / (this.maxScale - this.minScale)) * 100
                : 0;
            this.clampPos();
        },

        // Touchpad: geser 2 jari = wheel biasa (geser foto), cubit 2 jari = wheel + Ctrl (zoom).
        onWheel(e) {
            if (this.natW === 0) return;

            if (e.ctrlKey) {
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.zoomTo(this.scale * Math.exp(-e.deltaY * 0.01), e.clientX - rect.left, e.clientY - rect.top);
                return;
            }

            this.posX -= e.deltaX;
            this.posY -= e.deltaY;
            this.clampPos();
        },

        startDrag(e) {
            this.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };

            const points = Object.values(this.pointers);

            if (points.length >= 2) {
                // Dua jari menyentuh layar: mulai cubit.
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.dragging = false;
                this.pinchStartDist = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);
                this.pinchStartScale = this.scale;
                this.pinchLastMidX = (points[0].x + points[1].x) / 2 - rect.left;
                this.pinchLastMidY = (points[0].y + points[1].y) / 2 - rect.top;
                return;
            }

            this.dragging = true;
            this.dragStartX = e.clientX;
            this.dragStartY = e.clientY;
            this.startPosX = this.posX;
            this.startPosY = this.posY;
        },

        onDrag(e) {
            if (!(e.pointerId in this.pointers)) return;
            this.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };

            const points = Object.values(this.pointers);

            if (points.length >= 2) {
                const rect = this.$refs.viewport.getBoundingClientRect();
                const midX = (points[0].x + points[1].x) / 2 - rect.left;
                const midY = (points[0].y + points[1].y) / 2 - rect.top;
                const dist = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);

                // Geser mengikuti titik tengah kedua jari, lalu zoom sesuai jarak antar jari.
                this.posX += midX - this.pinchLastMidX;
                this.posY += midY - this.pinchLastMidY;
                this.pinchLastMidX = midX;
                this.pinchLastMidY = midY;

                if (this.pinchStartDist > 0) {
                    this.zoomTo(this.pinchStartScale * (dist / this.pinchStartDist), midX, midY);
                } else {
                    this.clampPos();
                }
                return;
            }

            if (!this.dragging) return;
            this.posX = this.startPosX + (e.clientX - this.dragStartX);
            this.posY = this.startPosY + (e.clientY - this.dragStartY);
            this.clampPos();
        },

        endDrag(e) {
            if (e && e.pointerId !== undefined) {
                delete this.pointers[e.pointerId];
            } else {
                this.pointers = {};
            }

            const rest = Object.values(this.pointers);

            if (rest.length === 1) {
                // Tinggal satu jari: lanjut geser dari posisi sekarang (tanpa lompatan).
                this.dragging = true;
                this.dragStartX = rest[0].x;
                this.dragStartY = rest[0].y;
                this.startPosX = this.posX;
                this.startPosY = this.posY;
            } else {
                this.dragging = false;
            }
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
            this.pointers = {};
            this.dragging = false;
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

        // Gestur 2 jari: pointer aktif (id -> {x, y}) untuk cubit di layar sentuh.
        pointers: {},
        pinchStartDist: 0,
        pinchStartScale: 1,
        pinchLastMidX: 0,
        pinchLastMidY: 0,

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

        // Ubah zoom dengan titik (cx, cy) di dalam viewport tetap di tempatnya
        // (dipakai gestur cubit / Ctrl + scroll; slider tetap lewat applyZoom).
        zoomTo(newScale, cx, cy) {
            newScale = Math.min(this.maxScale, Math.max(this.minScale, newScale));
            const ratio = newScale / this.scale;

            this.posX = cx - (cx - this.posX) * ratio;
            this.posY = cy - (cy - this.posY) * ratio;
            this.scale = newScale;
            this.zoomPercent = this.maxScale > this.minScale
                ? ((newScale - this.minScale) / (this.maxScale - this.minScale)) * 100
                : 0;
            this.clampPos();
        },

        // Touchpad: geser 2 jari = wheel biasa (geser foto), cubit 2 jari = wheel + Ctrl (zoom).
        onWheel(e) {
            if (this.natW === 0) return;

            if (e.ctrlKey) {
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.zoomTo(this.scale * Math.exp(-e.deltaY * 0.01), e.clientX - rect.left, e.clientY - rect.top);
                return;
            }

            this.posX -= e.deltaX;
            this.posY -= e.deltaY;
            this.clampPos();
        },

        startDrag(e) {
            this.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };

            const points = Object.values(this.pointers);

            if (points.length >= 2) {
                // Dua jari menyentuh layar: mulai cubit.
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.dragging = false;
                this.pinchStartDist = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);
                this.pinchStartScale = this.scale;
                this.pinchLastMidX = (points[0].x + points[1].x) / 2 - rect.left;
                this.pinchLastMidY = (points[0].y + points[1].y) / 2 - rect.top;
                return;
            }

            this.dragging = true;
            this.dragStartX = e.clientX;
            this.dragStartY = e.clientY;
            this.startPosX = this.posX;
            this.startPosY = this.posY;
        },

        onDrag(e) {
            if (!(e.pointerId in this.pointers)) return;
            this.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };

            const points = Object.values(this.pointers);

            if (points.length >= 2) {
                const rect = this.$refs.viewport.getBoundingClientRect();
                const midX = (points[0].x + points[1].x) / 2 - rect.left;
                const midY = (points[0].y + points[1].y) / 2 - rect.top;
                const dist = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);

                // Geser mengikuti titik tengah kedua jari, lalu zoom sesuai jarak antar jari.
                this.posX += midX - this.pinchLastMidX;
                this.posY += midY - this.pinchLastMidY;
                this.pinchLastMidX = midX;
                this.pinchLastMidY = midY;

                if (this.pinchStartDist > 0) {
                    this.zoomTo(this.pinchStartScale * (dist / this.pinchStartDist), midX, midY);
                } else {
                    this.clampPos();
                }
                return;
            }

            if (!this.dragging) return;
            this.posX = this.startPosX + (e.clientX - this.dragStartX);
            this.posY = this.startPosY + (e.clientY - this.dragStartY);
            this.clampPos();
        },

        endDrag(e) {
            if (e && e.pointerId !== undefined) {
                delete this.pointers[e.pointerId];
            } else {
                this.pointers = {};
            }

            const rest = Object.values(this.pointers);

            if (rest.length === 1) {
                // Tinggal satu jari: lanjut geser dari posisi sekarang (tanpa lompatan).
                this.dragging = true;
                this.dragStartX = rest[0].x;
                this.dragStartY = rest[0].y;
                this.startPosX = this.posX;
                this.startPosY = this.posY;
            } else {
                this.dragging = false;
            }
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
            this.pointers = {};
            this.dragging = false;
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

        // Gestur 2 jari: pointer aktif (id -> {x, y}) untuk cubit di layar sentuh.
        pointers: {},
        pinchStartDist: 0,
        pinchStartScale: 1,
        pinchLastMidX: 0,
        pinchLastMidY: 0,

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

        // Ubah zoom dengan titik (cx, cy) di dalam viewport tetap di tempatnya
        // (dipakai gestur cubit / Ctrl + scroll; slider tetap lewat applyZoom).
        zoomTo(newScale, cx, cy) {
            newScale = Math.min(this.maxScale, Math.max(this.minScale, newScale));
            const ratio = newScale / this.scale;

            this.posX = cx - (cx - this.posX) * ratio;
            this.posY = cy - (cy - this.posY) * ratio;
            this.scale = newScale;
            this.zoomPercent = this.maxScale > this.minScale
                ? ((newScale - this.minScale) / (this.maxScale - this.minScale)) * 100
                : 0;
            this.clampPos();
        },

        // Touchpad: geser 2 jari = wheel biasa (geser foto), cubit 2 jari = wheel + Ctrl (zoom).
        onWheel(e) {
            if (this.natW === 0) return;

            if (e.ctrlKey) {
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.zoomTo(this.scale * Math.exp(-e.deltaY * 0.01), e.clientX - rect.left, e.clientY - rect.top);
                return;
            }

            this.posX -= e.deltaX;
            this.posY -= e.deltaY;
            this.clampPos();
        },

        startDrag(e) {
            this.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };

            const points = Object.values(this.pointers);

            if (points.length >= 2) {
                // Dua jari menyentuh layar: mulai cubit.
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.dragging = false;
                this.pinchStartDist = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);
                this.pinchStartScale = this.scale;
                this.pinchLastMidX = (points[0].x + points[1].x) / 2 - rect.left;
                this.pinchLastMidY = (points[0].y + points[1].y) / 2 - rect.top;
                return;
            }

            this.dragging = true;
            this.dragStartX = e.clientX;
            this.dragStartY = e.clientY;
            this.startPosX = this.posX;
            this.startPosY = this.posY;
        },

        onDrag(e) {
            if (!(e.pointerId in this.pointers)) return;
            this.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };

            const points = Object.values(this.pointers);

            if (points.length >= 2) {
                const rect = this.$refs.viewport.getBoundingClientRect();
                const midX = (points[0].x + points[1].x) / 2 - rect.left;
                const midY = (points[0].y + points[1].y) / 2 - rect.top;
                const dist = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);

                // Geser mengikuti titik tengah kedua jari, lalu zoom sesuai jarak antar jari.
                this.posX += midX - this.pinchLastMidX;
                this.posY += midY - this.pinchLastMidY;
                this.pinchLastMidX = midX;
                this.pinchLastMidY = midY;

                if (this.pinchStartDist > 0) {
                    this.zoomTo(this.pinchStartScale * (dist / this.pinchStartDist), midX, midY);
                } else {
                    this.clampPos();
                }
                return;
            }

            if (!this.dragging) return;
            this.posX = this.startPosX + (e.clientX - this.dragStartX);
            this.posY = this.startPosY + (e.clientY - this.dragStartY);
            this.clampPos();
        },

        endDrag(e) {
            if (e && e.pointerId !== undefined) {
                delete this.pointers[e.pointerId];
            } else {
                this.pointers = {};
            }

            const rest = Object.values(this.pointers);

            if (rest.length === 1) {
                // Tinggal satu jari: lanjut geser dari posisi sekarang (tanpa lompatan).
                this.dragging = true;
                this.dragStartX = rest[0].x;
                this.dragStartY = rest[0].y;
                this.startPosX = this.posX;
                this.startPosY = this.posY;
            } else {
                this.dragging = false;
            }
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
            this.pointers = {};
            this.dragging = false;
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

        // Gestur 2 jari: pointer aktif (id -> {x, y}) untuk cubit di layar sentuh.
        pointers: {},
        pinchStartDist: 0,
        pinchStartScale: 1,
        pinchLastMidX: 0,
        pinchLastMidY: 0,

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

        // Ubah zoom dengan titik (cx, cy) di dalam viewport tetap di tempatnya
        // (dipakai gestur cubit / Ctrl + scroll; slider tetap lewat applyZoom).
        zoomTo(newScale, cx, cy) {
            newScale = Math.min(this.maxScale, Math.max(this.minScale, newScale));
            const ratio = newScale / this.scale;

            this.posX = cx - (cx - this.posX) * ratio;
            this.posY = cy - (cy - this.posY) * ratio;
            this.scale = newScale;
            this.zoomPercent = this.maxScale > this.minScale
                ? ((newScale - this.minScale) / (this.maxScale - this.minScale)) * 100
                : 0;
            this.clampPos();
        },

        // Touchpad: geser 2 jari = wheel biasa (geser foto), cubit 2 jari = wheel + Ctrl (zoom).
        onWheel(e) {
            if (this.natW === 0) return;

            if (e.ctrlKey) {
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.zoomTo(this.scale * Math.exp(-e.deltaY * 0.01), e.clientX - rect.left, e.clientY - rect.top);
                return;
            }

            this.posX -= e.deltaX;
            this.posY -= e.deltaY;
            this.clampPos();
        },

        startDrag(e) {
            this.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };

            const points = Object.values(this.pointers);

            if (points.length >= 2) {
                // Dua jari menyentuh layar: mulai cubit.
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.dragging = false;
                this.pinchStartDist = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);
                this.pinchStartScale = this.scale;
                this.pinchLastMidX = (points[0].x + points[1].x) / 2 - rect.left;
                this.pinchLastMidY = (points[0].y + points[1].y) / 2 - rect.top;
                return;
            }

            this.dragging = true;
            this.dragStartX = e.clientX;
            this.dragStartY = e.clientY;
            this.startPosX = this.posX;
            this.startPosY = this.posY;
        },

        onDrag(e) {
            if (!(e.pointerId in this.pointers)) return;
            this.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };

            const points = Object.values(this.pointers);

            if (points.length >= 2) {
                const rect = this.$refs.viewport.getBoundingClientRect();
                const midX = (points[0].x + points[1].x) / 2 - rect.left;
                const midY = (points[0].y + points[1].y) / 2 - rect.top;
                const dist = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);

                // Geser mengikuti titik tengah kedua jari, lalu zoom sesuai jarak antar jari.
                this.posX += midX - this.pinchLastMidX;
                this.posY += midY - this.pinchLastMidY;
                this.pinchLastMidX = midX;
                this.pinchLastMidY = midY;

                if (this.pinchStartDist > 0) {
                    this.zoomTo(this.pinchStartScale * (dist / this.pinchStartDist), midX, midY);
                } else {
                    this.clampPos();
                }
                return;
            }

            if (!this.dragging) return;
            this.posX = this.startPosX + (e.clientX - this.dragStartX);
            this.posY = this.startPosY + (e.clientY - this.dragStartY);
            this.clampPos();
        },

        endDrag(e) {
            if (e && e.pointerId !== undefined) {
                delete this.pointers[e.pointerId];
            } else {
                this.pointers = {};
            }

            const rest = Object.values(this.pointers);

            if (rest.length === 1) {
                // Tinggal satu jari: lanjut geser dari posisi sekarang (tanpa lompatan).
                this.dragging = true;
                this.dragStartX = rest[0].x;
                this.dragStartY = rest[0].y;
                this.startPosX = this.posX;
                this.startPosY = this.posY;
            } else {
                this.dragging = false;
            }
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
            this.pointers = {};
            this.dragging = false;
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

        // Gestur 2 jari: pointer aktif (id -> {x, y}) untuk cubit di layar sentuh.
        pointers: {},
        pinchStartDist: 0,
        pinchStartScale: 1,
        pinchLastMidX: 0,
        pinchLastMidY: 0,

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

        // Ubah zoom dengan titik (cx, cy) di dalam viewport tetap di tempatnya
        // (dipakai gestur cubit / Ctrl + scroll; slider tetap lewat applyZoom).
        zoomTo(newScale, cx, cy) {
            newScale = Math.min(this.maxScale, Math.max(this.minScale, newScale));
            const ratio = newScale / this.scale;

            this.posX = cx - (cx - this.posX) * ratio;
            this.posY = cy - (cy - this.posY) * ratio;
            this.scale = newScale;
            this.zoomPercent = this.maxScale > this.minScale
                ? ((newScale - this.minScale) / (this.maxScale - this.minScale)) * 100
                : 0;
            this.clampPos();
        },

        // Touchpad: geser 2 jari = wheel biasa (geser foto), cubit 2 jari = wheel + Ctrl (zoom).
        onWheel(e) {
            if (this.natW === 0) return;

            if (e.ctrlKey) {
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.zoomTo(this.scale * Math.exp(-e.deltaY * 0.01), e.clientX - rect.left, e.clientY - rect.top);
                return;
            }

            this.posX -= e.deltaX;
            this.posY -= e.deltaY;
            this.clampPos();
        },

        startDrag(e) {
            this.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };

            const points = Object.values(this.pointers);

            if (points.length >= 2) {
                // Dua jari menyentuh layar: mulai cubit.
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.dragging = false;
                this.pinchStartDist = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);
                this.pinchStartScale = this.scale;
                this.pinchLastMidX = (points[0].x + points[1].x) / 2 - rect.left;
                this.pinchLastMidY = (points[0].y + points[1].y) / 2 - rect.top;
                return;
            }

            this.dragging = true;
            this.dragStartX = e.clientX;
            this.dragStartY = e.clientY;
            this.startPosX = this.posX;
            this.startPosY = this.posY;
        },

        onDrag(e) {
            if (!(e.pointerId in this.pointers)) return;
            this.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };

            const points = Object.values(this.pointers);

            if (points.length >= 2) {
                const rect = this.$refs.viewport.getBoundingClientRect();
                const midX = (points[0].x + points[1].x) / 2 - rect.left;
                const midY = (points[0].y + points[1].y) / 2 - rect.top;
                const dist = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);

                // Geser mengikuti titik tengah kedua jari, lalu zoom sesuai jarak antar jari.
                this.posX += midX - this.pinchLastMidX;
                this.posY += midY - this.pinchLastMidY;
                this.pinchLastMidX = midX;
                this.pinchLastMidY = midY;

                if (this.pinchStartDist > 0) {
                    this.zoomTo(this.pinchStartScale * (dist / this.pinchStartDist), midX, midY);
                } else {
                    this.clampPos();
                }
                return;
            }

            if (!this.dragging) return;
            this.posX = this.startPosX + (e.clientX - this.dragStartX);
            this.posY = this.startPosY + (e.clientY - this.dragStartY);
            this.clampPos();
        },

        endDrag(e) {
            if (e && e.pointerId !== undefined) {
                delete this.pointers[e.pointerId];
            } else {
                this.pointers = {};
            }

            const rest = Object.values(this.pointers);

            if (rest.length === 1) {
                // Tinggal satu jari: lanjut geser dari posisi sekarang (tanpa lompatan).
                this.dragging = true;
                this.dragStartX = rest[0].x;
                this.dragStartY = rest[0].y;
                this.startPosX = this.posX;
                this.startPosY = this.posY;
            } else {
                this.dragging = false;
            }
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
            this.pointers = {};
            this.dragging = false;
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        },
    }));

    // Sama persis strukturnya dengan profilHeroFotoCropper di atas -- cuma
    // rasio (4:5, mengikuti bingkai foto section Sejarah) dan target property yang beda.
    Alpine.data('ourCraftsmenOwnerPhotoCropper', (existingPreviewUrl) => ({
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

        // Gestur 2 jari: pointer aktif (id -> {x, y}) untuk cubit di layar sentuh.
        pointers: {},
        pinchStartDist: 0,
        pinchStartScale: 1,
        pinchLastMidX: 0,
        pinchLastMidY: 0,

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

        // Ubah zoom dengan titik (cx, cy) di dalam viewport tetap di tempatnya
        // (dipakai gestur cubit / Ctrl + scroll; slider tetap lewat applyZoom).
        zoomTo(newScale, cx, cy) {
            newScale = Math.min(this.maxScale, Math.max(this.minScale, newScale));
            const ratio = newScale / this.scale;

            this.posX = cx - (cx - this.posX) * ratio;
            this.posY = cy - (cy - this.posY) * ratio;
            this.scale = newScale;
            this.zoomPercent = this.maxScale > this.minScale
                ? ((newScale - this.minScale) / (this.maxScale - this.minScale)) * 100
                : 0;
            this.clampPos();
        },

        // Touchpad: geser 2 jari = wheel biasa (geser foto), cubit 2 jari = wheel + Ctrl (zoom).
        onWheel(e) {
            if (this.natW === 0) return;

            if (e.ctrlKey) {
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.zoomTo(this.scale * Math.exp(-e.deltaY * 0.01), e.clientX - rect.left, e.clientY - rect.top);
                return;
            }

            this.posX -= e.deltaX;
            this.posY -= e.deltaY;
            this.clampPos();
        },

        startDrag(e) {
            this.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };

            const points = Object.values(this.pointers);

            if (points.length >= 2) {
                // Dua jari menyentuh layar: mulai cubit.
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.dragging = false;
                this.pinchStartDist = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);
                this.pinchStartScale = this.scale;
                this.pinchLastMidX = (points[0].x + points[1].x) / 2 - rect.left;
                this.pinchLastMidY = (points[0].y + points[1].y) / 2 - rect.top;
                return;
            }

            this.dragging = true;
            this.dragStartX = e.clientX;
            this.dragStartY = e.clientY;
            this.startPosX = this.posX;
            this.startPosY = this.posY;
        },

        onDrag(e) {
            if (!(e.pointerId in this.pointers)) return;
            this.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };

            const points = Object.values(this.pointers);

            if (points.length >= 2) {
                const rect = this.$refs.viewport.getBoundingClientRect();
                const midX = (points[0].x + points[1].x) / 2 - rect.left;
                const midY = (points[0].y + points[1].y) / 2 - rect.top;
                const dist = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);

                // Geser mengikuti titik tengah kedua jari, lalu zoom sesuai jarak antar jari.
                this.posX += midX - this.pinchLastMidX;
                this.posY += midY - this.pinchLastMidY;
                this.pinchLastMidX = midX;
                this.pinchLastMidY = midY;

                if (this.pinchStartDist > 0) {
                    this.zoomTo(this.pinchStartScale * (dist / this.pinchStartDist), midX, midY);
                } else {
                    this.clampPos();
                }
                return;
            }

            if (!this.dragging) return;
            this.posX = this.startPosX + (e.clientX - this.dragStartX);
            this.posY = this.startPosY + (e.clientY - this.dragStartY);
            this.clampPos();
        },

        endDrag(e) {
            if (e && e.pointerId !== undefined) {
                delete this.pointers[e.pointerId];
            } else {
                this.pointers = {};
            }

            const rest = Object.values(this.pointers);

            if (rest.length === 1) {
                // Tinggal satu jari: lanjut geser dari posisi sekarang (tanpa lompatan).
                this.dragging = true;
                this.dragStartX = rest[0].x;
                this.dragStartY = rest[0].y;
                this.startPosX = this.posX;
                this.startPosY = this.posY;
            } else {
                this.dragging = false;
            }
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
            this.$wire.set('ourCraftsmenOwnerPhotoCroppedBase64', dataUrl);
            this.closeModal();
        },

        cancelCrop() {
            this.closeModal();
        },

        closeModal() {
            this.open = false;
            this.rawImage = null;
            this.pointers = {};
            this.dragging = false;
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        },
    }));
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

        // Gestur 2 jari: pointer aktif (id -> {x, y}) untuk cubit di layar sentuh.
        pointers: {},
        pinchStartDist: 0,
        pinchStartScale: 1,
        pinchLastMidX: 0,
        pinchLastMidY: 0,

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

        // Ubah zoom dengan titik (cx, cy) di dalam viewport tetap di tempatnya
        // (dipakai gestur cubit / Ctrl + scroll; slider tetap lewat applyZoom).
        zoomTo(newScale, cx, cy) {
            newScale = Math.min(this.maxScale, Math.max(this.minScale, newScale));
            const ratio = newScale / this.scale;

            this.posX = cx - (cx - this.posX) * ratio;
            this.posY = cy - (cy - this.posY) * ratio;
            this.scale = newScale;
            this.zoomPercent = this.maxScale > this.minScale
                ? ((newScale - this.minScale) / (this.maxScale - this.minScale)) * 100
                : 0;
            this.clampPos();
        },

        // Touchpad: geser 2 jari = wheel biasa (geser foto), cubit 2 jari = wheel + Ctrl (zoom).
        onWheel(e) {
            if (this.natW === 0) return;

            if (e.ctrlKey) {
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.zoomTo(this.scale * Math.exp(-e.deltaY * 0.01), e.clientX - rect.left, e.clientY - rect.top);
                return;
            }

            this.posX -= e.deltaX;
            this.posY -= e.deltaY;
            this.clampPos();
        },

        startDrag(e) {
            this.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };

            const points = Object.values(this.pointers);

            if (points.length >= 2) {
                // Dua jari menyentuh layar: mulai cubit.
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.dragging = false;
                this.pinchStartDist = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);
                this.pinchStartScale = this.scale;
                this.pinchLastMidX = (points[0].x + points[1].x) / 2 - rect.left;
                this.pinchLastMidY = (points[0].y + points[1].y) / 2 - rect.top;
                return;
            }

            this.dragging = true;
            this.dragStartX = e.clientX;
            this.dragStartY = e.clientY;
            this.startPosX = this.posX;
            this.startPosY = this.posY;
        },

        onDrag(e) {
            if (!(e.pointerId in this.pointers)) return;
            this.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };

            const points = Object.values(this.pointers);

            if (points.length >= 2) {
                const rect = this.$refs.viewport.getBoundingClientRect();
                const midX = (points[0].x + points[1].x) / 2 - rect.left;
                const midY = (points[0].y + points[1].y) / 2 - rect.top;
                const dist = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);

                // Geser mengikuti titik tengah kedua jari, lalu zoom sesuai jarak antar jari.
                this.posX += midX - this.pinchLastMidX;
                this.posY += midY - this.pinchLastMidY;
                this.pinchLastMidX = midX;
                this.pinchLastMidY = midY;

                if (this.pinchStartDist > 0) {
                    this.zoomTo(this.pinchStartScale * (dist / this.pinchStartDist), midX, midY);
                } else {
                    this.clampPos();
                }
                return;
            }

            if (!this.dragging) return;
            this.posX = this.startPosX + (e.clientX - this.dragStartX);
            this.posY = this.startPosY + (e.clientY - this.dragStartY);
            this.clampPos();
        },

        endDrag(e) {
            if (e && e.pointerId !== undefined) {
                delete this.pointers[e.pointerId];
            } else {
                this.pointers = {};
            }

            const rest = Object.values(this.pointers);

            if (rest.length === 1) {
                // Tinggal satu jari: lanjut geser dari posisi sekarang (tanpa lompatan).
                this.dragging = true;
                this.dragStartX = rest[0].x;
                this.dragStartY = rest[0].y;
                this.startPosX = this.posX;
                this.startPosY = this.posY;
            } else {
                this.dragging = false;
            }
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
            this.pointers = {};
            this.dragging = false;
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

        // Gestur 2 jari: pointer aktif (id -> {x, y}) untuk cubit di layar sentuh.
        pointers: {},
        pinchStartDist: 0,
        pinchStartScale: 1,
        pinchLastMidX: 0,
        pinchLastMidY: 0,

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

        // Ubah zoom dengan titik (cx, cy) di dalam viewport tetap di tempatnya
        // (dipakai gestur cubit / Ctrl + scroll; slider tetap lewat applyZoom).
        zoomTo(newScale, cx, cy) {
            newScale = Math.min(this.maxScale, Math.max(this.minScale, newScale));
            const ratio = newScale / this.scale;

            this.posX = cx - (cx - this.posX) * ratio;
            this.posY = cy - (cy - this.posY) * ratio;
            this.scale = newScale;
            this.zoomPercent = this.maxScale > this.minScale
                ? ((newScale - this.minScale) / (this.maxScale - this.minScale)) * 100
                : 0;
            this.clampPos();
        },

        // Touchpad: geser 2 jari = wheel biasa (geser foto), cubit 2 jari = wheel + Ctrl (zoom).
        onWheel(e) {
            if (this.natW === 0) return;

            if (e.ctrlKey) {
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.zoomTo(this.scale * Math.exp(-e.deltaY * 0.01), e.clientX - rect.left, e.clientY - rect.top);
                return;
            }

            this.posX -= e.deltaX;
            this.posY -= e.deltaY;
            this.clampPos();
        },

        startDrag(e) {
            this.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };

            const points = Object.values(this.pointers);

            if (points.length >= 2) {
                // Dua jari menyentuh layar: mulai cubit.
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.dragging = false;
                this.pinchStartDist = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);
                this.pinchStartScale = this.scale;
                this.pinchLastMidX = (points[0].x + points[1].x) / 2 - rect.left;
                this.pinchLastMidY = (points[0].y + points[1].y) / 2 - rect.top;
                return;
            }

            this.dragging = true;
            this.dragStartX = e.clientX;
            this.dragStartY = e.clientY;
            this.startPosX = this.posX;
            this.startPosY = this.posY;
        },

        onDrag(e) {
            if (!(e.pointerId in this.pointers)) return;
            this.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };

            const points = Object.values(this.pointers);

            if (points.length >= 2) {
                const rect = this.$refs.viewport.getBoundingClientRect();
                const midX = (points[0].x + points[1].x) / 2 - rect.left;
                const midY = (points[0].y + points[1].y) / 2 - rect.top;
                const dist = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);

                // Geser mengikuti titik tengah kedua jari, lalu zoom sesuai jarak antar jari.
                this.posX += midX - this.pinchLastMidX;
                this.posY += midY - this.pinchLastMidY;
                this.pinchLastMidX = midX;
                this.pinchLastMidY = midY;

                if (this.pinchStartDist > 0) {
                    this.zoomTo(this.pinchStartScale * (dist / this.pinchStartDist), midX, midY);
                } else {
                    this.clampPos();
                }
                return;
            }

            if (!this.dragging) return;
            this.posX = this.startPosX + (e.clientX - this.dragStartX);
            this.posY = this.startPosY + (e.clientY - this.dragStartY);
            this.clampPos();
        },

        endDrag(e) {
            if (e && e.pointerId !== undefined) {
                delete this.pointers[e.pointerId];
            } else {
                this.pointers = {};
            }

            const rest = Object.values(this.pointers);

            if (rest.length === 1) {
                // Tinggal satu jari: lanjut geser dari posisi sekarang (tanpa lompatan).
                this.dragging = true;
                this.dragStartX = rest[0].x;
                this.dragStartY = rest[0].y;
                this.startPosX = this.posX;
                this.startPosY = this.posY;
            } else {
                this.dragging = false;
            }
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
            this.pointers = {};
            this.dragging = false;
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        },
    }));

    // Cropper foto kartu section Nilai Kami: satu modal dipakai bersama 4 kartu
    // (indeks kartu dikirim lewat onFileChange). Strukturnya sama dengan
    // sejarahFotoCropper -- bedanya rasio 4:3 (mengikuti bingkai foto kartu),
    // hasilnya dikirim ke nilaiKamiFotoCropped.{indeks}, dan gestur lebih lengkap:
    // geser 1 jari/mouse, geser 2 jari di touchpad (wheel), cubit 2 jari di layar
    // sentuh / touchpad (Ctrl + wheel), serta slider zoom.
    Alpine.data('nilaiKamiFotoCropper', (existingPreviewUrls) => ({
        open: false,
        rawImage: null,
        previews: [...(existingPreviewUrls || [])],
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

        // Pointer aktif (id -> {x, y}) untuk deteksi cubit 2 jari.
        pointers: {},
        pinchStartDist: 0,
        pinchStartScale: 1,
        pinchLastMidX: 0,
        pinchLastMidY: 0,

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

        // Ubah zoom dengan titik (cx, cy) di dalam viewport tetap di tempatnya.
        zoomTo(newScale, cx, cy) {
            newScale = Math.min(this.maxScale, Math.max(this.minScale, newScale));
            const ratio = newScale / this.scale;

            this.posX = cx - (cx - this.posX) * ratio;
            this.posY = cy - (cy - this.posY) * ratio;
            this.scale = newScale;
            this.zoomPercent = this.maxScale > this.minScale
                ? ((newScale - this.minScale) / (this.maxScale - this.minScale)) * 100
                : 0;
            this.clampPos();
        },

        applyZoom() {
            const target = this.minScale + (this.maxScale - this.minScale) * (this.zoomPercent / 100);
            this.zoomTo(target, this.viewW / 2, this.viewH / 2);
        },

        // Touchpad: geser 2 jari = wheel biasa (geser foto), cubit 2 jari = wheel + Ctrl (zoom).
        onWheel(e) {
            if (this.natW === 0) return;

            if (e.ctrlKey) {
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.zoomTo(this.scale * Math.exp(-e.deltaY * 0.01), e.clientX - rect.left, e.clientY - rect.top);
                return;
            }

            this.posX -= e.deltaX;
            this.posY -= e.deltaY;
            this.clampPos();
        },

        startDrag(e) {
            this.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };

            const points = Object.values(this.pointers);

            if (points.length >= 2) {
                // Dua jari menyentuh: mulai cubit.
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.dragging = false;
                this.pinchStartDist = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);
                this.pinchStartScale = this.scale;
                this.pinchLastMidX = (points[0].x + points[1].x) / 2 - rect.left;
                this.pinchLastMidY = (points[0].y + points[1].y) / 2 - rect.top;
                return;
            }

            this.dragging = true;
            this.dragStartX = e.clientX;
            this.dragStartY = e.clientY;
            this.startPosX = this.posX;
            this.startPosY = this.posY;
        },

        onDrag(e) {
            if (!(e.pointerId in this.pointers)) return;
            this.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };

            const points = Object.values(this.pointers);

            if (points.length >= 2) {
                const rect = this.$refs.viewport.getBoundingClientRect();
                const midX = (points[0].x + points[1].x) / 2 - rect.left;
                const midY = (points[0].y + points[1].y) / 2 - rect.top;
                const dist = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);

                // Geser mengikuti titik tengah kedua jari, lalu zoom sesuai jarak antar jari.
                this.posX += midX - this.pinchLastMidX;
                this.posY += midY - this.pinchLastMidY;
                this.pinchLastMidX = midX;
                this.pinchLastMidY = midY;

                if (this.pinchStartDist > 0) {
                    this.zoomTo(this.pinchStartScale * (dist / this.pinchStartDist), midX, midY);
                } else {
                    this.clampPos();
                }
                return;
            }

            if (!this.dragging) return;
            this.posX = this.startPosX + (e.clientX - this.dragStartX);
            this.posY = this.startPosY + (e.clientY - this.dragStartY);
            this.clampPos();
        },

        endDrag(e) {
            if (e && e.pointerId !== undefined) {
                delete this.pointers[e.pointerId];
            } else {
                this.pointers = {};
            }

            const rest = Object.values(this.pointers);

            if (rest.length === 1) {
                // Tinggal satu jari: lanjut geser dari posisi sekarang (tanpa lompatan).
                this.dragging = true;
                this.dragStartX = rest[0].x;
                this.dragStartY = rest[0].y;
                this.startPosX = this.posX;
                this.startPosY = this.posY;
            } else {
                this.dragging = false;
            }
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

            // Ganti seluruh array (bukan cuma satu indeks) supaya pratinjau pasti ter-update.
            const next = [...this.previews];
            next[this.activeIndex] = dataUrl;
            this.previews = next;

            this.$wire.set('nilaiKamiFotoCropped.' + this.activeIndex, dataUrl);
            this.closeModal();
        },

        cancelCrop() {
            this.closeModal();
        },

        closeModal() {
            this.open = false;
            this.rawImage = null;
            this.pointers = {};
            this.dragging = false;
            if (this.activeInput) this.activeInput.value = '';
        },
    }));
</script>
@endscript