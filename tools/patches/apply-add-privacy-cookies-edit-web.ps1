$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$admin = ".\resources\views\pages\admin\edit-web.blade.php"
$privacy = ".\resources\views\pages\frontend\privacy-policy.blade.php"
$cookies = ".\resources\views\pages\frontend\cookies.blade.php"

foreach ($file in @($admin, $privacy, $cookies)) {
    if (-not (Test-Path $file)) {
        throw "File target tidak ditemukan: $file"
    }
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = ".backup-privacy-cookies-edit-web-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "====================================================================" -ForegroundColor Yellow
Write-Host " Edit Web - Privacy Policy + Cookies (2 Rectangle Terpisah)" -ForegroundColor Yellow
Write-Host "====================================================================" -ForegroundColor Yellow

Step "[1/6] Backup file yang akan disentuh ..."
foreach ($file in @($admin, $privacy, $cookies)) {
    $relative = $file.TrimStart('.', '\')
    $dest = Join-Path $backupDir $relative
    New-Item -ItemType Directory -Path (Split-Path $dest -Parent) -Force | Out-Null
    Copy-Item $file $dest -Force
}
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/6] Tambahkan 2 rectangle + section ke Admin > Edit Web ..."

$patchPhp = @'
<?php

$root = getcwd();
$path = $root.'/resources/views/pages/admin/edit-web.blade.php';
$admin = file_get_contents($path);

if ($admin === false) {
    throw new RuntimeException('Gagal membaca edit-web.blade.php');
}

$nl = str_contains($admin, "\r\n") ? "\r\n" : "\n";

function nln(string $text, string $nl): string {
    return str_replace("\n", $nl, str_replace("\r\n", "\n", $text));
}

function insert_before(string $text, string $needle, string $insert, string $label): string {
    if (str_contains($text, trim($insert))) {
        echo "  - {$label}: sudah ada\n";
        return $text;
    }

    $pos = strpos($text, $needle);
    if ($pos === false) {
        throw new RuntimeException("Anchor tidak ditemukan: {$label}");
    }

    echo "  - {$label}: ditambahkan\n";
    return substr($text, 0, $pos).$insert.substr($text, $pos);
}

function add_line_before(string $text, string $needle, string $line, string $label): string {
    if (str_contains($text, trim($line))) {
        echo "  - {$label}: sudah ada\n";
        return $text;
    }

    $pos = strpos($text, $needle);
    if ($pos === false) {
        throw new RuntimeException("Anchor tidak ditemukan: {$label}");
    }

    echo "  - {$label}: ditambahkan\n";
    return substr($text, 0, $pos).$line.substr($text, $pos);
}

/* ============================================================
   SECTION LISTS
============================================================ */
$sectionLists = <<<'BLADE'
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

BLADE;

$admin = insert_before(
    $admin,
    '    /** Terms of Service: Hero + 9 bagian ketentuan + Kontak. */',
    nln($sectionLists, $nl),
    'daftar section Privacy + Cookies'
);

/* ============================================================
   STATE / DEFAULTS
============================================================ */
$state = <<<'BLADE'
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

BLADE;

$admin = insert_before(
    $admin,
    '    // ================= TERMS OF SERVICE =================',
    nln($state, $nl),
    'state/default Privacy + Cookies'
);

/* ============================================================
   GROUP CARDS - DUA RECTANGLE TERPISAH
============================================================ */
if (!str_contains($admin, "'key' => 'privacy-policy'")) {
    $needle = "        [".$nl."            'key' => 'terms-of-service',";
    $pos = strpos($admin, $needle);

    if ($pos === false) {
        throw new RuntimeException('Card Terms of Service tidak ditemukan untuk anchor rectangle Privacy/Cookies.');
    }

    $cards = <<<'BLADE'
        [
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

BLADE;

    $admin = substr($admin, 0, $pos).nln($cards, $nl).substr($admin, $pos);
    echo "  - 2 rectangle terpisah: ditambahkan\n";
} else {
    echo "  - rectangle Privacy/Cookies: sudah ada\n";
}

/* ============================================================
   GROUP NAV
============================================================ */
$admin = add_line_before(
    $admin,
    "            'terms-of-service' => \$this->termsSections[0]['key'],",
    nln("            'privacy-policy' => \$this->privacySections[0]['key'],\n            'cookies' => \$this->cookiesSections[0]['key'],\n", $nl),
    'selectGroup Privacy + Cookies'
);

$admin = add_line_before(
    $admin,
    "                'terms-of-service' => \$termsSections,",
    nln("                'privacy-policy' => \$privacySections,\n                'cookies' => \$cookiesSections,\n", $nl),
    'activeGroupSections Privacy + Cookies'
);

$admin = add_line_before(
    $admin,
    "                'terms-of-service' => 'Terms of Service',",
    nln("                'privacy-policy' => 'Privacy Policy',\n                'cookies' => 'Cookies',\n", $nl),
    'activeGroupLabel Privacy + Cookies'
);

/* ============================================================
   MOUNT
============================================================ */
$mount = <<<'BLADE'
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

BLADE;

$admin = insert_before(
    $admin,
    "        \$termsHeroData = HomeSection::dataFor('terms-hero', \$this->termsHeroDefaults());",
    nln($mount, $nl),
    'mount Privacy + Cookies'
);

/* ============================================================
   SAVE METHODS
============================================================ */
$saveMethods = <<<'BLADE'
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

BLADE;

$admin = insert_before(
    $admin,
    '    public function saveTermsHero(): void',
    nln($saveMethods, $nl),
    'save methods Privacy + Cookies'
);

/* ============================================================
   UI FORMS
============================================================ */
$ui = <<<'BLADE'
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

BLADE;

$admin = insert_before(
    $admin,
    "            @elseif (\$activeSection === 'terms-hero')",
    nln($ui, $nl),
    'form UI Privacy + Cookies'
);

if (file_put_contents($path, $admin) === false) {
    throw new RuntimeException('Gagal menulis edit-web.blade.php');
}

echo "ADMIN_PRIVACY_COOKIES_OK\n";
'@

$tmp = Join-Path $env:TEMP "patch-privacy-cookies-edit-web-$stamp.php"
[System.IO.File]::WriteAllText($tmp, $patchPhp, $utf8NoBom)

php $tmp
$exitCode = $LASTEXITCODE
Remove-Item $tmp -Force -ErrorAction SilentlyContinue

if ($exitCode -ne 0) {
    throw "Patch Admin Edit Web gagal. Backup: $backupDir"
}

Step "[3/6] Sinkronkan frontend Privacy Policy ..."

$privacyContent = @'
<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy &mdash; {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-white font-sans text-[#2A211B] antialiased">
    @include('partials.frontend.navbar')

    @php
        $legalSetting = \App\Models\Setting::current();
        $legalContactEmail = $legalSetting->email ?: 'info@karyaideedi.com';
        $legalGmailLink = $legalSetting->gmailComposeUrl();
        $legalContactAddress = $legalSetting->alamat ?: 'Alamat toko akan diperbarui melalui halaman Pengaturan Admin.';
        $legalWaLink = $legalSetting->whatsappDigits() ? 'https://wa.me/'.$legalSetting->whatsappDigits() : null;

        $privacyHero = \App\Models\HomeSection::dataFor('privacy-hero', [
            'eyebrow' => 'Privacy Policy',
            'heading' => 'Kebijakan Privasi',
            'description' => 'Privasi Anda penting bagi kami. Halaman ini menjelaskan secara transparan informasi apa yang kami kumpulkan, mengapa kami membutuhkannya, dan bagaimana kami menjaganya.',
            'updated_date' => '2026-08-01',
        ]);

        try {
            $legalUpdatedAt = \Illuminate\Support\Facades\Date::parse($privacyHero['updated_date'] ?? '2026-08-01')->translatedFormat('d F Y');
        } catch (\Throwable $e) {
            $legalUpdatedAt = \Illuminate\Support\Facades\Date::parse('2026-08-01')->translatedFormat('d F Y');
        }

        $privacyDefaults = [
            'information' => [
                'id' => 'informasi-dikumpulkan',
                'icon' => 'fa-database',
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
                'id' => 'penggunaan-informasi',
                'icon' => 'fa-gears',
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
                'id' => 'penyimpanan-keamanan',
                'icon' => 'fa-shield-halved',
                'title' => '3. Penyimpanan & Keamanan Data',
                'intro' => 'Data pesanan disimpan pada sistem basis data yang hanya dapat diakses oleh admin toko melalui panel administrasi yang dilindungi kata sandi. Kami menerapkan langkah keamanan yang wajar untuk mencegah akses, perubahan, atau pengungkapan data tanpa izin.',
                'bullets' => [],
                'outro' => 'Meski begitu, tidak ada metode transmisi data melalui internet yang 100% aman sepenuhnya. Kami senantiasa berupaya menjaga data Anda dengan sebaik mungkin.',
            ],
            'sharing' => [
                'id' => 'berbagi-data',
                'icon' => 'fa-people-arrows',
                'title' => '4. Berbagi Informasi dengan Pihak Ketiga',
                'intro' => 'Kami hanya membagikan informasi yang diperlukan kepada:',
                'bullets' => [
                    'Layanan WhatsApp - sebagai kanal komunikasi utama yang Anda pilih sendiri untuk bernegosiasi dan konfirmasi pesanan.',
                    'Mitra pengiriman/ekspedisi - sebatas nama & alamat, semata-mata untuk keperluan pengantaran barang pesanan Anda.',
                ],
                'outro' => 'Kami tidak membagikan data Anda kepada pengiklan atau pihak ketiga lain di luar kebutuhan operasional di atas.',
            ],
            'rights' => [
                'id' => 'hak-anda',
                'icon' => 'fa-user-shield',
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
                'id' => 'perubahan-kebijakan',
                'icon' => 'fa-rotate',
                'title' => '6. Perubahan Kebijakan Ini',
                'intro' => 'Kebijakan Privasi ini dapat kami perbarui sewaktu-waktu mengikuti perkembangan layanan. Tanggal pembaruan terakhir selalu tercantum di bagian atas halaman ini. Kami menganjurkan Anda meninjau halaman ini secara berkala.',
                'bullets' => [],
                'outro' => '',
            ],
        ];

        $privacySections = [];
        foreach ($privacyDefaults as $key => $default) {
            $saved = \App\Models\HomeSection::dataFor('privacy-'.$key, [
                'title' => $default['title'],
                'intro' => $default['intro'],
                'bullets' => $default['bullets'],
                'outro' => $default['outro'],
            ]);

            $privacySections[] = [
                'id' => $default['id'],
                'icon' => $default['icon'],
                'title' => (string) ($saved['title'] ?? $default['title']),
                'intro' => (string) ($saved['intro'] ?? $default['intro']),
                'bullets' => array_values(is_array($saved['bullets'] ?? null) ? $saved['bullets'] : $default['bullets']),
                'outro' => (string) ($saved['outro'] ?? $default['outro']),
            ];
        }

        $privacyContact = \App\Models\HomeSection::dataFor('privacy-contact', [
            'heading' => 'Ada Pertanyaan Soal Privasi Anda?',
            'description' => 'Hubungi kami kapan saja - kami siap menjelaskan bagaimana data Anda dikelola.',
            'button_label' => 'Hubungi via WhatsApp',
        ]);
    @endphp

    <section class="relative overflow-hidden bg-[#1A1A1A]">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-admin-accent/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-16 bottom-0 h-56 w-56 rounded-full bg-admin-accent/10 blur-3xl"></div>

        <div class="relative mx-auto max-w-4xl px-6 py-16 text-center sm:px-8 lg:py-20">
            <div class="mx-auto flex w-fit items-center gap-3 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-admin-accent-strong">
                <i class="fa-solid fa-lock text-[10px]"></i>
                {{ $privacyHero['eyebrow'] }}
            </div>
            <h1 class="mt-5 font-display text-4xl font-semibold leading-tight text-white sm:text-5xl">
                {{ $privacyHero['heading'] }}
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-sm leading-relaxed text-white/60 sm:text-base">
                {{ $privacyHero['description'] }}
            </p>
            <p class="mt-6 text-xs uppercase tracking-[0.15em] text-white/40">
                Terakhir diperbarui: {{ $legalUpdatedAt }}
            </p>
        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-6xl px-6 py-14 sm:px-8 lg:py-20">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-[260px_1fr]">
                <aside class="hidden lg:block">
                    <div class="sticky top-28 rounded-2xl border border-[#1A1A1A]/10 bg-admin-cream/40 p-6">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.15em] text-[#6B6E76]">
                            Daftar Isi
                        </p>
                        <ul class="mt-4 space-y-3">
                            @foreach ($privacySections as $section)
                                <li>
                                    <a href="#{{ $section['id'] }}" class="group flex items-start gap-2.5 text-sm text-[#6B6E76] transition-colors duration-300 hover:text-admin-accent">
                                        <i class="fa-solid {{ $section['icon'] }} mt-0.5 w-4 text-admin-accent/70 group-hover:text-admin-accent"></i>
                                        <span>{{ $section['title'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </aside>

                <div class="space-y-12">
                    @foreach ($privacySections as $section)
                        <div id="{{ $section['id'] }}" class="scroll-mt-28">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-admin-cream text-admin-accent">
                                    <i class="fa-solid {{ $section['icon'] }}"></i>
                                </span>
                                <h2 class="font-display text-xl font-semibold text-[#1A1A1A] sm:text-2xl">
                                    {{ $section['title'] }}
                                </h2>
                            </div>

                            <div class="mt-4 space-y-3 border-l-2 border-admin-accent/20 pl-[3.25rem] sm:pl-[3.25rem]">
                                @if ($section['intro'] !== '')
                                    <p class="text-sm leading-relaxed text-[#4A423B] sm:text-[15px]">
                                        {{ $section['intro'] }}
                                    </p>
                                @endif

                                @if (! empty($section['bullets']))
                                    <ul class="ml-1 list-disc space-y-2 pl-4 text-sm leading-relaxed text-[#4A423B] sm:text-[15px]">
                                        @foreach ($section['bullets'] as $point)
                                            <li>{{ $point }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if ($section['outro'] !== '')
                                    <p class="text-sm leading-relaxed text-[#4A423B] sm:text-[15px]">
                                        {{ $section['outro'] }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <div class="scroll-mt-28 rounded-2xl bg-[#1A1A1A] p-8 text-center sm:p-10">
                        <i class="fa-solid fa-envelope-open-text text-2xl text-admin-accent-strong"></i>
                        <h3 class="mt-3 font-display text-xl font-semibold text-white">
                            {{ $privacyContact['heading'] }}
                        </h3>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-white/60">
                            {{ $privacyContact['description'] }}
                        </p>

                        <div class="mt-5 flex flex-col items-center justify-center gap-3 text-sm text-white/70 sm:flex-row sm:gap-6">
                            @if ($legalGmailLink)
                                <a href="{{ $legalGmailLink }}" target="_blank" rel="noopener" class="flex items-center gap-2 transition-colors duration-300 hover:text-white">
                                    <i class="fa-solid fa-envelope text-admin-accent-strong"></i>
                                    {{ $legalContactEmail }}
                                </a>
                            @else
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid fa-envelope text-admin-accent-strong"></i>
                                    {{ $legalContactEmail }}
                                </span>
                            @endif

                            <span class="hidden h-4 w-px bg-white/15 sm:inline-block"></span>
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-location-dot text-admin-accent-strong"></i>
                                {{ $legalContactAddress }}
                            </span>
                        </div>

                        @if ($legalWaLink)
                            <a href="{{ $legalWaLink }}" target="_blank" rel="noopener" class="mt-6 inline-flex items-center gap-2 rounded-full bg-admin-accent px-6 py-2.5 text-sm font-semibold text-white transition-colors duration-300 hover:bg-admin-accent-strong">
                                <i class="fa-brands fa-whatsapp"></i>
                                {{ $privacyContact['button_label'] }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('partials.frontend.footer')
</body>
</html>
'@

[System.IO.File]::WriteAllText((Resolve-Path $privacy), $privacyContent, $utf8NoBom)
Write-Host "  - privacy-policy.blade.php terhubung ke HomeSection." -ForegroundColor Green

Step "[4/6] Sinkronkan frontend Cookies ..."

$cookiesContent = @'
<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cookies &mdash; {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-white font-sans text-[#2A211B] antialiased">
    @include('partials.frontend.navbar')

    @php
        $legalSetting = \App\Models\Setting::current();
        $legalContactEmail = $legalSetting->email ?: 'info@karyaideedi.com';
        $legalGmailLink = $legalSetting->gmailComposeUrl();
        $legalWaLink = $legalSetting->whatsappDigits() ? 'https://wa.me/'.$legalSetting->whatsappDigits() : null;

        $cookiesHero = \App\Models\HomeSection::dataFor('cookies-hero', [
            'eyebrow' => 'Cookies',
            'heading' => 'Kebijakan Cookie',
            'description' => 'Kami menjaga situs ini seringan dan sesederhana mungkin. Berikut rincian lengkap dan jujur soal data apa saja yang tersimpan di perangkat Anda saat berkunjung ke '.$legalSetting->site_name.'.',
            'updated_date' => '2026-08-01',
        ]);

        try {
            $legalUpdatedAt = \Illuminate\Support\Facades\Date::parse($cookiesHero['updated_date'] ?? '2026-08-01')->translatedFormat('d F Y');
        } catch (\Throwable $e) {
            $legalUpdatedAt = \Illuminate\Support\Facades\Date::parse('2026-08-01')->translatedFormat('d F Y');
        }

        $cookiesSummary = \App\Models\HomeSection::dataFor('cookies-summary', [
            'label' => 'Singkatnya:',
            'text' => 'kami tidak memasang cookie pelacak iklan atau analitik pihak ketiga. Yang kami simpan di perangkat Anda hanya hal-hal yang membuat pengalaman belanja lebih nyaman - seperti daftar favorit dan keranjang Anda sendiri.',
        ]);

        $cookieCategoryDefaults = [
            ['icon' => 'fa-gear', 'status' => 'always', 'name' => 'Esensial', 'desc' => 'Diperlukan agar situs berfungsi dengan baik - misalnya menjaga sesi login admin tetap aktif dan keamanan formulir. Tidak dapat dinonaktifkan karena situs tidak akan berjalan normal tanpanya.'],
            ['icon' => 'fa-heart', 'status' => 'local', 'name' => 'Preferensi Lokal', 'desc' => 'Favorit dan Keranjang belanja Anda disimpan di localStorage browser perangkat ini (bukan cookie, dan bukan di server kami) agar tetap tersimpan saat Anda kembali berkunjung - tanpa perlu membuat akun.'],
            ['icon' => 'fa-link', 'status' => 'local', 'name' => 'Tautan Lacak Pesanan', 'desc' => 'Setelah pesanan dibuat, token pelacakan pesanan Anda didaftarkan pada perangkat ini supaya halaman Lacak Pesanan bisa langsung menampilkan riwayat pesanan Anda tanpa harus login.'],
            ['icon' => 'fa-chart-line', 'status' => 'none', 'name' => 'Analitik Pihak Ketiga', 'desc' => 'Kami tidak memasang Google Analytics, Meta Pixel, atau alat pelacak iklan pihak ketiga mana pun di situs ini.'],
            ['icon' => 'fa-bullhorn', 'status' => 'none', 'name' => 'Iklan & Retargeting', 'desc' => 'Kami tidak membagikan data kunjungan Anda kepada jaringan iklan, dan tidak menampilkan iklan pihak ketiga di situs ini.'],
        ];

        $cookiesCategories = \App\Models\HomeSection::dataFor('cookies-categories', [
            'heading' => 'Rincian per Kategori',
            'description' => 'Kategori esensial & preferensi lokal tidak dapat kami hindari karena menjadi dasar fungsi belanja di situs ini. Sisanya, secara sadar tidak kami gunakan.',
            'items' => $cookieCategoryDefaults,
            'status_labels' => [
                'always' => 'Selalu Aktif',
                'local' => 'Tersimpan di Perangkat Anda',
                'none' => 'Tidak Digunakan',
            ],
        ]);

        $cookieTypes = array_values(is_array($cookiesCategories['items'] ?? null) ? $cookiesCategories['items'] : $cookieCategoryDefaults);
        foreach ($cookieTypes as $index => $item) {
            $cookieTypes[$index]['icon'] = $cookieCategoryDefaults[$index]['icon'] ?? 'fa-cookie-bite';
            $cookieTypes[$index]['status'] = $cookieCategoryDefaults[$index]['status'] ?? 'none';
        }

        $statusLabels = is_array($cookiesCategories['status_labels'] ?? null)
            ? $cookiesCategories['status_labels']
            : ['always' => 'Selalu Aktif', 'local' => 'Tersimpan di Perangkat Anda', 'none' => 'Tidak Digunakan'];

        $cookieStatusMap = [
            'always' => ['label' => $statusLabels['always'] ?? 'Selalu Aktif', 'color' => 'bg-admin-accent text-white'],
            'local' => ['label' => $statusLabels['local'] ?? 'Tersimpan di Perangkat Anda', 'color' => 'bg-admin-cream text-admin-accent'],
            'none' => ['label' => $statusLabels['none'] ?? 'Tidak Digunakan', 'color' => 'bg-[#F2F2F2] text-[#8A8A8A]'],
        ];

        $cookiesBrowser = \App\Models\HomeSection::dataFor('cookies-browser', [
            'heading' => 'Mengatur Penyimpanan di Browser Anda',
            'text' => 'Karena favorit dan keranjang tersimpan secara lokal di browser Anda, Anda bisa menghapusnya kapan saja melalui pengaturan "Clear browsing data" / "Hapus data situs" pada browser yang Anda gunakan. Menghapus data ini hanya akan mengosongkan favorit & keranjang di perangkat tersebut - tidak memengaruhi pesanan yang sudah dikonfirmasi.',
        ]);

        $cookiesContact = \App\Models\HomeSection::dataFor('cookies-contact', [
            'heading' => 'Masih Ada yang Ingin Ditanyakan?',
            'description' => 'Kami senang menjelaskan lebih lanjut soal bagaimana situs ini bekerja.',
            'button_label' => 'Hubungi via WhatsApp',
        ]);
    @endphp

    <section class="relative overflow-hidden bg-[#1A1A1A]">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-admin-accent/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-16 bottom-0 h-56 w-56 rounded-full bg-admin-accent/10 blur-3xl"></div>

        <div class="relative mx-auto max-w-4xl px-6 py-16 text-center sm:px-8 lg:py-20">
            <div class="mx-auto flex w-fit items-center gap-3 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-admin-accent-strong">
                <i class="fa-solid fa-cookie-bite text-[10px]"></i>
                {{ $cookiesHero['eyebrow'] }}
            </div>
            <h1 class="mt-5 font-display text-4xl font-semibold leading-tight text-white sm:text-5xl">
                {{ $cookiesHero['heading'] }}
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-sm leading-relaxed text-white/60 sm:text-base">
                {{ $cookiesHero['description'] }}
            </p>
            <p class="mt-6 text-xs uppercase tracking-[0.15em] text-white/40">
                Terakhir diperbarui: {{ $legalUpdatedAt }}
            </p>
        </div>
    </section>

    <section class="bg-admin-cream/40">
        <div class="mx-auto max-w-5xl px-6 py-10 sm:px-8">
            <div class="flex flex-col items-start gap-4 rounded-2xl border border-admin-accent/20 bg-white p-6 sm:flex-row sm:items-center sm:p-7">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-admin-cream text-admin-accent">
                    <i class="fa-solid fa-circle-check text-lg"></i>
                </span>
                <p class="text-sm leading-relaxed text-[#4A423B] sm:text-[15px]">
                    <span class="font-semibold text-[#1A1A1A]">{{ $cookiesSummary['label'] }}</span>
                    {{ $cookiesSummary['text'] }}
                </p>
            </div>
        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-5xl px-6 py-14 sm:px-8 lg:py-20">
            <h2 class="font-display text-2xl font-semibold text-[#1A1A1A] sm:text-3xl">
                {{ $cookiesCategories['heading'] }}
            </h2>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-[#6B6E76]">
                {{ $cookiesCategories['description'] }}
            </p>

            <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2">
                @foreach ($cookieTypes as $cookie)
                    @php $statusInfo = $cookieStatusMap[$cookie['status']] ?? $cookieStatusMap['none']; @endphp
                    <div class="flex flex-col rounded-2xl border border-[#1A1A1A]/10 p-6 transition-shadow duration-300 hover:shadow-[0_8px_24px_-8px_rgba(34,26,20,0.15)]">
                        <div class="flex items-start justify-between gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-admin-cream text-admin-accent">
                                <i class="fa-solid {{ $cookie['icon'] }}"></i>
                            </span>
                            <span class="rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-wide {{ $statusInfo['color'] }}">
                                {{ $statusInfo['label'] }}
                            </span>
                        </div>
                        <h3 class="mt-4 font-display text-lg font-semibold text-[#1A1A1A]">
                            {{ $cookie['name'] }}
                        </h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-[#6B6E76]">
                            {{ $cookie['desc'] }}
                        </p>
                    </div>
                @endforeach
            </div>

            <div class="mt-12 rounded-2xl border border-[#1A1A1A]/10 bg-admin-cream/30 p-6 sm:p-8">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-admin-accent">
                        <i class="fa-solid fa-sliders"></i>
                    </span>
                    <h3 class="font-display text-lg font-semibold text-[#1A1A1A]">
                        {{ $cookiesBrowser['heading'] }}
                    </h3>
                </div>
                <p class="mt-3 text-sm leading-relaxed text-[#4A423B] sm:text-[15px]">
                    {{ $cookiesBrowser['text'] }}
                </p>
            </div>

            <div class="mt-8 rounded-2xl bg-[#1A1A1A] p-8 text-center sm:p-10">
                <i class="fa-solid fa-comments text-2xl text-admin-accent-strong"></i>
                <h3 class="mt-3 font-display text-xl font-semibold text-white">
                    {{ $cookiesContact['heading'] }}
                </h3>
                <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-white/60">
                    {{ $cookiesContact['description'] }}
                </p>

                <div class="mt-5 flex items-center justify-center gap-2 text-sm text-white/70">
                    @if ($legalGmailLink)
                        <a href="{{ $legalGmailLink }}" target="_blank" rel="noopener" class="flex items-center gap-2 transition-colors duration-300 hover:text-white">
                            <i class="fa-solid fa-envelope text-admin-accent-strong"></i>
                            {{ $legalContactEmail }}
                        </a>
                    @else
                        <i class="fa-solid fa-envelope text-admin-accent-strong"></i>
                        {{ $legalContactEmail }}
                    @endif
                </div>

                @if ($legalWaLink)
                    <a href="{{ $legalWaLink }}" target="_blank" rel="noopener" class="mt-6 inline-flex items-center gap-2 rounded-full bg-admin-accent px-6 py-2.5 text-sm font-semibold text-white transition-colors duration-300 hover:bg-admin-accent-strong">
                        <i class="fa-brands fa-whatsapp"></i>
                        {{ $cookiesContact['button_label'] }}
                    </a>
                @endif
            </div>
        </div>
    </section>

    @include('partials.frontend.footer')
</body>
</html>
'@

[System.IO.File]::WriteAllText((Resolve-Path $cookies), $cookiesContent, $utf8NoBom)
Write-Host "  - cookies.blade.php terhubung ke HomeSection." -ForegroundColor Green

Step "[5/6] Validasi syntax + clear cache ..."
foreach ($file in @($admin, $privacy, $cookies)) {
    php -l $file | Out-Host
    if ($LASTEXITCODE -ne 0) {
        throw "Syntax error pada $file. Backup tersedia di: $backupDir"
    }
}

php artisan optimize:clear | Out-Host

Step "[6/6] Verifikasi 2 rectangle terpisah ..."
Write-Host "`nRectangle/group:" -ForegroundColor Cyan
Select-String -Path $admin -Pattern "'key' => 'privacy-policy'|'key' => 'cookies'|'key' => 'terms-of-service'" |
    Select-Object LineNumber, Line |
    Format-Table -AutoSize | Out-Host

Write-Host "`nKoneksi frontend Privacy:" -ForegroundColor Cyan
Select-String -Path $privacy -Pattern "HomeSection::dataFor\('privacy-" |
    Select-Object LineNumber, Line |
    Format-Table -AutoSize | Out-Host

Write-Host "`nKoneksi frontend Cookies:" -ForegroundColor Cyan
Select-String -Path $cookies -Pattern "HomeSection::dataFor\('cookies-" |
    Select-Object LineNumber, Line |
    Format-Table -AutoSize | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host ""
Write-Host "Admin > Edit Web sekarang memiliki:" -ForegroundColor Yellow
Write-Host "  [Privacy Policy]  - rectangle sendiri" -ForegroundColor White
Write-Host "  [Cookies]         - rectangle sendiri" -ForegroundColor White
Write-Host "  [Terms of Service]- tetap rectangle sendiri" -ForegroundColor White
Write-Host ""
Write-Host "Privacy Policy: Hero + 6 bagian + Kontak." -ForegroundColor White
Write-Host "Cookies: Hero + Ringkasan + Rincian Kategori + Pengaturan Browser + Kontak." -ForegroundColor White
Write-Host "Tidak ada migration baru dan tidak menyentuh halaman lain." -ForegroundColor DarkGray
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
