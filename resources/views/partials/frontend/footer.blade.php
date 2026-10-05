{{--
    ==========================================================
    FOOTER â€” Homepage Karya Ide Edi
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
       referensi Figma (Bahasa Inggris, posisi sama, TIDAK
       diterjemahkan). Semua link sudah disambungkan ke halaman nyata
       (tidak ada lagi yang "#"): "About Us" -> Profil, "Order Status"
       & "Track Your Order" -> halaman tracking (/lacak), "Contact Us"
       -> WhatsApp asli (kalau nomornya terisi di Pengaturan), "Our
       Craftsmen" -> /pengrajin-kami (konten & foto masih DUMMY, lihat
       catatan di view-nya), "Sustainability" -> /keberlanjutan
       (halaman generik soal kualitas/custom furniture), "Careers" ->
       ditaruh persis di bawah section Garansi â€” bukan halaman
       terpisah), "Warranty" -> Profil#garansi (section Garansi,
       isinya klaim yang sama dengan homepage: Garansi Retur 30 Hari).
       "Press" DIHAPUS dari kolom ini atas instruksi eksplisit (skip,
       bukan kelupaan).

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

    $footerWaLink = $footerSetting->whatsappDigits()
        ? 'https://wa.me/'.$footerSetting->whatsappDigits()
        : null;

    // Company & Support: label PERSIS sama dengan referensi Figma
    // (Bahasa Inggris, tidak diterjemahkan) -- href '#' untuk yang
    // halamannya belum ada. "About Us" -> halaman Profil (satu-satunya
    // yang punya padanan nyata di project ini). "Order Status" & "Track
    // Your Order" -> halaman tracking pesanan (/lacak). "Press" sengaja
    // TIDAK ada di array ini (dihapus dari footer atas instruksi).
    $footerCompany = [
        ['label' => 'About Us', 'href' => route('profile.index')],
        ['label' => 'Our Craftsmen', 'href' => route('craftsmen.index')],
        ['label' => 'Sustainability', 'href' => route('sustainability.index')],
    ];

    $footerSupport = [
        ['label' => 'Order Status', 'href' => route('tracking.index')],
        ['label' => 'Track Your Order', 'href' => route('tracking.index')],
        ['label' => 'Contact Us', 'href' => $footerWaLink ?? '#'],
    ];
@endphp

@include('partials.frontend.marquee-brand')

<footer class="bg-[#1A1A1A] text-white">
    <div class="mx-auto max-w-7xl px-6 py-14 sm:px-8 lg:px-10 lg:py-16">

        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 sm:gap-10 lg:grid-cols-4 lg:gap-8">

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
                    {{-- Instagram, TikTok & Facebook: dari App\Models\Setting (Pengaturan admin), hanya tampil kalau link-nya diisi. --}}
                    @if ($footerSetting->instagram_url)
                        <a href="{{ $footerSetting->instagram_url }}" target="_blank" rel="noopener" aria-label="Instagram" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white/70 transition-colors duration-300 hover:bg-white/20 hover:text-white">
                            <i class="fa-brands fa-instagram text-sm"></i>
                        </a>
                    @endif
                    @if ($footerSetting->tiktok_url)
                        <a href="{{ $footerSetting->tiktok_url }}" target="_blank" rel="noopener" aria-label="TikTok" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white/70 transition-colors duration-300 hover:bg-white/20 hover:text-white">
                            <i class="fa-brands fa-tiktok text-sm"></i>
                        </a>
                    @endif
                    @if ($footerSetting->facebook_url)
                        <a href="{{ $footerSetting->facebook_url }}" target="_blank" rel="noopener" aria-label="Facebook" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white/70 transition-colors duration-300 hover:bg-white/20 hover:text-white">
                            <i class="fa-brands fa-facebook-f text-sm"></i>
                        </a>
                    @endif
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
                            <a href="{{ route('products.index', ['category' => $category->slug]) }}" class="text-sm text-white/60 transition-colors duration-300 hover:text-white">
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
        <div data-kie-footer-bottom class="mt-12 flex flex-col items-center gap-4 border-t border-white/10 pt-6 sm:flex-row sm:justify-between">
            <p class="text-[11px] uppercase tracking-wide text-white/40">
                &copy; {{ now()->year }} {{ $footerSetting->site_name }}. All rights reserved.
            </p>

            <div data-kie-footer-legal class="flex items-center gap-6">
                <a href="{{ route('legal.privacy') }}" class="text-[11px] uppercase tracking-wide text-white/40 transition-colors duration-300 hover:text-white/70">Privacy Policy</a>
                <a href="{{ route('legal.terms') }}" class="text-[11px] uppercase tracking-wide text-white/40 transition-colors duration-300 hover:text-white/70">Terms of Service</a>
                <a href="{{ route('legal.cookies') }}" class="text-[11px] uppercase tracking-wide text-white/40 transition-colors duration-300 hover:text-white/70">Cookies</a>
            </div>

            {{-- Badge metode pembayaran -- generik, mengikuti pola visual referensi --}}
                                    {{-- Badge metode pembayaran: memakai file logo OFFICIAL yang disimpan lokal --}}
                        {{-- Badge metode pembayaran -- ukuran + warna dipertahankan seperti versi awal --}}
                        {{-- Badge metode pembayaran -- ukuran & warna asli, logo resmi --}}
            <div data-kie-footer-payments class="flex items-center gap-1.5">
                <a href="{{ route('payment.transfer', 'bca') }}" class="flex h-5 w-8 items-center justify-center overflow-hidden rounded-sm bg-[#4A4A4A]" aria-label="Pembayaran via BCA" title="Pembayaran via BCA"><img src="{{ asset('images/payment-official/bca.png') }}" alt="BCA" style="display:block;max-width:56px;max-height:23px;width:auto;height:auto;object-fit:contain;"></a>
                <a href="{{ route('payment.transfer', 'bri') }}" class="flex h-5 w-8 items-center justify-center overflow-hidden rounded-sm bg-[#4A4A4A]" aria-label="Pembayaran via BRI" title="Pembayaran via BRI"><img src="{{ asset('images/payment-official/bri.png') }}" alt="Bank BRI" style="display:block;max-width:27px;max-height:12px;width:auto;height:auto;object-fit:contain;"></a>
                <a href="{{ route('payment.transfer', 'dana') }}" class="flex h-5 w-8 items-center justify-center overflow-hidden rounded-sm bg-[#4A4A4A]" aria-label="Pembayaran via DANA" title="Pembayaran via DANA"><img src="{{ asset('images/payment-official/dana.svg') }}" alt="DANA" style="display:block;max-width:27px;max-height:12px;width:auto;height:auto;object-fit:contain;"></a>
            </div>
    </div>

{{-- KIE-TICKER-REFERENCE-FOOTER-V2 --}}
<style>
    /*
     * Pita FURNITUR TOKO MEBEL / KARYA IDE EDI
     * dibuat seperti referensi:
     * gelap, tipis, garis atas-bawah, tipografi renggang,
     * menyatu dengan footer.
     *
     * TEKS dan animasi bergeraknya TIDAK diganti.
     */
    [data-kie-reference-ticker-shell] {
        box-sizing: border-box !important;
        width: 100% !important;
        max-width: none !important;

        height: 46px !important;
        min-height: 46px !important;

        margin: 0 !important;
        padding: 0 !important;

        display: flex !important;
        align-items: center !important;

        overflow: hidden !important;

        background: var(--kie-reference-footer-bg, #151719) !important;

        border: 0 !important;
        border-top: 1px solid rgba(255,255,255,.09) !important;
        border-bottom: 1px solid rgba(255,255,255,.09) !important;
        border-radius: 0 !important;

        box-shadow: none !important;
    }

    [data-kie-reference-ticker-track] {
        width: max-content !important;
        min-width: max-content !important;

        margin: 0 !important;

        display: flex !important;
        align-items: center !important;

        white-space: nowrap !important;

        background: transparent !important;
        box-shadow: none !important;

        color: rgba(255,255,255,.70) !important;

        font-family:
            "Courier New",
            ui-monospace,
            SFMono-Regular,
            Menlo,
            Monaco,
            Consolas,
            monospace !important;

        font-size: 11px !important;
        font-weight: 600 !important;
        line-height: 1 !important;

        letter-spacing: .30em !important;
        text-transform: uppercase !important;
    }

    [data-kie-reference-ticker-track] * {
        background: transparent !important;
        box-shadow: none !important;

        color: inherit !important;

        font-family: inherit !important;
        font-size: inherit !important;
        font-weight: inherit !important;
        line-height: inherit !important;
        letter-spacing: inherit !important;
        text-transform: inherit !important;
    }

    footer[data-kie-reference-footer] {
        margin-top: 0 !important;
        border-top: 0 !important;
    }

    @media (max-width: 639.98px) {
        [data-kie-reference-ticker-shell] {
            height: 38px !important;
            min-height: 38px !important;
        }

        [data-kie-reference-ticker-track] {
            font-size: 9px !important;
            letter-spacing: .23em !important;
        }
    }
</style>

<script>
(() => {
    const transparentColor = (value) => {
        return !value
            || value === 'transparent'
            || value === 'rgba(0, 0, 0, 0)';
    };

    const parseRgb = (value) => {
        const match = String(value || '').match(
            /rgba?\(\s*(\d+)[,\s]+(\d+)[,\s]+(\d+)/
        );

        return match
            ? [Number(match[1]), Number(match[2]), Number(match[3])]
            : null;
    };

    const luminance = (value) => {
        const rgb = parseRgb(value);

        if (!rgb) return 999;

        return (
            rgb[0] * .2126 +
            rgb[1] * .7152 +
            rgb[2] * .0722
        );
    };

    const normalize = (value) => {
        return String(value || '')
            .replace(/\s+/g, ' ')
            .trim()
            .toUpperCase();
    };

    const visible = (element) => {
        const rect = element.getBoundingClientRect();
        const style = getComputedStyle(element);

        return rect.width > 0
            && rect.height > 0
            && style.display !== 'none'
            && style.visibility !== 'hidden';
    };

    const applyKieTickerReference = () => {
        const footer = document.querySelector('footer');

        if (!footer) return;

        footer.setAttribute('data-kie-reference-footer', '');

        /*
         * Cari teks pita di SELURUH halaman.
         * Jadi tidak peduli source Blade-nya berada di file mana.
         */
        const textCandidates = [...document.querySelectorAll('body *')]
            .filter((element) => {
                if (!visible(element)) return false;

                const content = normalize(element.textContent);

                return content.includes('FURNITUR TOKO MEBEL')
                    && (
                        content.includes('KARYA IDE EDI')
                        || content.includes('KARYA IDE-EDI')
                    );
            });

        if (!textCandidates.length) return;

        /*
         * Prioritas pertama: elemen yang memang sedang dianimasikan.
         */
        const animatedCandidates = [];

        textCandidates.forEach((candidate) => {
            [candidate, ...candidate.querySelectorAll('*')]
                .forEach((element) => {
                    if (!visible(element)) return;

                    const content = normalize(element.textContent);

                    if (
                        !content.includes('FURNITUR TOKO MEBEL')
                        && !content.includes('KARYA IDE EDI')
                        && !content.includes('KARYA IDE-EDI')
                    ) {
                        return;
                    }

                    const style = getComputedStyle(element);

                    if (
                        style.animationName
                        && style.animationName !== 'none'
                    ) {
                        animatedCandidates.push(element);
                    }
                });
        });

        let track = animatedCandidates[0] || null;

        /*
         * Kalau animasi ditempel lewat parent/utility lain,
         * pilih elemen matching paling kecil.
         */
        if (!track) {
            track = [...textCandidates]
                .sort((a, b) => {
                    const ra = a.getBoundingClientRect();
                    const rb = b.getBoundingClientRect();

                    return (ra.width * ra.height)
                        - (rb.width * rb.height);
                })[0];
        }

        if (!track) return;

        /*
         * Cari wrapper pita:
         * biasanya elemen lebar viewport dengan overflow hidden.
         */
        let shell = track.parentElement;
        let current = track.parentElement;

        while (
            current
            && current !== document.body
            && current !== footer
        ) {
            const rect = current.getBoundingClientRect();
            const style = getComputedStyle(current);

            const overflowSuitable = [
                'hidden',
                'clip',
                'auto'
            ].includes(style.overflowX);

            if (
                rect.width >= window.innerWidth * .70
                && rect.height >= 20
                && rect.height <= 120
                && overflowSuitable
            ) {
                shell = current;
                break;
            }

            current = current.parentElement;
        }

        if (!shell) return;

        /*
         * Ambil warna gelap footer yang BENAR.
         */
        const footerColorCandidates = [
            footer,
            ...footer.querySelectorAll(':scope > *'),
            ...footer.querySelectorAll(':scope > * > *')
        ]
            .map((element) => getComputedStyle(element).backgroundColor)
            .filter((color) => !transparentColor(color))
            .sort((a, b) => luminance(a) - luminance(b));

        const footerBackground =
            footerColorCandidates[0] || '#151719';

        shell.setAttribute(
            'data-kie-reference-ticker-shell',
            ''
        );

        track.setAttribute(
            'data-kie-reference-ticker-track',
            ''
        );

        shell.style.setProperty(
            '--kie-reference-footer-bg',
            footerBackground
        );

        /*
         * Pastikan benar-benar menempel dengan footer.
         */
        shell.style.marginBottom = '0';
        shell.style.marginTop = '0';
        footer.style.marginTop = '0';

        /*
         * Hilangkan warna emas dari wrapper dekat track,
         * tanpa menyentuh isi footer.
         */
        let parent = track.parentElement;

        while (
            parent
            && parent !== shell
            && parent !== footer
        ) {
            parent.style.background = 'transparent';
            parent.style.boxShadow = 'none';
            parent = parent.parentElement;
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            applyKieTickerReference,
            { once: true }
        );
    } else {
        applyKieTickerReference();
    }

    document.addEventListener(
        'livewire:navigated',
        applyKieTickerReference
    );

    window.addEventListener(
        'resize',
        applyKieTickerReference
    );
})();
</script>
</footer>

<x-whatsapp-float :href="$footerWaLink" />
<x-back-to-top />