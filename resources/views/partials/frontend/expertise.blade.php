{{--
    ==========================================================
    KENAPA PILIH KAMI — split panel foto kiri / teks kanan — Homepage
    ==========================================================
    Section ditempatkan tepat SETELAH "Ulasan Pelanggan Kami"
    (testimonials.blade.php).

    Isi teks & foto sekarang diatur admin lewat Edit Web > tab
    "Kenapa Pilih Kami" (disimpan di tabel home_sections, key
    "keahlian"). Default di bawah ini cuma fallback kalau admin
    belum pernah menyimpan apa-apa.

    Catatan: sebelumnya section ini masih 100% teks placeholder
    Figma lama ("Mintech", roofing/atap, tombol play + klaim
    "25 Years of Excellence" yang tidak nyata). Semua itu sudah
    dihapus/diganti supaya kontennya jujur & bisa diedit admin,
    sama pola-nya dengan features.blade.php & mission.blade.php.

    Ikon sosial (Instagram, TikTok, Facebook) tetap statis sesuai
    instruksi -- cuma WhatsApp yang dinamis (ambil dari Pengaturan).

    Pemakaian:
        @include('partials.frontend.expertise')
    ==========================================================
--}}
@php
    $keahlianDefaults = [
        'badge_text' => 'Kenapa Pilih Kami',
        'title' => 'Kualitas yang Bisa Anda Percaya',
        'description' => 'Setiap furnitur kami dibuat dari material pilihan dan dikerjakan dengan tangan secara teliti, menghasilkan produk yang kokoh, nyaman, dan tahan lama untuk mengisi rumah Anda.',
        'checklist' => [
            'Material kayu pilihan yang sudah melalui proses seleksi ketat.',
            'Dikerjakan pengrajin berpengalaman dengan standar rapi dan presisi.',
        ],
        'image_path' => null,
        'media_type' => 'photo',
        'video_url' => null,
        'video_path' => null,
    ];

    $keahlianData = \App\Models\HomeSection::dataFor('keahlian', $keahlianDefaults);

    $keahlianImageUrl = $keahlianData['image_path']
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($keahlianData['image_path'])
        : asset('images/admin-login/kursi.png');

    $keahlianSetting = \App\Models\Setting::current();
    $keahlianWhatsapp = $keahlianSetting->whatsappDigits();

    // ============ Media panel kiri: foto ATAU video ============
    // Lihat App\Models\HomeSection::classifyVideoUrl() untuk daftar
    // platform yang dikenali (YouTube, TikTok, Instagram, Facebook,
    // Google Drive, atau tautan file video langsung).
    $keahlianVideoUploadUrl = $keahlianData['video_path']
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($keahlianData['video_path'])
        : null;

    $keahlianVideo = null;

    if ($keahlianData['media_type'] === 'video_upload' && $keahlianVideoUploadUrl) {
        $keahlianVideo = ['provider' => 'direct', 'embed_url' => $keahlianVideoUploadUrl];
    } elseif ($keahlianData['media_type'] === 'video_url' && $keahlianData['video_url']) {
        $keahlianVideo = \App\Models\HomeSection::classifyVideoUrl($keahlianData['video_url']);
    }
@endphp

<section class="bg-white">
    <div class="grid grid-cols-1 lg:grid-cols-2 lg:items-center">

        {{--
            ============ KIRI: Foto ============
            Rasio dikunci aspect-10/9 -- SAMA PERSIS dengan rasio crop di
            Admin > Edit Web > Kenapa Pilih Kami (lihat keahlianFotoCropper,
            ASPECT_W/ASPECT_H = 10/9 di edit-web.blade.php). Sebelumnya panel
            ini pakai tinggi tetap per breakpoint (h-90/sm:h-115/lg:min-h-140)
            + object-contain, jadi bagian foto yang kelihatan di Beranda BISA
            beda dari yang admin pilih waktu nge-crop -- fix ini menyamakan
            keduanya supaya tidak ada risiko salah crop.
        --}}
        <div class="relative aspect-10/9 w-full overflow-hidden bg-[#1A1A1A]">
            @if ($keahlianVideo && $keahlianVideo['provider'] === 'direct')
                {{-- Video langsung (upload dari perangkat ATAU tautan file video) --
                     kontrol penuh: autoplay+suara begitu masuk viewport, suara
                     meredup pelan saat dilewati, tombol mute manual kiri-bawah. --}}
                <div
                    x-data="keahlianVideoPlayer()"
                    x-init="init()"
                    class="absolute inset-0 h-full w-full"
                >
                    <video
                        x-ref="video"
                        src="{{ $keahlianVideo['embed_url'] }}"
                        class="absolute inset-0 h-full w-full object-cover"
                        playsinline
                        muted
                        loop
                        preload="auto"
                    ></video>

                    <button
                        type="button"
                        x-on:click="toggleMute()"
                        class="absolute bottom-4 left-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-black/50 text-white backdrop-blur transition hover:bg-black/70"
                        :aria-label="muted ? 'Aktifkan suara video' : 'Matikan suara video'"
                    >
                        <i class="fa-solid" :class="muted ? 'fa-volume-xmark' : 'fa-volume-high'"></i>
                    </button>
                </div>
            @elseif ($keahlianVideo && $keahlianVideo['provider'] === 'youtube')
                {{-- YouTube: iframe baru dimuat saat scroll sampai sini, dikontrol lewat
                     YouTube postMessage API (autoplay+suara masuk, redup+pause keluar,
                     tombol mute manual kiri-bawah -- sama seperti video langsung). --}}
                <div
                    x-data="keahlianYoutubePlayer(@js($keahlianVideo['embed_url']))"
                    x-init="init()"
                    class="absolute inset-0 h-full w-full"
                >
                    <iframe
                        x-ref="iframe"
                        title="{{ $keahlianData['title'] }}"
                        class="absolute inset-0 h-full w-full"
                        style="border:0;"
                        allow="autoplay; encrypted-media; picture-in-picture"
                        allowfullscreen
                    ></iframe>

                    <button
                        type="button"
                        x-on:click="toggleMute()"
                        class="absolute bottom-4 left-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-black/50 text-white backdrop-blur transition hover:bg-black/70"
                        :aria-label="muted ? 'Aktifkan suara video' : 'Matikan suara video'"
                    >
                        <i class="fa-solid" :class="muted ? 'fa-volume-xmark' : 'fa-volume-high'"></i>
                    </button>
                </div>
            @elseif ($keahlianVideo)
                {{-- Facebook / TikTok / Instagram / Google Drive: embed resmi platform.
                     Iframe baru dimuat saat section ini masuk layar (hemat bandwidth),
                     tapi putar-otomatis-bersuara & efek redup mengikuti pemutar bawaan
                     masing-masing platform -- tidak ada API publik yang bisa kita pakai
                     untuk memaksanya dari luar seperti pada video langsung/YouTube. --}}
                <div x-data="{ loaded: false }" x-init="new IntersectionObserver((entries) => { if (entries[0].isIntersecting) { loaded = true; } }, { threshold: 0.3 }).observe($el)" class="absolute inset-0 h-full w-full">
                    <template x-if="loaded">
                        <iframe
                            src="{{ $keahlianVideo['embed_url'] }}"
                            title="{{ $keahlianData['title'] }}"
                            class="absolute inset-0 h-full w-full"
                            style="border:0;"
                            allow="autoplay; encrypted-media; picture-in-picture"
                            allowfullscreen
                        ></iframe>
                    </template>
                </div>
            @else
                <img
                    src="{{ $keahlianImageUrl }}"
                    alt="{{ $keahlianData['title'] }}"
                    class="absolute inset-0 h-full w-full object-cover"
                >
            @endif
        </div>

        {{-- ============ KANAN: Eyebrow, heading, paragraf, checklist, ikon sosial ============ --}}
        <div class="flex flex-col justify-center bg-white px-6 py-12 sm:px-8 lg:px-12 lg:py-16 xl:px-16">

            {{-- Eyebrow --}}
            <div class="flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.2em] text-[#1A1A1A]">
                <span class="h-px w-8 bg-[#1A1A1A]"></span>
                {{ $keahlianData['badge_text'] }}
            </div>

            {{-- Heading --}}
            <h2 class="mt-4 text-4xl font-extrabold uppercase leading-[1.05] text-[#1A1A1A] sm:text-5xl">
                {{ $keahlianData['title'] }}
            </h2>

            <p class="mt-5 max-w-md text-sm leading-relaxed text-[#6B6E76]">
                {{ $keahlianData['description'] }}
            </p>

            {{-- Checklist --}}
            <div class="mt-6 space-y-4">
                @foreach ($keahlianData['checklist'] as $point)
                    <div class="flex items-start gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-[#1A1A1A]/25 text-[#1A1A1A]">
                            <i class="fa-solid fa-check text-[11px]"></i>
                        </span>
                        <p class="max-w-sm text-sm leading-relaxed text-[#1A1A1A]">{{ $point }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Ikon sosial — 4 sel persegi berdampingan dalam satu container bergaris. Instagram, TikTok & Facebook dari App\Models\Setting (Pengaturan admin), hanya tampil kalau link-nya diisi. --}}
            <div class="mt-7 inline-flex w-fit overflow-hidden rounded-lg border border-[#1A1A1A]/15">
                @if ($keahlianSetting->instagram_url)
                    <a href="{{ $keahlianSetting->instagram_url }}" target="_blank" rel="noopener" aria-label="Instagram" class="flex h-11 w-11 items-center justify-center border-r border-[#1A1A1A]/15 text-[#1A1A1A] transition-colors duration-300 hover:bg-[#F1F1F1]">
                        <i class="fa-brands fa-instagram text-sm"></i>
                    </a>
                @endif
                @if ($keahlianSetting->tiktok_url)
                    <a href="{{ $keahlianSetting->tiktok_url }}" target="_blank" rel="noopener" aria-label="TikTok" class="flex h-11 w-11 items-center justify-center border-r border-[#1A1A1A]/15 text-[#1A1A1A] transition-colors duration-300 hover:bg-[#F1F1F1]">
                        <i class="fa-brands fa-tiktok text-sm"></i>
                    </a>
                @endif
                @if ($keahlianSetting->facebook_url)
                    <a href="{{ $keahlianSetting->facebook_url }}" target="_blank" rel="noopener" aria-label="Facebook" class="flex h-11 w-11 items-center justify-center border-r border-[#1A1A1A]/15 text-[#1A1A1A] transition-colors duration-300 hover:bg-[#F1F1F1]">
                        <i class="fa-brands fa-facebook-f text-sm"></i>
                    </a>
                @endif
                <a
                    href="{{ $keahlianWhatsapp ? 'https://wa.me/'.$keahlianWhatsapp : '#' }}"
                    target="{{ $keahlianWhatsapp ? '_blank' : '_self' }}"
                    rel="noopener"
                    aria-label="WhatsApp"
                    class="flex h-11 w-11 items-center justify-center text-[#1A1A1A] transition-colors duration-300 hover:bg-[#F1F1F1]"
                >
                    <i class="fa-brands fa-whatsapp text-sm"></i>
                </a>
            </div>
        </div>
    </div>
</section>