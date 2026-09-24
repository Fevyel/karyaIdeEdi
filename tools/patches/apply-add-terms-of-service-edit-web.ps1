$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script ini dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$admin = ".\resources\views\pages\admin\edit-web.blade.php"
$front = ".\resources\views\pages\frontend\terms-of-service.blade.php"

if (-not (Test-Path $admin)) { throw "File tidak ditemukan: $admin" }
if (-not (Test-Path $front)) { throw "File tidak ditemukan: $front" }

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = ".backup-terms-edit-web-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "==============================================================" -ForegroundColor Yellow
Write-Host " Terms of Service - Edit Web + Sinkronisasi Seluruh Bagian" -ForegroundColor Yellow
Write-Host "==============================================================" -ForegroundColor Yellow

Step "[1/6] Membuat backup ..."
New-Item -ItemType Directory -Path "$backupDir\resources\views\pages\admin" -Force | Out-Null
New-Item -ItemType Directory -Path "$backupDir\resources\views\pages\frontend" -Force | Out-Null
Copy-Item $admin "$backupDir\resources\views\pages\admin\edit-web.blade.php" -Force
Copy-Item $front "$backupDir\resources\views\pages\frontend\terms-of-service.blade.php" -Force
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/6] Menambahkan rectangle + 11 tab Terms of Service ke Edit Web ..."

$phpPatch = @'
<?php

$root = getcwd();
$adminPath = $root.'/resources/views/pages/admin/edit-web.blade.php';
$admin = file_get_contents($adminPath);

if ($admin === false) {
    throw new RuntimeException('Gagal membaca edit-web.blade.php');
}

$nl = str_contains($admin, "\r\n") ? "\r\n" : "\n";

function insertBefore(string $text, string $needle, string $insert, string $label): string
{
    if (str_contains($text, trim($insert))) {
        echo "  - {$label}: sudah ada\n";
        return $text;
    }

    $pos = strpos($text, $needle);

    if ($pos === false) {
        throw new RuntimeException("Anchor tidak ditemukan: {$label}");
    }

    return substr($text, 0, $pos).$insert.substr($text, $pos);
}

function addLineBefore(string $text, string $needle, string $line, string $label): string
{
    if (str_contains($text, trim($line))) {
        echo "  - {$label}: sudah ada\n";
        return $text;
    }

    $pos = strpos($text, $needle);

    if ($pos === false) {
        throw new RuntimeException("Anchor tidak ditemukan: {$label}");
    }

    return substr($text, 0, $pos).$line.substr($text, $pos);
}

function normalizeNl(string $text, string $nl): string
{
    return str_replace("\n", $nl, str_replace("\r\n", "\n", $text));
}

$sections = <<<'BLADE'
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

BLADE;

$admin = insertBefore(
    $admin,
    '    public array $ourCraftsmenSections = [',
    normalizeNl($sections, $nl),
    'daftar section Terms'
);

$props = <<<'BLADE'
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

BLADE;

$admin = insertBefore(
    $admin,
    '    public bool $ourCraftsmenHeroUseCustomBg = false;',
    normalizeNl($props, $nl),
    'state + defaults Terms'
);

if (! str_contains($admin, "'key' => 'terms-of-service'")) {
    $marker = preg_quote("'key' => 'our-craftsmen'", '~');
    if (! preg_match('~[ \t]*\[\R[ \t]*'.$marker.'~', $admin, $m, PREG_OFFSET_CAPTURE)) {
        throw new RuntimeException('Card Our Craftsmen tidak ditemukan.');
    }

    $offset = $m[0][1];
    $card = <<<'BLADE'
        [
            'key' => 'terms-of-service',
            'label' => 'Terms of Service',
            'icon' => 'fa-file-contract',
            'description' => 'Hero, 9 bagian ketentuan layanan, dan Kontak.',
            'ready' => true,
        ],

BLADE;
    $admin = substr($admin, 0, $offset).normalizeNl($card, $nl).substr($admin, $offset);
}

$admin = addLineBefore(
    $admin,
    "            'our-craftsmen' => \$this->ourCraftsmenSections[0]['key'],",
    normalizeNl("            'terms-of-service' => \$this->termsSections[0]['key'],\n", $nl),
    'selectGroup Terms'
);

$admin = addLineBefore(
    $admin,
    "                'our-craftsmen' => \$ourCraftsmenSections,",
    normalizeNl("                'terms-of-service' => \$termsSections,\n", $nl),
    'activeGroupSections Terms'
);

$admin = addLineBefore(
    $admin,
    "                'our-craftsmen' => 'Our Craftsmen',",
    normalizeNl("                'terms-of-service' => 'Terms of Service',\n", $nl),
    'activeGroupLabel Terms'
);

$mount = <<<'BLADE'
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

BLADE;

$admin = insertBefore(
    $admin,
    '        $ourCraftsmenHeroDefaults = $this->ourCraftsmenHeroDefaults();',
    normalizeNl($mount, $nl),
    'mount Terms'
);

$saves = <<<'BLADE'
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

BLADE;

$admin = insertBefore(
    $admin,
    '    public function saveOurCraftsmenHero(): void',
    normalizeNl($saves, $nl),
    'save Terms'
);

$ui = <<<'BLADE'
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

BLADE;

$admin = insertBefore(
    $admin,
    "            @elseif (\$activeSection === 'our-craftsmen-hero')",
    normalizeNl($ui, $nl),
    'UI Terms'
);

if (file_put_contents($adminPath, $admin) === false) {
    throw new RuntimeException('Gagal menulis edit-web.blade.php');
}

echo "ADMIN_PATCH_OK\n";
'@

$tmp = Join-Path $env:TEMP "patch-terms-edit-web-$stamp.php"
[System.IO.File]::WriteAllText($tmp, $phpPatch, $utf8NoBom)

php $tmp
$exitCode = $LASTEXITCODE
Remove-Item $tmp -Force -ErrorAction SilentlyContinue

if ($exitCode -ne 0) {
    throw "Patch Edit Web gagal. Backup tersedia di: $backupDir"
}

Step "[3/6] Menghubungkan halaman Terms of Service ke HomeSection ..."

$frontContent = @'
__FRONT__
'@

$frontContent = $frontContent.Replace('__FRONT__', @'
<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms of Service &mdash; {{ \App\Models\Setting::current()->site_name }}</title>
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

        $termsHero = \App\Models\HomeSection::dataFor('terms-hero', [
            'eyebrow' => 'Terms of Service',
            'heading' => 'Ketentuan Layanan',
            'description' => 'Ketentuan berikut mengatur hubungan Anda dengan '.$legalSetting->site_name.' - mulai dari proses pemesanan custom furniture hingga pengiriman ke tangan Anda.',
            'updated_date' => '2026-08-01',
        ]);

        try {
            $legalUpdatedAt = \Illuminate\Support\Facades\Date::parse($termsHero['updated_date'] ?? '2026-08-01')->translatedFormat('d F Y');
        } catch (\Throwable $e) {
            $legalUpdatedAt = \Illuminate\Support\Facades\Date::parse('2026-08-01')->translatedFormat('d F Y');
        }

        $termsDefaults = [
            'acceptance' => [
                'id' => 'penerimaan-ketentuan',
                'icon' => 'fa-file-signature',
                'title' => '1. Penerimaan Ketentuan',
                'text' => 'Dengan mengakses dan menggunakan situs '.$legalSetting->site_name.', melihat katalog produk, atau mengirimkan formulir booking custom furniture, Anda dianggap telah membaca, memahami, dan menyetujui seluruh ketentuan yang tercantum dalam halaman ini.',
            ],
            'custom-order' => [
                'id' => 'pesanan-custom',
                'icon' => 'fa-ruler-combined',
                'title' => '2. Proses Pemesanan Custom',
                'text' => 'Setiap produk pada dasarnya bersifat custom-made (dibuat sesuai pesanan), sehingga:',
                'bullets' => [
                    'Pengiriman formulir booking bukan konfirmasi final - pesanan baru dianggap sah setelah dikonfirmasi kedua belah pihak melalui WhatsApp.',
                    'Spesifikasi (ukuran, bahan, warna, deskripsi custom) yang Anda kirimkan menjadi acuan utama proses produksi.',
                    'Perubahan spesifikasi setelah produksi dimulai dapat memengaruhi waktu pengerjaan dan biaya tambahan, dan akan didiskusikan terlebih dahulu.',
                ],
            ],
            'payment' => [
                'id' => 'harga-pembayaran',
                'icon' => 'fa-tags',
                'title' => '3. Harga & Pembayaran',
                'text' => 'Harga yang tercantum di katalog merupakan estimasi awal dan dapat berubah menyesuaikan kompleksitas custom, bahan, dan ukuran akhir yang disepakati saat negosiasi via WhatsApp. Skema pembayaran (termasuk uang muka/DP bila berlaku) akan diinformasikan dan disepakati bersama sebelum produksi dimulai - kami tidak memproses pembayaran otomatis melalui situs ini.',
            ],
            'delivery' => [
                'id' => 'pengiriman-waktu',
                'icon' => 'fa-truck-fast',
                'title' => '4. Waktu Pengerjaan & Pengiriman',
                'text' => 'Estimasi waktu pengerjaan disampaikan saat konfirmasi pesanan dan dapat bervariasi tergantung tingkat kesulitan desain serta antrian produksi yang sedang berjalan. Status terkini dapat dipantau kapan saja melalui halaman Lacak Pesanan menggunakan tautan unik yang diberikan untuk setiap pesanan.',
            ],
            'cancellation' => [
                'id' => 'pembatalan-perubahan',
                'icon' => 'fa-ban',
                'title' => '5. Pembatalan & Perubahan Pesanan',
                'text' => 'Pembatalan sebelum proses produksi dimulai dapat diajukan melalui WhatsApp dan akan diproses sesuai kesepakatan terkait pengembalian uang muka (bila ada). Setelah produksi berjalan, pembatalan menjadi lebih terbatas mengingat bahan dan waktu kerja yang sudah dialokasikan khusus untuk pesanan Anda - kondisi ini akan dijelaskan secara terbuka saat negosiasi.',
            ],
            'warranty' => [
                'id' => 'garansi-retur',
                'icon' => 'fa-shield-heart',
                'title' => '6. Garansi & Pengembalian',
                'text' => 'Ketentuan lengkap mengenai garansi produk dan kebijakan retur diatur secara khusus pada halaman Profil, bagian Garansi & Pengiriman, agar informasinya selalu konsisten dan mudah ditemukan di satu tempat.',
                'link_labels' => [
                    'Lihat ketentuan Garansi',
                    'Lihat ketentuan Pengiriman & Retur',
                ],
            ],
            'copyright' => [
                'id' => 'hak-kekayaan-intelektual',
                'icon' => 'fa-copyright',
                'title' => '7. Hak Cipta & Konten Situs',
                'text' => 'Seluruh nama merek, logo, foto produk, dan konten pada situs ini adalah milik '.$legalSetting->site_name.' dan dilindungi hak cipta. Penggunaan, penyalinan, atau reproduksi konten tanpa izin tertulis tidak diperkenankan.',
            ],
            'liability' => [
                'id' => 'batasan-tanggung-jawab',
                'icon' => 'fa-scale-balanced',
                'title' => '8. Batasan Tanggung Jawab',
                'text' => 'Kami berupaya menampilkan informasi produk dan harga seakurat mungkin, namun variasi kecil pada warna atau tekstur material alami (kayu) adalah hal wajar dan bukan merupakan cacat produk. Kami tidak bertanggung jawab atas keterlambatan yang disebabkan oleh faktor di luar kendali kami, seperti kendala pihak ekspedisi.',
            ],
            'changes' => [
                'id' => 'perubahan-ketentuan',
                'icon' => 'fa-rotate',
                'title' => '9. Perubahan Ketentuan',
                'text' => 'Ketentuan Layanan ini dapat diperbarui sewaktu-waktu untuk menyesuaikan perkembangan layanan kami. Tanggal pembaruan terakhir selalu tercantum pada bagian atas halaman ini.',
            ],
        ];

        $termsSections = [];
        foreach ($termsDefaults as $key => $default) {
            $dataDefaults = [
                'title' => $default['title'],
                'text' => $default['text'],
            ];

            if (isset($default['bullets'])) {
                $dataDefaults['bullets'] = $default['bullets'];
            }

            if (isset($default['link_labels'])) {
                $dataDefaults['link_labels'] = $default['link_labels'];
            }

            $saved = \App\Models\HomeSection::dataFor('terms-'.$key, $dataDefaults);

            $section = [
                'id' => $default['id'],
                'icon' => $default['icon'],
                'title' => (string) ($saved['title'] ?? $default['title']),
                'text' => (string) ($saved['text'] ?? $default['text']),
            ];

            if (isset($default['bullets'])) {
                $section['bullets'] = array_values(is_array($saved['bullets'] ?? null) ? $saved['bullets'] : $default['bullets']);
            }

            if (isset($default['link_labels'])) {
                $labels = array_values(is_array($saved['link_labels'] ?? null) ? $saved['link_labels'] : $default['link_labels']);
                $section['links'] = [
                    ['href' => route('profile.index').'#garansi', 'label' => $labels[0] ?? $default['link_labels'][0]],
                    ['href' => route('profile.index').'#pengiriman', 'label' => $labels[1] ?? $default['link_labels'][1]],
                ];
            }

            $termsSections[] = $section;
        }

        $termsContact = \App\Models\HomeSection::dataFor('terms-contact', [
            'heading' => 'Butuh Penjelasan Lebih Lanjut?',
            'description' => 'Tim kami siap membantu menjelaskan ketentuan pemesanan sebelum Anda melanjutkan booking.',
            'button_label' => 'Hubungi via WhatsApp',
        ]);
    @endphp

    {{-- =====================================================
         HERO
    ====================================================== --}}
    <section class="relative overflow-hidden bg-[#1A1A1A]">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-admin-accent/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-16 bottom-0 h-56 w-56 rounded-full bg-admin-accent/10 blur-3xl"></div>

        <div class="relative mx-auto max-w-4xl px-6 py-16 text-center sm:px-8 lg:py-20">
            <div class="mx-auto flex w-fit items-center gap-3 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-admin-accent-strong">
                <i class="fa-solid fa-file-contract text-[10px]"></i>
                {{ $termsHero['eyebrow'] }}
            </div>
            <h1 class="mt-5 font-display text-4xl font-semibold leading-tight text-white sm:text-5xl">
                {{ $termsHero['heading'] }}
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-sm leading-relaxed text-white/60 sm:text-base">
                {{ $termsHero['description'] }}
            </p>
            <p class="mt-6 text-xs uppercase tracking-[0.15em] text-white/40">
                Terakhir diperbarui: {{ $legalUpdatedAt }}
            </p>
        </div>
    </section>

    {{-- =====================================================
         KONTEN + TOC
    ====================================================== --}}
    <section class="bg-white">
        <div class="mx-auto max-w-6xl px-6 py-14 sm:px-8 lg:py-20">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-[260px_1fr]">

                {{-- TOC --}}
                <aside class="hidden lg:block">
                    <div class="sticky top-28 rounded-2xl border border-[#1A1A1A]/10 bg-admin-cream/40 p-6">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.15em] text-[#6B6E76]">
                            Daftar Isi
                        </p>

                        <ul class="mt-4 space-y-3">
                            @foreach ($termsSections as $section)
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

                {{-- SECTIONS --}}
                <div class="space-y-12">
                    @foreach ($termsSections as $section)
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
                                <p class="text-sm leading-relaxed text-[#4A423B] sm:text-[15px]">
                                    {{ $section['text'] }}
                                </p>

                                @if (! empty($section['bullets']))
                                    <ul class="ml-1 list-disc space-y-2 pl-4 text-sm leading-relaxed text-[#4A423B] sm:text-[15px]">
                                        @foreach ($section['bullets'] as $point)
                                            <li>{{ $point }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if (! empty($section['links']))
                                    <div class="flex flex-col gap-2 sm:flex-row sm:gap-4">
                                        @foreach ($section['links'] as $link)
                                            <a href="{{ $link['href'] }}" class="inline-flex items-center gap-2 rounded-full border border-admin-accent/30 px-4 py-2 text-xs font-semibold text-admin-accent transition-colors duration-300 hover:bg-admin-cream">
                                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                                {{ $link['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    {{-- KONTAK --}}
                    <div class="scroll-mt-28 rounded-2xl bg-[#1A1A1A] p-8 text-center sm:p-10">
                        <i class="fa-solid fa-handshake text-2xl text-admin-accent-strong"></i>
                        <h3 class="mt-3 font-display text-xl font-semibold text-white">
                            {{ $termsContact['heading'] }}
                        </h3>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-white/60">
                            {{ $termsContact['description'] }}
                        </p>
                        <div class="mt-5 flex items-center justify-center gap-2 text-sm text-white/70">
                            @if ($legalGmailLink)
                                <a href="{{ $legalGmailLink }}" target="_blank" rel="noopener" class="flex items-center gap-2 transition-colors duration-300 hover:text-white"><i class="fa-solid fa-envelope text-admin-accent-strong"></i> {{ $legalContactEmail }}</a>
                            @else
                                <i class="fa-solid fa-envelope text-admin-accent-strong"></i> {{ $legalContactEmail }}
                            @endif
                        </div>
                        @if ($legalWaLink)
                            <a href="{{ $legalWaLink }}" target="_blank" rel="noopener" class="mt-6 inline-flex items-center gap-2 rounded-full bg-admin-accent px-6 py-2.5 text-sm font-semibold text-white transition-colors duration-300 hover:bg-admin-accent-strong">
                                <i class="fa-brands fa-whatsapp"></i>
                                {{ $termsContact['button_label'] }}
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

'@)

[System.IO.File]::WriteAllText((Resolve-Path $front), $frontContent, $utf8NoBom)
Write-Host "  - Frontend Terms of Service sekarang membaca data dari Edit Web." -ForegroundColor Green

Step "[4/6] Validasi syntax PHP/Blade ..."
foreach ($file in @($admin, $front)) {
    php -l $file | Out-Host
    if ($LASTEXITCODE -ne 0) {
        throw "Syntax error pada $file. Restore tersedia di: $backupDir"
    }
}

Step "[5/6] Clear cache Laravel ..."
php artisan optimize:clear | Out-Host

Step "[6/6] Verifikasi koneksi Terms ..."
Write-Host "`nGroup / section Terms di Edit Web:" -ForegroundColor Cyan
Select-String -Path $admin -Pattern "terms-of-service|terms-hero|terms-contact|saveTermsSection" |
    Select-Object -First 20 LineNumber, Line |
    Format-Table -AutoSize | Out-Host

Write-Host "`nHomeSection Terms pada frontend:" -ForegroundColor Cyan
Select-String -Path $front -Pattern "HomeSection::dataFor\('terms-" |
    Select-Object LineNumber, Line |
    Format-Table -AutoSize | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host ""
Write-Host "Edit Web sekarang memiliki rectangle Terms of Service dengan 11 tab:" -ForegroundColor Yellow
Write-Host "  Hero + 9 bagian ketentuan + Kontak." -ForegroundColor White
Write-Host "Setiap tab terhubung ke bagian yang sama di /ketentuan-layanan." -ForegroundColor White
Write-Host "Daftar Isi di frontend otomatis memakai judul terbaru dari admin." -ForegroundColor White
Write-Host "Tidak ada migration baru." -ForegroundColor DarkGray
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
