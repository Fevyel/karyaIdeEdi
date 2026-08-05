<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin-panel')] #[Title('Profil Saya')] class extends Component
{
    use WithFileUploads;

    public string $name = '';

    public ?string $nama_toko = '';

    public string $email = '';

    public ?string $whatsapp = '';

    public ?string $alamat = '';

    public ?string $bio = '';

    /** Foto baru yang sedang dipilih (belum disimpan). */
    public $foto_baru = null;

    public bool $showPasswordModal = false;

    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->nama_toko = $user->nama_toko;
        $this->email = $user->email;
        $this->whatsapp = $user->whatsapp;
        $this->alamat = $user->alamat;
        $this->bio = $user->bio;
    }

    /**
     * URL foto yang ditampilkan: preview foto baru (kalau ada yang dipilih),
     * kalau tidak pakai foto profil yang sudah tersimpan.
     */
    public function getFotoPreviewUrlProperty(): ?string
    {
        if ($this->foto_baru) {
            return $this->foto_baru->temporaryUrl();
        }

        return Auth::user()->fotoProfilUrl();
    }

    public function updatedFotoBaru(): void
    {
        $this->validate([
            'foto_baru' => ['image', 'max:2048'],
        ], [
            'foto_baru.image' => 'File harus berupa gambar.',
            'foto_baru.max' => 'Ukuran foto maksimal 2MB.',
        ]);
    }

    /**
     * Simpan perubahan data profil (termasuk foto baru jika ada).
     */
    public function save(): void
    {
        $user = Auth::user();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'nama_toko' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'bio' => ['nullable', 'string', 'max:500'],
            'foto_baru' => ['nullable', 'image', 'max:2048'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'foto_baru.image' => 'File harus berupa gambar.',
            'foto_baru.max' => 'Ukuran foto maksimal 2MB.',
        ]);

        if ($this->foto_baru) {
            if ($user->foto_profil) {
                Storage::disk('public')->delete($user->foto_profil);
            }

            $path = $this->foto_baru->store('profil', 'public');
            $user->foto_profil = $path;
            $this->foto_baru = null;
        }

        $user->name = $this->name;
        $user->nama_toko = $this->nama_toko;
        $user->email = $this->email;
        $user->whatsapp = $this->whatsapp;
        $user->alamat = $this->alamat;
        $user->bio = $this->bio;
        $user->save();

        $this->dispatch('profil-tersimpan');
    }

    /**
     * Ganti password: wajib verifikasi password lama dulu.
     */
    public function changePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min' => 'Password baru minimal 8 karakter.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user = Auth::user();

        if (! Hash::check($this->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password saat ini salah.',
            ]);
        }

        $user->password = Hash::make($this->new_password);
        $user->save();

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->showPasswordModal = false;

        $this->dispatch('password-tersimpan');
    }
};
?>

<div class="mx-auto max-w-6xl space-y-6" x-data="{ showSaved: false, showPasswordSaved: false }"
    x-on:profil-tersimpan.window="showSaved = true; setTimeout(() => showSaved = false, 3000)"
    x-on:password-tersimpan.window="showPasswordSaved = true; setTimeout(() => showPasswordSaved = false, 3000)"
>
    <div>
        <h2 class="font-display text-xl font-semibold text-admin-ink sm:text-2xl">
            Profil Saya
        </h2>
        <p class="mt-1 text-sm text-admin-ink-soft">
            Kelola data pemilik toko yang ditampilkan di panel admin.
        </p>
    </div>

    {{-- notifikasi tersimpan --}}
    <div
        x-show="showSaved"
        x-transition
        x-cloak
        class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
    >
        <i class="fa-solid fa-circle-check"></i>
        Profil berhasil disimpan.
    </div>
    <div
        x-show="showPasswordSaved"
        x-transition
        x-cloak
        class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
    >
        <i class="fa-solid fa-circle-check"></i>
        Password berhasil diubah.
    </div>

    <form wire:submit="save" class="space-y-6">

        {{-- ================= CARD: FOTO PROFIL ================= --}}
        <div class="rounded-2xl border border-admin-border bg-admin-surface p-5 shadow-sm sm:p-6">
            <h3 class="mb-4 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                <i class="fa-solid fa-image text-admin-accent"></i>
                Foto Profil
            </h3>

            <div class="flex flex-col items-center gap-5 sm:flex-row">
                <div class="relative shrink-0">
                    @if ($this->fotoPreviewUrl)
                        <img
                            src="{{ $this->fotoPreviewUrl }}"
                            alt="Foto profil"
                            class="h-24 w-24 rounded-full object-cover ring-4 ring-admin-cream"
                        >
                    @else
                        <span class="flex h-24 w-24 items-center justify-center rounded-full bg-admin-panel text-2xl font-semibold text-white ring-4 ring-admin-cream">
                            {{ Auth::user()->initials() }}
                        </span>
                    @endif

                    <label
                        for="foto_baru"
                        class="absolute -bottom-1 -right-1 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-admin-accent text-white shadow-sm ring-2 ring-white transition hover:bg-admin-accent-strong"
                        title="Ganti foto"
                    >
                        <i class="fa-solid fa-camera text-xs"></i>
                    </label>
                    <input id="foto_baru" type="file" wire:model="foto_baru" accept="image/*" class="hidden">
                </div>

                <div class="text-center sm:text-left">
                    <p class="text-sm font-medium text-admin-ink">
                        {{ $this->fotoPreviewUrl ? 'Foto siap disimpan' : 'Belum ada foto profil' }}
                    </p>
                    <p class="mt-1 text-xs text-admin-ink-soft">
                        Format JPG/PNG, maksimal 2MB. Klik ikon kamera untuk memilih foto baru.
                    </p>
                    <div wire:loading wire:target="foto_baru" class="mt-2 flex items-center justify-center gap-1.5 text-xs text-admin-accent sm:justify-start">
                        <i class="fa-solid fa-circle-notch animate-spin"></i> Mengunggah pratinjau...
                    </div>
                    @error('foto_baru')
                        <p class="mt-1.5 flex items-center justify-center gap-1 text-xs font-medium text-red-600 sm:justify-start">
                            <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ================= CARD: DATA PEMILIK TOKO ================= --}}
        <div class="rounded-2xl border border-admin-border bg-admin-surface p-5 shadow-sm sm:p-6">
            <h3 class="mb-5 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                <i class="fa-solid fa-user text-admin-accent"></i>
                Data Pemilik Toko
            </h3>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="name" class="mb-1.5 block text-sm font-medium text-admin-ink">
                        Nama Lengkap
                    </label>
                    <div class="relative">
                        <i class="fa-regular fa-user pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-admin-ink-soft"></i>
                        <input
                            id="name" type="text" wire:model="name" placeholder="Nama lengkap Anda"
                            class="w-full rounded-lg border {{ $errors->has('name') ? 'border-red-400' : 'border-admin-border' }} bg-admin-surface py-2.5 pl-10 pr-3 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20 "
                        >
                    </div>
                    @error('name')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="nama_toko" class="mb-1.5 block text-sm font-medium text-admin-ink">
                        Nama Toko
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-couch pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-admin-ink-soft"></i>
                        <input
                            id="nama_toko" type="text" wire:model="nama_toko" placeholder="Karya Ide Edi"
                            class="w-full rounded-lg border {{ $errors->has('nama_toko') ? 'border-red-400' : 'border-admin-border' }} bg-admin-surface py-2.5 pl-10 pr-3 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20 "
                        >
                    </div>
                    @error('nama_toko')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-admin-ink">
                        Email
                    </label>
                    <div class="relative">
                        <i class="fa-regular fa-envelope pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-admin-ink-soft"></i>
                        <input
                            id="email" type="email" wire:model="email" placeholder="admin@example.com"
                            class="w-full rounded-lg border {{ $errors->has('email') ? 'border-red-400' : 'border-admin-border' }} bg-admin-surface py-2.5 pl-10 pr-3 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20 "
                        >
                    </div>
                    @error('email')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="whatsapp" class="mb-1.5 block text-sm font-medium text-admin-ink">
                        Nomor WhatsApp
                    </label>
                    <div class="relative">
                        <i class="fa-brands fa-whatsapp pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-admin-ink-soft"></i>
                        <input
                            id="whatsapp" type="text" wire:model="whatsapp" placeholder="08xxxxxxxxxx"
                            class="w-full rounded-lg border {{ $errors->has('whatsapp') ? 'border-red-400' : 'border-admin-border' }} bg-admin-surface py-2.5 pl-10 pr-3 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20 "
                        >
                    </div>
                    @error('whatsapp')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="alamat" class="mb-1.5 block text-sm font-medium text-admin-ink">
                        Alamat
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-location-dot pointer-events-none absolute left-3.5 top-3 text-sm text-admin-ink-soft"></i>
                        <textarea
                            id="alamat" wire:model="alamat" rows="2" placeholder="Alamat lengkap toko"
                            class="w-full resize-none rounded-lg border {{ $errors->has('alamat') ? 'border-red-400' : 'border-admin-border' }} bg-admin-surface py-2.5 pl-10 pr-3 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20 "
                        ></textarea>
                    </div>
                    @error('alamat')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="bio" class="mb-1.5 block text-sm font-medium text-admin-ink">
                        Bio Singkat
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-pen-nib pointer-events-none absolute left-3.5 top-3 text-sm text-admin-ink-soft"></i>
                        <textarea
                            id="bio" wire:model="bio" rows="3" maxlength="500" placeholder="Sedikit cerita tentang Anda atau toko..."
                            class="w-full resize-none rounded-lg border {{ $errors->has('bio') ? 'border-red-400' : 'border-admin-border' }} bg-admin-surface py-2.5 pl-10 pr-3 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20 "
                        ></textarea>
                    </div>
                    @error('bio')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ================= CARD: KEAMANAN ================= --}}
        <div class="rounded-2xl border border-admin-border bg-admin-surface p-5 shadow-sm sm:p-6">
            <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                <i class="fa-solid fa-lock text-admin-accent"></i>
                Keamanan
            </h3>
            <p class="mb-4 text-xs text-admin-ink-soft">
                Ubah password akun Anda secara berkala untuk menjaga keamanan.
            </p>
            <button
                type="button"
                @click="$wire.showPasswordModal = true"
                class="flex items-center gap-2 rounded-lg border border-admin-border px-4 py-2.5 text-sm font-medium text-admin-ink transition hover:border-admin-accent hover:text-admin-accent"
            >
                <i class="fa-solid fa-key text-xs"></i>
                Ubah Password
            </button>
        </div>

        {{-- ================= TOMBOL SIMPAN ================= --}}
        <div class="flex justify-end">
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="flex items-center gap-2 rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-(--color-admin-panel)/20 transition-all duration-200 hover:bg-admin-accent-strong active:scale-[0.99] disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan
                </span>
                <span wire:loading wire:target="save" class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-notch animate-spin"></i> Menyimpan...
                </span>
            </button>
        </div>
    </form>

    {{-- ================= MODAL UBAH PASSWORD ================= --}}
    <div
        x-show="$wire.showPasswordModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
    >
        <div
            x-show="$wire.showPasswordModal"
            x-transition
            @click.outside="$wire.showPasswordModal = false"
            class="w-full max-w-sm rounded-2xl bg-admin-surface p-6 shadow-2xl"
        >
            <div class="mb-5 flex items-center justify-between">
                <h3 class="flex items-center gap-2 text-sm font-semibold text-admin-ink">
                    <i class="fa-solid fa-key text-admin-accent"></i>
                    Ubah Password
                </h3>
                <button type="button" @click="$wire.showPasswordModal = false" class="text-admin-ink-soft hover:text-admin-ink">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form wire:submit="changePassword" class="space-y-4">
                <div>
                    <label for="current_password" class="mb-1.5 block text-sm font-medium text-admin-ink">
                        Password Saat Ini
                    </label>
                    <input
                        id="current_password" type="password" wire:model="current_password"
                        class="w-full rounded-lg border {{ $errors->has('current_password') ? 'border-red-400' : 'border-admin-border' }} bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20 "
                    >
                    @error('current_password')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="new_password" class="mb-1.5 block text-sm font-medium text-admin-ink">
                        Password Baru
                    </label>
                    <input
                        id="new_password" type="password" wire:model="new_password"
                        class="w-full rounded-lg border {{ $errors->has('new_password') ? 'border-red-400' : 'border-admin-border' }} bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20 "
                    >
                    @error('new_password')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="new_password_confirmation" class="mb-1.5 block text-sm font-medium text-admin-ink">
                        Konfirmasi Password Baru
                    </label>
                    <input
                        id="new_password_confirmation" type="password" wire:model="new_password_confirmation"
                        class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink transition focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                    >
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="changePassword"
                    class="flex w-full items-center justify-center gap-2 rounded-full bg-admin-panel px-4 py-3 text-sm font-semibold text-white transition hover:bg-admin-accent-strong disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="changePassword">Simpan Password Baru</span>
                    <span wire:loading wire:target="changePassword" class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-notch animate-spin"></i> Memproses...
                    </span>
                </button>
            </form>
        </div>
    </div>
</div>