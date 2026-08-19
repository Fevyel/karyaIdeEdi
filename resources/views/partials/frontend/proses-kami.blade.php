{{--
    ==========================================================
    PROSES KAMI — Homepage Karya Ide Edi
    ==========================================================
    Implementasi 1:1 dari desain Figma milik user (source of truth).
    Teks & komposisi dipertahankan persis sesuai screenshot yang
    diberikan — termasuk copy yang di-generate dari template desain
    aslinya, atas instruksi eksplisit pemilik desain.

    Foto memakai aset yang sudah ada di project (foto proses kerja
    grayscale), posisi/crop/overlay disamakan dengan referensi —
    bukan foto baru.

    Pemakaian:
        @include('partials.frontend.proses-kami')
    ==========================================================
--}}

@php
    $prosesSetting = \App\Models\Setting::current();
@endphp

<section class="bg-[#FEEDD8]">
    <div class="grid grid-cols-1 lg:grid-cols-2 lg:min-h-140">

        {{-- ============ KIRI: Foto (grayscale) + tombol play + badge overlay ============ --}}
        <div class="relative min-h-80 sm:min-h-105 lg:min-h-0">
            <img
                src="https://images.pexels.com/photos/5974400/pexels-photo-5974400.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=1400"
                alt="Proses pembuatan furnitur Karya Ide Edi"
                class="absolute inset-0 h-full w-full object-cover grayscale"
            >

            <span class="absolute left-1/2 top-1/2 flex h-16 w-16 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-[#E63946] shadow-lg">
                <i class="fa-solid fa-play ml-1 text-lg text-white"></i>
            </span>

            <div class="absolute -bottom-px left-0 z-10 bg-[#1A1A1A] px-6 py-4">
                <p class="text-[10px] font-semibold uppercase leading-none tracking-wide text-white/60">Watch Now</p>
                <p class="mt-1.5 text-sm font-semibold leading-none text-white">25 Years of Excellence</p>
            </div>
        </div>

        {{-- ============ KANAN: Teks ============ --}}
        <div class="relative flex flex-col justify-center overflow-hidden bg-admin-canvas px-8 py-12 sm:px-12 lg:px-16 lg:py-0">
            {{-- Ghost heading — layer teks besar transparan di belakang heading utama (sesuai Figma). --}}
            <span
                aria-hidden="true"
                class="pointer-events-none absolute -top-2 left-8 select-none font-display text-5xl font-bold uppercase leading-[1.05] text-admin-ink/10 sm:text-6xl lg:left-16"
            >
                We Can Restore<br>Your Roof To The<br>Original Look
            </span>

            <div class="relative">
                <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-admin-accent">
                    <span class="text-base leading-none">—</span>
                    Trusted Best Company
                </p>

                <h2 class="mt-20 font-display text-4xl font-bold uppercase leading-tight text-admin-ink sm:mt-24 sm:text-5xl">
                    See Our Experts<br>In Action
                </h2>

                <p class="mt-5 max-w-md text-sm leading-relaxed text-admin-ink-soft">
                    Mintech has been helping organizations throughout the world to manage
                    their roofing infrastructure with our unique approach to technology
                    management and consultancy. Our process ensures every shingle is
                    perfectly placed for lifetime durability.
                </p>

                <ul class="mt-6 space-y-3">
                    <li class="flex items-start gap-2.5 text-sm text-admin-ink">
                        <span class="mt-0.5 flex h-4.5 w-4.5 shrink-0 items-center justify-center rounded-full border border-admin-accent text-admin-accent">
                            <i class="fa-solid fa-check text-[9px]"></i>
                        </span>
                        We use only the highest quality industrial-grade roofing materials.
                    </li>
                    <li class="flex items-start gap-2.5 text-sm text-admin-ink">
                        <span class="mt-0.5 flex h-4.5 w-4.5 shrink-0 items-center justify-center rounded-full border border-admin-accent text-admin-accent">
                            <i class="fa-solid fa-check text-[9px]"></i>
                        </span>
                        Safety is our priority with 100% adherence to modern construction standards.
                    </li>
                </ul>

                {{-- Ikon sosial — 4 ikon sesuai Figma. WhatsApp pakai data asli (Setting), 3 lainnya '#' (pola sama dengan tombol CTA lain di project yang belum ada route-nya). --}}
                <div class="mt-6 flex items-center gap-3">
                    <a href="#" aria-label="Instagram" class="flex h-9 w-9 items-center justify-center rounded-full border border-admin-ink/15 text-admin-ink transition-colors duration-300 hover:bg-[#1A1A1A] hover:text-white">
                        <i class="fa-brands fa-instagram text-sm"></i>
                    </a>
                    <a href="#" aria-label="TikTok" class="flex h-9 w-9 items-center justify-center rounded-full border border-admin-ink/15 text-admin-ink transition-colors duration-300 hover:bg-[#1A1A1A] hover:text-white">
                        <i class="fa-brands fa-tiktok text-sm"></i>
                    </a>
                    <a href="#" aria-label="Facebook" class="flex h-9 w-9 items-center justify-center rounded-full border border-admin-ink/15 text-admin-ink transition-colors duration-300 hover:bg-[#1A1A1A] hover:text-white">
                        <i class="fa-brands fa-facebook-f text-sm"></i>
                    </a>
                    <a
                        href="{{ $prosesSetting->whatsapp ? 'https://wa.me/'.preg_replace('/\D/', '', $prosesSetting->whatsapp) : '#' }}"
                        target="{{ $prosesSetting->whatsapp ? '_blank' : '_self' }}"
                        rel="noopener"
                        aria-label="WhatsApp"
                        class="flex h-9 w-9 items-center justify-center rounded-full border border-admin-ink/15 text-admin-ink transition-colors duration-300 hover:bg-[#1A1A1A] hover:text-white"
                    >
                        <i class="fa-brands fa-whatsapp text-sm"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>