$ErrorActionPreference = 'Stop'

function Step($message) {
    Write-Host "`n$message" -ForegroundColor Cyan
}

if (-not (Test-Path '.\artisan')) {
    throw "File artisan tidak ditemukan. Jalankan script dari root project: C:\xampp\htdocs\karyaIdeEdi"
}

$adminPath = '.\resources\views\pages\admin\edit-web.blade.php'
$frontPath = '.\resources\views\pages\frontend\pengrajin.blade.php'

if (-not (Test-Path $adminPath)) { throw "File $adminPath tidak ditemukan." }
if (-not (Test-Path $frontPath)) { throw "File $frontPath tidak ditemukan." }

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupDir = ".backup-our-craftsmen-complete-$stamp"

Write-Host '=============================================================' -ForegroundColor Yellow
Write-Host ' Lunaskan Edit Web + Upgrade Halaman Our Craftsmen' -ForegroundColor Yellow
Write-Host '=============================================================' -ForegroundColor Yellow

Step '[1/6] Backup file yang disentuh ...'
New-Item -ItemType Directory -Path "$backupDir\resources\views\pages\admin" -Force | Out-Null
New-Item -ItemType Directory -Path "$backupDir\resources\views\pages\frontend" -Force | Out-Null
Copy-Item $adminPath "$backupDir\resources\views\pages\admin\edit-web.blade.php" -Force
Copy-Item $frontPath "$backupDir\resources\views\pages\frontend\pengrajin.blade.php" -Force
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step '[2/6] Sinkronkan Edit Web > Our Craftsmen ...'
$tmp = Join-Path $env:TEMP "patch-our-craftsmen-$stamp.php"

@'
<?php
$root = getcwd();
$adminPath = $root . DIRECTORY_SEPARATOR . 'resources/views/pages/admin/edit-web.blade.php';
$frontPath = $root . DIRECTORY_SEPARATOR . 'resources/views/pages/frontend/pengrajin.blade.php';

$admin = file_get_contents($adminPath);
if ($admin === false) throw new RuntimeException('Gagal membaca edit-web.blade.php');

function replaceRequired(string $text, string $old, string $new, string $label): string
{
    if (strpos($text, $new) !== false && strpos($text, $old) === false) {
        echo "  - {$label}: sudah sesuai\n";
        return $text;
    }
    if (strpos($text, $old) === false) {
        throw new RuntimeException("Bagian '{$label}' tidak ditemukan persis. Script berhenti agar tidak merusak file.");
    }
    echo "  - {$label}: diperbarui\n";
    return str_replace($old, $new, $text);
}

// 1. Aktifkan seluruh section dan ubah Daftar Pengrajin -> Profil Pemilik.
$oldSections = <<<'BLADE'
    public array $ourCraftsmenSections = [
        ['key' => 'our-craftsmen-hero', 'label' => 'Hero', 'icon' => 'fa-image', 'ready' => true],
        ['key' => 'our-craftsmen-daftar', 'label' => 'Daftar Pengrajin', 'icon' => 'fa-users', 'ready' => false],
        ['key' => 'our-craftsmen-cta', 'label' => 'CTA', 'icon' => 'fa-bullhorn', 'ready' => false],
    ];
BLADE;
$newSections = <<<'BLADE'
    public array $ourCraftsmenSections = [
        ['key' => 'our-craftsmen-hero', 'label' => 'Hero', 'icon' => 'fa-image', 'ready' => true],
        ['key' => 'our-craftsmen-daftar', 'label' => 'Profil Pemilik', 'icon' => 'fa-user-tie', 'ready' => true],
        ['key' => 'our-craftsmen-cta', 'label' => 'CTA', 'icon' => 'fa-bullhorn', 'ready' => true],
    ];
BLADE;
$admin = replaceRequired($admin, $oldSections, $newSections, 'daftar section Our Craftsmen');

$admin = replaceRequired(
    $admin,
    "            'description' => 'Hero, Daftar Pengrajin, CTA.',",
    "            'description' => 'Hero, Profil Pemilik, CTA.',",
    'deskripsi kartu grup Our Craftsmen'
);

// 2. Tambahkan state + defaults untuk Profil Pemilik dan CTA.
$propertyMarker = <<<'BLADE'
    /**
     * Hero halaman "Dokumentasi"
BLADE;
if (strpos($admin, 'public string $ourCraftsmenOwnerName') === false) {
    $pos = strpos($admin, $propertyMarker);
    if ($pos === false) throw new RuntimeException('Marker properti Dokumentasi tidak ditemukan.');

    $ownerProperties = <<<'BLADE'

    // ================= OUR CRAFTSMEN — PROFIL PEMILIK =================

    public string $ourCraftsmenOwnerEyebrow = 'Pemilik & Founder';

    public string $ourCraftsmenOwnerName = 'Pemilik Karya Ide Edi';

    public string $ourCraftsmenOwnerRole = 'Founder & Creative Director';

    public string $ourCraftsmenOwnerHeading = 'Berawal dari ketelitian, tumbuh menjadi karya yang dipercaya.';

    public string $ourCraftsmenOwnerDescription = 'Karya Ide Edi dibangun dengan perhatian pada detail, fungsi, dan karakter setiap ruang. Setiap pesanan dikerjakan dengan pendekatan personal agar furnitur tidak hanya mengisi ruang, tetapi benar-benar menjadi bagian dari cerita pemiliknya.';

    public string $ourCraftsmenOwnerQuote = 'Bagi kami, furnitur yang baik bukan sekadar terlihat indah. Ia harus terasa tepat untuk orang dan ruang yang menggunakannya.';

    public ?string $ourCraftsmenOwnerPhotoPathLama = null;

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

    // ================= OUR CRAFTSMEN — CTA =================

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

BLADE;
    $admin = substr($admin, 0, $pos) . $ownerProperties . substr($admin, $pos);
    echo "  - state Profil Pemilik + CTA: ditambahkan\n";
} else {
    echo "  - state Profil Pemilik + CTA: sudah ada\n";
}

// 3. Load data baru saat mount().
$mountNeedle = <<<'BLADE'
        $this->loadFrameGradient('ourCraftsmenHero', $ourCraftsmenHeroData['bg_gradient'] ?? null);
BLADE;
if (strpos($admin, '$ourCraftsmenOwnerData = HomeSection::dataFor') === false) {
    if (strpos($admin, $mountNeedle) === false) throw new RuntimeException('Marker mount Our Craftsmen Hero tidak ditemukan.');
    $mountInsert = $mountNeedle . <<<'BLADE'


        $ourCraftsmenOwnerData = HomeSection::dataFor('our-craftsmen-daftar', $this->ourCraftsmenOwnerDefaults());
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
        $this->ourCraftsmenCtaEyebrow = (string) ($ourCraftsmenCtaData['eyebrow'] ?? $this->ourCraftsmenCtaEyebrow);
        $this->ourCraftsmenCtaHeading = (string) ($ourCraftsmenCtaData['heading'] ?? $this->ourCraftsmenCtaHeading);
        $this->ourCraftsmenCtaDescription = (string) ($ourCraftsmenCtaData['description'] ?? $this->ourCraftsmenCtaDescription);
        $this->ourCraftsmenCtaButtonText = (string) ($ourCraftsmenCtaData['button_text'] ?? $this->ourCraftsmenCtaButtonText);
BLADE;
    $admin = str_replace($mountNeedle, $mountInsert, $admin);
    echo "  - mount Profil Pemilik + CTA: ditambahkan\n";
} else {
    echo "  - mount Profil Pemilik + CTA: sudah ada\n";
}

// 4. Tambahkan preview + save methods sebelum saveMission().
$methodMarker = "    public function saveMission(): void\n";
if (strpos($admin, 'public function saveOurCraftsmenOwner(): void') === false) {
    $methodPos = strpos($admin, $methodMarker);
    if ($methodPos === false) throw new RuntimeException('Marker saveMission() tidak ditemukan.');

    $methods = <<<'BLADE'
    public function getOurCraftsmenOwnerPhotoPreviewUrlProperty(): ?string
    {
        if ($this->ourCraftsmenOwnerPhotoUpload) {
            try {
                return $this->ourCraftsmenOwnerPhotoUpload->temporaryUrl();
            } catch (\Throwable) {
                return null;
            }
        }

        return $this->ourCraftsmenOwnerPhotoPathLama
            ? Storage::disk('public')->url($this->ourCraftsmenOwnerPhotoPathLama)
            : null;
    }

    public function removeOurCraftsmenOwnerPhoto(): void
    {
        $this->ourCraftsmenOwnerPhotoUpload = null;

        if ($this->ourCraftsmenOwnerPhotoPathLama) {
            Storage::disk('public')->delete($this->ourCraftsmenOwnerPhotoPathLama);
            $this->ourCraftsmenOwnerPhotoPathLama = null;
        }
    }

    public function saveOurCraftsmenOwner(): void
    {
        $validated = $this->validate([
            'ourCraftsmenOwnerEyebrow' => ['required', 'string', 'max:50'],
            'ourCraftsmenOwnerName' => ['required', 'string', 'max:80'],
            'ourCraftsmenOwnerRole' => ['required', 'string', 'max:100'],
            'ourCraftsmenOwnerHeading' => ['required', 'string', 'max:140'],
            'ourCraftsmenOwnerDescription' => ['required', 'string', 'max:800'],
            'ourCraftsmenOwnerQuote' => ['nullable', 'string', 'max:350'],
            'ourCraftsmenOwnerPhotoUpload' => ['nullable', 'image', 'max:8192'],
            'ourCraftsmenOwnerStats' => ['required', 'array', 'size:3'],
            'ourCraftsmenOwnerStats.*.value' => ['required', 'string', 'max:30'],
            'ourCraftsmenOwnerStats.*.label' => ['required', 'string', 'max:60'],
        ]);

        if ($this->ourCraftsmenOwnerPhotoUpload) {
            if ($this->ourCraftsmenOwnerPhotoPathLama) {
                Storage::disk('public')->delete($this->ourCraftsmenOwnerPhotoPathLama);
            }

            $extension = $this->ourCraftsmenOwnerPhotoUpload->getClientOriginalExtension() ?: 'jpg';
            $this->ourCraftsmenOwnerPhotoPathLama = $this->ourCraftsmenOwnerPhotoUpload->storeAs(
                'home-sections',
                'our-craftsmen-owner-'.Str::uuid().'.'.$extension,
                'public'
            );
            $this->ourCraftsmenOwnerPhotoUpload = null;
        }

        HomeSection::forSection('our-craftsmen-daftar')->update([
            'data' => [
                'eyebrow' => $validated['ourCraftsmenOwnerEyebrow'],
                'name' => $validated['ourCraftsmenOwnerName'],
                'role' => $validated['ourCraftsmenOwnerRole'],
                'heading' => $validated['ourCraftsmenOwnerHeading'],
                'description' => $validated['ourCraftsmenOwnerDescription'],
                'quote' => $validated['ourCraftsmenOwnerQuote'] !== '' ? $validated['ourCraftsmenOwnerQuote'] : null,
                'photo_path' => $this->ourCraftsmenOwnerPhotoPathLama,
                'stats' => array_values($validated['ourCraftsmenOwnerStats']),
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveOurCraftsmenCta(): void
    {
        $validated = $this->validate([
            'ourCraftsmenCtaEyebrow' => ['required', 'string', 'max:50'],
            'ourCraftsmenCtaHeading' => ['required', 'string', 'max:120'],
            'ourCraftsmenCtaDescription' => ['required', 'string', 'max:500'],
            'ourCraftsmenCtaButtonText' => ['required', 'string', 'max:50'],
        ]);

        HomeSection::forSection('our-craftsmen-cta')->update([
            'data' => [
                'eyebrow' => $validated['ourCraftsmenCtaEyebrow'],
                'heading' => $validated['ourCraftsmenCtaHeading'],
                'description' => $validated['ourCraftsmenCtaDescription'],
                'button_text' => $validated['ourCraftsmenCtaButtonText'],
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

BLADE;
    $admin = substr($admin, 0, $methodPos) . $methods . substr($admin, $methodPos);
    echo "  - method Profil Pemilik + CTA: ditambahkan\n";
} else {
    echo "  - method Profil Pemilik + CTA: sudah ada\n";
}

// 5. Tambahkan UI form untuk dua section yang sebelumnya "Segera".
$uiNeedle = <<<'BLADE'
                </form>
            @else
                <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-admin-border px-5 py-16 text-center">
BLADE;
if (strpos($admin, "wire:submit=\"saveOurCraftsmenOwner\"") === false) {
    $uiPos = strpos($admin, $uiNeedle, strpos($admin, "@elseif (\$activeSection === 'our-craftsmen-hero')"));
    if ($uiPos === false) throw new RuntimeException('Marker UI setelah form Hero Our Craftsmen tidak ditemukan.');

    $forms = <<<'BLADE'
                </form>
            @elseif ($activeSection === 'our-craftsmen-daftar')
                <form wire:submit="saveOurCraftsmenOwner" class="space-y-6">
                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-user-tie text-admin-accent"></i>
                            Profil Pemilik Karya Ide Edi
                        </h3>
                        <p class="text-xs text-admin-ink-soft">
                            Section editorial untuk memperkenalkan pemilik/founder secara profesional. Foto, identitas, cerita singkat, kutipan, dan 3 poin utama semuanya terhubung langsung ke halaman Our Craftsmen.
                        </p>
                    </div>

                    <div class="grid gap-5 lg:grid-cols-[18rem_minmax(0,1fr)]">
                        <div class="space-y-3 rounded-xl border border-admin-border p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Foto Pemilik</p>

                            @if ($this->ourCraftsmenOwnerPhotoPreviewUrl)
                                <div class="aspect-4/5 overflow-hidden rounded-xl bg-admin-cream">
                                    <img src="{{ $this->ourCraftsmenOwnerPhotoPreviewUrl }}" alt="Preview foto pemilik" class="h-full w-full object-cover">
                                </div>
                                <button type="button" wire:click="removeOurCraftsmenOwnerPhoto" class="inline-flex items-center gap-2 text-xs font-semibold text-red-600 hover:text-red-700">
                                    <i class="fa-solid fa-trash"></i> Hapus foto
                                </button>
                            @else
                                <div class="flex aspect-4/5 items-center justify-center rounded-xl border border-dashed border-admin-border bg-admin-cream/50 text-center text-xs text-admin-ink-soft">
                                    Belum ada foto pemilik
                                </div>
                            @endif

                            <input type="file" wire:model="ourCraftsmenOwnerPhotoUpload" accept="image/*" class="block w-full text-xs text-admin-ink file:mr-2 file:rounded-full file:border-0 file:bg-admin-accent file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
                            <div wire:loading wire:target="ourCraftsmenOwnerPhotoUpload" class="text-xs text-admin-ink-soft"><i class="fa-solid fa-circle-notch animate-spin"></i> Mengunggah...</div>
                            @error('ourCraftsmenOwnerPhotoUpload')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            <p class="text-[11px] leading-relaxed text-admin-ink-soft">Disarankan foto portrait 4:5, tajam, dan pencahayaan natural. Maksimal 8 MB.</p>
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
                        <p class="mb-4 text-xs text-admin-ink-soft">Contoh: “10+ / Tahun pengalaman”, “Custom / Dibuat sesuai kebutuhan”, atau “Jepara / Workshop”.</p>
                        <div class="grid gap-4 md:grid-cols-3">
                            @foreach ($ourCraftsmenOwnerStats as $i => $stat)
                                <div class="space-y-2 rounded-lg bg-admin-cream/50 p-3">
                                    <input type="text" maxlength="30" wire:model="ourCraftsmenOwnerStats.{{ $i }}.value" placeholder="Nilai" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2 text-sm font-semibold text-admin-ink focus:border-admin-accent focus:outline-none">
                                    <input type="text" maxlength="60" wire:model="ourCraftsmenOwnerStats.{{ $i }}.label" placeholder="Keterangan" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2 text-xs text-admin-ink focus:border-admin-accent focus:outline-none">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end"><button type="submit" wire:loading.attr="disabled" wire:target="saveOurCraftsmenOwner" class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition hover:bg-admin-accent-strong disabled:opacity-60"><span wire:loading.remove wire:target="saveOurCraftsmenOwner"><i class="fa-solid fa-floppy-disk mr-2 text-xs"></i>Simpan Profil Pemilik</span><span wire:loading wire:target="saveOurCraftsmenOwner"><i class="fa-solid fa-circle-notch mr-2 animate-spin"></i>Menyimpan...</span></button></div>
                </form>
            @elseif ($activeSection === 'our-craftsmen-cta')
                <form wire:submit="saveOurCraftsmenCta" class="space-y-6">
                    <div><h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink"><i class="fa-solid fa-bullhorn text-admin-accent"></i>CTA Our Craftsmen</h3><p class="text-xs text-admin-ink-soft">Bagian penutup halaman. Tombol otomatis menuju WhatsApp toko jika nomor WhatsApp tersedia di Pengaturan; jika belum, tombol menuju halaman booking.</p></div>
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
BLADE;

    $admin = substr($admin, 0, $uiPos) . $forms . substr($admin, $uiPos + strlen($uiNeedle));
    echo "  - form Profil Pemilik + CTA: diaktifkan\n";
} else {
    echo "  - form Profil Pemilik + CTA: sudah ada\n";
}

file_put_contents($adminPath, $admin);

// 6. Frontend Our Craftsmen — rewrite penuh supaya seluruh section sinkron.
$front = <<<'BLADE'
<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Our Craftsmen | {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-[#F8F5F0] font-sans text-[#2A211B] antialiased">
    @include('partials.frontend.navbar')

    @php
        $setting = \App\Models\Setting::current();
        $craftsmenWaNumber = $setting->whatsappDigits();

        $hero = \App\Models\HomeSection::dataFor('our-craftsmen-hero', [
            'bg_color' => null,
            'eyebrow' => 'Our Craftsmen',
            'heading' => 'Tangan-Tangan di Balik Setiap Produk',
            'description' => 'Di balik setiap furnitur ada proses, ketelitian, dan keputusan yang dibuat dengan tangan. Karya Ide Edi tumbuh dari keyakinan bahwa kualitas terbaik lahir dari perhatian pada detail.',
        ]);

        $heroFrame = \App\Support\FrameBackground::resolve($hero['bg_color'] ?? null, $hero['bg_gradient'] ?? null, '#3A2418');
        $heroBase = $heroFrame['base'];

        $heroContrast = function (string $hex): array {
            $hex = ltrim($hex, '#');
            $r = hexdec(substr($hex, 0, 2)) / 255;
            $g = hexdec(substr($hex, 2, 2)) / 255;
            $b = hexdec(substr($hex, 4, 2)) / 255;
            $lin = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
            $lum = 0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b);
            return $lum > 0.5
                ? ['title' => '#2D1C13', 'body' => 'rgba(45,28,19,.72)', 'accent' => '#9B6E3E']
                : ['title' => '#FFFDF9', 'body' => 'rgba(255,253,249,.74)', 'accent' => '#D5A66D'];
        };
        $heroColors = $heroContrast($heroBase);

        $owner = \App\Models\HomeSection::dataFor('our-craftsmen-daftar', [
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
        ]);
        $ownerPhoto = ! empty($owner['photo_path'])
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($owner['photo_path'])
            : null;
        $ownerStats = is_array($owner['stats'] ?? null) ? array_slice(array_values($owner['stats']), 0, 3) : [];

        $cta = \App\Models\HomeSection::dataFor('our-craftsmen-cta', [
            'eyebrow' => 'Mulai dari Sebuah Ide',
            'heading' => 'Punya ide furnitur custom?',
            'description' => 'Ceritakan ukuran, fungsi, gaya, atau referensi yang Anda inginkan. Kami siap membantu menerjemahkannya menjadi furnitur yang sesuai dengan ruang Anda.',
            'button_text' => 'Konsultasi via WhatsApp',
        ]);
    @endphp

    {{-- HERO --}}
    <section class="relative isolate overflow-hidden" style="background: {{ $heroFrame['css'] }};">
        <div class="pointer-events-none absolute inset-0 opacity-60" aria-hidden="true">
            <div class="absolute -right-20 -top-24 h-96 w-96 rounded-full border border-white/8"></div>
            <div class="absolute -right-8 -top-10 h-64 w-64 rounded-full border border-white/8"></div>
            <div class="absolute bottom-0 left-0 h-px w-full bg-linear-to-r from-transparent via-white/16 to-transparent"></div>
        </div>

        <div class="relative mx-auto grid min-h-[34rem] max-w-7xl items-center px-6 py-20 sm:px-8 lg:grid-cols-[1fr_22rem] lg:gap-16 lg:px-10 lg:py-24">
            <div class="max-w-4xl">
                <div class="flex items-center gap-4 text-xs font-semibold uppercase tracking-[0.34em]" style="color: {{ $heroColors['accent'] }};">
                    <span class="h-px w-10" style="background-color: {{ $heroColors['accent'] }};"></span>
                    {{ $hero['eyebrow'] }}
                </div>

                <h1 class="mt-6 font-display text-5xl font-semibold leading-[1.02] sm:text-6xl lg:text-7xl" style="color: {{ $heroColors['title'] }};">
                    {{ $hero['heading'] }}
                </h1>

                <p class="mt-7 max-w-3xl text-base leading-8 sm:text-lg sm:leading-9" style="color: {{ $heroColors['body'] }};">
                    {{ $hero['description'] }}
                </p>
            </div>

            <div class="mt-12 hidden lg:block">
                <div class="ml-auto w-64 border-l pl-8" style="border-color: color-mix(in srgb, {{ $heroColors['accent'] }} 42%, transparent);">
                    <p class="font-display text-6xl leading-none" style="color: {{ $heroColors['accent'] }};">01</p>
                    <p class="mt-4 text-xs font-semibold uppercase tracking-[0.28em]" style="color: {{ $heroColors['body'] }};">Crafted by People</p>
                    <p class="mt-3 text-sm leading-7" style="color: {{ $heroColors['body'] }};">Bukan sekadar produksi. Setiap karya lahir dari pertimbangan manusia, pengalaman, dan ketelitian.</p>
                </div>
            </div>
        </div>
    </section>

    @include('partials.frontend.frame-seam', ['from' => $heroBase, 'to' => '#F8F5F0'])

    {{-- PROFIL PEMILIK --}}
    <section class="relative overflow-hidden bg-[#F8F5F0] py-18 sm:py-20 lg:py-28">
        <div class="pointer-events-none absolute -left-24 top-20 h-80 w-80 rounded-full bg-[#EADAC6]/55 blur-3xl"></div>
        <div class="relative mx-auto max-w-7xl px-6 sm:px-8 lg:px-10">
            <div class="grid items-center gap-12 lg:grid-cols-[0.86fr_1.14fr] lg:gap-20">
                <div class="relative mx-auto w-full max-w-lg lg:mx-0">
                    <div class="absolute -left-5 -top-5 h-28 w-28 rounded-4xl border border-[#C99B67]/35"></div>
                    <div class="relative aspect-4/5 overflow-hidden rounded-4xl bg-linear-to-br from-[#E7D6C1] to-[#CDB091] shadow-[0_35px_90px_-45px_rgba(58,36,24,.55)]">
                        @if ($ownerPhoto)
                            <img src="{{ $ownerPhoto }}" alt="{{ $owner['name'] }}" class="h-full w-full object-cover" loading="lazy">
                        @else
                            <div class="flex h-full w-full flex-col items-center justify-center px-10 text-center text-[#684A34]">
                                <span class="flex h-20 w-20 items-center justify-center rounded-full bg-white/55 text-3xl shadow-sm"><i class="fa-solid fa-user-tie"></i></span>
                                <p class="mt-5 font-display text-2xl font-semibold">Foto Pemilik</p>
                                <p class="mt-2 max-w-xs text-sm leading-6 text-[#765D4B]">Unggah foto asli melalui Edit Web &gt; Our Craftsmen &gt; Profil Pemilik.</p>
                            </div>
                        @endif
                        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-36 bg-linear-to-t from-[#21140D]/70 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 p-6 text-white sm:p-8">
                            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-[#E8C28F]">{{ $owner['role'] }}</p>
                            <h2 class="mt-2 font-display text-3xl font-semibold sm:text-4xl">{{ $owner['name'] }}</h2>
                        </div>
                    </div>
                    <div class="absolute -bottom-5 -right-5 rounded-3xl bg-[#3A2418] px-5 py-4 text-white shadow-xl">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.25em] text-[#DDB47E]">Karya Ide Edi</p>
                        <p class="mt-1 text-sm font-semibold">Made with intention</p>
                    </div>
                </div>

                <div>
                    <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.32em] text-[#A66E38]">
                        <span class="h-px w-10 bg-[#A66E38]"></span>
                        {{ $owner['eyebrow'] }}
                    </div>

                    <h2 class="mt-5 max-w-3xl font-display text-4xl font-semibold leading-tight text-[#3A2418] sm:text-5xl lg:text-6xl">
                        {{ $owner['heading'] }}
                    </h2>

                    <p class="mt-7 max-w-3xl text-base leading-8 text-[#6E6258] sm:text-lg sm:leading-9">
                        {{ $owner['description'] }}
                    </p>

                    @if (! empty($owner['quote']))
                        <blockquote class="mt-8 border-l-2 border-[#C9955C] pl-6 font-display text-xl italic leading-9 text-[#543929] sm:text-2xl">
                            “{{ $owner['quote'] }}”
                        </blockquote>
                    @endif

                    @if (count($ownerStats) > 0)
                        <div class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            @foreach ($ownerStats as $stat)
                                <div class="rounded-3xl border border-[#E4D6C5] bg-white/70 p-5 shadow-[0_18px_45px_-38px_rgba(58,36,24,.45)] backdrop-blur">
                                    <p class="font-display text-2xl font-semibold text-[#4B2F1F]">{{ $stat['value'] ?? '' }}</p>
                                    <p class="mt-1 text-xs leading-5 text-[#817267]">{{ $stat['label'] ?? '' }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- STRIP NILAI / IDENTITAS --}}
    <section class="overflow-hidden border-y border-[#E6D5C2] bg-[#F1E6D8] py-4">
        <div class="flex min-w-max items-center gap-8 whitespace-nowrap font-display text-lg font-semibold uppercase tracking-[0.22em] text-[#5B3B28] sm:text-xl">
            @foreach (range(1, 3) as $copy)
                <span>Custom Furniture</span><span class="text-[#B17A45]">•</span>
                <span>Detail Matters</span><span class="text-[#B17A45]">•</span>
                <span>Made by Hand</span><span class="text-[#B17A45]">•</span>
                <span>Karya Ide Edi</span><span class="text-[#B17A45]">•</span>
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    <section class="relative overflow-hidden bg-[#21140D]">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -right-20 -top-28 h-96 w-96 rounded-full bg-[#9F6635]/18 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-20 h-96 w-96 rounded-full bg-[#D3A66E]/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-10 px-6 py-16 sm:px-8 lg:grid-cols-[1fr_auto] lg:px-10 lg:py-20">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.34em] text-[#D6A76F]">{{ $cta['eyebrow'] }}</p>
                <h2 class="mt-4 font-display text-4xl font-semibold leading-tight text-white sm:text-5xl">{{ $cta['heading'] }}</h2>
                <p class="mt-5 max-w-2xl text-base leading-8 text-white/65">{{ $cta['description'] }}</p>
            </div>

            <a
                href="{{ $craftsmenWaNumber ? 'https://wa.me/'.$craftsmenWaNumber : route('booking.index') }}"
                @if ($craftsmenWaNumber) target="_blank" rel="noopener" @endif
                class="group inline-flex w-fit items-center gap-3 rounded-full bg-white px-7 py-4 text-sm font-semibold text-[#2F1D13] shadow-xl transition duration-300 hover:-translate-y-0.5 hover:bg-[#F4E8D8]"
            >
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#2F1D13] text-white"><i class="fa-brands fa-whatsapp"></i></span>
                {{ $cta['button_text'] }}
                <i class="fa-solid fa-arrow-right text-xs transition-transform duration-300 group-hover:translate-x-1"></i>
            </a>
        </div>
    </section>

    @include('partials.frontend.footer')
</body>
</html>
BLADE;

file_put_contents($frontPath, $front);
echo "  - frontend Our Craftsmen: di-upgrade & disinkronkan\n";
echo "PATCH_OK\n";
'@ | Set-Content -Path $tmp -Encoding UTF8

php $tmp
Remove-Item $tmp -Force

Step '[3/6] Validasi syntax PHP / Blade ...'
php -l $adminPath | Out-Host
php -l $frontPath | Out-Host

Step '[4/6] Bersihkan cache view ...'
php artisan view:clear | Out-Host

Step '[5/6] Ringkasan perubahan ...'
Write-Host '  Edit Web > Our Craftsmen:' -ForegroundColor DarkGray
Write-Host '   - Hero           : tetap aktif dan terhubung' -ForegroundColor DarkGray
Write-Host '   - Profil Pemilik : AKTIF, foto + identitas + cerita + quote + 3 highlight' -ForegroundColor DarkGray
Write-Host '   - CTA            : AKTIF, judul + deskripsi + teks tombol' -ForegroundColor DarkGray
Write-Host '  Frontend /pengrajin-kami:' -ForegroundColor DarkGray
Write-Host '   - Hero modern editorial' -ForegroundColor DarkGray
Write-Host '   - Profil pemilik menggantikan data dummy pengrajin' -ForegroundColor DarkGray
Write-Host '   - Strip identitas brand + CTA modern' -ForegroundColor DarkGray
Write-Host '  Tidak ada migration/database schema baru.' -ForegroundColor DarkGray

Step '[6/6] Selesai ...'
Write-Host 'SELESAI' -ForegroundColor Green
Write-Host 'Setelah ini jalankan: npm run build' -ForegroundColor Yellow
