{{--
    ==========================================================
    FOOTER — Homepage Karya Ide Edi
    ==========================================================
    Layout & struktur mengikuti referensi Figma sedekat mungkin:
    background gelap, brand + 3 kolom (masing-masing 5 item persis
    seperti referensi), bottom bar dengan copyright, link legal, dan
    ikon metode pembayaran.

    3 PENYESUAIAN YANG SENGAJA TETAP DIPERTAHANKAN (bukan salah baca
    instruksi -- ini batas yang tidak bisa dilewati tanpa membuat
    website menampilkan info yang keliru):

    1. Nama brand & copyright TETAP "Karya Ide Edi" (bukan "Luma &
       Living" seperti di referensi) -- itu nama brand lain, mengganti
       nama sendiri jadi nama kompetitor jelas bukan yang dimaksud.

    2. Kolom "KATALOG" query LANGSUNG dari App\Models\Category (data
       kategori ASLI yang dikelola admin di menu Kategori) -- BUKAN
       hardcode "Bedroom/Dining/Office" dari referensi. Kebetulan
       sangat cocok: kategori asli project ini (Sofa, Meja, Kursi,
       Lemari, dst -- lihat database/seeders/CategorySeeder.php)
       punya jumlah & gaya penulisan yang mirip referensi.

    3. Kolom "COMPANY" & "SUPPORT": labelnya SAMA PERSIS dengan
       referensi Figma (Bahasa Inggris, 5 item tiap kolom, posisi
       sama, TIDAK diterjemahkan), tapi karena halaman "Careers",
       "Press", "Order Status", "Track Your Order", dll BELUM ADA di
       project ini, link-nya mengarah ke "#" -- pola yang SUDAH ada
       sebelumnya di project ini juga (lihat tombol "Jelajahi Profil"
       di hero.blade.php, sama-sama "#" karena halamannya belum
       dibuat). "Contact Us" terhubung ke WhatsApp asli kalau data-nya
       terisi di Pengaturan.

    Ikon metode pembayaran (Visa/Mastercard/Apple Pay/PayPal) ditampilkan
    sebagai badge generik ala kebanyakan website (belum tentu semua
    sudah didukung toko ini secara aktual) -- murni mengikuti pola
    visual referensi.

    Pemakaian:
        @include('partials.frontend.footer')
    ==========================================================
--}}
@php
    $footerSetting = \App\Models\Setting::current();

    $footerCategories = \App\Models\Category::query()
        ->active()
        ->ordered()
        ->take(5)
        ->get();

    $footerWaLink = $footerSetting->whatsapp
        ? 'https://wa.me/'.preg_replace('/[^0-9]/', '', $footerSetting->whatsapp)
        : null;

    // Company & Support: label PERSIS sama dengan referensi Figma
    // (Bahasa Inggris, tidak diterjemahkan) -- href '#' untuk yang
    // halamannya belum ada, pola yang sama seperti tombol "Jelajahi
    // Profil" di hero.blade.php.
    $footerCompany = [
        ['label' => 'About Us', 'href' => '#'],
        ['label' => 'Our Craftsmen', 'href' => '#'],
        ['label' => 'Sustainability', 'href' => '#'],
        ['label' => 'Careers', 'href' => '#'],
        ['label' => 'Press', 'href' => '#'],
    ];

    $footerSupport = [
        ['label' => 'Order Status', 'href' => '#'],
        ['label' => 'Shipping & Returns', 'href' => '#'],
        ['label' => 'Track Your Order', 'href' => '#'],
        ['label' => 'Warranty', 'href' => '#'],
        ['label' => 'Contact Us', 'href' => $footerWaLink ?? '#'],
    ];
@endphp

<footer class="bg-[#1A1A1A] text-white">
    <div class="mx-auto max-w-7xl px-6 py-14 sm:px-8 lg:px-10 lg:py-16">

        <div class="grid grid-cols-2 gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8">

            {{-- ============ KOLOM 1: BRAND ============ --}}
            <div class="col-span-2 lg:col-span-1">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    @include('partials.logo', [
                        'boxSize' => 'h-9 w-9',
                        'rounded' => 'rounded-lg',
                        'boxClass' => 'bg-white/10',
                        'iconClass' => 'text-sm text-white',
                    ])
                    <span class="font-display text-lg font-semibold text-white">
                        {{ $footerSetting->site_name }}
                    </span>
                </a>

                <p class="mt-4 max-w-70 text-sm leading-relaxed text-white/50">
                    Keanggunan di setiap sudut. Kami membuat lebih dari sekadar furnitur kami merancang ruang tempat kenangan.
                </p>

                <div class="mt-5 flex items-center gap-3">
                    {{-- Instagram & TikTok: placeholder visual, App\Models\Setting belum punya kolomnya --}}
                    <a href="#" aria-label="Instagram" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white/70 transition-colors duration-300 hover:bg-white/20 hover:text-white">
                        <i class="fa-brands fa-instagram text-sm"></i>
                    </a>
                    <a href="#" aria-label="TikTok" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white/70 transition-colors duration-300 hover:bg-white/20 hover:text-white">
                        <i class="fa-brands fa-tiktok text-sm"></i>
                    </a>
                    <a href="#" aria-label="Facebook" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white/70 transition-colors duration-300 hover:bg-white/20 hover:text-white">
                        <i class="fa-brands fa-facebook-f text-sm"></i>
                    </a>
                    @if ($footerWaLink)
                        <a href="{{ $footerWaLink }}" target="_blank" rel="noopener" aria-label="WhatsApp" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white/70 transition-colors duration-300 hover:bg-white/20 hover:text-white">
                            <i class="fa-brands fa-whatsapp text-sm"></i>
                        </a>
                    @endif
                </div>
            </div>

            {{-- ============ KOLOM 2: KATALOG (data kategori ASLI) ============ --}}
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.15em] text-white/40">Katalog</p>
                <ul class="mt-4 space-y-2.5">
                    @forelse ($footerCategories as $category)
                        <li>
                            <a href="#" class="text-sm text-white/60 transition-colors duration-300 hover:text-white">
                                {{ $category->name }}
                            </a>
                        </li>
                    @empty
                        <li>
                            <a href="{{ route('home') }}" wire:navigate class="text-sm text-white/60 transition-colors duration-300 hover:text-white">
                                Lihat Semua Produk
                            </a>
                        </li>
                    @endforelse
                </ul>
            </div>

            {{-- ============ KOLOM 3: COMPANY ============ --}}
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.15em] text-white/40">Company</p>
                <ul class="mt-4 space-y-2.5">
                    @foreach ($footerCompany as $item)
                        <li>
                            <a href="{{ $item['href'] }}" class="text-sm text-white/60 transition-colors duration-300 hover:text-white">
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- ============ KOLOM 4: SUPPORT ============ --}}
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.15em] text-white/40">Support</p>
                <ul class="mt-4 space-y-2.5">
                    @foreach ($footerSupport as $item)
                        <li>
                            <a
                                href="{{ $item['href'] }}"
                                @if ($item['href'] === $footerWaLink) target="_blank" rel="noopener" @endif
                                class="text-sm text-white/60 transition-colors duration-300 hover:text-white"
                            >
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- ============ BOTTOM BAR ============ --}}
        <div class="mt-12 flex flex-col items-center gap-4 border-t border-white/10 pt-6 sm:flex-row sm:justify-between">
            <p class="text-[11px] uppercase tracking-wide text-white/40">
                &copy; {{ now()->year }} {{ $footerSetting->site_name }}. All rights reserved.
            </p>

            <div class="flex items-center gap-6">
                <a href="#" class="text-[11px] uppercase tracking-wide text-white/40 transition-colors duration-300 hover:text-white/70">Privacy Policy</a>
                <a href="#" class="text-[11px] uppercase tracking-wide text-white/40 transition-colors duration-300 hover:text-white/70">Terms of Service</a>
                <a href="#" class="text-[11px] uppercase tracking-wide text-white/40 transition-colors duration-300 hover:text-white/70">Cookies</a>
            </div>

            {{-- Badge metode pembayaran -- generik, mengikuti pola visual referensi --}}
            <div class="flex items-center gap-1.5">
                <span class="flex h-5 w-8 items-center justify-center overflow-hidden rounded-sm bg-[#4A4A4A]">
                    <i class="fa-brands fa-cc-visa text-[10px] text-[#1A1A1A]/70"></i>
                </span>
                <span class="flex h-5 w-8 items-center justify-center overflow-hidden rounded-sm bg-[#4A4A4A]">
                    <i class="fa-brands fa-cc-mastercard text-[10px] text-[#1A1A1A]/70"></i>
</span>
                <span class="flex h-5 w-8 items-center justify-center overflow-hidden rounded-sm bg-[#4A4A4A]">
                    <i class="fa-brands fa-apple-pay text-[10px] text-[#1A1A1A]/70"></i>
                </span>
                <span class="flex h-5 w-8 items-center justify-center overflow-hidden rounded-sm bg-[#4A4A4A]">
                    <i class="fa-brands fa-paypal text-[10px] text-[#1A1A1A]/70"></i>
                </span>
            </div>
        </div>
    </div>
</footer>