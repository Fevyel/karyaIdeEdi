<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembayaran {{ $paymentMethod['label'] }} &mdash; {{ $siteSetting->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-[#F5F1EA] font-sans text-[#2A211B] antialiased">
    @include('partials.frontend.navbar')

    @php
        $paymentNumber = trim((string) ($paymentMethod['number'] ?? ''));
        $paymentDigits = preg_replace('/\D+/', '', $paymentNumber);
        $paymentReady = $paymentDigits !== '';
        $waDigits = $siteSetting->whatsappDigits();

        $waMessage = 'Halo '.$siteSetting->site_name.', saya ingin konfirmasi pembayaran melalui '.$paymentMethod['label'].'.';
        $waConfirmUrl = $waDigits
            ? 'https://wa.me/'.$waDigits.'?text='.rawurlencode($waMessage)
            : null;
    @endphp

    <main class="relative overflow-hidden">
        <div class="pointer-events-none absolute -right-24 top-12 h-72 w-72 rounded-full bg-admin-accent/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-24 bottom-10 h-72 w-72 rounded-full bg-[#1A1A1A]/5 blur-3xl"></div>

        <section class="relative mx-auto max-w-3xl px-5 py-12 sm:px-8 sm:py-16">
            <div class="mb-6">
                <a href="{{ url()->previous() }}" class="inline-flex items-center gap-2 text-sm font-medium text-[#6B625B] transition hover:text-admin-accent">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    Kembali
                </a>
            </div>

            <div class="overflow-hidden rounded-[28px] border border-[#2A211B]/10 bg-white shadow-[0_24px_70px_-35px_rgba(42,33,27,0.35)]">
                <div class="border-b border-[#2A211B]/10 bg-[#1A1A1A] px-6 py-8 text-center sm:px-10">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-white/45">Pembayaran</p>

                    <div class="mx-auto mt-4 flex h-16 w-28 items-center justify-center rounded-2xl bg-[#4A4A4A] px-4">
                        @if (file_exists(public_path($paymentMethod['logo'])))
                            <img
                                src="{{ asset($paymentMethod['logo']) }}"
                                alt="{{ $paymentMethod['label'] }}"
                                class="max-h-9 max-w-full object-contain"
                            >
                        @else
                            <span class="text-lg font-bold text-white">{{ $paymentMethod['label'] }}</span>
                        @endif
                    </div>

                    <h1 class="mt-5 font-display text-3xl font-semibold text-white sm:text-4xl">
                        {{ $paymentMethod['label'] }}
                    </h1>
                    <p class="mt-2 text-sm text-white/55">{{ $paymentMethod['type'] }}</p>
                </div>

                <div class="p-6 sm:p-10">
                    @if ($paymentReady)
                        <div
                            x-data="{
                                copied: false,
                                copyNumber() {
                                    const value = @js($paymentDigits);
                                    navigator.clipboard.writeText(value).then(() => {
                                        this.copied = true;
                                        setTimeout(() => this.copied = false, 1800);
                                    });
                                }
                            }"
                            class="space-y-6"
                        >
                            <div class="rounded-2xl border border-admin-accent/20 bg-admin-cream/40 p-5 sm:p-6">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#7B716A]">
                                    {{ $paymentMethod['number_label'] }}
                                </p>

                                <div class="mt-2 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="font-display text-2xl font-semibold tracking-[0.06em] text-[#1A1A1A] sm:text-3xl">
                                            {{ $paymentNumber }}
                                        </p>

                                        @if (trim((string) $paymentMethod['account_name']) !== '')
                                            <p class="mt-1 text-sm text-[#6B625B]">
                                                a.n. <span class="font-semibold text-[#2A211B]">{{ $paymentMethod['account_name'] }}</span>
                                            </p>
                                        @endif
                                    </div>

                                    <button
                                        type="button"
                                        x-on:click="copyNumber()"
                                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-[#1A1A1A] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-admin-accent"
                                    >
                                        <i class="fa-regular fa-copy"></i>
                                        <span x-show="!copied">Salin Nomor</span>
                                        <span x-show="copied" x-cloak>Tersalin</span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <div class="rounded-2xl bg-[#F8F6F2] p-4">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-xs font-bold text-admin-accent shadow-sm">1</span>
                                    <p class="mt-3 text-sm font-semibold text-[#2A211B]">Buka aplikasi pembayaran</p>
                                    <p class="mt-1 text-xs leading-relaxed text-[#746B64]">Gunakan m-banking, ATM, atau aplikasi DANA sesuai metode yang dipilih.</p>
                                </div>

                                <div class="rounded-2xl bg-[#F8F6F2] p-4">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-xs font-bold text-admin-accent shadow-sm">2</span>
                                    <p class="mt-3 text-sm font-semibold text-[#2A211B]">Transfer ke nomor di atas</p>
                                    <p class="mt-1 text-xs leading-relaxed text-[#746B64]">Masukkan nominal sesuai nilai yang telah disepakati pada pesanan Anda.</p>
                                </div>

                                <div class="rounded-2xl bg-[#F8F6F2] p-4">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-xs font-bold text-admin-accent shadow-sm">3</span>
                                    <p class="mt-3 text-sm font-semibold text-[#2A211B]">Konfirmasi pembayaran</p>
                                    <p class="mt-1 text-xs leading-relaxed text-[#746B64]">Simpan bukti transfer dan kirimkan kepada tim kami untuk verifikasi.</p>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3.5">
                                <div class="flex items-start gap-3">
                                    <i class="fa-solid fa-shield-halved mt-0.5 text-amber-600"></i>
                                    <p class="text-xs leading-relaxed text-amber-900">
                                        Pastikan nama tujuan pembayaran sesuai sebelum menyelesaikan transfer.
                                        {{ $siteSetting->site_name }} tidak pernah meminta PIN, password, atau kode OTP melalui halaman ini.
                                    </p>
                                </div>
                            </div>

                            @if ($waConfirmUrl)
                                <a
                                    href="{{ $waConfirmUrl }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="flex w-full items-center justify-center gap-2 rounded-full bg-admin-accent px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-admin-accent-strong"
                                >
                                    <i class="fa-brands fa-whatsapp text-base"></i>
                                    Konfirmasi Pembayaran via WhatsApp
                                </a>
                            @endif
                        </div>
                    @else
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-center">
                            <i class="fa-solid fa-circle-info text-xl text-amber-600"></i>
                            <h2 class="mt-3 text-lg font-semibold text-amber-950">Metode pembayaran belum tersedia</h2>
                            <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-amber-800">
                                Nomor {{ $paymentMethod['label'] }} belum diisi oleh admin.
                                Silakan pilih metode pembayaran lain atau hubungi toko.
                            </p>

                            @if ($waConfirmUrl)
                                <a
                                    href="{{ $waConfirmUrl }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="mt-5 inline-flex items-center gap-2 rounded-full bg-[#1A1A1A] px-5 py-2.5 text-sm font-semibold text-white"
                                >
                                    <i class="fa-brands fa-whatsapp"></i>
                                    Hubungi Toko
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </main>

    @include('partials.frontend.footer')
</body>
</html>