<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Favorit &mdash; {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-[#FAF7F2] font-sans antialiased text-[#2A211B]">
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
