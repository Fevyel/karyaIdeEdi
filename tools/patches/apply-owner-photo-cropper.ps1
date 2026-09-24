$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path '.\artisan')) {
    throw "Jalankan script ini dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$adminPath = '.\resources\views\pages\admin\edit-web.blade.php'
if (-not (Test-Path $adminPath)) {
    throw "File tidak ditemukan: $adminPath"
}

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = "$adminPath.bak-owner-crop-$stamp"

Write-Host '=============================================================' -ForegroundColor Yellow
Write-Host ' Samakan Foto Pemilik dengan Cropper Admin 4:5' -ForegroundColor Yellow
Write-Host '=============================================================' -ForegroundColor Yellow

Step '[1/5] Backup file ...'
Copy-Item $adminPath $backup -Force
Write-Host "  Backup: $backup" -ForegroundColor DarkGray

Step '[2/5] Terapkan cropper Foto Pemilik ...'
$tmp = Join-Path $env:TEMP "patch-owner-crop-$stamp.php"

@'
<?php
$path = getcwd() . DIRECTORY_SEPARATOR . 'resources/views/pages/admin/edit-web.blade.php';
$admin = file_get_contents($path);
if ($admin === false) throw new RuntimeException('Gagal membaca edit-web.blade.php');

function mustContain(string $text, string $needle, string $label): void {
    if (strpos($text, $needle) === false) {
        throw new RuntimeException("Marker tidak ditemukan: {$label}. Script berhenti agar tidak merusak file.");
    }
}

// 1) State hasil crop base64.
if (strpos($admin, 'public ?string $ourCraftsmenOwnerPhotoCroppedBase64') === false) {
    $needle = '    public ?string $ourCraftsmenOwnerPhotoPathLama = null;';
    mustContain($admin, $needle, 'property foto pemilik');
    $admin = str_replace(
        $needle,
        $needle . "\n\n    /** Hasil crop portrait 4:5. Foto baru WAJIB melewati cropper sebelum disimpan. */\n    public ?string \$ourCraftsmenOwnerPhotoCroppedBase64 = null;",
        $admin
    );
    echo "  - state hasil crop: ditambahkan\n";
} else {
    echo "  - state hasil crop: sudah ada\n";
}

// 2) Preview: hasil crop baru -> file tersimpan lama.
$previewPattern = '~    public function getOurCraftsmenOwnerPhotoPreviewUrlProperty\(\): \?string\s*\{.*?\n    \}\n\n    public function removeOurCraftsmenOwnerPhoto~s';
$previewReplacement = <<<'BLADE'
    public function getOurCraftsmenOwnerPhotoPreviewUrlProperty(): ?string
    {
        if ($this->ourCraftsmenOwnerPhotoCroppedBase64) {
            return $this->ourCraftsmenOwnerPhotoCroppedBase64;
        }

        return $this->ourCraftsmenOwnerPhotoPathLama
            ? Storage::disk('public')->url($this->ourCraftsmenOwnerPhotoPathLama)
            : null;
    }

    public function removeOurCraftsmenOwnerPhoto
BLADE;
if (preg_match($previewPattern, $admin)) {
    $admin = preg_replace($previewPattern, $previewReplacement, $admin, 1);
    echo "  - preview foto: memakai hasil crop\n";
} elseif (strpos($admin, 'if ($this->ourCraftsmenOwnerPhotoCroppedBase64)') === false) {
    throw new RuntimeException('Method preview Foto Pemilik tidak ditemukan.');
}

// 3) Reset crop ketika foto dihapus.
$removeStart = strpos($admin, '    public function removeOurCraftsmenOwnerPhoto(): void');
$removeEnd = $removeStart !== false ? strpos($admin, '    public function selectOurCraftsmenOwnerBgPreset', $removeStart) : false;
if ($removeStart === false || $removeEnd === false) {
    throw new RuntimeException('Method remove Foto Pemilik tidak ditemukan.');
}
$removeBlock = substr($admin, $removeStart, $removeEnd - $removeStart);
if (strpos($removeBlock, '$this->ourCraftsmenOwnerPhotoCroppedBase64 = null;') === false) {
    $removeBlock = str_replace(
        "        \$this->ourCraftsmenOwnerPhotoUpload = null;",
        "        \$this->ourCraftsmenOwnerPhotoUpload = null;\n        \$this->ourCraftsmenOwnerPhotoCroppedBase64 = null;",
        $removeBlock
    );
    $admin = substr($admin, 0, $removeStart) . $removeBlock . substr($admin, $removeEnd);
    echo "  - reset hasil crop saat hapus foto: ditambahkan\n";
}

// 4) Save: jangan lagi menyimpan upload mentah. Simpan JPEG hasil crop 4:5.
$saveStart = strpos($admin, '    public function saveOurCraftsmenOwner(): void');
$saveEnd = $saveStart !== false ? strpos($admin, '    public function saveOurCraftsmenCta(): void', $saveStart) : false;
if ($saveStart === false || $saveEnd === false) {
    throw new RuntimeException('Method saveOurCraftsmenOwner tidak ditemukan.');
}
$save = substr($admin, $saveStart, $saveEnd - $saveStart);
$save = str_replace(
    "            'ourCraftsmenOwnerPhotoUpload' => ['nullable', 'image', 'max:8192'],",
    "            'ourCraftsmenOwnerPhotoCroppedBase64' => ['nullable', 'string'],",
    $save
);

if (strpos($save, '$imagePath = $this->ourCraftsmenOwnerPhotoPathLama;') === false) {
    $uploadPattern = '~\n        if \(\$this->ourCraftsmenOwnerPhotoUpload\) \{.*?\n        \}\n~s';
    if (! preg_match($uploadPattern, $save)) {
        throw new RuntimeException('Blok penyimpanan upload mentah Foto Pemilik tidak ditemukan.');
    }
    $cropSave = <<<'BLADE'

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
BLADE;
    $save = preg_replace($uploadPattern, $cropSave . "\n", $save, 1);
}
$save = str_replace(
    "                'photo_path' => \$this->ourCraftsmenOwnerPhotoPathLama,",
    "                'photo_path' => \$imagePath,",
    $save
);
$admin = substr($admin, 0, $saveStart) . $save . substr($admin, $saveEnd);
echo "  - save Foto Pemilik: diwajibkan dari hasil crop\n";

// 5) Ganti UI upload langsung dengan alur cropper yang sama seperti foto admin lain.
$ownerSection = strpos($admin, "@elseif (\$activeSection === 'our-craftsmen-daftar')");
if ($ownerSection === false) throw new RuntimeException('Section UI Pemilik & Founder tidak ditemukan.');
$photoStart = strpos($admin, '<div class="space-y-3 rounded-xl border border-admin-border p-4">', $ownerSection);
$identityStart = $photoStart !== false ? strpos($admin, '<div class="space-y-4 rounded-xl border border-admin-border p-4">', $photoStart) : false;
if ($photoStart === false || $identityStart === false) {
    throw new RuntimeException('Panel Foto Pemilik tidak ditemukan dengan aman.');
}

$newPhotoUi = <<<'BLADE'
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

BLADE;
$admin = substr($admin, 0, $photoStart) . $newPhotoUi . substr($admin, $identityStart);
echo "  - UI Foto Pemilik: disamakan dengan cropper admin lain\n";

// 6) Alpine cropper 4:5, struktur sama dengan cropper Sejarah.
if (strpos($admin, "Alpine.data('ourCraftsmenOwnerPhotoCropper'") === false) {
    $jsMarker = "    Alpine.data('sejarahFotoCropper'";
    $jsPos = strpos($admin, $jsMarker);
    if ($jsPos === false) throw new RuntimeException('Marker Alpine sejarahFotoCropper tidak ditemukan.');

    $ownerCropperJs = <<<'JS'
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
JS;
    $admin = substr($admin, 0, $jsPos) . $ownerCropperJs . "\n" . substr($admin, $jsPos);
    echo "  - Alpine cropper 4:5 Foto Pemilik: ditambahkan\n";
} else {
    echo "  - Alpine cropper Foto Pemilik: sudah ada\n";
}

file_put_contents($path, $admin);
echo "PATCH_OK\n";
'@ | Set-Content -Path $tmp -Encoding UTF8

php $tmp
Remove-Item $tmp -Force

Step '[3/5] Validasi syntax PHP/Blade ...'
php -l $adminPath | Out-Host

Step '[4/5] Bersihkan cache view ...'
php artisan view:clear | Out-Host

Step '[5/5] Selesai ...'
Write-Host 'SELESAI' -ForegroundColor Green
Write-Host 'Foto Pemilik sekarang WAJIB melalui crop 4:5 sebelum disimpan.' -ForegroundColor Green
Write-Host 'Upload mentah langsung sudah tidak dipakai untuk Foto Pemilik.' -ForegroundColor DarkGray
Write-Host 'Tidak ada bagian frontend atau database yang diubah.' -ForegroundColor DarkGray
