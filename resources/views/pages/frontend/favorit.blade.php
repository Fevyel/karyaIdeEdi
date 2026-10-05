<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    @include('partials.favicon')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Favorit &mdash; {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
{{-- KIE-FAVORITES-MOBILE-TWO-COLS:START --}}
<style>
    @media (max-width: 639.98px) {
        body[data-kie-favorites-page] main[data-store-page="favorites"] {
            padding-left: .9rem !important;
            padding-right: .9rem !important;
            padding-top: 1rem !important;
            padding-bottom: 1.4rem !important;
        }

        body[data-kie-favorites-page] main[data-store-page="favorites"] > div:first-child {
            padding-bottom: 1rem !important;
            gap: .45rem !important;
        }

        body[data-kie-favorites-page] main[data-store-page="favorites"] h1 {
            font-size: 2rem !important;
            line-height: 1.05 !important;
        }

        body[data-kie-favorites-page] main[data-store-page="favorites"] > div:first-child p:last-child {
            margin-top: .45rem !important;
            font-size: .82rem !important;
            line-height: 1.65 !important;
        }

        body[data-kie-favorites-page] [data-favorites-list] {
            margin-top: .95rem !important;
        }

        body[data-kie-favorites-page] [data-favorites-list] > div,
        body[data-kie-favorites-page] [data-kie-favorites-grid] {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: .75rem !important;
        }

        body[data-kie-favorites-page] [data-favorites-list] article {
            border-radius: 1rem !important;
        }

        body[data-kie-favorites-page] [data-favorites-list] article .aspect-square {
            aspect-ratio: 1 / 1 !important;
        }

        body[data-kie-favorites-page] [data-favorites-list] article .p-4 {
            padding: .7rem !important;
        }

        body[data-kie-favorites-page] [data-favorites-list] article [data-remove-favorite] {
            width: 1.95rem !important;
            height: 1.95rem !important;
            top: .45rem !important;
            right: .45rem !important;
        }

        body[data-kie-favorites-page] [data-favorites-list] article [data-remove-favorite] i {
            font-size: .62rem !important;
        }

        body[data-kie-favorites-page] [data-favorites-list] article p[class*='text-[9px]'] {
            font-size: 8px !important;
            line-height: 1.3 !important;
            letter-spacing: .12em !important;
        }

        body[data-kie-favorites-page] [data-favorites-list] article a.block,
        body[data-kie-favorites-page] [data-favorites-list] article a.mt-1 {
            font-size: .9rem !important;
            line-height: 1.25 !important;
            margin-top: .25rem !important;
        }

        body[data-kie-favorites-page] [data-favorites-list] article p.mt-2 {
            margin-top: .35rem !important;
            font-size: .85rem !important;
        }

        body[data-kie-favorites-page] [data-favorites-list] article .mt-4 {
            margin-top: .55rem !important;
            gap: .35rem !important;
        }

        body[data-kie-favorites-page] [data-favorites-list] article [data-readd-cart-id] {
            padding: .58rem .42rem !important;
            font-size: 9px !important;
            line-height: 1.1 !important;
        }

        body[data-kie-favorites-page] [data-favorites-list] article a[aria-label='Lihat produk'] {
            width: 2.1rem !important;
            height: 2rem !important;
        }
    }
</style>
{{-- KIE-FAVORITES-MOBILE-TWO-COLS:END --}}
</head>
<body data-kie-favorites-page class="min-h-screen bg-[#FAF7F2] font-sans antialiased text-[#2A211B]">
    @include('partials.frontend.navbar')

    <main data-store-page="favorites" class="mx-auto min-h-[70vh] max-w-7xl px-5 py-12 sm:px-8 lg:px-10 lg:py-16">
        <div class="flex flex-col gap-4 border-b border-[#E7DED2] pb-8 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-[#9C6B3F]">Koleksi Anda</p>
                <h1 class="mt-2 font-display text-4xl font-semibold tracking-tight text-[#2A211B] sm:text-5xl">Favorit</h1>
                <p class="mt-3 max-w-xl text-sm leading-7 text-[#7C7065]">Simpan produk yang ingin Anda pertimbangkan kembali. Koleksi ini tersimpan di perangkat Anda dan tidak memerlukan akun.</p>
            </div>
        </div>

        <div data-favorites-list class="mt-10"></div>
        <div data-favorites-empty class="hidden rounded-[24px] border border-dashed border-[#D9CCBD] bg-white px-6 py-20 text-center shadow-[0_18px_60px_-35px_rgba(42,33,27,.25)]">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[#F1E6D7] text-[#9C6B3F]"><i class="fa-regular fa-heart text-xl"></i></div>
            <h2 class="mt-5 font-display text-2xl font-semibold">Belum ada produk favorit</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-[#82766B]">Tekan ikon hati pada produk yang Anda sukai. Produk pilihan Anda akan muncul di sini.</p>
            <a href="{{ route('products.index') }}" class="mt-6 inline-flex rounded-md bg-[#2A211B] px-5 py-3 text-xs font-semibold text-white transition hover:bg-[#403129]">Jelajahi Produk</a>
        </div>
    </main>

    @include('partials.frontend.footer')
</body>
</html>
