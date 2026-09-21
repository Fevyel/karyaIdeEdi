<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin-panel')] #[Title('Pengaturan')] class extends Component
{
    public string $site_name = '';

    public string $tagline = '';

    public string $whatsapp = '';

    public string $alamat = '';

    public string $email = '';

    public string $instagram_url = '';

    public string $tiktok_url = '';

    public string $facebook_url = '';

    /** Hasil crop dari kanvas JS, dikirim sebagai data URL base64 PNG. Null = logo tidak diganti. */
    public ?string $logoBase64 = null;

    /** Untuk pratinjau di sisi server (URL logo yang sedang tersimpan). */
    public ?string $existingLogoUrl = null;

    public function mount(): void
    {
        $setting = Setting::current();

        $this->site_name = $setting->site_name;
        $this->tagline = (string) $setting->tagline;
        $this->whatsapp = (string) $setting->whatsapp;
        $this->alamat = (string) $setting->alamat;
        $this->email = (string) $setting->email;
        $this->instagram_url = (string) $setting->instagram_url;
        $this->tiktok_url = (string) $setting->tiktok_url;
        $this->facebook_url = (string) $setting->facebook_url;
        $this->existingLogoUrl = $setting->logoUrl();
    }

    /**
     * Rule tambahan untuk kolom link sosial media: kalau URL yang diisi
     * ternyata cocok dengan domain platform LAIN (mis. link tiktok.com
     * dimasukkan ke kolom Instagram), tolak dengan pesan yang menyebutkan
     * platform mana yang sebenarnya terdeteksi.
     */
    protected function socialPlatformMismatchRule(string $field): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($field): void {
            $detected = Setting::detectSocialPlatform($value);

            if ($detected !== null && $detected !== $field) {
                $expectedLabel = Setting::socialPlatforms()[$field]['label'];
                $detectedLabel = Setting::socialPlatforms()[$detected]['label'];

                $fail("Link ini terdeteksi sebagai tautan {$detectedLabel}, bukan {$expectedLabel}. Periksa kembali link yang dimasukkan.");
            }
        };
    }

    public function save()
    {
        $validated = $this->validate([
            'site_name' => ['required', 'string', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:150'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:300'],
            'email' => ['nullable', 'email', 'max:150'],
            'instagram_url' => ['nullable', 'url', 'max:255', $this->socialPlatformMismatchRule('instagram_url')],
            'tiktok_url' => ['nullable', 'url', 'max:255', $this->socialPlatformMismatchRule('tiktok_url')],
            'facebook_url' => ['nullable', 'url', 'max:255', $this->socialPlatformMismatchRule('facebook_url')],
        ]);

        $setting = Setting::current();

        // Logo baru dikirim sebagai data URL base64 hasil crop di sisi klien.
        if ($this->logoBase64 && str_starts_with($this->logoBase64, 'data:image/png;base64,')) {
            $binary = base64_decode(substr($this->logoBase64, strlen('data:image/png;base64,')));

            if ($binary !== false && @getimagesizefromstring($binary) !== false) {
                $oldPath = $setting->logo_path;

                $newPath = 'site/logo-'.now()->timestamp.'.png';
                Storage::disk('public')->put($newPath, $binary);
                $validated['logo_path'] = $newPath;

                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        }

        $setting->update($validated);

        session()->flash('pengaturan-tersimpan', true);

        // Redirect penuh (bukan cuma morph komponen) supaya logo baru di sidebar,
        // yang berada di luar komponen ini, ikut ter-render ulang.
        return $this->redirect(route('admin.settings'), navigate: true);
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-xl font-semibold text-[var(--color-admin-ink)] sm:text-2xl">
                Pengaturan
            </h2>
            <p class="mt-1 text-sm text-[var(--color-admin-ink-soft)]">
                Atur identitas website dan nomor WhatsApp yang tampil untuk pelanggan.
            </p>
        </div>
    </div>

    {{-- notifikasi tersimpan (bertahan lewat redirect via session flash) --}}
    @if (session('pengaturan-tersimpan'))
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 3000)"
            x-show="show"
            x-transition
            class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
        >
            <i class="fa-solid fa-circle-check"></i>
            Pengaturan tersimpan.
        </div>
    @endif

    <form
        wire:submit="save"
        x-data="logoCropper('{{ $existingLogoUrl }}')"
        class="grid grid-cols-1 gap-6"
    >

        {{-- ================= SECTION 1: IDENTITAS WEBSITE ================= --}}
        <div class="flex flex-col rounded-2xl border border-[var(--color-admin-border)] bg-admin-surface p-5 shadow-sm sm:p-6">
            <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-[var(--color-admin-ink)]">
                <i class="fa-solid fa-globe text-[var(--color-admin-accent)]"></i>
                Identitas Website
            </h3>
            <p class="mb-5 text-xs text-[var(--color-admin-ink-soft)]">
                Nama, tagline, dan logo yang ditampilkan di website dan panel admin.
            </p>

            <div class="space-y-5">
                <div>
                    <label for="site_name" class="mb-1.5 block text-sm font-medium text-[var(--color-admin-ink)]">
                        Nama Website
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-couch pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-[var(--color-admin-ink-soft)]"></i>
                        <input
                            id="site_name" type="text" wire:model="site_name" placeholder="Karya Ide Edi"
                            class="w-full rounded-lg border border-[var(--color-admin-border)] bg-admin-surface py-2.5 pl-10 pr-3 text-sm text-[var(--color-admin-ink)] transition focus:border-[var(--color-admin-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--color-admin-accent)]/20"
                        >
                    </div>
                    @error('site_name')
                        <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tagline" class="mb-1.5 block text-sm font-medium text-[var(--color-admin-ink)]">
                        Tagline
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-quote-left pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-[var(--color-admin-ink-soft)]"></i>
                        <input
                            id="tagline" type="text" wire:model="tagline" placeholder="Slogan singkat toko Anda"
                            class="w-full rounded-lg border border-[var(--color-admin-border)] bg-admin-surface py-2.5 pl-10 pr-3 text-sm text-[var(--color-admin-ink)] transition focus:border-[var(--color-admin-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--color-admin-accent)]/20"
                        >
                    </div>
                </div>

                {{-- ================= LOGO + CROPPER ================= --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-[var(--color-admin-ink)]">
                        Logo
                    </label>
                    <div class="flex flex-col items-center gap-5 sm:flex-row">
                        <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-dashed border-[var(--color-admin-border)] bg-[var(--color-admin-cream)]">
                            <template x-if="previewUrl">
                                <img :src="previewUrl" alt="Preview logo" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!previewUrl">
                                <i class="fa-solid fa-image text-2xl text-[var(--color-admin-ink-soft)]"></i>
                            </template>
                        </div>

                        <div class="text-center sm:text-left">
                            <label
                                for="logo_input"
                                class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-[var(--color-admin-border)] px-4 py-2 text-sm font-medium text-[var(--color-admin-ink)] transition hover:border-[var(--color-admin-accent)] hover:text-[var(--color-admin-accent)]"
                            >
                                <i class="fa-solid fa-upload text-xs"></i>
                                Pilih Logo
                            </label>
                            <input id="logo_input" type="file" accept="image/*" class="hidden" x-on:change="onFileChange($event)">
                            <p class="mt-2 text-xs text-[var(--color-admin-ink-soft)]">
                                Setelah memilih foto, Anda bisa atur posisi &amp; zoom sebelum disimpan.
                            </p>
                            @error('logoBase64')
                                <p class="mt-1.5 flex items-center justify-center gap-1 text-xs font-medium text-red-600 sm:justify-start">
                                    <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <label for="setting_whatsapp" class="mb-1.5 block text-sm font-medium text-[var(--color-admin-ink)]">
                        WhatsApp
                    </label>
                    <p class="mb-1.5 text-xs text-[var(--color-admin-ink-soft)]">
                        Nomor ini otomatis dipakai di semua tombol WhatsApp di seluruh website.
                    </p>
                    <div class="relative">
                        <i class="fa-brands fa-whatsapp pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-[var(--color-admin-ink-soft)]"></i>
                        <input
                            id="setting_whatsapp" type="text" wire:model="whatsapp" placeholder="08xxxxxxxxxx"
                            class="w-full rounded-lg border border-[var(--color-admin-border)] bg-admin-surface py-2.5 pl-10 pr-3 text-sm text-[var(--color-admin-ink)] transition focus:border-[var(--color-admin-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--color-admin-accent)]/20"
                        >
                    </div>
                </div>

                <div>
                    <label for="setting_alamat" class="mb-1.5 block text-sm font-medium text-[var(--color-admin-ink)]">
                        Alamat
                    </label>
                    <p class="mb-1.5 text-xs text-[var(--color-admin-ink-soft)]">
                        Alamat ini otomatis dipakai di section "Kunjungi Kami" pada Beranda (tepat sebelum
                        Footer). Tautan tombol "Lihat di Google Maps" diatur terpisah di Admin &gt; Edit Web
                        &gt; Beranda &gt; Lokasi.
                    </p>
                    <div class="relative">
                        <i class="fa-solid fa-location-dot pointer-events-none absolute left-3.5 top-3 text-sm text-[var(--color-admin-ink-soft)]"></i>
                        <textarea
                            id="setting_alamat" rows="2" wire:model="alamat" placeholder="Jl. Contoh No. 1, Kecamatan, Kota"
                            class="w-full resize-none rounded-lg border border-[var(--color-admin-border)] bg-admin-surface py-2.5 pl-10 pr-3 text-sm text-[var(--color-admin-ink)] transition focus:border-[var(--color-admin-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--color-admin-accent)]/20"
                        ></textarea>
                    </div>
                    @error('alamat')
                        <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600">
                            <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ================= SECTION 2: MEDIA SOSIAL & KONTAK ================= --}}
        <div class="flex flex-col rounded-2xl border border-[var(--color-admin-border)] bg-admin-surface p-5 shadow-sm sm:p-6">
            <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-[var(--color-admin-ink)]">
                <i class="fa-solid fa-share-nodes text-[var(--color-admin-accent)]"></i>
                Media Sosial &amp; Kontak
            </h3>
            <p class="mb-5 text-xs text-[var(--color-admin-ink-soft)]">
                Link ini otomatis dipakai di semua ikon sosial media dan tombol kontak di seluruh website.
                Tombol hanya muncul kalau link-nya diisi.
            </p>

            <div class="space-y-5">
                <div>
                    <label for="setting_instagram" class="mb-1.5 block text-sm font-medium text-[var(--color-admin-ink)]">
                        Instagram
                    </label>
                    <div class="relative">
                        <i class="fa-brands fa-instagram pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-[var(--color-admin-ink-soft)]"></i>
                        <input
                            id="setting_instagram" type="url" wire:model="instagram_url" placeholder="https://instagram.com/namatoko"
                            class="w-full rounded-lg border border-[var(--color-admin-border)] bg-admin-surface py-2.5 pl-10 pr-3 text-sm text-[var(--color-admin-ink)] transition focus:border-[var(--color-admin-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--color-admin-accent)]/20"
                        >
                    </div>
                    @error('instagram_url')
                        <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600">
                            <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="setting_tiktok" class="mb-1.5 block text-sm font-medium text-[var(--color-admin-ink)]">
                        TikTok
                    </label>
                    <div class="relative">
                        <i class="fa-brands fa-tiktok pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-[var(--color-admin-ink-soft)]"></i>
                        <input
                            id="setting_tiktok" type="url" wire:model="tiktok_url" placeholder="https://tiktok.com/@namatoko"
                            class="w-full rounded-lg border border-[var(--color-admin-border)] bg-admin-surface py-2.5 pl-10 pr-3 text-sm text-[var(--color-admin-ink)] transition focus:border-[var(--color-admin-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--color-admin-accent)]/20"
                        >
                    </div>
                    @error('tiktok_url')
                        <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600">
                            <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="setting_facebook" class="mb-1.5 block text-sm font-medium text-[var(--color-admin-ink)]">
                        Facebook
                    </label>
                    <div class="relative">
                        <i class="fa-brands fa-facebook-f pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-[var(--color-admin-ink-soft)]"></i>
                        <input
                            id="setting_facebook" type="url" wire:model="facebook_url" placeholder="https://facebook.com/namatoko"
                            class="w-full rounded-lg border border-[var(--color-admin-border)] bg-admin-surface py-2.5 pl-10 pr-3 text-sm text-[var(--color-admin-ink)] transition focus:border-[var(--color-admin-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--color-admin-accent)]/20"
                        >
                    </div>
                    @error('facebook_url')
                        <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600">
                            <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="setting_email" class="mb-1.5 block text-sm font-medium text-[var(--color-admin-ink)]">
                        Email (Gmail)
                    </label>
                    <p class="mb-1.5 text-xs text-[var(--color-admin-ink-soft)]">
                        Kalau pelanggan klik tombol Gmail di website, mereka langsung diarahkan ke halaman kirim pesan Gmail dengan alamat ini sebagai tujuan.
                    </p>
                    <div class="relative">
                        <i class="fa-solid fa-envelope pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-[var(--color-admin-ink-soft)]"></i>
                        <input
                            id="setting_email" type="email" wire:model="email" placeholder="tokoanda@gmail.com"
                            class="w-full rounded-lg border border-[var(--color-admin-border)] bg-admin-surface py-2.5 pl-10 pr-3 text-sm text-[var(--color-admin-ink)] transition focus:border-[var(--color-admin-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--color-admin-accent)]/20"
                        >
                    </div>
                    @error('email')
                        <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600">
                            <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ================= TOMBOL SIMPAN ================= --}}
        <div class="flex justify-end lg:col-span-2">
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="flex items-center gap-2 rounded-full bg-[var(--color-admin-panel)] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-[var(--color-admin-panel)]/20 transition-all duration-200 hover:bg-[var(--color-admin-accent-strong)] active:scale-[0.99] disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan
                </span>
                <span wire:loading wire:target="save" class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-notch animate-spin"></i> Menyimpan...
                </span>
            </button>
        </div>

        {{-- ================= MODAL CROPPER ================= --}}
        <div
            x-show="open"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
            style="display: none;"
        >
            <div
                x-show="open"
                x-transition.scale.origin.center
                @click.outside="cancelCrop()"
                class="w-full max-w-md rounded-2xl bg-admin-surface p-6 shadow-2xl"
            >
                <h4 class="mb-1 text-sm font-semibold text-[var(--color-admin-ink)]">Atur Posisi Logo</h4>
                <p class="mb-4 text-xs text-[var(--color-admin-ink-soft)]">Geser gambar untuk memindah, gunakan slider untuk zoom.</p>

                <div
                    x-ref="viewport"
                    class="relative mx-auto h-80 w-80 cursor-move touch-none overflow-hidden rounded-2xl border-2 border-[var(--color-admin-accent)] bg-[var(--color-admin-cream)] select-none"
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
                    <i class="fa-solid fa-magnifying-glass-minus text-xs text-[var(--color-admin-ink-soft)]"></i>
                    <input
                        type="range" min="0" max="100" x-model.number="zoomPercent"
                        x-on:input="applyZoom()"
                        class="h-1.5 w-full cursor-pointer appearance-none rounded-full bg-[var(--color-admin-border)] accent-[var(--color-admin-accent)]"
                    >
                    <i class="fa-solid fa-magnifying-glass-plus text-xs text-[var(--color-admin-ink-soft)]"></i>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" x-on:click="cancelCrop()" class="rounded-full border border-[var(--color-admin-border)] px-4 py-2 text-xs font-semibold text-[var(--color-admin-ink-soft)] transition hover:bg-[var(--color-admin-cream)]">
                        Batal
                    </button>
                    <button type="button" x-on:click="confirmCrop()" class="rounded-full bg-[var(--color-admin-accent)] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[var(--color-admin-accent-strong)]">
                        Gunakan Foto Ini
                    </button>
                </div>

                <canvas x-ref="cropCanvas" class="hidden"></canvas>
            </div>
        </div>
    </form>
</div>

@script
<script>
    Alpine.data('logoCropper', (existingLogoUrl) => ({
        open: false,
        rawImage: null,
        previewUrl: existingLogoUrl || null,
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
        VIEW: 320,
        OUT: 640,

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
            canvas.width = this.OUT;
            canvas.height = this.OUT;
            const ctx = canvas.getContext('2d');

            const sx = -this.posX / this.scale;
            const sy = -this.posY / this.scale;
            const sSize = this.VIEW / this.scale;

            ctx.clearRect(0, 0, this.OUT, this.OUT);
            ctx.drawImage(this.$refs.cropImg, sx, sy, sSize, sSize, 0, 0, this.OUT, this.OUT);

            const dataUrl = canvas.toDataURL('image/png');
            this.previewUrl = dataUrl;
            this.$wire.set('logoBase64', dataUrl);
            this.open = false;
        },

        cancelCrop() {
            this.open = false;
            this.rawImage = null;
        },
    }));
</script>
@endscript

