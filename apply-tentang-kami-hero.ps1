# ============================================================
# Aktifkan tab "Tentang Kami" (dulu "Profil Toko") di Admin > Edit Web,
# dan sambungkan ke section Hero (bagian A) di halaman frontend /profil.
#
# Yang berubah:
#
# 1) resources/views/pages/admin/edit-web.blade.php
#    - Tab pertama grup "Tentang Kami": label "Profil Toko" -> "Tentang
#      Kami", status ready => true (bisa diklik, tidak lagi "Segera").
#    - Deskripsi kartu "Tentang Kami" di grid menu ikut diupdate jadi
#      "Tentang Kami, Sejarah, Pengrajin, Keberlanjutan, Karier."
#    - Form baru buat edit: label kecil di atas judul, judul (2 baris),
#      paragraf, dan foto kanan (crop rasio 10:9, alur sama persis
#      dengan foto Header/Sejak Berdiri -- geser + slider zoom).
#    - Tombol "Lihat Produk" & "Hubungi Kami" di halaman itu TETAP tidak
#      bisa diedit dari sini (sesuai aturan "tombol tidak boleh diubah").
#    - 4 section lain di grup ini (Sejarah, Pengrajin, Keberlanjutan,
#      Karier) TIDAK disentuh -- masih "Segera" seperti sebelumnya,
#      nanti diaktifkan satu-satu menyusul.
#
# 2) resources/views/pages/frontend/profil.blade.php
#    - Section A (Hero) sekarang ambil datanya dari HomeSection
#      (section_key 'profil-toko'), bukan teks hardcode lagi. Sebelum
#      admin pernah menyimpan apa pun, tampilannya PERSIS SAMA seperti
#      sebelumnya (default value = teks/foto yang sudah ada sekarang).
#    - Section B ("Tentang Karya Ide Edi"), C (Nilai Kami), dst di
#      halaman itu TIDAK disentuh sama sekali.
#
# TIDAK ADA bagian lain yang disentuh di kedua file ini.
#
# Kedua file ditulis pakai [System.IO.File]::WriteAllText, encoding
# UTF-8 DENGAN BOM (sama seperti file aslinya).
#
# Cara pakai (dari VS Code integrated terminal, root project
# C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-tentang-kami-hero.ps1
#
# Setelah itu langsung cek di browser (php artisan serve):
#   - Admin > Edit Web > kartu "Tentang Kami" > tab "Tentang Kami"
#   - Halaman publik /profil (link "Tentang Kami" di navbar)
# Tidak perlu npm run build (tidak ada class Tailwind baru).
# ============================================================

$ErrorActionPreference = "Stop"

function Backup-File {
    param([string]$Path)
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$Path.bak-before-tentang-kami-hero-$stamp"
    Copy-Item -Path $Path -Destination $backupPath -Force
    Write-Host "  Backup dibuat: $backupPath" -ForegroundColor DarkGray
    return $backupPath
}

function Replace-ExactlyOnce {
    param(
        [string]$Path,
        [string]$Old,
        [string]$New,
        [bool]$UseBom
    )

    $content = [System.IO.File]::ReadAllText((Join-Path (Get-Location) $Path))

    $occurrences = ([regex]::Matches($content, [regex]::Escape($Old))).Count
    if ($occurrences -eq 0) {
        Write-Host "[ERROR] Teks yang mau diganti tidak ditemukan di $Path." -ForegroundColor Red
        Write-Host "        Kemungkinan file ini sudah beda dari yang saya kira -- SAYA BERHENTI, tidak ada yang diubah di file ini." -ForegroundColor Red
        exit 1
    }
    if ($occurrences -gt 1) {
        Write-Host "[ERROR] Teks yang mau diganti muncul $occurrences kali di $Path (harusnya cuma 1)." -ForegroundColor Red
        Write-Host "        SAYA BERHENTI supaya tidak salah ganti bagian yang lain." -ForegroundColor Red
        exit 1
    }

    Backup-File -Path $Path | Out-Null

    $newContent = $content.Replace($Old, $New)
    $encoding = New-Object System.Text.UTF8Encoding($UseBom)
    [System.IO.File]::WriteAllText((Join-Path (Get-Location) $Path), $newContent, $encoding)

    Write-Host "  OK -- $Path sudah di-patch." -ForegroundColor Green
}

$editWebPath = "resources\views\pages\admin\edit-web.blade.php"
$profilPath  = "resources\views\pages\frontend\profil.blade.php"

# ------------------------------------------------------------
# P1 (properties + rename+ready)
# ------------------------------------------------------------
$ew_old_1 = @'
    public array $tentangKamiSections = [
        ['key' => 'profil-toko', 'label' => 'Profil Toko', 'icon' => 'fa-store', 'ready' => false],
        ['key' => 'sejarah', 'label' => 'Sejarah', 'icon' => 'fa-clock-rotate-left', 'ready' => false],
        ['key' => 'pengrajin', 'label' => 'Pengrajin', 'icon' => 'fa-hammer', 'ready' => false],
        ['key' => 'keberlanjutan', 'label' => 'Keberlanjutan', 'icon' => 'fa-leaf', 'ready' => false],
        ['key' => 'karier', 'label' => 'Karier', 'icon' => 'fa-briefcase', 'ready' => false],
    ];
'@

$ew_new_1 = @'
    public array $tentangKamiSections = [
        ['key' => 'profil-toko', 'label' => 'Tentang Kami', 'icon' => 'fa-store', 'ready' => true],
        ['key' => 'sejarah', 'label' => 'Sejarah', 'icon' => 'fa-clock-rotate-left', 'ready' => false],
        ['key' => 'pengrajin', 'label' => 'Pengrajin', 'icon' => 'fa-hammer', 'ready' => false],
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
'@

Replace-ExactlyOnce -Path $editWebPath -Old $ew_old_1 -New $ew_new_1 -UseBom $true

# ------------------------------------------------------------
# P2 (card description text)
# ------------------------------------------------------------
$ew_old_2 = @'
            'description' => 'Profil Toko, Sejarah, Pengrajin, Keberlanjutan, Karier.',
'@

$ew_new_2 = @'
            'description' => 'Tentang Kami, Sejarah, Pengrajin, Keberlanjutan, Karier.',
'@

Replace-ExactlyOnce -Path $editWebPath -Old $ew_old_2 -New $ew_new_2 -UseBom $true

# ------------------------------------------------------------
# P3 (profilHeroDefaults method)
# ------------------------------------------------------------
$ew_old_3 = @'
    public function mount(): void
    {
        $defaults = $this->headerDefaults();
'@

$ew_new_3 = @'
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

    public function mount(): void
    {
        $defaults = $this->headerDefaults();
'@

Replace-ExactlyOnce -Path $editWebPath -Old $ew_old_3 -New $ew_new_3 -UseBom $true

# ------------------------------------------------------------
# P4 (mount loads profilHero data)
# ------------------------------------------------------------
$ew_old_4 = @'
        $faqDefaults = $this->faqDefaults();
        $faqData = HomeSection::dataFor('faq', $faqDefaults);
        $this->faqItems = $faqData['items'];
    }
'@

$ew_new_4 = @'
        $faqDefaults = $this->faqDefaults();
        $faqData = HomeSection::dataFor('faq', $faqDefaults);
        $this->faqItems = $faqData['items'];

        $profilHeroDefaults = $this->profilHeroDefaults();
        $profilHeroData = HomeSection::dataFor('profil-toko', $profilHeroDefaults);
        $this->profilHeroEyebrow = $profilHeroData['eyebrow'];
        $this->profilHeroHeadingLine1 = $profilHeroData['heading_line1'];
        $this->profilHeroHeadingLine2 = $profilHeroData['heading_line2'];
        $this->profilHeroDescription = $profilHeroData['description'];
        $this->profilHeroFotoPathLama = $profilHeroData['image_path'];
    }
'@

Replace-ExactlyOnce -Path $editWebPath -Old $ew_old_4 -New $ew_new_4 -UseBom $true

# ------------------------------------------------------------
# P5 (preview url getter)
# ------------------------------------------------------------
$ew_old_5 = @'
    public function getHeaderImagePreviewUrlProperty(): string
    {
        if ($this->headerImageCroppedBase64) {
            return $this->headerImageCroppedBase64;
        }

        return $this->headerImagePathLama
            ? Storage::disk('public')->url($this->headerImagePathLama)
            : asset('images/admin-login/hero.png');
    }

    /** URL video yang sudah tersimpan (hasil upload dari perangkat). Null kalau belum pernah upload apapun. */
    public function getKeahlianVideoPreviewUrlProperty(): ?string
'@

$ew_new_5 = @'
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

    /** URL video yang sudah tersimpan (hasil upload dari perangkat). Null kalau belum pernah upload apapun. */
    public function getKeahlianVideoPreviewUrlProperty(): ?string
'@

Replace-ExactlyOnce -Path $editWebPath -Old $ew_old_5 -New $ew_new_5 -UseBom $true

# ------------------------------------------------------------
# P6 (saveProfilHero method)
# ------------------------------------------------------------
$ew_old_6 = @'
        HomeSection::forSection('faq')->update([
            'data' => [
                'items' => $validated['faqItems'],
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }
};
?>
'@

$ew_new_6 = @'
        HomeSection::forSection('faq')->update([
            'data' => [
                'items' => $validated['faqItems'],
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
};
?>
'@

Replace-ExactlyOnce -Path $editWebPath -Old $ew_old_6 -New $ew_new_6 -UseBom $true

# ------------------------------------------------------------
# P7 (blade form for profil-toko)
# ------------------------------------------------------------
$ew_old_7 = @'
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
'@

$ew_new_7 = @'
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
'@

Replace-ExactlyOnce -Path $editWebPath -Old $ew_old_7 -New $ew_new_7 -UseBom $true

# ------------------------------------------------------------
# P8 (Alpine cropper JS)
# ------------------------------------------------------------
$ew_old_8 = @'
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
'@

$ew_new_8 = @'
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
</script>
@endscript
'@

Replace-ExactlyOnce -Path $editWebPath -Old $ew_old_8 -New $ew_new_8 -UseBom $true

# ------------------------------------------------------------
# PP1 (php data fetch)
# ------------------------------------------------------------
$pf_old_1 = @'
    @php
        $profileSetting = \App\Models\Setting::current();
        $profileWaNumber = $profileSetting->whatsappDigits();
    @endphp
'@

$pf_new_1 = @'
    @php
        $profileSetting = \App\Models\Setting::current();
        $profileWaNumber = $profileSetting->whatsappDigits();

        // Section A (Hero) -- bisa diedit admin lewat Admin > Edit Web >
        // Tentang Kami > (tab) Tentang Kami. Lihat App\Models\HomeSection,
        // section_key 'profil-toko'. Tombol "Lihat Produk" & "Hubungi Kami"
        // TIDAK diedit di sini (link & tulisan tetap, sama seperti tombol
        // CTA Header Beranda).
        $profilHero = \App\Models\HomeSection::dataFor('profil-toko', [
            'eyebrow' => 'Tentang Kami',
            'heading_line1' => 'Mewujudkan Ruang',
            'heading_line2' => 'yang Punya Cerita.',
            'description' => $profileSetting->site_name.' menghadirkan furnitur yang dibuat dengan teliti untuk melengkapi ruang Anda \u2014 bukan sekadar mengisinya. Setiap karya dirancang untuk nyaman digunakan sekaligus enak dipandang, untuk rumah maupun ruang kerja.',
            'image_path' => null,
        ]);

        $profilHeroImageUrl = $profilHero['image_path']
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($profilHero['image_path'])
            : asset('images/admin-login/kursi.png');
    @endphp
'@

Replace-ExactlyOnce -Path $profilPath -Old $pf_old_1 -New $pf_new_1 -UseBom $true

# ------------------------------------------------------------
# PP2 (hero text)
# ------------------------------------------------------------
$pf_old_2 = @'
                <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-admin-accent">
                    <span class="h-px w-8 bg-admin-accent"></span>
                    Tentang Kami
                </div>

                <h1 class="mt-5 font-display text-4xl leading-[1.12] text-[#3D2B1F] sm:text-5xl lg:text-[3.25rem]">
                    Mewujudkan Ruang
                    <br class="hidden sm:block">
                    yang Punya Cerita.
                </h1>

                <p class="mt-6 max-w-md text-sm leading-relaxed text-[#6B6E76] sm:text-base">
                    {{ $profileSetting->site_name }} menghadirkan furnitur yang dibuat dengan
                    teliti untuk melengkapi ruang Anda — bukan sekadar mengisinya. Setiap
                    karya dirancang untuk nyaman digunakan sekaligus enak dipandang, untuk
                    rumah maupun ruang kerja.
                </p>
'@

$pf_new_2 = @'
                <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-admin-accent">
                    <span class="h-px w-8 bg-admin-accent"></span>
                    {{ $profilHero['eyebrow'] }}
                </div>

                <h1 class="mt-5 font-display text-4xl leading-[1.12] text-[#3D2B1F] sm:text-5xl lg:text-[3.25rem]">
                    {{ $profilHero['heading_line1'] }}
                    <br class="hidden sm:block">
                    {{ $profilHero['heading_line2'] }}
                </h1>

                <p class="mt-6 max-w-md text-sm leading-relaxed text-[#6B6E76] sm:text-base">
                    {{ $profilHero['description'] }}
                </p>
'@

Replace-ExactlyOnce -Path $profilPath -Old $pf_old_2 -New $pf_new_2 -UseBom $true

# ------------------------------------------------------------
# PP3 (hero image src)
# ------------------------------------------------------------
$pf_old_3 = @'
                    <img
                        src="{{ asset('images/admin-login/kursi.png') }}"
                        alt="Furniture {{ $profileSetting->site_name }}"
                        class="h-[82%] w-auto object-contain drop-shadow-2xl"
                    >
'@

$pf_new_3 = @'
                    <img
                        src="{{ $profilHeroImageUrl }}"
                        alt="Furniture {{ $profileSetting->site_name }}"
                        class="h-[82%] w-auto object-contain drop-shadow-2xl"
                    >
'@

Replace-ExactlyOnce -Path $profilPath -Old $pf_old_3 -New $pf_new_3 -UseBom $true

Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Selesai. Backup .bak-before-tentang-kami-hero-* dibuat di lokasi masing-masing file." -ForegroundColor Cyan
Write-Host " Cek: Admin > Edit Web > Tentang Kami > tab Tentang Kami, dan halaman publik /profil." -ForegroundColor Cyan
Write-Host " Tidak perlu npm run build." -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
