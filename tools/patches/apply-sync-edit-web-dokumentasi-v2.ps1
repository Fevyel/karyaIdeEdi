$ErrorActionPreference = 'Stop'

function Step($m) { Write-Host "`n$m" -ForegroundColor Cyan }

if (-not (Test-Path '.\artisan')) {
    throw "Jalankan script dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

Write-Host '=============================================================' -ForegroundColor Yellow
Write-Host ' Sinkronisasi Edit Web Dokumentasi + Pita Foto/Video' -ForegroundColor Yellow
Write-Host '=============================================================' -ForegroundColor Yellow

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = ".backup-dokumentasi-v2-$stamp"
$admin = '.\resources\views\pages\admin\edit-web.blade.php'
$front = '.\resources\views\pages\frontend\booking.blade.php'

Step '[1/6] Backup file yang akan diubah ...'
New-Item -ItemType Directory -Path "$backup\resources\views\pages\admin" -Force | Out-Null
New-Item -ItemType Directory -Path "$backup\resources\views\pages\frontend" -Force | Out-Null
Copy-Item $admin "$backup\resources\views\pages\admin\edit-web.blade.php" -Force
Copy-Item $front "$backup\resources\views\pages\frontend\booking.blade.php" -Force
Write-Host "  Backup: $backup" -ForegroundColor DarkGray

Step '[2/6] Patch Edit Web Dokumentasi ...'
$tmp = Join-Path $env:TEMP "patch-dokumentasi-v2-$stamp.php"
@'
<?php
$root = getcwd();
$adminPath = $root.'/resources/views/pages/admin/edit-web.blade.php';
$frontPath = $root.'/resources/views/pages/frontend/booking.blade.php';
$admin = file_get_contents($adminPath);
$front = file_get_contents($frontPath);
if ($admin === false || $front === false) throw new RuntimeException('Gagal membaca file target.');

function need_replace(string $s, string $old, string $new, string $label): string {
    if (strpos($s, $new) !== false && strpos($s, $old) === false) { echo "  - $label: sudah sesuai\n"; return $s; }
    if (strpos($s, $old) === false) throw new RuntimeException("Pattern tidak ditemukan: $label");
    echo "  - $label: diubah\n";
    return str_replace($old, $new, $s);
}

// 1) Sidebar Dokumentasi: Hero / Galeri Video / Galeri Foto / Pita Foto & Video
$admin = preg_replace(
    '~public array \\$dokumentasiSections = \\[.*?\\n    \\];~s',
    <<<'PHPBLOCK'
public array $dokumentasiSections = [
        ['key' => 'dokumentasi', 'label' => 'Hero', 'icon' => 'fa-image', 'ready' => true],
        ['key' => 'dokumentasi-3', 'label' => 'Galeri Video', 'icon' => 'fa-video', 'ready' => true],
        ['key' => 'dokumentasi-foto', 'label' => 'Galeri Foto', 'icon' => 'fa-images', 'ready' => true],
        ['key' => 'dokumentasi-media', 'label' => 'Pita Foto & Video', 'icon' => 'fa-film', 'ready' => true],
    ];
PHPBLOCK,
    $admin,
    1,
    $countSections
);
if ($countSections !== 1) throw new RuntimeException('Gagal mengganti daftar section Dokumentasi.');

$admin = preg_replace(
    "~'description' => 'Hero dan galeri video dokumentasi\\.',~",
    "'description' => 'Hero, Galeri Video, Galeri Foto, dan Pita Foto & Video.',",
    $admin,
    1
);

// 2) Properti baru untuk Galeri Foto dan Pita Media.
$propMarker = '    public array $dok3UploadBaru = [];';
if (strpos($admin, 'public array $dokFotoKeys = [];') === false) {
    $pos = strpos($admin, $propMarker);
    if ($pos === false) throw new RuntimeException('Marker properti dok3 tidak ditemukan.');
    $pos += strlen($propMarker);
    $props = <<<'PHPBLOCK'


    // ---- Galeri Foto Dokumentasi (khusus FOTO) ----
    public string $dokFotoJudul = 'Momen Karya dalam Bingkai';
    public string $dokFotoSubjudul = 'Galeri Foto';
    public string $dokFotoDeskripsi = 'Dokumentasi visual yang menampilkan proses, detail pengerjaan, hingga hasil akhir furnitur secara lebih dekat, bersih, dan profesional.';
    public array $dokFotoKeys = [];
    public array $dokFotoTipe = [];
    public array $dokFotoKeterangan = [];
    public array $dokFotoPathLama = [];
    public array $dokFotoUploadBaru = [];

    // ---- Pita Foto & Video Dokumentasi (jumlah TIDAK TERBATAS) ----
    public array $dokMediaKeys = [];
    public array $dokMediaTipe = [];
    public array $dokMediaKeterangan = [];
    public array $dokMediaPathLama = [];
    public array $dokMediaUploadBaru = [];
PHPBLOCK;
    $admin = substr($admin,0,$pos).$props.substr($admin,$pos);
    echo "  - Properti Galeri Foto/Pita Media: ditambahkan\n";
}

// 3) Defaults baru.
$defaultsMarker = "    /**\n     * Isi bawaan galeri Dokumentasi";
if (strpos($admin, 'private function dokumentasiFotoDefaults()') === false) {
    $pos = strpos($admin, $defaultsMarker);
    if ($pos === false) throw new RuntimeException('Marker defaults Dokumentasi tidak ditemukan.');
    $defaults = <<<'PHPBLOCK'
    private function dokumentasiFotoDefaults(): array
    {
        return [
            'judul' => 'Momen Karya dalam Bingkai',
            'subjudul' => 'Galeri Foto',
            'deskripsi' => 'Dokumentasi visual yang menampilkan proses, detail pengerjaan, hingga hasil akhir furnitur secara lebih dekat, bersih, dan profesional.',
            'items' => [],
        ];
    }

    private function dokumentasiMediaDefaults(): array
    {
        return ['items' => []];
    }

PHPBLOCK;
    $admin = substr($admin,0,$pos).$defaults.substr($admin,$pos);
    echo "  - Defaults Galeri Foto/Pita Media: ditambahkan\n";
}

// 4) Mount data baru. Galeri Foto fallback ke foto lama supaya data existing tidak hilang.
$mountMarker = "        \$this->muatItemDinamis('dok3', \$dok3Data['items'] ?? []);";
if (strpos($admin, "HomeSection::dataFor('dokumentasi-foto'") === false) {
    $pos = strpos($admin, $mountMarker);
    if ($pos === false) throw new RuntimeException('Marker mount dok3 tidak ditemukan.');
    $pos += strlen($mountMarker);
    $mount = <<<'PHPBLOCK'


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

        $dokMediaData = HomeSection::dataFor('dokumentasi-media', $this->dokumentasiMediaDefaults());
        $this->muatItemDinamis('dokMedia', is_array($dokMediaData['items'] ?? null) ? $dokMediaData['items'] : []);
PHPBLOCK;
    $admin = substr($admin,0,$pos).$mount.substr($admin,$pos);
    echo "  - Mount Galeri Foto/Pita Media: ditambahkan\n";
}

// 5) Computed preview + add/remove item.
$methodMarker = "    /**\n     * Decode data URL base64 hasil crop";
if (strpos($admin, 'getDokFotoPreviewUrlsProperty') === false) {
    $pos = strpos($admin, $methodMarker);
    if ($pos === false) throw new RuntimeException('Marker method helper tidak ditemukan.');
    $methods = <<<'PHPBLOCK'
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

    public function getDokMediaPreviewUrlsProperty(): array
    {
        return collect($this->dokMediaPathLama)->map(fn (?string $path) => $path ? Storage::disk('public')->url($path) : null)->all();
    }

    public function addDokMediaItem(): void
    {
        $key = (string) Str::uuid();
        $this->dokMediaKeys[] = $key;
        $this->dokMediaTipe[$key] = 'foto';
        $this->dokMediaKeterangan[$key] = '';
        $this->dokMediaPathLama[$key] = null;
        $this->dokMediaUploadBaru[$key] = null;
    }

    public function removeDokMediaItem(string $key): void
    {
        if (! in_array($key, $this->dokMediaKeys, true)) return;
        if ($this->dokMediaPathLama[$key] ?? null) Storage::disk('public')->delete($this->dokMediaPathLama[$key]);
        $this->dokMediaKeys = array_values(array_diff($this->dokMediaKeys, [$key]));
        unset($this->dokMediaTipe[$key], $this->dokMediaKeterangan[$key], $this->dokMediaPathLama[$key], $this->dokMediaUploadBaru[$key]);
    }

PHPBLOCK;
    $admin = substr($admin,0,$pos).$methods.substr($admin,$pos);
    echo "  - Method Galeri Foto/Pita Media: ditambahkan\n";
}

// 6) Galeri Video sekarang benar-benar VIDEO ONLY.
$admin = str_replace(
    '$rules["dok3Tipe.$key"] = [\'required\', \'in:foto,video\'];',
    '$rules["dok3Tipe.$key"] = [\'required\', \'in:video\'];',
    $admin
);
$admin = preg_replace(
    '~\\$rules\\["dok3UploadBaru\\.\\$key"\\] = \\(\\$this->dok3Tipe\\[\\$key\\] \\?\\? \'foto\'\\) === \'video\'\\s*\\? \\[(.*?)\\]\\s*:\\s*\\[\'nullable\', \'image\', \'max:8192\'\\];~s',
    '$rules["dok3UploadBaru.$key"] = [\'nullable\', \'file\', \'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime\', \'max:51200\'];',
    $admin,
    1
);
$oldExtLine = <<<'TXT'
$extension = $upload->getClientOriginalExtension() ?: ($this->dok3Tipe[$key] === 'video' ? 'mp4' : 'jpg');
TXT;
$newExtLine = <<<'TXT'
$extension = $upload->getClientOriginalExtension() ?: 'mp4';
TXT;
$admin = str_replace($oldExtLine, $newExtLine, $admin);

// 7) Hero Dokumentasi hanya menyimpan HERO. Data galeri lama dipertahankan sebagai fallback,
//    tetapi tidak lagi diedit dari tab Hero.
$saveHeroStart = strpos($admin, "    public function saveDokumentasi(): void");
$saveHeroEnd = strpos($admin, "    /**\n     * Simpan Dokumentasi 3", $saveHeroStart ?: 0);
if ($saveHeroStart === false || $saveHeroEnd === false) throw new RuntimeException('Function saveDokumentasi tidak ditemukan.');
$newSaveHero = <<<'PHPBLOCK'
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

PHPBLOCK;
$admin = substr($admin,0,$saveHeroStart).$newSaveHero.substr($admin,$saveHeroEnd);

// 8) Save Galeri Foto + Pita Media.
$saveMarker = "    /** Klik salah satu swatch preset";
if (strpos($admin, 'public function saveDokumentasiFoto()') === false) {
    $pos = strpos($admin, $saveMarker);
    if ($pos === false) throw new RuntimeException('Marker save section tidak ditemukan.');
    $saveMethods = <<<'PHPBLOCK'
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

    public function saveDokumentasiMedia(): void
    {
        $rules = [];
        foreach ($this->dokMediaKeys as $key) {
            $rules["dokMediaTipe.$key"] = ['required', 'in:foto,video'];
            $rules["dokMediaKeterangan.$key"] = ['nullable', 'string', 'max:80'];
            $rules["dokMediaUploadBaru.$key"] = ($this->dokMediaTipe[$key] ?? 'foto') === 'video'
                ? ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime', 'max:51200']
                : ['nullable', 'image', 'max:8192'];
        }
        if ($rules !== []) $this->validate($rules);
        foreach ($this->dokMediaKeys as $key) {
            $upload = $this->dokMediaUploadBaru[$key] ?? null;
            if (! $upload) continue;
            if ($this->dokMediaPathLama[$key] ?? null) Storage::disk('public')->delete($this->dokMediaPathLama[$key]);
            $ext = $upload->getClientOriginalExtension() ?: (($this->dokMediaTipe[$key] ?? 'foto') === 'video' ? 'mp4' : 'jpg');
            $this->dokMediaPathLama[$key] = $upload->storeAs('home-sections', 'dokumentasi-media-'.Str::uuid().'.'.$ext, 'public');
            $this->dokMediaUploadBaru[$key] = null;
        }
        $items = collect($this->dokMediaKeys)->map(fn ($key) => ($this->dokMediaPathLama[$key] ?? null) ? [
            'tipe' => $this->dokMediaTipe[$key] ?? 'foto', 'path' => $this->dokMediaPathLama[$key], 'keterangan' => trim((string) ($this->dokMediaKeterangan[$key] ?? '')),
        ] : null)->filter()->values()->all();
        HomeSection::forSection('dokumentasi-media')->update(['data' => ['items' => $items]]);
        session()->flash('edit-web-tersimpan', true);
    }

PHPBLOCK;
    $admin = substr($admin,0,$pos).$saveMethods.substr($admin,$pos);
    echo "  - Save Galeri Foto/Pita Media: ditambahkan\n";
}

// 9) Hapus UI galeri campuran LAMA dari tab Hero.
$oldGalleryStart = strpos($admin, '                    {{-- GALERI DOKUMENTASI');
if ($oldGalleryStart !== false) {
    $oldGalleryEnd = strpos($admin, '                    <div class="flex justify-end">', $oldGalleryStart);
    if ($oldGalleryEnd === false) throw new RuntimeException('Akhir UI galeri lama tidak ditemukan.');
    $admin = substr($admin,0,$oldGalleryStart).substr($admin,$oldGalleryEnd);
    echo "  - UI Galeri Dokumentasi lama di Hero: dihapus\n";
}

// 10) Ganti seluruh UI Galeri Video lama + sisipkan Galeri Foto + Pita Media.
$uiStart = strpos($admin, "            @elseif (\$activeSection === 'dokumentasi-3')");
$uiEnd = strpos($admin, "            @elseif (\$activeSection === 'warna')", $uiStart ?: 0);
if ($uiStart === false || $uiEnd === false) throw new RuntimeException('Blok UI Dokumentasi lanjutan tidak ditemukan.');
$newUi = <<<'BLADE'
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
                        <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Daftar Foto</p><p class="mt-1 text-xs text-admin-ink-soft">Input hanya menerima file gambar. Susunan di frontend otomatis mengikuti layout bento yang sudah dibuat.</p></div><button type="button" wire:click="addDokFotoItem" class="rounded-full border border-admin-accent px-3.5 py-2 text-xs font-semibold text-admin-accent hover:bg-admin-accent hover:text-white"><i class="fa-solid fa-plus mr-1"></i> Tambah Foto</button></div>
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
            @elseif ($activeSection === 'dokumentasi-media')
                <form wire:submit="saveDokumentasiMedia" class="space-y-6">
                    <div><h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink"><i class="fa-solid fa-film text-admin-accent"></i> Pita Foto & Video</h3><p class="text-xs text-admin-ink-soft">Pita media berjalan di bawah Galeri Foto. Boleh campur FOTO dan VIDEO, jumlah item tidak dibatasi. Geraknya dari kiri ke kanan, berlawanan arah dengan pita teks “FURNITUR TOKO MEBEL”.</p></div>
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Media Berjalan</p><p class="mt-1 text-xs text-admin-ink-soft">Setiap batas antar-media dibuat memudar/gradasi agar perpindahannya halus.</p></div><button type="button" wire:click="addDokMediaItem" class="rounded-full border border-admin-accent px-3.5 py-2 text-xs font-semibold text-admin-accent hover:bg-admin-accent hover:text-white"><i class="fa-solid fa-plus mr-1"></i> Tambah Media</button></div>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($dokMediaKeys as $urutan => $key)
                                <div wire:key="dok-media-{{ $key }}" class="space-y-3 rounded-xl border border-admin-border bg-admin-surface p-3">
                                    <div class="flex items-center justify-between"><span class="text-xs font-semibold text-admin-ink">Media {{ $urutan + 1 }}</span><button type="button" wire:click="removeDokMediaItem('{{ $key }}')" class="h-8 w-8 rounded-full text-admin-danger hover:bg-admin-danger/10"><i class="fa-solid fa-trash text-xs"></i></button></div>
                                    <div class="inline-flex gap-1 rounded-lg bg-admin-cream p-1">@foreach (['foto' => 'fa-image', 'video' => 'fa-video'] as $tipe => $icon)<button type="button" wire:click="$set('dokMediaTipe.{{ $key }}', '{{ $tipe }}')" class="rounded-md px-3 py-1.5 text-[11px] font-semibold {{ ($dokMediaTipe[$key] ?? 'foto') === $tipe ? 'bg-admin-panel text-white' : 'text-admin-ink-soft' }}"><i class="fa-solid {{ $icon }} mr-1"></i>{{ ucfirst($tipe) }}</button>@endforeach</div>
                                    @if ($this->dokMediaPreviewUrls[$key] ?? null) @if (($dokMediaTipe[$key] ?? 'foto') === 'video')<video src="{{ $this->dokMediaPreviewUrls[$key] }}" class="aspect-video w-full rounded-lg bg-black object-cover" autoplay muted loop playsinline></video>@else<img src="{{ $this->dokMediaPreviewUrls[$key] }}" class="aspect-video w-full rounded-lg object-cover" alt="Media {{ $urutan + 1 }}">@endif @endif
                                    <input type="file" wire:model="dokMediaUploadBaru.{{ $key }}" accept="{{ ($dokMediaTipe[$key] ?? 'foto') === 'video' ? 'video/mp4,video/webm,video/ogg,video/quicktime' : 'image/*' }}" class="block w-full text-xs file:mr-2 file:rounded-full file:border-0 file:bg-admin-accent file:px-3 file:py-1.5 file:text-white">
                                    @error("dokMediaUploadBaru.$key")<p class="text-[11px] text-red-600">{{ $message }}</p>@enderror
                                    <input type="text" maxlength="80" wire:model="dokMediaKeterangan.{{ $key }}" placeholder="Keterangan (opsional)" class="w-full rounded-md border border-admin-border bg-admin-surface px-2.5 py-2 text-xs">
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex justify-end"><button type="submit" class="rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white"><i class="fa-solid fa-floppy-disk mr-2"></i>Simpan Pita Media</button></div>
                </form>
BLADE;
$admin = substr($admin,0,$uiStart).$newUi.substr($admin,$uiEnd);

// ================= FRONTEND =================
// Ganti seluruh blok Galeri Foto premium sampai sebelum footer. Video di atasnya TIDAK disentuh.
$photoMarker = strpos($front, '// GALERI FOTO PREMIUM');
if ($photoMarker === false) throw new RuntimeException('Marker GALERI FOTO PREMIUM tidak ditemukan di frontend.');
$frontStart = strrpos(substr($front,0,$photoMarker), '    @php');
$frontEnd = strpos($front, "    @include('partials.frontend.footer')", $photoMarker);
if ($frontStart === false || $frontEnd === false) throw new RuntimeException('Batas blok Galeri Foto frontend tidak ditemukan.');

$newFront = <<<'BLADE'
    @php
        // GALERI FOTO PREMIUM — data baru section_key 'dokumentasi-foto'.
        // Fallback ke galeri lama hanya untuk menjaga foto existing sebelum
        // admin pertama kali menekan Simpan di tab Galeri Foto yang baru.
        $dokFotoSection = \App\Models\HomeSection::dataFor('dokumentasi-foto', [
            'judul' => 'Momen Karya dalam Bingkai',
            'subjudul' => 'Galeri Foto',
            'deskripsi' => 'Dokumentasi visual yang menampilkan proses, detail pengerjaan, hingga hasil akhir furnitur secara lebih dekat, bersih, dan profesional.',
            'items' => [],
        ]);
        $dokPhotoBentukItem = function (array $item) {
            $src = isset($item['path']) && $item['path'] ? \Illuminate\Support\Facades\Storage::disk('public')->url($item['path']) : ($item['url'] ?? null);
            if (! $src || ($item['tipe'] ?? null) !== 'foto') return null;
            return ['src' => $src, 'keterangan' => trim((string) ($item['keterangan'] ?? ''))];
        };
        $dokPhotoItems = array_values(array_filter(array_map($dokPhotoBentukItem, is_array($dokFotoSection['items'] ?? null) ? $dokFotoSection['items'] : [])));
        if ($dokPhotoItems === []) {
            $dokPhotoItems = array_values(array_filter(array_map($dokPhotoBentukItem, is_array($dokumentasiHero['galeri'] ?? null) ? $dokumentasiHero['galeri'] : [])));
        }
        $dokPhotoCount = count($dokPhotoItems);
    @endphp

    @if ($dokPhotoCount > 0)
        <section class="relative overflow-hidden border-t border-[#E7DCCF] bg-white py-18 sm:py-20 lg:py-24">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-20 bg-linear-to-b from-[#F6EFE6]/85 to-transparent"></div>
            <div x-data="{open:false,activeSrc:'',activeTitle:'',activeNumber:'',show(src,title,n){this.activeSrc=src;this.activeTitle=title;this.activeNumber=n;this.open=true;document.body.classList.add('overflow-hidden')},close(){this.open=false;document.body.classList.remove('overflow-hidden')}}" x-on:keydown.escape.window="close()" class="relative mx-auto max-w-7xl px-6 sm:px-8 lg:px-10">
                <div class="grid gap-8 lg:grid-cols-[minmax(0,1.05fr)_21rem] lg:items-end">
                    <div><div class="mb-4 flex items-center gap-3"><span class="h-px w-10 bg-[#C39058]"></span><p class="text-[11px] font-semibold uppercase tracking-[0.42em] text-[#BC8651] sm:text-xs">{{ $dokFotoSection['subjudul'] }}</p></div><h2 class="font-display text-4xl font-semibold leading-none text-[#3D2B1F] sm:text-5xl lg:text-[3.8rem]">{{ $dokFotoSection['judul'] }}</h2><p class="mt-6 max-w-2xl text-base leading-9 text-[#6E6357] sm:text-lg">{{ $dokFotoSection['deskripsi'] }}</p></div>
                    <div class="rounded-4xl border border-[#E2D2BF] bg-[#FCFAF7] p-5 shadow-[0_18px_50px_-38px_rgba(61,43,31,0.35)]"><div class="flex items-center gap-4"><span class="flex h-12 w-12 items-center justify-center rounded-full bg-[#4B2F1F] text-white"><i class="fa-solid fa-camera-retro"></i></span><div><p class="text-[10px] font-semibold uppercase tracking-[0.28em] text-[#AA8E71]">Portofolio</p><p class="text-lg font-semibold text-[#3D2B1F]">{{ $dokPhotoCount }} Foto Pilihan</p></div></div><div class="mt-4 h-px bg-linear-to-r from-[#E1D2BF] to-transparent"></div><p class="mt-4 text-sm leading-7 text-[#7A6B5E]">Disusun seperti editorial gallery agar dokumentasi terasa estetik, mewah, dan profesional.</p></div>
                </div>
                <div class="mt-10 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4" style="grid-auto-rows:220px;">
                    @foreach ($dokPhotoItems as $i => $photo)
                        @php
                            $n = str_pad($i + 1, 2, '0', STR_PAD_LEFT); $title = $photo['keterangan'] ?: 'Foto Dokumentasi '.$n; $sisa = $dokPhotoCount % 4;
                            $isLast = $i === $dokPhotoCount - 1;
                            $layout = match ($i % 6) {0 => 'md:col-span-2 md:row-span-2',1 => 'xl:row-span-2',2 => '',3 => '',4 => 'md:col-span-2',default => ''};
                            if ($isLast && $sisa === 1) $layout = 'md:col-span-2 xl:col-span-4 md:row-span-2';
                        @endphp
                        <button type="button" x-on:click="show(@js($photo['src']),@js($title),@js($n))" class="group relative {{ $layout }} overflow-hidden rounded-4xl border border-[#E7DACB] bg-[#F3E6D5] text-left shadow-[0_22px_60px_-40px_rgba(61,43,31,0.4)] transition duration-300 hover:-translate-y-1">
                            <img src="{{ $photo['src'] }}" alt="{{ $title }}" class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-[1.05]" loading="lazy"><div class="absolute inset-0 bg-linear-to-t from-[#140D08]/88 via-[#140D08]/22 to-transparent"></div>
                            <div class="absolute left-4 right-4 top-4 flex justify-between"><span class="rounded-full border border-white/18 bg-black/24 px-3 py-2 text-xs font-semibold text-white backdrop-blur">{{ $n }}</span><span class="rounded-full border border-white/15 bg-white/10 px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-white backdrop-blur"><i class="fa-solid fa-image mr-1"></i> Foto</span></div>
                            <div class="absolute inset-x-0 bottom-0 p-5 sm:p-6"><p class="text-[11px] font-semibold uppercase tracking-[0.34em] text-[#E8C692]">Dokumentasi Visual</p><h3 class="mt-2 line-clamp-2 text-xl font-semibold text-white">{{ $title }}</h3></div>
                        </button>
                    @endforeach
                </div>
                <div class="mt-8 flex items-center justify-center gap-4 text-sm text-[#9A7E61]"><span class="hidden h-px w-14 bg-[#D9C9B4] sm:block"></span><p>Klik foto untuk melihat dalam ukuran lebih besar</p><span class="hidden h-px w-14 bg-[#D9C9B4] sm:block"></span></div>
                <div x-show="open" x-transition.opacity x-cloak class="fixed inset-0 z-100 flex items-center justify-center bg-[#120C08]/80 px-4 py-6 backdrop-blur-sm"><div class="absolute inset-0" x-on:click="close()"></div><div class="relative z-10 w-full max-w-6xl overflow-hidden rounded-4xl bg-[#17110E]"><div class="flex items-center justify-between border-b border-white/10 px-6 py-4"><div><p class="text-[11px] uppercase tracking-[0.3em] text-[#D4B083]" x-text="'Foto '+activeNumber"></p><h3 class="mt-1 text-xl font-semibold text-white" x-text="activeTitle"></h3></div><button type="button" x-on:click="close()" class="h-11 w-11 rounded-full border border-white/12 text-white"><i class="fa-solid fa-xmark"></i></button></div><div class="bg-[#120C08] p-4"><img x-bind:src="activeSrc" x-bind:alt="activeTitle" class="max-h-[78vh] w-full rounded-3xl object-contain"></div></div></div>
            </div>
        </section>
    @endif

    @php
        // PITA FOTO & VIDEO — section_key 'dokumentasi-media'. Jumlah bebas.
        $dokMediaData = \App\Models\HomeSection::dataFor('dokumentasi-media', ['items' => []]);
        $dokMediaItems = collect(is_array($dokMediaData['items'] ?? null) ? $dokMediaData['items'] : [])->filter(fn ($x) => is_array($x) && !empty($x['path']))->map(fn ($x) => [
            'tipe' => ($x['tipe'] ?? 'foto') === 'video' ? 'video' : 'foto',
            'src' => \Illuminate\Support\Facades\Storage::disk('public')->url($x['path']),
            'keterangan' => trim((string) ($x['keterangan'] ?? '')),
        ])->values()->all();
        if ($dokMediaItems === []) {
            $dokMediaItems = array_merge(
                array_map(fn ($x) => ['tipe'=>'foto','src'=>$x['src'],'keterangan'=>$x['keterangan']], array_slice($dokPhotoItems,0,6)),
                array_map(fn ($x) => ['tipe'=>'video','src'=>$x['src'],'keterangan'=>$x['keterangan']], array_slice($dokVideoItems ?? [],0,4))
            );
        }
        $dokMediaRepeat = $dokMediaItems;
    @endphp

    @if (count($dokMediaItems) > 0)
        <section class="kie-docmedia" aria-label="Pita dokumentasi foto dan video">
            <div class="kie-docmedia__fade kie-docmedia__fade--left"></div><div class="kie-docmedia__fade kie-docmedia__fade--right"></div>
            <div class="kie-docmedia__track">
                @foreach ([1,2] as $copy)
                    <div class="kie-docmedia__group">
                        @foreach ($dokMediaRepeat as $item)
                            <figure class="kie-docmedia__item">
                                @if ($item['tipe'] === 'video')<video src="{{ $item['src'] }}" autoplay muted loop playsinline preload="metadata"></video><span class="kie-docmedia__badge"><i class="fa-solid fa-play"></i> VIDEO</span>@else<img src="{{ $item['src'] }}" alt="{{ $item['keterangan'] ?: 'Dokumentasi foto' }}" loading="lazy"><span class="kie-docmedia__badge"><i class="fa-solid fa-image"></i> FOTO</span>@endif
                                @if ($item['keterangan'])<figcaption>{{ $item['keterangan'] }}</figcaption>@endif
                            </figure>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>
        <style>
            .kie-docmedia{position:relative;overflow:hidden;width:100%;background:linear-gradient(90deg,#2b1b12,#4a2d1d 48%,#2b1b12);padding:.65rem 0;isolation:isolate}
            .kie-docmedia__track{display:flex;width:max-content;animation:kie-docmedia-ltr 75s linear infinite;will-change:transform}.kie-docmedia:hover .kie-docmedia__track{animation-play-state:paused}.kie-docmedia__group{display:flex;flex-shrink:0;align-items:center}
            .kie-docmedia__item{position:relative;flex:0 0 clamp(230px,22vw,350px);height:clamp(120px,12vw,175px);overflow:hidden;margin:0 -.45rem;background:#2b1b12;-webkit-mask-image:linear-gradient(to right,transparent 0,#000 9%,#000 91%,transparent 100%);mask-image:linear-gradient(to right,transparent 0,#000 9%,#000 91%,transparent 100%)}
            .kie-docmedia__item img,.kie-docmedia__item video{width:100%;height:100%;object-fit:cover;display:block;transition:transform .6s ease,filter .4s ease}.kie-docmedia__item:hover img,.kie-docmedia__item:hover video{transform:scale(1.045);filter:saturate(1.06)}
            .kie-docmedia__item:after{content:'';position:absolute;inset:0;background:linear-gradient(to top,rgba(20,12,8,.72),transparent 55%);pointer-events:none}.kie-docmedia__badge{position:absolute;z-index:2;top:.75rem;right:1.1rem;border:1px solid rgba(255,255,255,.2);background:rgba(20,12,8,.42);color:#fff;border-radius:999px;padding:.35rem .65rem;font-size:.62rem;font-weight:700;letter-spacing:.14em;backdrop-filter:blur(8px)}
            .kie-docmedia__item figcaption{position:absolute;z-index:2;left:1.15rem;right:1.15rem;bottom:.85rem;color:#fff;font-size:.78rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.kie-docmedia__fade{position:absolute;z-index:4;top:0;bottom:0;width:9vw;pointer-events:none}.kie-docmedia__fade--left{left:0;background:linear-gradient(to right,#2b1b12,transparent)}.kie-docmedia__fade--right{right:0;background:linear-gradient(to left,#2b1b12,transparent)}
            @keyframes kie-docmedia-ltr{from{transform:translateX(-50%)}to{transform:translateX(0)}}@media(prefers-reduced-motion:reduce){.kie-docmedia__track{animation:none}}
        </style>
    @endif

BLADE;
$front = substr($front,0,$frontStart).$newFront.substr($front,$frontEnd);

file_put_contents($adminPath,$admin);
file_put_contents($frontPath,$front);
echo "PATCH_OK\n";
'@ | Set-Content -Path $tmp -Encoding UTF8

php $tmp
Remove-Item $tmp -Force

Step '[3/6] Validasi syntax PHP ...'
php -l $admin | Out-Host
php -l $front | Out-Host

Step '[4/6] Bersihkan cache view ...'
php artisan view:clear | Out-Host

Step '[5/6] Ringkasan ...'
Write-Host '  Edit Web > Dokumentasi sekarang:' -ForegroundColor DarkGray
Write-Host '   - Hero                : teks + video hero yang sudah ada' -ForegroundColor DarkGray
Write-Host '   - Galeri Video        : input VIDEO saja' -ForegroundColor DarkGray
Write-Host '   - Galeri Foto         : input FOTO saja' -ForegroundColor DarkGray
Write-Host '   - Pita Foto & Video   : campuran foto/video, jumlah tidak terbatas' -ForegroundColor DarkGray
Write-Host '  Frontend:' -ForegroundColor DarkGray
Write-Host '   - Galeri foto tetap bento premium' -ForegroundColor DarkGray
Write-Host '   - Pita media baru bergerak KIRI -> KANAN' -ForegroundColor DarkGray
Write-Host '   - Antar-media memakai fade/gradasi supaya perpindahan halus' -ForegroundColor DarkGray

Step '[6/6] Selesai'
Write-Host 'SELESAI' -ForegroundColor Green
Write-Host 'Jalankan npm run build lalu Ctrl+F5 di browser.' -ForegroundColor Yellow
