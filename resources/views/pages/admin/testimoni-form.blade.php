<?php

use App\Models\Testimonial;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin-panel')] class extends Component
{
    public ?int $testimonialId = null;

    public bool $isEdit = false;

    public string $customer_name = '';

    public string $jabatan = '';

    public int $rating = 5;

    public string $comment = '';

    public bool $is_active = true;

    public int $urutan = 0;

    /** Hasil crop (drag + zoom custom, sama persis seperti coverCropper Kategori), dikirim sebagai data URL base64 (JPEG). Null = foto tidak diganti. */
    public ?string $fotoCroppedBase64 = null;

    public ?string $foto_lama = null;

    /**
     * Satu komponen dipakai untuk Tambah (tanpa $testimonial) maupun
     * Edit (route model binding otomatis mengisi $testimonial).
     *
     * Bisa juga dipakai untuk edit komentar yang masuk lewat menu
     * Interaksi (tabel yang sama) -- approval_status baris tersebut
     * TIDAK diubah di sini, murni edit konten.
     */
    public function mount(?Testimonial $testimonial = null): void
    {
        if ($testimonial && $testimonial->exists) {
            $this->isEdit = true;
            $this->testimonialId = $testimonial->id;
            $this->customer_name = $testimonial->customer_name;
            $this->jabatan = (string) $testimonial->jabatan;
            $this->rating = $testimonial->rating ?? 5;
            $this->comment = $testimonial->comment;
            $this->is_active = $testimonial->is_active;
            $this->urutan = $testimonial->urutan;
            $this->foto_lama = $testimonial->foto;
        }
    }

    public function getPageTitleProperty(): string
    {
        return $this->isEdit ? 'Edit Testimoni' : 'Tambah Testimoni';
    }

    /**
     * Prioritas: hasil crop baru (base64, siap dipakai langsung sebagai src) ->
     * foto lama yang tersimpan di storage -> null (belum ada foto).
     */
    public function getFotoPreviewUrlProperty(): ?string
    {
        if ($this->fotoCroppedBase64) {
            return $this->fotoCroppedBase64;
        }

        return $this->foto_lama ? Storage::disk('public')->url($this->foto_lama) : null;
    }

    protected function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'max:2000'],
            'urutan' => ['required', 'integer', 'min:0'],
            'fotoCroppedBase64' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'customer_name.required' => 'Nama pelanggan wajib diisi.',
            'comment.required' => 'Isi testimoni wajib diisi.',
            'comment.max' => 'Isi testimoni maksimal 2000 karakter.',
        ];
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

    public function save(): void
    {
        $this->validate();

        $testimonial = $this->isEdit ? Testimonial::findOrFail($this->testimonialId) : new Testimonial();

        if ($this->fotoCroppedBase64) {
            $binary = $this->decodeBase64Image($this->fotoCroppedBase64);

            if ($binary !== null) {
                if ($this->isEdit && $this->foto_lama) {
                    Storage::disk('public')->delete($this->foto_lama);
                }

                $path = 'testimoni/'.Str::uuid().'.jpg';
                Storage::disk('public')->put($path, $binary);
                $testimonial->foto = $path;
            }
        }

        $testimonial->customer_name = $this->customer_name;
        $testimonial->jabatan = $this->jabatan !== '' ? $this->jabatan : null;
        $testimonial->rating = $this->rating;
        $testimonial->comment = $this->comment;
        $testimonial->is_active = $this->is_active;
        $testimonial->urutan = $this->urutan;

        if (! $this->isEdit) {
            // Testimoni yang dibuat langsung admin di menu ini otomatis
            // disetujui -- tidak melalui alur moderasi menu Interaksi.
            $testimonial->approval_status = 'approved';
            $testimonial->is_read_admin = true;
        }

        $testimonial->save();

        session()->flash('status', $this->isEdit
            ? 'Testimoni "'.$testimonial->customer_name.'" berhasil diperbarui.'
            : 'Testimoni "'.$testimonial->customer_name.'" berhasil ditambahkan.');

        $this->redirect(route('admin.testimonials'), navigate: true);
    }
};
?>

<div class="mx-auto max-w-6xl space-y-6" x-data="testimonialFotoCropper(@js($this->fotoPreviewUrl))">

    <div class="flex items-center gap-3">
        <a
            href="{{ route('admin.testimonials') }}"
            wire:navigate
            class="flex h-9 w-9 items-center justify-center rounded-lg border border-admin-border text-admin-ink-soft transition-colors duration-200 hover:bg-admin-cream hover:text-admin-ink"
        >
            <x-icon-arrow direction="left" />
        </a>
        <div>
            <h2 class="font-display text-xl font-semibold text-admin-ink sm:text-2xl">
                {{ $this->pageTitle }}
            </h2>
            <p class="mt-1 text-sm text-admin-ink-soft">
                {{ $isEdit ? 'Perbarui detail testimoni ini.' : 'Buat testimoni baru untuk ditampilkan di halaman Testimoni / Beranda.' }}
            </p>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">

        {{-- ================= CARD: FOTO ================= --}}
        <div class="rounded-2xl border border-admin-border bg-admin-surface p-5 shadow-sm sm:p-6">
            <h3 class="mb-4 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                <i class="fa-solid fa-image text-admin-accent"></i>
                Foto Pelanggan
            </h3>

            <div class="flex flex-col items-center gap-5 sm:flex-row">
                {{-- Preview bulat (rounded-full), sama persis seperti avatar
                     yang tampil di tabel daftar Testimoni & section beranda. --}}
                <div class="relative w-24 shrink-0">
                    <template x-if="previewUrl">
                        <img :src="previewUrl" alt="Preview foto" class="h-24 w-24 rounded-full object-cover ring-4 ring-admin-cream">
                    </template>
                    <template x-if="!previewUrl">
                        <span class="flex h-24 w-24 items-center justify-center rounded-full bg-admin-cream text-admin-ink-soft ring-4 ring-admin-cream">
                            <i class="fa-solid fa-user text-2xl"></i>
                        </span>
                    </template>

                    <label
                        for="foto_input"
                        class="absolute -bottom-1 -right-1 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-admin-accent text-white shadow-sm ring-2 ring-admin-surface transition hover:bg-admin-accent-strong"
                        title="Pilih foto"
                    >
                        <i class="fa-solid fa-camera text-xs"></i>
                    </label>
                    <input x-ref="fileInput" id="foto_input" type="file" accept="image/*" class="hidden" x-on:change="onFileChange($event)">
                </div>

                <div class="text-center sm:text-left">
                    <p class="text-sm font-medium text-admin-ink" x-text="previewUrl ? 'Foto siap disimpan' : 'Belum ada foto (opsional)'"></p>
                    <p class="mt-1 text-xs text-admin-ink-soft">
                        Format JPG/PNG, hasil crop mengikuti rasio 1:1 (lingkaran) -- sama persis seperti avatar yang tampil di daftar Testimoni.
                    </p>
                </div>
            </div>
        </div>

        {{-- ================= CARD: INFORMASI TESTIMONI ================= --}}
        <div class="rounded-2xl border border-admin-border bg-admin-surface p-5 shadow-sm sm:p-6">
            <h3 class="mb-5 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                <i class="fa-solid fa-quote-left text-admin-accent"></i>
                Informasi Testimoni
            </h3>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="customer_name" class="mb-1.5 block text-sm font-medium text-admin-ink">Nama Pelanggan</label>
                    <input
                        id="customer_name" type="text" wire:model="customer_name" placeholder="Nama pelanggan"
                        class="w-full rounded-lg border {{ $errors->has('customer_name') ? 'border-red-400' : 'border-admin-border' }} bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20 "
                    >
                    @error('customer_name')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="jabatan" class="mb-1.5 block text-sm font-medium text-admin-ink">
                        Jabatan / Keterangan <span class="font-normal text-admin-ink-soft">(opsional)</span>
                    </label>
                    <input
                        id="jabatan" type="text" wire:model="jabatan" placeholder="Pembeli, Jakarta"
                        class="w-full rounded-lg border {{ $errors->has('jabatan') ? 'border-red-400' : 'border-admin-border' }} bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20 "
                    >
                    @error('jabatan')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-admin-ink">Rating</label>
                    <div class="flex items-center gap-1">
                        @for ($i = 1; $i <= 5; $i++)
                            <button
                                type="button"
                                wire:click="$set('rating', {{ $i }})"
                                class="text-xl transition hover:scale-110 {{ $i <= $rating ? 'text-admin-gold' : 'text-admin-border' }}"
                            >
                                <i class="fa-solid fa-star"></i>
                            </button>
                        @endfor
                        <span class="ml-2 text-sm text-admin-ink-soft">{{ $rating }}/5</span>
                    </div>
                    @error('rating')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="urutan" class="mb-1.5 block text-sm font-medium text-admin-ink">Urutan Tampil</label>
                    <input
                        id="urutan" type="number" min="0" wire:model="urutan"
                        class="w-full rounded-lg border {{ $errors->has('urutan') ? 'border-red-400' : 'border-admin-border' }} bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20 "
                    >
                    @error('urutan')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
                    <p class="mt-1.5 text-xs text-admin-ink-soft">Angka lebih kecil tampil lebih dulu.</p>
                </div>

                <div class="sm:col-span-2">
                    <label for="comment" class="mb-1.5 block text-sm font-medium text-admin-ink">Isi Testimoni</label>
                    <textarea
                        id="comment" wire:model="comment" rows="4" maxlength="2000" placeholder="Tulis komentar/testimoni pelanggan di sini"
                        class="w-full resize-none rounded-lg border {{ $errors->has('comment') ? 'border-red-400' : 'border-admin-border' }} bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20 "
                    ></textarea>
                    @error('comment')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="flex w-fit cursor-pointer items-center gap-2">
                        <input
                            type="checkbox" wire:model="is_active"
                            class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-2 focus:ring-admin-accent/30"
                        >
                        <span class="text-sm text-admin-ink-soft">Aktifkan testimoni <span class="font-medium text-admin-ink">(tampil di frontend)</span></span>
                    </label>
                </div>
            </div>
        </div>

        {{-- ================= TOMBOL AKSI ================= --}}
        <div class="flex justify-end gap-3">
            <a
                href="{{ route('admin.testimonials') }}"
                wire:navigate
                class="flex items-center gap-2 rounded-full border border-admin-border px-6 py-3 text-sm font-medium text-admin-ink-soft transition hover:bg-admin-cream hover:text-admin-ink"
            >
                Batal
            </a>
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-(--color-admin-panel)/20 transition-all duration-200 hover:bg-admin-accent-strong active:scale-[0.99] disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk text-xs"></i>
                    {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Testimoni' }}
                </span>
                <span wire:loading wire:target="save" class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-notch animate-spin"></i> Menyimpan...
                </span>
            </button>
        </div>
    </form>

    {{-- ================= MODAL CROP FOTO ================= --}}
    {{--
        x-teleport memindahkan modal ini jadi anak langsung <body> saat dirender.
        Sama persis seperti logoCropper (Pengaturan) & coverCropper (Kategori),
        supaya "fixed inset-0" benar-benar relatif ke viewport.
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
                <h4 class="mb-1 text-sm font-semibold text-admin-ink">Sesuaikan Foto Pelanggan</h4>
                <p class="mb-4 text-xs text-admin-ink-soft">Geser gambar untuk memindah, gunakan slider untuk zoom. Hasil crop berbentuk lingkaran (1:1) -- sama persis seperti avatar di daftar Testimoni.</p>

                <div
                    x-ref="viewport"
                    class="relative mx-auto aspect-square w-full max-w-70 cursor-move touch-none overflow-hidden rounded-full border-2 border-admin-accent bg-admin-cream select-none"
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
    Alpine.data('testimonialFotoCropper', (existingPreviewUrl) => ({
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

        // Rasio crop = 1:1 (lingkaran), sama seperti avatar testimoni
        // yang tampil di tabel daftar & section beranda. JANGAN diubah
        // jadi rasio lain tanpa mengubah juga tampilan avatar di tempat lain.
        ASPECT_W: 1,
        ASPECT_H: 1,

        // Ukuran viewport (area crop) diukur langsung dari elemen yang
        // benar-benar dirender di layar (bukan angka tetap), supaya selalu
        // akurat di semua ukuran layar (desktop/tablet/mobile) dan preview
        // benar-benar identik dengan hasil akhir (WYSIWYG).
        viewW: 0,
        viewH: 0,

        // Resolusi file output tetap tinggi & konsisten rasio 1:1,
        // terlepas dari ukuran viewport di layar.
        OUT_W: 500,
        OUT_H: 500,

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
                // SELURUH area crop, apa pun orientasi foto aslinya
                // (potret/lanskap).
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

            // Area sumber (dalam koordinat gambar asli, bukan koordinat
            // layar) yang persis terlihat di dalam viewport saat ini.
            // Karena viewport & output SAMA-SAMA rasio 1:1, region ini
            // di-scale langsung ke ukuran output tanpa distorsi apa pun
            // -- inilah yang menjamin preview = hasil akhir (WYSIWYG).
            const sx = -this.posX / this.scale;
            const sy = -this.posY / this.scale;
            const sWidth = this.viewW / this.scale;
            const sHeight = this.viewH / this.scale;

            ctx.clearRect(0, 0, this.OUT_W, this.OUT_H);
            ctx.drawImage(this.$refs.cropImg, sx, sy, sWidth, sHeight, 0, 0, this.OUT_W, this.OUT_H);

            const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
            this.previewUrl = dataUrl;
            this.$wire.set('fotoCroppedBase64', dataUrl);
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
