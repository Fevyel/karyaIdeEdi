<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Toko Mebel') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    </head>
    <body class="min-h-screen bg-admin-canvas font-sans antialiased">

        {{-- ================= NAVBAR ================= --}}
        @php $siteSetting = \App\Models\Setting::current(); @endphp
        @include('partials.frontend.navbar')

        {{-- ================= HERO ================= --}}
        @include('partials.frontend.hero')

        {{-- ================= KEUNGGULAN ================= --}}
        @include('partials.frontend.features')

        {{-- ================= SEJAK BERDIRI ================= --}}
        @include('partials.frontend.mission')

        {{-- ================= SEMUA PRODUK ================= --}}
        @include('partials.frontend.products')

        {{-- ================= PRODUK BERDASARKAN KATEGORI ================= --}}
        @include('partials.frontend.categories')

        {{-- ================= ULASAN PELANGGAN KAMI ================= --}}
        @include('partials.frontend.testimonials')

        {{-- ================= KEUNGGULAN KAMI ================= --}}
        @include('partials.frontend.expertise')

        {{-- ================= FOOTER ================= --}}
        @include('partials.frontend.footer')
    </body>
</html>