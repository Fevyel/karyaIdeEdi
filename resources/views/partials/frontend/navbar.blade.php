@php
    $navSetting = \App\Models\Setting::current();

    $navMenu = [
        ['label' => 'Beranda',   'route' => 'home',              'icon' => 'fa-house'],
        ['label' => 'Tentang Kami', 'route' => 'profile.index',     'icon' => 'fa-couch'],
        ['label' => 'Produk',    'route' => 'products.index',    'icon' => 'fa-layer-group'],
        ['label' => 'Testimoni', 'route' => 'testimonials.index','icon' => 'fa-star'],
        ['label' => 'Dokumentasi',   'route' => 'booking.index',     'icon' => 'fa-calendar-check'],
    ];

    $navCategories = \App\Models\Category::query()
        ->active()
        ->ordered()
        ->get();

    $currentSearch = trim((string) request('search', ''));
    $currentCategory = trim((string) request('category', ''));
@endphp

{{-- KIE-FRONTEND-RESPONSIVE:START
     Responsive compact v3. Desktop >=1024px sengaja tidak disentuh. --}}
@once
<style>
    /* ==========================================================
       TABLET + MOBILE ONLY (<1024px)
       ========================================================== */
    @media (max-width: 1023.98px) {
        html[data-site='frontend'] {
            zoom: 100%;
            font-size: 15px;
            width: 100%;
            max-width: 100%;
            overflow-x: clip;
        }
        html[data-site='frontend'] body {
            width: 100%;
            max-width: 100%;
            overflow-x: clip;
        }
        html[data-site='frontend'] img,
        html[data-site='frontend'] video,
        html[data-site='frontend'] iframe { max-width: 100%; }

        /* Ruang antar section dipadatkan di seluruh frontend non-desktop. */
        html[data-site='frontend'] .frame-seam-strip { height: 8px !important; }
        html[data-site='frontend'] section[class~='py-12'],
        html[data-site='frontend'] section[class~='py-14'],
        html[data-site='frontend'] section[class~='py-16'],
        html[data-site='frontend'] section[class~='py-18'],
        html[data-site='frontend'] section[class~='py-20'],
        html[data-site='frontend'] section[class~='py-24'],
        html[data-site='frontend'] section > [class~='py-12'],
        html[data-site='frontend'] section > [class~='py-14'],
        html[data-site='frontend'] section > [class~='py-16'],
        html[data-site='frontend'] section > [class~='py-18'],
        html[data-site='frontend'] section > [class~='py-20'],
        html[data-site='frontend'] section > [class~='py-24'] {
            padding-top: 2.75rem !important;
            padding-bottom: 2.75rem !important;
        }
        html[data-site='frontend'] section [class~='mt-16'],
        html[data-site='frontend'] section [class~='mt-14'],
        html[data-site='frontend'] section [class~='mt-12'] { margin-top: 2rem !important; }
        html[data-site='frontend'] section [class~='mt-10'] { margin-top: 1.65rem !important; }
        html[data-site='frontend'] section [class~='mt-8'] { margin-top: 1.35rem !important; }
        html[data-site='frontend'] section [class~='gap-16'],
        html[data-site='frontend'] section [class~='gap-12'] { gap: 2rem !important; }
        html[data-site='frontend'] section [class~='gap-10'],
        html[data-site='frontend'] section [class~='gap-8'] { gap: 1.5rem !important; }
        html[data-site='frontend'] section [class~='leading-9'] { line-height: 1.7 !important; }

        /* Navbar: tetap lengkap tetapi lebih padat. */
        html[data-site='frontend'] [data-kie-navbar-main] {
            min-width: 0;
            min-height: 0;
        }
        html[data-site='frontend'] [data-kie-navbar-search] { min-width: 0; }
        html[data-site='frontend'] [data-kie-navbar-mobile-menu] {
            overscroll-behavior-x: contain;
            scroll-snap-type: x proximity;
            -webkit-overflow-scrolling: touch;
        }
        html[data-site='frontend'] [data-kie-navbar-mobile-menu] > a {
            scroll-snap-align: start;
            min-height: 2.45rem;
            height: 2.45rem;
            font-size: 12px !important;
        }

        /* Beranda: Produk & Kategori menjadi slider horizontal. */
        html[data-site='frontend'] [data-kie-home-products-slider],
        html[data-site='frontend'] [data-kie-home-categories-slider] {
            display: flex !important;
            grid-template-columns: none !important;
            overflow-x: auto !important;
            overflow-y: visible !important;
            gap: .85rem !important;
            margin-left: -1rem !important;
            margin-right: -1rem !important;
            padding-left: 1rem !important;
            padding-right: 1rem !important;
            padding-bottom: .75rem !important;
            scroll-snap-type: x mandatory;
            scroll-padding-left: 1rem;
            overscroll-behavior-x: contain;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        html[data-site='frontend'] [data-kie-home-products-slider]::-webkit-scrollbar,
        html[data-site='frontend'] [data-kie-home-categories-slider]::-webkit-scrollbar { display: none; }
        html[data-site='frontend'] [data-kie-home-products-slider] > *,
        html[data-site='frontend'] [data-kie-home-categories-slider] > * {
            scroll-snap-align: start;
            min-width: 0;
        }
        html[data-site='frontend'] [data-kie-home-products-slider] > * {
            flex: 0 0 min(42vw, 18rem);
        }
        html[data-site='frontend'] [data-kie-home-categories-slider] > * {
            flex: 0 0 min(34vw, 15rem);
        }

        /* Katalog: 2 produk per baris sampai sebelum desktop. */
        html[data-site='frontend'] [data-kie-product-grid] {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            column-gap: 1rem !important;
            row-gap: 2rem !important;
        }
        html[data-site='frontend'] [data-kie-product-grid] [data-favorite-product],
        html[data-site='frontend'] [data-kie-product-grid] [data-cart-product] {
            width: 2.15rem !important;
            height: 2.15rem !important;
        }

        /* Testimoni: tetap slider, tetapi kartu jauh lebih ringkas. */
        html[data-site='frontend'] [data-kie-testimonials-track] {
            display: flex !important;
            grid-template-columns: none !important;
            overflow-x: auto !important;
            overflow-y: visible !important;
            gap: .85rem !important;
            padding-bottom: .75rem !important;
            scroll-snap-type: x mandatory;
            overscroll-behavior-x: contain;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        html[data-site='frontend'] [data-kie-testimonials-track]::-webkit-scrollbar { display: none; }
        html[data-site='frontend'] [data-kie-testimonial-card] {
            flex: 0 0 min(64vw, 18rem) !important;
            width: auto !important;
            max-width: 18rem;
            padding: 1rem !important;
            border-radius: 1rem !important;
            scroll-snap-align: start;
        }
        html[data-site='frontend'] [data-kie-testimonial-card] img.h-11,
        html[data-site='frontend'] [data-kie-testimonial-card] span.h-11 {
            width: 2.25rem !important;
            height: 2.25rem !important;
        }
        html[data-site='frontend'] [data-kie-testimonial-card] p.text-sm { font-size: .78rem !important; }
        html[data-site='frontend'] [data-kie-testimonial-card] .mt-4 { margin-top: .75rem !important; }
        html[data-site='frontend'] [data-kie-testimonial-card] .mt-3 { margin-top: .6rem !important; }

        /* Detail produk: tab tidak memaksa layar melebar. */
        html[data-site='frontend'] [data-kie-product-tabs] {
            max-width: 100%;
            overflow-x: auto;
            overscroll-behavior-x: contain;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }
        html[data-site='frontend'] [data-kie-product-tabs]::-webkit-scrollbar { display: none; }
        html[data-site='frontend'] [data-kie-product-tabs] > button { flex: 0 0 auto; white-space: nowrap; }

        /* Footer & floating controls. */
        html[data-site='frontend'] [data-kie-footer-bottom],
        html[data-site='frontend'] [data-kie-footer-legal] { flex-wrap: wrap; }
        html[data-site='frontend'] [data-kie-footer-payments] { justify-content: center; }
        html[data-site='frontend'] #back-to-top-btn,
        html[data-site='frontend'] #whatsapp-float-btn {
            right: max(.85rem, env(safe-area-inset-right)) !important;
        }
        html[data-site='frontend'] [data-kie-payment-number] {
            overflow-wrap: anywhere;
            word-break: break-word;
        }
    }

    /* ==========================================================
       PHONE ONLY (<640px): lebih kecil dan hemat ruang.
       ========================================================== */
    @media (max-width: 639.98px) {
        html[data-site='frontend'] { font-size: 14px; }

        html[data-site='frontend'] section[class~='py-12'],
        html[data-site='frontend'] section[class~='py-14'],
        html[data-site='frontend'] section[class~='py-16'],
        html[data-site='frontend'] section[class~='py-18'],
        html[data-site='frontend'] section[class~='py-20'],
        html[data-site='frontend'] section[class~='py-24'],
        html[data-site='frontend'] section > [class~='py-12'],
        html[data-site='frontend'] section > [class~='py-14'],
        html[data-site='frontend'] section > [class~='py-16'],
        html[data-site='frontend'] section > [class~='py-18'],
        html[data-site='frontend'] section > [class~='py-20'],
        html[data-site='frontend'] section > [class~='py-24'] {
            padding-top: 2.15rem !important;
            padding-bottom: 2.15rem !important;
        }
        html[data-site='frontend'] .frame-seam-strip { height: 6px !important; }

        html[data-site='frontend'] section [class~='mt-16'],
        html[data-site='frontend'] section [class~='mt-14'],
        html[data-site='frontend'] section [class~='mt-12'] { margin-top: 1.5rem !important; }
        html[data-site='frontend'] section [class~='mt-10'] { margin-top: 1.25rem !important; }
        html[data-site='frontend'] section [class~='mt-8'] { margin-top: 1rem !important; }
        html[data-site='frontend'] section [class~='gap-16'],
        html[data-site='frontend'] section [class~='gap-12'] { gap: 1.5rem !important; }
        html[data-site='frontend'] section [class~='gap-10'],
        html[data-site='frontend'] section [class~='gap-8'] { gap: 1.15rem !important; }

        /* Tipografi + gutter halaman dibuat lebih proporsional untuk HP. */
        html[data-site='frontend'] section h1 {
            font-size: clamp(2rem, 9vw, 2.5rem) !important;
            line-height: 1.06 !important;
        }
        html[data-site='frontend'] section h2 {
            font-size: clamp(1.45rem, 7vw, 1.9rem) !important;
            line-height: 1.12 !important;
        }
        html[data-site='frontend'] section > [class~='px-6'],
        html[data-site='frontend'] section > [class~='px-5'] {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        /* Navbar 2 baris yang lebih ramping. */
        html[data-site='frontend'] [data-kie-navbar-main] {
            display: grid !important;
            grid-template-columns: auto minmax(0, 1fr);
            grid-template-rows: auto auto;
            height: auto !important;
            column-gap: .65rem;
            row-gap: .45rem;
            padding: .5rem .85rem .45rem !important;
        }
        html[data-site='frontend'] [data-kie-navbar-actions] { display: contents !important; }
        html[data-site='frontend'] [data-kie-navbar-search] {
            grid-column: 1 / -1;
            grid-row: 2;
            width: 100% !important;
            height: 2.35rem !important;
        }
        html[data-site='frontend'] [data-kie-navbar-icons] {
            grid-column: 2;
            grid-row: 1;
            justify-self: end;
        }
        html[data-site='frontend'] [data-kie-navbar-icons] > a {
            width: 2.35rem !important;
            height: 2.35rem !important;
        }
        html[data-site='frontend'] [data-kie-navbar-mobile-menu] {
            justify-content: center !important;
            gap: .45rem;
            padding-left: .35rem !important;
            padding-right: .35rem !important;
        }
        html[data-site='frontend'] [data-kie-navbar-mobile-menu] > a {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        /* Hero Beranda lebih pendek dan judul tidak mendominasi layar HP. */
        html[data-site='frontend'] [data-kie-home-hero] { min-height: auto !important; }
        html[data-site='frontend'] [data-kie-home-hero-content] {
            padding-top: 2.5rem !important;
            padding-bottom: 2.75rem !important;
        }
        html[data-site='frontend'] [data-kie-home-hero-title] {
            font-size: clamp(2.15rem, 10.5vw, 2.8rem) !important;
            line-height: 1 !important;
        }
        html[data-site='frontend'] [data-kie-home-hero-actions] {
            display: grid !important;
            grid-template-columns: 1fr;
            gap: .6rem !important;
            width: min(100%, 19rem);
            margin-left: auto;
            margin-right: auto;
        }
        html[data-site='frontend'] [data-kie-home-hero-actions] > a {
            width: 100%;
            min-height: 2.7rem;
            justify-content: center;
        }
        html[data-site='frontend'] [data-kie-home-hero-stats] {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            align-items: stretch;
            gap: .35rem !important;
        }
        html[data-site='frontend'] [data-kie-home-hero-stats] > div {
            min-width: 0 !important;
            padding: .55rem .35rem !important;
        }

        /* Slider HP: kartu cukup kecil sehingga kartu berikutnya terlihat sedikit. */
        html[data-site='frontend'] [data-kie-home-products-slider] > * {
            flex-basis: min(62vw, 15.5rem);
        }
        html[data-site='frontend'] [data-kie-home-categories-slider] > * {
            flex-basis: min(54vw, 13.5rem);
        }

        html[data-site='frontend'] [data-kie-product-grid] {
            column-gap: .7rem !important;
            row-gap: 1.45rem !important;
        }
        html[data-site='frontend'] [data-kie-product-grid] h3 { font-size: .86rem !important; }
        html[data-site='frontend'] [data-kie-product-grid] .pt-3 { padding-top: .6rem !important; }
        html[data-site='frontend'] [data-kie-product-grid] [data-favorite-product],
        html[data-site='frontend'] [data-kie-product-grid] [data-cart-product] {
            width: 1.95rem !important;
            height: 1.95rem !important;
        }

        html[data-site='frontend'] [data-kie-testimonial-card] {
            flex-basis: min(64vw, 14.5rem) !important;
            max-width: 14.5rem;
            padding: .85rem !important;
        }

        /* Dokumentasi: video portrait dua kolom, tetapi viewer tetap ukuran/aspect asli. */
        html[data-site='frontend'] [data-kie-video-grid] {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: .7rem !important;
        }
        html[data-site='frontend'] [data-kie-video-card] {
            border-radius: 1rem !important;
        }
        html[data-site='frontend'] [data-kie-video-card] [data-kie-video-caption] {
            padding: .75rem !important;
        }
        html[data-site='frontend'] [data-kie-video-card] [data-kie-video-caption] h3 {
            font-size: .82rem !important;
            line-height: 1.25 !important;
        }

        /* iOS tidak auto-zoom saat form difokuskan. */
        html[data-site='frontend'] input,
        html[data-site='frontend'] select,
        html[data-site='frontend'] textarea { font-size: 16px; }

        html[data-site='frontend'] [data-kie-footer-bottom] {
            flex-direction: column !important;
            gap: .85rem !important;
            text-align: center;
        }
        html[data-site='frontend'] [data-kie-footer-legal] {
            justify-content: center;
            gap: .55rem 1rem !important;
        }
    }

    @media (max-width: 359.98px) {
        html[data-site='frontend'] { font-size: 13.5px; }
        html[data-site='frontend'] [data-kie-home-products-slider] > * { flex-basis: 68vw; }
        html[data-site='frontend'] [data-kie-home-categories-slider] > * { flex-basis: 60vw; }
        html[data-site='frontend'] [data-kie-testimonial-card] { flex-basis: 70vw !important; }
    }

    /* KIE-MOBILE-NAV-BALANCE:START */
    @media (max-width: 639.98px) {
        html[data-site='frontend'] [data-kie-navbar-mobile-menu] {
            display: grid !important;
            grid-template-columns: repeat(5, max-content) !important;
            justify-content: center !important;
            align-items: center !important;
            gap: .45rem !important;
            width: 100% !important;
            padding-left: .35rem !important;
            padding-right: .35rem !important;
            overflow-x: auto !important;
        }

        html[data-site='frontend'] [data-kie-navbar-mobile-menu] > a {
            min-width: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            justify-content: center !important;
            text-align: center !important;
            white-space: nowrap !important;
        }

        html[data-site='frontend'] [data-kie-navbar-search] {
            width: clamp(7.5rem, 33vw, 9rem) !important;
            min-width: 7.5rem !important;
            max-width: 9rem !important;
        }
    }

    @media (max-width: 374.98px) {
        html[data-site='frontend'] [data-kie-navbar-mobile-menu] {
            gap: .28rem !important;
            padding-left: .2rem !important;
            padding-right: .2rem !important;
        }

        html[data-site='frontend'] [data-kie-navbar-mobile-menu] > a {
            font-size: 11px !important;
        }

        html[data-site='frontend'] [data-kie-navbar-search] {
            width: clamp(6.6rem, 30vw, 7.5rem) !important;
            min-width: 6.6rem !important;
            max-width: 7.5rem !important;
        }
    }
    /* KIE-MOBILE-NAV-BALANCE:END */

</style>
@endonce
{{-- KIE-FRONTEND-RESPONSIVE:END --}}

{{-- KIE-MOBILE-SEARCH-TOP:START --}}
<style>
    /*
     * MOBILE ONLY
     * Bentuk search tetap sama seperti desktop.
     * Yang diubah hanya proporsinya:
     * - lebih tipis
     * - lebih panjang
     * - tetap tepat sebelum tombol favorit
     */
    @media (max-width: 639.98px) {
        html[data-site='frontend'] [data-kie-navbar-main] {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;

            height: 3.45rem !important;
            min-height: 3.45rem !important;

            gap: .35rem !important;
            padding: .4rem .65rem !important;
        }

        html[data-site='frontend'] [data-kie-navbar-main] > a:first-child {
            flex: 0 0 auto !important;
            margin-right: .1rem !important;
        }

        html[data-site='frontend'] [data-kie-navbar-actions] {
            display: flex !important;
            flex: 1 1 auto !important;
            min-width: 0 !important;

            align-items: center !important;
            justify-content: flex-end !important;

            margin-left: auto !important;
            gap: 0 !important;
        }

        html[data-site='frontend'] [data-kie-navbar-search] {
            order: 1 !important;

            grid-column: auto !important;
            grid-row: auto !important;

            flex: 1 1 auto !important;
            width: clamp(7.5rem, 33vw, 9rem) !important;
            min-width: 7.5rem !important;
            max-width: 9rem !important;

            height: 1.8rem !important;
            border-radius: .6rem !important;
            overflow: hidden !important;
        }

        html[data-site='frontend'] [data-kie-navbar-search] input {
            min-width: 0 !important;
            height: 100% !important;

            padding-left: .35rem !important;
            padding-right: .25rem !important;

            font-size: 12px !important;
            line-height: 1 !important;
        }

        html[data-site='frontend'] [data-kie-navbar-search] input::placeholder {
            font-size: 11px !important;
            line-height: 1 !important;
        }

        html[data-site='frontend'] [data-kie-navbar-search] button {
            width: 1.85rem !important;
            min-width: 1.85rem !important;
            height: 100% !important;

            padding: 0 !important;
            border-radius: 0 !important;
        }

        html[data-site='frontend'] [data-kie-navbar-search] button i,
        html[data-site='frontend'] [data-kie-navbar-search] > i {
            font-size: .62rem !important;
        }

        html[data-site='frontend'] [data-kie-navbar-icons] {
            order: 2 !important;

            grid-column: auto !important;
            grid-row: auto !important;
            justify-self: auto !important;

            display: flex !important;
            flex: 0 0 auto !important;
            align-items: center !important;

            gap: .02rem !important;
        }

        html[data-site='frontend'] [data-kie-navbar-icons] > a,
        html[data-site='frontend'] [data-kie-navbar-icons] > button {
            width: 1.82rem !important;
            min-width: 1.82rem !important;
            height: 1.82rem !important;
        }

        html[data-site='frontend'] [data-kie-navbar-icons] i {
            font-size: .8rem !important;
        }
    }

    @media (max-width: 374.98px) {
        html[data-site='frontend'] [data-kie-navbar-main] {
            gap: 0 !important;
            padding-left: .45rem !important;
            padding-right: .45rem !important;
        }

        html[data-site='frontend'] [data-kie-navbar-search] {
            width: clamp(6.6rem, 30vw, 7.5rem) !important;
            min-width: 6.6rem !important;
            max-width: 7.5rem !important;
        }

        html[data-site='frontend'] [data-kie-navbar-icons] > a,
        html[data-site='frontend'] [data-kie-navbar-icons] > button {
            width: 1.68rem !important;
            min-width: 1.68rem !important;
            height: 1.68rem !important;
        }
    }
</style>
{{-- KIE-MOBILE-SEARCH-TOP:END --}}

<style>
/* KIE-MOBILE-NAV-FINAL:START */
@media (max-width: 639.98px) {
    html[data-site='frontend'] [data-kie-navbar-mobile-menu] {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        gap: .30rem !important;
        width: 100% !important;
        padding-left: .75rem !important;
        padding-right: .75rem !important;
        overflow-x: hidden !important;
    }

    html[data-site='frontend'] [data-kie-navbar-mobile-menu] > a {
        flex: 0 0 auto !important;
        min-width: 0 !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        font-size: 11.5px !important;
        text-align: center !important;
        white-space: nowrap !important;
    }

    html[data-site='frontend'] [data-kie-navbar-main] > a:first-child {
        gap: .45rem !important;
        margin-right: 0 !important;
    }

    html[data-site='frontend'] [data-kie-navbar-main] > a:first-child span {
        font-size: 15.5px !important;
    }

    html[data-site='frontend'] [data-kie-navbar-actions] {
        gap: .12rem !important;
    }

    html[data-site='frontend'] [data-kie-navbar-search] {
        flex: 0 0 clamp(8.6rem, 38vw, 9rem) !important;
        width: clamp(8.6rem, 38vw, 9rem) !important;
        min-width: 8.6rem !important;
        max-width: 9rem !important;
    }
}

@media (max-width: 374.98px) {
    html[data-site='frontend'] [data-kie-navbar-mobile-menu] {
        gap: .15rem !important;
        padding-left: .45rem !important;
        padding-right: .45rem !important;
    }

    html[data-site='frontend'] [data-kie-navbar-mobile-menu] > a {
        font-size: 10.5px !important;
    }

    html[data-site='frontend'] [data-kie-navbar-main] > a:first-child span {
        font-size: 14.5px !important;
    }

    html[data-site='frontend'] [data-kie-navbar-search] {
        flex-basis: 7.8rem !important;
        width: 7.8rem !important;
        min-width: 7.8rem !important;
        max-width: 7.8rem !important;
    }
}
/* KIE-MOBILE-NAV-FINAL:END */
</style>


<style>
/* KIE-MOBILE-MENU-SPACING-FINAL:START */
@media (max-width: 639.98px) {
    html[data-site='frontend'] [data-kie-navbar-mobile-menu] {
        justify-content: space-evenly !important;
        gap: 0 !important;
        padding-left: .35rem !important;
        padding-right: .35rem !important;
    }

    html[data-site='frontend'] [data-kie-navbar-mobile-menu] > a {
        font-size: 11px !important;
    }
}

@media (max-width: 374.98px) {
    html[data-site='frontend'] [data-kie-navbar-mobile-menu] {
        gap: 0 !important;
        padding-left: .25rem !important;
        padding-right: .25rem !important;
    }

    html[data-site='frontend'] [data-kie-navbar-mobile-menu] > a {
        font-size: 10.5px !important;
    }
}
/* KIE-MOBILE-MENU-SPACING-FINAL:END */
</style>

<header class="sticky top-0 z-100 border-b border-admin-border/80 bg-admin-surface/95 backdrop-blur-md">

    <div data-kie-navbar-main class="mx-auto flex h-17 max-w-360 items-center gap-4 px-5 sm:px-7 lg:gap-7 lg:px-10">

        {{-- =========================================================
             LOGO
        ========================================================== --}}
        <a
            href="{{ route('home') }}"
            class="group flex shrink-0 items-center gap-2.5"
            aria-label="Karya Ide Edi - Beranda"
        >
            @include('partials.logo', [
                'boxSize' => 'h-9 w-9',
                'rounded' => 'rounded-lg',
                'boxClass' => 'bg-admin-panel',
                'iconClass' => 'text-sm text-white',
                'icon' => 'fa-couch',
            ])

            <span class="font-display text-[17px] font-semibold tracking-[-0.02em] text-admin-ink transition-colors group-hover:text-admin-panel">
                {{ $navSetting->site_name }}
            </span>
        </a>


        {{-- =========================================================
             NAVIGASI DESKTOP
        ========================================================== --}}
        <nav class="hidden items-center lg:flex">
            <div class="flex items-center gap-0.5">

                @foreach ($navMenu as $item)
                    @php
                        $isActive = $item['route'] && request()->routeIs($item['route']);
                        $href = $item['route'] ? route($item['route']) : '#';
                    @endphp

                    <a
                        href="{{ $href }}"
                        class="group relative flex h-10 items-center px-3 text-[13px] font-semibold tracking-[-0.01em] transition-colors duration-200
                        {{ $isActive
                            ? 'text-admin-panel'
                            : 'text-admin-ink-soft hover:text-admin-ink' }}"
                    >
                        {{ $item['label'] }}

                        <span
                            class="absolute bottom-0.5 left-3 right-3 h-0.5 origin-center rounded-full bg-admin-gold transition-transform duration-200
                            {{ $isActive ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100' }}"
                        ></span>
                    </a>
                @endforeach

            </div>
        </nav>


        {{-- =========================================================
             AREA KANAN
        ========================================================== --}}
        <div data-kie-navbar-actions class="ml-auto flex min-w-0 items-center gap-2.5 max-md:flex-1 lg:gap-3">


            {{-- =====================================================
                 SEARCH DESKTOP
            ====================================================== --}}
            <form
                action="{{ route('products.index') }}"
                method="GET"
                data-kie-navbar-search
                class="flex h-10 min-w-0 overflow-hidden rounded-xl max-md:flex-1 border border-admin-border bg-admin-canvas transition-all duration-200 focus-within:border-admin-accent focus-within:ring-4 focus-within:ring-admin-accent/10 lg:w-97.5"
            >

                <div class="flex min-w-0 flex-1 items-center">
                    <i class="fa-solid fa-magnifying-glass ml-3.5 shrink-0 text-[12px] text-admin-ink-soft"></i>

                    <input
                        type="search"
                        name="search"
                        value="{{ $currentSearch }}"
                        placeholder="Cari produk..."
                        autocomplete="off"
                        class="min-w-0 flex-1 bg-transparent px-3 text-[13px] text-admin-ink placeholder:text-admin-ink-soft/80 focus:outline-none"
                    >
                </div>


                {{-- Dropdown kategori --}}
                <div class="relative hidden border-l border-admin-border lg:block">
                    <select
                        name="category"
                        onchange="this.form.submit()"
                        class="h-full w-31.25 cursor-pointer appearance-none bg-transparent px-3 pr-7 text-[12px] font-medium text-admin-ink-soft outline-none"
                        aria-label="Pilih kategori"
                    >
                        <option value="">Semua Produk</option>

                        @foreach ($navCategories as $category)
                            <option
                                value="{{ $category->slug }}"
                                {{ $currentCategory === $category->slug ? 'selected' : '' }}
                            >
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>

                    <i class="fa-solid fa-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[9px] text-admin-ink-soft"></i>
                </div>


                {{-- Tombol search --}}
                <button
                    type="submit"
                    aria-label="Cari produk"
                    class="flex w-11 shrink-0 items-center justify-center bg-admin-panel text-white transition-colors duration-200 hover:bg-admin-panel/90"
                >
                    <i class="fa-solid fa-magnifying-glass text-[12px]"></i>
                </button>

            </form>


            {{-- =====================================================
                 IKON FAVORIT / KERANJANG / ADMIN
            ====================================================== --}}
            <div data-kie-navbar-icons class="flex shrink-0 items-center">

                {{-- Favorit --}}
                <a
                    href="{{ route('favorites.index') }}"
                    aria-label="Favorit"
                    class="group relative flex h-10 w-10 items-center justify-center rounded-full text-admin-ink-soft transition-all duration-200 hover:bg-admin-canvas hover:text-admin-accent"
                >
                    <i class="fa-regular fa-heart text-[16px] transition-transform duration-200 group-hover:scale-110"></i>

                    <span
                        data-favorite-count
                        class="absolute right-0 top-0 hidden min-h-4 min-w-4 items-center justify-center rounded-full bg-admin-accent px-1 text-[9px] font-bold leading-none text-white shadow-sm"
                    >0</span>
                </a>


                {{-- Keranjang --}}
                <a
                    href="{{ route('cart.index') }}"
                    aria-label="Keranjang"
                    class="group relative flex h-10 w-10 items-center justify-center rounded-full text-admin-ink-soft transition-all duration-200 hover:bg-admin-canvas hover:text-admin-accent"
                >
                    <i class="fa-solid fa-bag-shopping text-[15px] transition-transform duration-200 group-hover:scale-110"></i>

                    <span
                        data-cart-count
                        class="absolute right-0 top-0 hidden min-h-4 min-w-4 items-center justify-center rounded-full bg-admin-accent px-1 text-[9px] font-bold leading-none text-white shadow-sm"
                    >0</span>
                </a>


                {{-- Notifikasi/admin hanya untuk pemilik --}}
                @auth
                    <div class="ml-0.5">
                        <livewire:notification-bell />
                    </div>
                @endauth

            </div>

        </div>
    </div>


    {{-- =============================================================
         NAVIGASI HP & TABLET (< lg)
         Semua menu langsung tampak di bawah baris logo/pencarian,
         tanpa tombol garis 3. Kalau layar terlalu sempit, baris ini
         bisa digeser samping (scrollbar disembunyikan).
    ============================================================== --}}
    <nav aria-label="Navigasi utama" class="lg:hidden">
        <div data-kie-navbar-mobile-menu class="mx-auto flex max-w-360 items-center justify-between overflow-x-auto px-2.5 [scrollbar-width:none] sm:px-5 [&::-webkit-scrollbar]:hidden">

            @foreach ($navMenu as $item)
                @php
                    $isActive = $item['route'] && request()->routeIs($item['route']);
                    $href = $item['route'] ? route($item['route']) : '#';
                @endphp

                <a
                    href="{{ $href }}"
                    class="group relative flex h-10 shrink-0 items-center px-2.5 text-[13px] font-semibold tracking-[-0.01em] transition-colors duration-200
                    {{ $isActive
                        ? 'text-admin-panel'
                        : 'text-admin-ink-soft hover:text-admin-ink' }}"
                >
                    {{ $item['label'] }}

                    <span
                        class="absolute bottom-0.5 left-2.5 right-2.5 h-0.5 origin-center rounded-full bg-admin-gold transition-transform duration-200
                        {{ $isActive ? 'scale-x-100' : 'scale-x-0' }}"
                    ></span>
                </a>
            @endforeach

        </div>
    </nav>

</header>

