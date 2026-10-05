<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    @include('partials.favicon')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Galeri Foto | {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-white">
    @include('partials.frontend.navbar')

    @php
        $dokumentasiHero = \App\Models\HomeSection::dataFor('dokumentasi', ['galeri' => []]);
    @endphp

    @include('partials.frontend.dokumentasi-foto-section', ['showPhotoMoreButton' => false])

    @include('partials.frontend.footer')
</body>
</html>
