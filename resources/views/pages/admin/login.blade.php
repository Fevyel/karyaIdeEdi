<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin')] #[Title('Login Admin')] class extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = true;

    /**
     * Proses login admin: validasi input, cek rate limit,
     * autentikasi via Laravel Auth bawaan, regenerasi session, lalu redirect.
     */
    public function login(): mixed
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $throttleKey = strtolower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, maxAttempts: 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey, decaySeconds: 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }
};
?>

@php
    // Ambil foto hero furniture dari public/images/admin-login/.
    // Prioritas: hero.jpg. Jika tidak ada, pakai file pertama yang ditemukan.
    // (Presentasional saja — tidak menyentuh logic login di atas.)
    $adminHeroDir = public_path('images/admin-login');
    $adminHeroFile = null;

    if (is_dir($adminHeroDir)) {
        $adminHeroFiles = collect(scandir($adminHeroDir))
            ->reject(fn ($file) => in_array($file, ['.', '..']))
            ->filter(fn ($file) => is_file($adminHeroDir.DIRECTORY_SEPARATOR.$file))
            ->sort()
            ->values();

        $adminHeroFile = $adminHeroFiles->first(fn ($file) => in_array(strtolower($file), ['hero.jpg', 'hero.jpeg', 'hero.png', 'hero.webp']))
            ?? $adminHeroFiles->first();
    }

    $adminHeroUrl = $adminHeroFile ? asset('images/admin-login/'.$adminHeroFile).'?v='.@filemtime($adminHeroDir.DIRECTORY_SEPARATOR.$adminHeroFile) : null;
    $siteSetting = \App\Models\Setting::current();
    $adminStoreName = $siteSetting->site_name;
@endphp

<div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[var(--color-admin-cream)] px-4 py-8 sm:px-6 lg:py-12">

    {{-- tekstur & aksen dekoratif halaman --}}
    <div class="pointer-events-none absolute inset-0 opacity-[0.05]" style="background-image: radial-gradient(circle at 1px 1px, var(--color-admin-panel) 1px, transparent 0); background-size: 26px 26px;"></div>
    <div class="pointer-events-none absolute -left-40 -top-40 h-96 w-96 rounded-full bg-[var(--color-admin-accent)]/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-32 -right-24 h-96 w-96 rounded-full bg-[var(--color-admin-gold)]/10 blur-3xl"></div>

    {{-- ================= CARD BESAR ================= --}}
    <div class="animate-fade-in-up relative w-full max-w-5xl overflow-hidden rounded-[2rem] bg-white shadow-2xl shadow-black/10 ring-1 ring-black/[0.03] lg:rounded-[2.5rem]">
        <div class="relative flex flex-col lg:min-h-[620px] lg:flex-row">

            {{-- tulisan vertikal "ADMIN PANEL" di tepi kiri (desktop) --}}
            <div class="pointer-events-none absolute inset-y-0 left-0 z-30 hidden w-9 items-center justify-center lg:flex">
                <span class="[writing-mode:vertical-rl] rotate-180 text-[11px] font-semibold uppercase tracking-[0.35em] text-white/60">
                    Admin Panel
                </span>
            </div>

            {{-- ================= PANEL KIRI — BRANDING (desktop) ================= --}}
            {{-- bg-[var(--color-admin-panel)] dipasang langsung di container (bukan hanya lewat SVG),
                 supaya panel tetap solid & memenuhi tinggi kartu walau SVG lengkungan gagal render. --}}
            <div class="relative hidden w-[46%] shrink-0 overflow-hidden bg-[var(--color-admin-panel)] lg:block">

                {{-- lengkungan pemisah panel kanan, khas showroom furniture --}}
                <svg class="pointer-events-none absolute inset-y-0 -right-px z-[1] h-full w-20 text-[var(--color-admin-panel)]" viewBox="0 0 100 800" preserveAspectRatio="none" fill="currentColor">
                    <path d="M0,0 C55,140 55,660 0,800 L100,800 L100,0 Z"></path>
                </svg>
                <svg class="pointer-events-none absolute inset-y-0 -right-px z-0 h-full w-24 text-[var(--color-admin-gold)] opacity-30" viewBox="0 0 100 800" preserveAspectRatio="none" fill="currentColor">
                    <path d="M0,0 C60,150 60,650 0,800 L100,800 L100,0 Z"></path>
                </svg>

                {{-- pola titik halus & cahaya dekoratif --}}
                <div class="pointer-events-none absolute inset-0 z-[1] opacity-[0.06]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 24px 24px;"></div>
                <div class="pointer-events-none absolute -left-16 -top-24 z-[1] h-64 w-64 rounded-full bg-[var(--color-admin-gold)]/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-20 -left-10 z-[1] h-64 w-64 rounded-full bg-[var(--color-admin-accent)]/25 blur-3xl"></div>

                {{-- konten panel kiri --}}
                <div class="relative z-10 flex h-full min-h-[560px] flex-col justify-between py-10 pl-10 pr-8">

                    {{-- logo & nama toko — sumber tunggal: partials.logo (Pengaturan > Identitas Website) --}}
                    <div class="flex items-center gap-3">
                        @include('partials.logo', [
                            'boxSize' => 'h-10 w-10',
                            'rounded' => 'rounded-xl',
                            'boxClass' => 'bg-[var(--color-admin-accent)] shadow-lg shadow-black/20',
                            'imgClass' => 'shadow-lg shadow-black/20',
                            'iconClass' => 'text-white',
                        ])
                        <span class="font-display text-base font-semibold tracking-wide text-white">
                            {{ $adminStoreName }}
                        </span>
                    </div>

                    {{-- foto furniture melayang (menggantikan objek "hp" pada referensi) --}}
                    <div class="relative flex flex-1 items-center justify-center py-6">
                        @if ($adminHeroUrl)
                            <img
                                src="{{ $adminHeroUrl }}"
                                alt="Produk furniture {{ $adminStoreName }}"
                                class="w-[82%] max-w-[280px] object-contain drop-shadow-[0_30px_28px_rgba(0,0,0,0.55)] transition-transform duration-500 hover:-translate-y-1.5"
                            >
                        @else
                            <div class="flex aspect-square w-[70%] max-w-[240px] items-center justify-center rounded-full bg-white/5">
                                <i class="fa-solid fa-couch text-6xl text-white/25"></i>
                            </div>
                        @endif

                        {{-- bayangan lantai, kesan melayang --}}
                        <div class="absolute bottom-2 left-1/2 h-5 w-[55%] -translate-x-1/2 rounded-full bg-black/40 blur-xl"></div>
                    </div>

                    {{-- kutipan & caption bawah --}}
                    <div>
                        <i class="fa-solid fa-quote-left mb-2 text-lg text-[var(--color-admin-gold)]/70"></i>
                        <p class="font-display text-lg font-semibold italic leading-snug text-white">
                            Setiap detail furniture punya cerita, kelola dengan hati.
                        </p>
                        <p class="mt-3 text-xs leading-relaxed text-white/50">
                            Panel admin ini membantu Anda mengelola katalog produk, pemesanan custom, dan komunikasi pelanggan {{ $adminStoreName }} — semua dalam satu tempat.
                        </p>
                        <p class="mt-4 text-[11px] text-white/30">
                            &copy; {{ date('Y') }} {{ $adminStoreName }}. Panel khusus admin.
                        </p>
                    </div>
                </div>
            </div>

            {{-- ================= PANEL KANAN — FORM LOGIN ================= --}}
            <div class="flex w-full flex-col items-center justify-center bg-white px-6 py-10 sm:px-10 lg:w-[53%] lg:px-14 lg:py-12">
                <div class="w-full max-w-sm">

                    {{-- logo mobile saja — sumber tunggal: partials.logo --}}
                    <div class="mb-8 flex items-center gap-3 lg:hidden">
                        @include('partials.logo', [
                            'boxSize' => 'h-10 w-10',
                            'rounded' => 'rounded-xl',
                            'boxClass' => 'bg-[var(--color-admin-panel)]',
                            'iconClass' => 'text-white',
                        ])
                        <span class="font-sans text-base font-semibold text-[var(--color-admin-ink)]">
                            {{ $adminStoreName }}
                        </span>
                    </div>

                    <div class="mb-8">
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-[var(--color-admin-accent)]">
                            Login
                        </p>
                        <h1 class="mt-2 font-sans text-2xl font-extrabold leading-tight tracking-tight text-[var(--color-admin-ink)] sm:text-[1.75rem]">
                            Selamat Datang Kembali Boss
                        </h1>
                        <p class="mt-3 text-sm leading-relaxed text-[var(--color-admin-ink-soft)]">
                            Silakan masuk menggunakan akun pemilik untuk mengakses Dashboard Admin.
                        </p>
                    </div>

                    <form wire:submit="login" class="space-y-7" x-data="{ showPassword: false }">

                        {{-- Email — underline, ikon di kanan seperti referensi --}}
                        <div class="group relative">
                            <div class="flex items-end gap-2 border-b-2 border-[var(--color-admin-border)] pb-2 transition-colors duration-300 focus-within:border-[var(--color-admin-accent)] @error('email') !border-red-400 @enderror">
                                <div class="relative flex-1">
                                    <input
                                        id="email"
                                        type="email"
                                        wire:model="email"
                                        autocomplete="username"
                                        autofocus
                                        placeholder=" "
                                        class="peer w-full border-0 bg-transparent p-0 text-sm text-[var(--color-admin-ink)] outline-none placeholder-transparent"
                                    >
                                    <label
                                        for="email"
                                        class="pointer-events-none absolute left-0 top-1/2 -translate-y-1/2 text-sm text-[var(--color-admin-ink-soft)] transition-all duration-300 ease-out peer-focus:-top-4 peer-focus:translate-y-0 peer-focus:text-xs peer-focus:text-[var(--color-admin-accent)] peer-[&:not(:placeholder-shown)]:-top-4 peer-[&:not(:placeholder-shown)]:translate-y-0 peer-[&:not(:placeholder-shown)]:text-xs"
                                    >
                                        Email
                                    </label>
                                </div>
                                <i class="fa-solid fa-user mb-0.5 text-sm text-[var(--color-admin-ink-soft)] transition-colors duration-300 group-focus-within:text-[var(--color-admin-accent)]"></i>
                            </div>
                            @error('email')
                                <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600">
                                    <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Password — underline, ikon di kanan seperti referensi --}}
                        <div class="group relative">
                            <div class="flex items-end gap-2 border-b-2 border-[var(--color-admin-border)] pb-2 transition-colors duration-300 focus-within:border-[var(--color-admin-accent)] @error('password') !border-red-400 @enderror">
                                <div class="relative flex-1">
                                    <input
                                        id="password"
                                        :type="showPassword ? 'text' : 'password'"
                                        wire:model="password"
                                        autocomplete="current-password"
                                        placeholder=" "
                                        class="peer w-full border-0 bg-transparent p-0 text-sm text-[var(--color-admin-ink)] outline-none placeholder-transparent"
                                    >
                                    <label
                                        for="password"
                                        class="pointer-events-none absolute left-0 top-1/2 -translate-y-1/2 text-sm text-[var(--color-admin-ink-soft)] transition-all duration-300 ease-out peer-focus:-top-4 peer-focus:translate-y-0 peer-focus:text-xs peer-focus:text-[var(--color-admin-accent)] peer-[&:not(:placeholder-shown)]:-top-4 peer-[&:not(:placeholder-shown)]:translate-y-0 peer-[&:not(:placeholder-shown)]:text-xs"
                                    >
                                        Password
                                    </label>
                                </div>
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="mb-0.5 text-sm text-[var(--color-admin-ink-soft)] transition-colors duration-300 hover:text-[var(--color-admin-accent)]"
                                    tabindex="-1"
                                >
                                    <i class="fa-solid" :class="showPassword ? 'fa-eye-slash' : 'fa-key'"></i>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600">
                                    <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <label for="remember" class="flex cursor-pointer items-center select-none">
                                <input
                                    id="remember"
                                    type="checkbox"
                                    wire:model="remember"
                                    class="h-4 w-4 rounded border-[var(--color-admin-border)] text-[var(--color-admin-accent)] transition focus:ring-2 focus:ring-[var(--color-admin-accent)]/30"
                                >
                                <span class="ml-2 text-sm text-[var(--color-admin-ink-soft)]">Ingat Saya</span>
                            </label>
                        </div>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="login"
                            class="group flex w-full items-center justify-center gap-2 rounded-full bg-[var(--color-admin-panel)] px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-[var(--color-admin-panel)]/25 transition-all duration-300 hover:-translate-y-0.5 hover:bg-[var(--color-admin-accent-strong)] hover:shadow-xl hover:shadow-[var(--color-admin-accent)]/30 active:translate-y-0 active:scale-[0.98] disabled:opacity-60 disabled:hover:translate-y-0"
                        >
                            <span wire:loading.remove wire:target="login" class="flex items-center gap-2">
                                Masuk ke Dashboard
                                <x-icon-arrow direction="long-right" class="transition-transform duration-300 group-hover:translate-x-1" />
                            </span>
                            <span wire:loading wire:target="login" class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch animate-spin"></i> Memproses...
                            </span>
                        </button>

                        {{-- tautan bawah, gaya "Forgot / Help" seperti referensi (informasional) --}}
                        <div class="flex items-center justify-end gap-4 pt-1 text-xs font-medium text-[var(--color-admin-ink-soft)]">
                            <span class="inline-flex items-center gap-1">
                                <i class="fa-solid fa-key text-[10px]"></i> Lupa Sandi?
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <i class="fa-solid fa-circle-question text-[10px]"></i> Bantuan
                            </span>
                        </div>
                    </form>

                    <p class="mt-7 text-center text-xs text-[var(--color-admin-ink-soft)]">
                        <i class="fa-solid fa-shield-halved mr-1"></i>
                        Akses halaman ini dibatasi hanya untuk pemilik & staf {{ $adminStoreName }}.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
