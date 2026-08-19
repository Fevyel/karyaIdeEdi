{{--
    ==========================================================
    SEDANG DIKERJAKAN — Homepage Karya Ide Edi
    ==========================================================
    Section baru setelah Testimoni, mengikuti referensi desain Figma
    (2 kolom: gambar besar grayscale + tombol play di kiri, teks di
    kanan). Ini ADAPTASI, bukan salinan mentah — teks & label
    diterjemahkan/disesuaikan ke identitas furniture Karya Ide Edi,
    bukan konten roofing/konstruksi dari referensi asli.

    CATATAN JUJUR SOAL ASET GAMBAR:
    Referensi Figma memakai foto lifestyle/action (tukang di atap).
    Project ini TIDAK PUNYA foto sejenis (behind-the-scenes/workshop).
    Foto yang tersedia di storage/app/public/kategori & /produk adalah
    hasil upload testing yang isinya foto pribadi orang lain (bukan
    konten furniture) — SENGAJA TIDAK dipakai karena tidak relevan &
    tidak pantas ditampilkan di website publik.
    Sebagai gantinya, dipakai public/images/admin-login/kursi.png
    (foto produk asli yang sudah ada di project) dengan treatment
    grayscale + panel gelap, supaya section tetap terasa "workshop/
    proses" tanpa memakai gambar yang tidak relevan. Kalau nanti ada
    foto asli proses produksi/workshop, tinggal ganti <img> di bagian
    KIRI dengan foto itu (hapus class grayscale kalau fotonya memang
    sudah punya nuansa gelap/dramatis sendiri).

    CATATAN SOAL ANGKA "12 TAHUN":
    Referensi Figma menulis "25 Years of Excellence" -- angka itu TIDAK
    dipakai karena bukan data asli project. Dipakai "12" karena itu
    angka pengalaman yang SUDAH ditampilkan di Hero (statistik "12th
    Pengalaman"), supaya konsisten dalam satu halaman -- bukan angka
    karangan baru.

    CATATAN SOAL SOCIAL ICON:
    Referensi Figma menampilkan 4 ikon (Instagram, TikTok, Facebook,
    WhatsApp). App\Models\Setting HANYA punya kolom `whatsapp` -- tidak
    ada kolom instagram/tiktok/facebook. Jadi HANYA ikon WhatsApp yang
    ditampilkan (dan hanya kalau kolomnya terisi), sesuai instruksi
    "jangan mengarang link sosial".

    Pemakaian:
        @include('partials.frontend.showcase')
    ==========================================================
--}}
@php
    $showcaseSetting = \App\Models\Setting::current();
    $showcaseWaLink = $showcaseSetting->whatsapp
        ? 'https://wa.me/'.preg_replace('/[^0-9]/', '', $showcaseSetting->whatsapp)
        : null;
@endphp

<section class="bg-[#F9F7F2]">
    <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-0 overflow-hidden rounded-[28px] bg-white shadow-sm sm:mx-6 sm:my-14 lg:grid-cols-2 lg:mx-8 lg:my-20 xl:mx-10">

        {{-- ============ KIRI: Gambar besar grayscale + tombol play + badge ============ --}}
        <div class="relative flex h-80 items-center justify-center overflow-hidden bg-[#2A241E] sm:h-[26rem] lg:h-[32rem]">
            <img
                src="{{ asset('images/admin-login/kursi.png') }}"
                alt="Proses pengerjaan furniture {{ $showcaseSetting->site_name }}"
                class="h-[85%] w-auto object-contain opacity-90 grayscale"
            >

            {{-- Tombol play (dekoratif -- belum ada video untuk dihubungkan) --}}
            <span class="absolute flex h-16 w-16 items-center justify-center rounded-full bg-[#E4483A] text-white shadow-xl shadow-black/30 transition-transform duration-300 hover:scale-105">
                <i class="fa-solid fa-play text-lg"></i>
            </span>

            {{-- Badge bawah kiri --}}
            <div class="absolute bottom-5 left-5 rounded-xl bg-[#1A1A1A] px-4 py-3 shadow-lg">
                <p class="text-[10px] font-bold uppercase tracking-wider text-white/60">Tonton Prosesnya</p>
                <p class="mt-0.5 text-sm font-semibold text-white">12 Tahun Pengalaman</p>
            </div>
        </div>

        {{-- ============ KANAN: Konten teks ============ --}}
        <div class="px-6 py-10 sm:px-10 sm:py-12 lg:px-12 xl:px-16">
            <div class="flex items-center gap-3">
                <span class="h-px w-8 bg-[#3D2B1F]/30"></span>
                <span class="text-[11px] font-semibold uppercase tracking-[0.15em] text-[#8A8880]">Proses Kami</span>
            </div>

            <h2 class="mt-4 font-display text-3xl leading-tight text-[#1A1A1A] sm:text-4xl">
                Sedang Dikerjakan
            </h2>

            <p class="mt-4 max-w-md text-sm leading-relaxed text-[#6B6E76]">
                Setiap detail dikerjakan langsung oleh tangan pengrajin berpengalaman kami, mulai dari
                pemilihan material hingga sentuhan akhir — memastikan setiap karya yang sampai ke rumah
                Anda benar-benar istimewa.
            </p>

            <ul class="mt-6 space-y-4">
                <li class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-[#3D2B1F]/25">
                        <i class="fa-solid fa-check text-[10px] text-[#1A1A1A]"></i>
                    </span>
                    <p class="text-sm leading-relaxed text-[#3D2B1F]">
                        Material kayu solid pilihan, bukan produk pabrikan massal.
                    </p>
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-[#3D2B1F]/25">
                        <i class="fa-solid fa-check text-[10px] text-[#1A1A1A]"></i>
                    </span>
                    <p class="text-sm leading-relaxed text-[#3D2B1F]">
                        Finishing dikerjakan tangan ahli untuk hasil akhir yang rapi dan tahan lama.
                    </p>
                </li>
            </ul>

            @if ($showcaseWaLink)
                <div class="mt-8 flex items-center gap-3">
                    <a
                        href="{{ $showcaseWaLink }}"
                        target="_blank"
                        rel="noopener"
                        aria-label="Hubungi kami lewat WhatsApp"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-[#3D2B1F]/15 text-[#3D2B1F] transition-colors duration-300 hover:border-admin-accent hover:text-admin-accent"
                    >
                        <i class="fa-brands fa-whatsapp text-base"></i>
                    </a>
                </div>
            @endif
        </div>
    </div>
</section>