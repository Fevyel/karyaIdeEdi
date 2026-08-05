<#
    apply-kategori-cropper-wysiwyg.ps1

    Jalankan dari DALAM folder project (folder yang berisi file "artisan").
    Contoh:
        cd C:\xampp\htdocs\karyaIdeEdi
        powershell -ExecutionPolicy Bypass -File .\apply-kategori-cropper-wysiwyg.ps1

    Script ini HANYA mengubah SATU file:
      resources/views/pages/admin/kategori-form.blade.php

    Perubahan (patch tertarget, BUKAN menimpa seluruh file):
      1. Preview kecil di form: dari kotak 1:1 (h-24 w-24) menjadi
         rasio 4:5 (aspect-4/5 w-24) -- sama seperti card di frontend.
      2. Teks deskripsi: dari "rasio 1:1" menjadi "rasio 4:5".
      3. Viewport modal crop: dari kotak tetap 320x320px menjadi
         responsif "aspect-4/5 w-full max-w-[280px]".
      4. Logic Alpine.js: VIEW/OUT (persegi) diganti jadi viewW/viewH
         yang DIUKUR LANGSUNG dari elemen sungguhan di layar (getBoundingClientRect),
         dengan scale "cover-fit" (Math.max, bukan Math.min) supaya benar
         untuk area persegi panjang. Output tetap 800x1000 (rasio 4:5).

    Tidak menyentuh cropper lain (logo di Pengaturan, thumbnail di Produk),
    migration, model, route, atau file lain sama sekali.

    Aman dijalankan berkali-kali (idempotent) -- kalau patch sudah pernah
    diterapkan, script akan mendeteksinya dan tidak melakukan apa-apa lagi.
#>

$ErrorActionPreference = "Stop"

if (-not (Test-Path ".\artisan")) {
    Write-Host "ERROR: File 'artisan' tidak ditemukan di folder ini." -ForegroundColor Red
    Write-Host "Pastikan kamu menjalankan script ini dari dalam folder root project Laravel." -ForegroundColor Red
    exit 1
}

$targetPath = "resources\views\pages\admin\kategori-form.blade.php"

if (-not (Test-Path $targetPath)) {
    Write-Host "ERROR: File tidak ditemukan: $targetPath" -ForegroundColor Red
    Write-Host "Pastikan fitur Kategori (kategori-form) sudah ada di project ini." -ForegroundColor Red
    exit 1
}

$raw = [System.IO.File]::ReadAllText((Join-Path (Get-Location) $targetPath))

if ($raw.Contains("ASPECT_W: 4")) {
    Write-Host "Patch sudah pernah diterapkan sebelumnya - tidak ada yang diubah." -ForegroundColor Yellow
    exit 0
}

function Apply-Patch {
    param(
        [string]$Content,
        [string]$Old,
        [string]$New,
        [string]$Label
    )
    if (-not $Content.Contains($Old)) {
        Write-Host "  [SKIP] Blok '$Label' tidak ditemukan persis seperti yang diharapkan." -ForegroundColor Red
        Write-Host "         Kemungkinan file sudah diubah manual. Patch dibatalkan, tidak ada file yang ditimpa." -ForegroundColor Red
        exit 1
    }
    Write-Host "  [OK] Menerapkan patch: $Label" -ForegroundColor Green
    return $Content.Replace($Old, $New)
}

Write-Host ""
Write-Host "== Menambal resources/views/pages/admin/kategori-form.blade.php ==" -ForegroundColor Cyan

# ---------- Patch 1: preview kecil di form ----------
$old1 = @'
            <div class="flex flex-col items-center gap-5 sm:flex-row">
                <div class="relative shrink-0">
                    <template x-if="previewUrl">
                        <img :src="previewUrl" alt="Preview cover" class="h-24 w-24 rounded-2xl object-cover ring-4 ring-admin-cream">
                    </template>
                    <template x-if="!previewUrl">
                        <span class="flex h-24 w-24 items-center justify-center rounded-2xl bg-admin-cream text-admin-ink-soft ring-4 ring-admin-cream">
                            <i class="fa-solid fa-tags text-2xl"></i>
                        </span>
                    </template>

                    <label
                        for="cover_input"
                        class="absolute -bottom-1 -right-1 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-admin-accent text-white shadow-sm ring-2 ring-admin-surface transition hover:bg-admin-accent-strong"
                        title="Pilih foto"
                    >
                        <i class="fa-solid fa-camera text-xs"></i>
                    </label>
                    <input x-ref="fileInput" id="cover_input" type="file" accept="image/*" class="hidden" x-on:change="onFileChange($event)">
                </div>

                <div class="text-center sm:text-left">
                    <p class="text-sm font-medium text-admin-ink" x-text="previewUrl ? 'Foto siap disimpan' : 'Belum ada cover kategori'"></p>
                    <p class="mt-1 text-xs text-admin-ink-soft">
                        Format JPG/PNG, hasil crop rasio 1:1. Ditampilkan di section "Produk Berdasarkan Kategori" pada halaman utama.
                    </p>
                </div>
            </div>
'@

$new1 = @'
            <div class="flex flex-col items-center gap-5 sm:flex-row">
                {{-- Preview mengikuti rasio card kategori di frontend (aspect-4/5),
                     BUKAN kotak 1:1 -- supaya admin melihat proporsi asli, sama
                     persis seperti section "Produk Berdasarkan Kategori" di Beranda. --}}
                <div class="relative w-24 shrink-0">
                    <template x-if="previewUrl">
                        <img :src="previewUrl" alt="Preview cover" class="aspect-4/5 w-24 rounded-2xl object-cover ring-4 ring-admin-cream">
                    </template>
                    <template x-if="!previewUrl">
                        <span class="flex aspect-4/5 w-24 items-center justify-center rounded-2xl bg-admin-cream text-admin-ink-soft ring-4 ring-admin-cream">
                            <i class="fa-solid fa-tags text-2xl"></i>
                        </span>
                    </template>

                    <label
                        for="cover_input"
                        class="absolute -bottom-1 -right-1 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-admin-accent text-white shadow-sm ring-2 ring-admin-surface transition hover:bg-admin-accent-strong"
                        title="Pilih foto"
                    >
                        <i class="fa-solid fa-camera text-xs"></i>
                    </label>
                    <input x-ref="fileInput" id="cover_input" type="file" accept="image/*" class="hidden" x-on:change="onFileChange($event)">
                </div>

                <div class="text-center sm:text-left">
                    <p class="text-sm font-medium text-admin-ink" x-text="previewUrl ? 'Foto siap disimpan' : 'Belum ada cover kategori'"></p>
                    <p class="mt-1 text-xs text-admin-ink-soft">
                        Format JPG/PNG, hasil crop mengikuti rasio 4:5 -- persis seperti card kategori yang tampil di section "Produk Berdasarkan Kategori" pada halaman utama.
                    </p>
                </div>
            </div>
'@

$content = Apply-Patch -Content $raw -Old $old1 -New $new1 -Label "preview kecil (4:5)"

# ---------- Patch 2: deskripsi + viewport modal ----------
$old2 = @'
                <h4 class="mb-1 text-sm font-semibold text-admin-ink">Sesuaikan Cover Kategori</h4>
                <p class="mb-4 text-xs text-admin-ink-soft">Geser gambar untuk memindah, gunakan slider untuk zoom. Hasil crop selalu rasio 1:1.</p>

                <div
                    x-ref="viewport"
                    class="relative mx-auto h-80 w-80 cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none"
                    x-on:pointerdown="startDrag($event)"
                    x-on:pointermove="onDrag($event)"
                    x-on:pointerup="endDrag()"
                    x-on:pointerleave="endDrag()"
                >
'@

$new2 = @'
                <h4 class="mb-1 text-sm font-semibold text-admin-ink">Sesuaikan Cover Kategori</h4>
                <p class="mb-4 text-xs text-admin-ink-soft">Geser gambar untuk memindah, gunakan slider untuk zoom. Hasil crop mengikuti proporsi card kategori (4:5) -- sama persis seperti tampilan di halaman utama.</p>

                <div
                    x-ref="viewport"
                    class="relative mx-auto aspect-4/5 w-full max-w-[280px] cursor-move touch-none overflow-hidden rounded-2xl border-2 border-admin-accent bg-admin-cream select-none"
                    x-on:pointerdown="startDrag($event)"
                    x-on:pointermove="onDrag($event)"
                    x-on:pointerup="endDrag()"
                    x-on:pointerleave="endDrag()"
                >
'@

$content = Apply-Patch -Content $content -Old $old2 -New $new2 -Label "deskripsi + viewport modal (responsif 4:5)"

# ---------- Patch 3: logic Alpine.js (bagian paling penting) ----------
$old3 = @'
        dragStartX: 0,
        dragStartY: 0,
        startPosX: 0,
        startPosY: 0,
        VIEW: 320,
        OUT: 800,

        init() {
            // Kunci scroll halaman belakang selama modal crop terbuka.
            this.$watch('open', (isOpen) => {
                document.body.style.overflow = isOpen ? 'hidden' : '';
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

            this.minScale = this.VIEW / Math.min(this.natW, this.natH);
            this.maxScale = this.minScale * 3;
            this.scale = this.minScale;
            this.zoomPercent = 0;

            // pusatkan gambar di tengah viewport
            this.posX = (this.VIEW - this.natW * this.scale) / 2;
            this.posY = (this.VIEW - this.natH * this.scale) / 2;
        },

        clampPos() {
            const w = this.natW * this.scale;
            const h = this.natH * this.scale;
            this.posX = Math.min(0, Math.max(this.VIEW - w, this.posX));
            this.posY = Math.min(0, Math.max(this.VIEW - h, this.posY));
        },
'@

$new3 = @'
        dragStartX: 0,
        dragStartY: 0,
        startPosX: 0,
        startPosY: 0,

        // Rasio crop = rasio card kategori di frontend (lihat aspect-4/5
        // pada resources/views/partials/frontend/categories.blade.php).
        // JANGAN diubah jadi 1:1 lagi -- kalau rasio card frontend berubah,
        // ubah juga class "aspect-4/5" di viewport modal ini + di sini.
        ASPECT_W: 4,
        ASPECT_H: 5,

        // Ukuran viewport (area crop) diukur langsung dari elemen yang
        // benar-benar dirender di layar (bukan angka tetap), supaya selalu
        // akurat di semua ukuran layar (desktop/tablet/mobile) dan preview
        // benar-benar identik dengan hasil akhir (WYSIWYG).
        viewW: 0,
        viewH: 0,

        // Resolusi file output tetap tinggi & konsisten rasio 4:5,
        // terlepas dari ukuran viewport di layar.
        OUT_W: 800,
        OUT_H: 1000,

        init() {
            // Kunci scroll halaman belakang selama modal crop terbuka.
            this.$watch('open', (isOpen) => {
                document.body.style.overflow = isOpen ? 'hidden' : '';
            });

            // Kalau layar di-resize/rotate saat modal masih terbuka,
            // ukur ulang viewport & sesuaikan posisi supaya tetap valid.
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

            // Tunggu satu tick supaya modal (x-show) sudah selesai dirender
            // sebelum mengukur ukuran viewport sesungguhnya di layar.
            this.$nextTick(() => {
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.viewW = rect.width;
                this.viewH = rect.height;

                // "Cover fit": skala minimum yang membuat gambar menutupi
                // SELURUH area crop persegi panjang, apa pun orientasi foto
                // aslinya (potret/lanskap). Pakai Math.max, bukan Math.min,
                // karena area crop sekarang tidak lagi persegi (1:1).
                this.minScale = Math.max(this.viewW / this.natW, this.viewH / this.natH);
                this.maxScale = this.minScale * 3;
                this.scale = this.minScale;
                this.zoomPercent = 0;

                // pusatkan gambar di tengah viewport
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
'@

$content = Apply-Patch -Content $content -Old $old3 -New $new3 -Label "logic Alpine.js (viewport measurement + cover-fit scale)"

# ---------- Patch 4: confirmCrop (output canvas) ----------
$old4 = @'
        confirmCrop() {
            const canvas = this.$refs.cropCanvas;
            canvas.width = this.OUT;
            canvas.height = this.OUT;
            const ctx = canvas.getContext('2d');

            const sx = -this.posX / this.scale;
            const sy = -this.posY / this.scale;
            const sSize = this.VIEW / this.scale;

            ctx.clearRect(0, 0, this.OUT, this.OUT);
            ctx.drawImage(this.$refs.cropImg, sx, sy, sSize, sSize, 0, 0, this.OUT, this.OUT);
'@

$new4 = @'
        confirmCrop() {
            const canvas = this.$refs.cropCanvas;
            canvas.width = this.OUT_W;
            canvas.height = this.OUT_H;
            const ctx = canvas.getContext('2d');

            // Area sumber (dalam koordinat gambar asli, bukan koordinat
            // layar) yang persis terlihat di dalam viewport saat ini.
            // Karena viewport & output SAMA-SAMA rasio 4:5, region ini
            // di-scale langsung ke ukuran output tanpa distorsi apa pun
            // -- inilah yang menjamin preview = hasil akhir (WYSIWYG).
            const sx = -this.posX / this.scale;
            const sy = -this.posY / this.scale;
            const sWidth = this.viewW / this.scale;
            const sHeight = this.viewH / this.scale;

            ctx.clearRect(0, 0, this.OUT_W, this.OUT_H);
            ctx.drawImage(this.$refs.cropImg, sx, sy, sWidth, sHeight, 0, 0, this.OUT_W, this.OUT_H);
'@

$content = Apply-Patch -Content $content -Old $old4 -New $new4 -Label "confirmCrop (output 800x1000, rasio 4:5)"

$encoding = New-Object System.Text.UTF8Encoding($false)
$normalized = $content -replace "`r`n", "`n"
$normalized = $normalized -replace "`n", "`r`n"
[System.IO.File]::WriteAllText((Join-Path (Get-Location) $targetPath), $normalized, $encoding)

Write-Host ""
Write-Host "== SELESAI ==" -ForegroundColor Cyan
Write-Host "Tidak perlu migrate ulang, tidak perlu npm run build ulang" -ForegroundColor White
Write-Host "(hanya markup Blade + Alpine.js inline, tidak ada class Tailwind baru" -ForegroundColor White
Write-Host "selain 'aspect-4/5' yang sudah dipakai di frontend, jadi sudah pasti" -ForegroundColor White
Write-Host "ada di build CSS kamu)." -ForegroundColor White
Write-Host ""
Write-Host "Coba langsung di:" -ForegroundColor White
Write-Host "  /admin/kategori/tambah" -ForegroundColor Gray
Write-Host "  atau /admin/kategori/{id}/edit" -ForegroundColor Gray
Write-Host ""
