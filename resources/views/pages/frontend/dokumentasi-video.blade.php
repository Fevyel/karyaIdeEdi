<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    @include('partials.favicon')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Galeri Video | {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-[#F7F4EF] text-[#3D2B1F]">
@include('partials.frontend.navbar')

<main class="pt-4 sm:pt-6">
    @php
        $dokumentasiHero = ['galeri' => [], 'subjudul' => ''];
        $dokumentasiVideo = ['provider' => null, 'embed_url' => null];
    @endphp

    @include('partials.frontend.dokumentasi-video-section', ['showAllVideos' => true])
</main>

@include('partials.frontend.footer')
</body>
</html>