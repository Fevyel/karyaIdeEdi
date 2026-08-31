<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Careers — {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-white font-sans text-[#2A211B] antialiased">
    @include('partials.frontend.navbar')

    @php
        $careersWaNumber = \App\Models\Setting::current()->whatsappDigits();
    @endphp

    {{-- =====================================================
         HERO — "belum ada lowongan, hubungi via WA" sesuai jawaban.
    ====================================================== --}}
    <section class="relative overflow-hidden bg-[#F9F7F2]">
        <div class="mx-auto flex max-w-3xl flex-col items-center px-6 py-20 text-center sm:px-8 lg:py-28">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-admin-cream text-admin-accent">
                <i class="fa-solid fa-briefcase text-lg"></i>
            </span>

            <div class="mt-6 flex items-center justify-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-admin-accent">
                <span class="h-px w-8 bg-admin-accent"></span>
                Careers
                <span class="h-px w-8 bg-admin-accent"></span>
            </div>

            <h1 class="mt-4 font-display text-4xl font-semibold leading-tight text-[#1A1A1A] sm:text-5xl">
                Belum Ada Lowongan Saat Ini
            </h1>

            <p class="mx-auto mt-5 max-w-xl text-sm leading-relaxed text-[#6B6E76] sm:text-base">
                Saat ini Karya Ide Edi belum membuka lowongan pekerjaan. Kalau Anda tertarik
                bergabung atau ingin mengirimkan CV untuk kesempatan berikutnya, silakan
                hubungi kami langsung lewat WhatsApp.
            </p>

            <a
                href="{{ $careersWaNumber ? 'https://wa.me/'.$careersWaNumber : route('booking.index') }}"
                @if ($careersWaNumber) target="_blank" rel="noopener" @endif
                class="mt-8 inline-flex items-center gap-2 rounded-lg bg-admin-accent px-6 py-3 text-sm font-medium text-white transition-colors duration-300 hover:bg-admin-accent-strong"
            >
                <i class="fa-brands fa-whatsapp"></i>
                Hubungi via WhatsApp
            </a>

            <a
                href="{{ route('home') }}"
                class="mt-4 text-sm font-medium text-[#1A1A1A]/60 underline-offset-4 transition-colors duration-300 hover:text-[#1A1A1A] hover:underline"
            >
                Kembali ke Beranda
            </a>
        </div>
    </section>

    @include('partials.frontend.footer')
</body>
</html>
