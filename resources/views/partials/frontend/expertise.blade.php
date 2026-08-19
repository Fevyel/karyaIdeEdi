{{--
    ==========================================================
    "IN ACTION" — split panel foto kiri / teks kanan — Homepage
    ==========================================================
    Section ditempatkan tepat SETELAH "Ulasan Pelanggan Kami"
    (testimonials.blade.php), mengikuti referensi Figma milik
    pengguna SECARA LITERAL — struktur, spacing, warna, DAN teks
    (atas instruksi eksplisit: jangan diinterpretasi ulang, jangan
    disederhanakan, jangan diganti kontennya).

    CATATAN JUJUR (bukan alasan untuk menyimpang, cuma transparansi):
    - Teks di section ini ("Mintech", roofing/atap, "25 Years of
      Excellence") persis seperti di desain Figma yang diberikan —
      BUKAN konten furniture. Ini disalin apa adanya sesuai instruksi
      eksplisit pengguna untuk mengikuti Figma 100%, bukan salah ketik.
    - Foto: Figma memakai foto pekerja di atap (full-bleed, hitam-putih).
      Project ini tidak punya foto lifestyle/orang di public/images —
      isinya cuma 2 foto produk transparan (lemari & kursi, sudah
      diaudit di hero.blade.php & versi sebelumnya section ini).
      Dipakai kursi.png + filter grayscale sebagai placeholder paling
      mendekati (tetap hitam-putih seperti Figma), sampai ada foto
      lifestyle asli yang diunggah.

    Pemakaian:
        @include('partials.frontend.expertise')
    ==========================================================
--}}
<section class="bg-white">
    <div class="grid grid-cols-1 lg:grid-cols-2 lg:items-stretch">

        {{-- ============ KIRI: Panel foto gelap — full-bleed, badge & tombol play ============ --}}
        <div class="relative h-90 overflow-hidden bg-[#1A1A1A] sm:h-115 lg:h-auto lg:min-h-140">
            <img
                src="{{ asset('images/admin-login/kursi.png') }}"
                alt="In Action"
                class="absolute left-1/2 top-1/2 h-[78%] w-auto -translate-x-1/2 -translate-y-1/2 object-contain grayscale"
            >

            {{-- Tombol play — dekoratif, sesuai Figma (tidak ada video di project ini) --}}
            <span class="absolute left-1/2 top-1/2 flex h-16 w-16 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-[#D6473F] shadow-lg">
                <i class="fa-solid fa-play ml-1 text-lg text-white"></i>
            </span>

            {{-- Badge overlay bawah-kiri --}}
            <div class="absolute bottom-6 left-6 rounded-lg bg-[#1A1A1A] px-4 py-3 shadow-lg">
                <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-white/60">Watch Now</p>
                <p class="mt-0.5 text-sm font-semibold text-white">25 Years of Excellence</p>
            </div>
        </div>

        {{-- ============ KANAN: Eyebrow, heading, paragraf, checklist, ikon sosial ============ --}}
        <div class="flex flex-col justify-center bg-white px-6 py-12 sm:px-8 lg:px-12 lg:py-16 xl:px-16">

            {{-- Eyebrow --}}
            <div class="flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.2em] text-[#1A1A1A]">
                <span class="h-px w-8 bg-[#1A1A1A]"></span>
                Trusted Best Company
            </div>

            {{-- Heading --}}
            <h2 class="mt-4 text-4xl font-extrabold uppercase leading-[1.05] text-[#1A1A1A] sm:text-5xl">
                In Action
            </h2>

            <p class="mt-5 max-w-md text-sm leading-relaxed text-[#6B6E76]">
                Mintech has been helping organizations throughout the world to manage
                their roofing infrastructure with our unique approach to technology
                management and consultancy. Our process ensures every shingle is
                perfectly placed for lifetime durability.
            </p>

            {{-- Checklist --}}
            <div class="mt-6 space-y-4">
                <div class="flex items-start gap-3">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-[#1A1A1A]/25 text-[#1A1A1A]">
                        <i class="fa-solid fa-check text-[11px]"></i>
                    </span>
                    <p class="max-w-sm text-sm leading-relaxed text-[#1A1A1A]">We use only the highest quality industrial-grade roofing materials.</p>
                </div>
                <div class="flex items-start gap-3">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-[#1A1A1A]/25 text-[#1A1A1A]">
                        <i class="fa-solid fa-check text-[11px]"></i>
                    </span>
                    <p class="max-w-sm text-sm leading-relaxed text-[#1A1A1A]">Safety is our priority with 100% adherence to modern construction standards.</p>
                </div>
            </div>

            {{-- Ikon sosial — 4 sel persegi berdampingan dalam satu container bergaris, sesuai Figma --}}
            <div class="mt-7 inline-flex w-fit overflow-hidden rounded-lg border border-[#1A1A1A]/15">
                <a href="#" aria-label="Instagram" class="flex h-11 w-11 items-center justify-center border-r border-[#1A1A1A]/15 text-[#1A1A1A] transition-colors duration-300 hover:bg-[#F1F1F1]">
                    <i class="fa-brands fa-instagram text-sm"></i>
                </a>
                <a href="#" aria-label="TikTok" class="flex h-11 w-11 items-center justify-center border-r border-[#1A1A1A]/15 text-[#1A1A1A] transition-colors duration-300 hover:bg-[#F1F1F1]">
                    <i class="fa-brands fa-tiktok text-sm"></i>
                </a>
                <a href="#" aria-label="Facebook" class="flex h-11 w-11 items-center justify-center border-r border-[#1A1A1A]/15 text-[#1A1A1A] transition-colors duration-300 hover:bg-[#F1F1F1]">
                    <i class="fa-brands fa-facebook-f text-sm"></i>
                </a>
                @php $expertiseWhatsapp = \App\Models\Setting::current()->whatsapp; @endphp
                <a
                    href="{{ $expertiseWhatsapp ? 'https://wa.me/'.preg_replace('/\D/', '', $expertiseWhatsapp) : '#' }}"
                    target="{{ $expertiseWhatsapp ? '_blank' : '_self' }}"
                    rel="noopener"
                    aria-label="WhatsApp"
                    class="flex h-11 w-11 items-center justify-center text-[#1A1A1A] transition-colors duration-300 hover:bg-[#F1F1F1]"
                >
                    <i class="fa-brands fa-whatsapp text-sm"></i>
                </a>
            </div>
        </div>
    </div>
</section>
