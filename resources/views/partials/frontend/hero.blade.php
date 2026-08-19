{{--
    ==========================================================
    HERO SECTION — Homepage Karya Ide Edi
    ==========================================================
    Komponen UI murni (belum ada logika bisnis apapun), mengikuti
    pola yang sama dengan navbar: pakai token warna & font yang
    sudah ada di resources/css/app.css (--color-admin-*, font-display)
    UNTUK STRUKTUR, tapi beberapa warna di section ini sengaja memakai
    nilai hex arbitrary (bukan token global) karena disamakan presisi
    dengan hasil sampling piksel dari desain referensi Figma —
    supaya tidak mengubah warna section/halaman lain yang masih
    memakai token global tersebut.

    - Judul, tagline & CTA memakai nama toko dari Pengaturan Website
      (App\Models\Setting) supaya konsisten dengan navbar.
    - Deskripsi, tagline "Menghidupkan Setiap Sudut", label tombol,
      dan angka statistik MASIH statis (hardcode) — belum ada
      sumber data untuk itu di project ini. Gampang diganti nanti
      begitu ada kolom/tabel yang sesuai.
    - Tombol "Lihat Katalog" & "Jelajahi Profil" mengarah ke '#'
      karena route Produk & Profil belum dibuat (pola sama seperti
      $navMenu di navbar).
    - Gambar produk kanan: BELUM ada foto asli (foto di desain Figma
      memakai model asli, TIDAK diganti dengan foto lain dari web
      untuk menghindari masalah hak cipta/wajah orang). Tinggal
      export foto itu langsung dari Figma dan taruh di
      public/images/hero-furniture.png (lihat komentar di bagian
      gambar di bawah).

    Pemakaian:
        @include('partials.frontend.hero')
    ==========================================================
--}}
@php
    $heroSetting = \App\Models\Setting::current();
@endphp

<section class="relative overflow-hidden bg-[#F9F7F2]">
    <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 py-12 sm:px-8 lg:grid-cols-2 lg:gap-8 lg:px-10 lg:py-16">

        {{-- ============ KIRI: Teks, CTA, Statistik ============ --}}
        <div class="animate-fade-in-up">
            <h1 class="font-display text-3xl leading-[1.15] text-[#3D2B1F] sm:text-4xl lg:text-[2.5rem] xl:text-[2.75rem]">
                <span class="font-semibold">Furnitur</span><br>
                <span class="font-semibold">{{ $heroSetting->site_name }}</span><br>
                <span class="font-normal">Menghidupkan Setiap Sudut</span>
            </h1>

            <p class="mt-6 max-w-md text-sm leading-relaxed text-[#6B6E76]">
                Setiap karya dibuat dengan tangan menggunakan material pilihan berkualitas
                tinggi, dirancang secara teliti dan detail untuk mempercantik interior Anda.
            </p>

            {{-- CTA --}}
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <a
                    href="{{ route('products.index') }}"
                    class="group inline-flex items-center gap-2 rounded-lg bg-[#1A1A1A] px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-all duration-300 hover:bg-black hover:shadow-md"
                >
                    Lihat Katalog
                    <x-icon-arrow direction="right" class="transition-transform duration-300 group-hover:translate-x-1" />
                </a>
                <a
                    href="{{ route('profile.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-[#DCDDD7] px-5 py-2.5 text-sm font-medium text-[#3D2B1F] transition-all duration-300 hover:border-admin-accent hover:text-admin-accent"
                >
                    Jelajahi Profil
                </a>
            </div>

            {{-- Statistik --}}
            <dl class="mt-5 flex flex-wrap gap-x-10 gap-y-4 border-t border-[#3D2B1F]/10 pt-4">
                <div>
                    <dt class="font-display text-xl text-[#1A1A1A] sm:text-2xl">500+</dt>
                    <dd class="mt-1 text-xs font-medium uppercase tracking-wide text-[#8A8880]">Pelanggan Puas</dd>
                </div>
                <div>
                    <dt class="font-display text-xl text-[#1A1A1A] sm:text-2xl">2.000+</dt>
                    <dd class="mt-1 text-xs font-medium uppercase tracking-wide text-[#8A8880]">Karya Produk</dd>
                </div>
                <div>
                    <dt class="font-display text-xl text-[#1A1A1A] sm:text-2xl">12th</dt>
                    <dd class="mt-1 text-xs font-medium uppercase tracking-wide text-[#8A8880]">Pengalaman</dd>
                </div>
            </dl>
        </div>

        {{-- ============ KANAN: Gambar produk ============ --}}
        <div class="relative flex items-center justify-center">
            {{--
                Foto produk — pakai aset yang sudah ada di project
                (public/images/admin-login/hero.png), bukan foto dari
                referensi desain (menghindari reproduksi foto wajah orang).
            --}}
            <span class="relative flex aspect-10/9 w-full items-center justify-center overflow-hidden rounded-[28px] bg-[#D7A26E] shadow-xl shadow-black/10">
                <img
                    src="{{ asset('images/admin-login/hero.png') }}"
                    alt="Furniture {{ $heroSetting->site_name }}"
                    class="h-[88%] w-auto object-contain drop-shadow-2xl"
                >
            </span>
        </div>
    </div>
</section>