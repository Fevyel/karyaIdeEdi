{{--
    ==========================================================
    MARQUEE BRAND — pita teks berjalan tepat di atas footer
    ==========================================================
    Rectangle fullwidth dengan tulisan "FURNITUR TOKO MEBEL" dan
    "KARYA IDE-EDI" bergantian (dipisah titik) yang bergerak terus
    (loop mulus, kanan ke kiri).

    Sengaja self-contained (CSS-nya inline di file ini) supaya langsung
    jalan tanpa perlu `npm run build`, dan nama class-nya diawali
    `kie-marquee` agar tidak bentrok dengan style lain.

    Cara kerja loop mulus: track berisi 2 grup yang ISINYA IDENTIK, lalu
    di-geser 0 -> -50%. Begitu grup pertama habis keluar layar, posisinya
    persis sama dengan awal, jadi tidak ada lompatan.

    Yang bisa diatur cepat (di bagian <style> di bawah):
        --kie-marquee-speed : durasi satu putaran (makin besar = makin pelan)
        --kie-marquee-bg    : warna latar pita
        --kie-marquee-ink   : warna tulisan

    Pemakaian (sudah dipasang di partials/frontend/footer.blade.php):
        @include('partials.frontend.marquee-brand')
    ==========================================================
--}}
@php
    $marqueeTexts = ['FURNITUR TOKO MEBEL', 'KARYA IDE-EDI'];
    $marqueeRepeat = 14; // jumlah pengulangan pasangan teks per grup; cukup untuk layar sangat lebar (4K)
@endphp

<div class="kie-marquee" aria-hidden="true">
    <div class="kie-marquee__track">
        @foreach ([1, 2] as $group)
            <div class="kie-marquee__group">
                @for ($i = 0; $i < $marqueeRepeat; $i++)
                    @foreach ($marqueeTexts as $marqueeText)
                        <span class="kie-marquee__item">{{ $marqueeText }}</span>
                        <span class="kie-marquee__dot">&bull;</span>
                    @endforeach
                @endfor
            </div>
        @endforeach
    </div>
</div>

<style>
    .kie-marquee {
        --kie-marquee-speed: 90s;
        --kie-marquee-bg: #C9A566;
        --kie-marquee-ink: #2A1B12;

        position: relative;
        width: 100%;
        overflow: hidden;
        background: var(--kie-marquee-bg);
        color: var(--kie-marquee-ink);
        padding: 0.6rem 0;
        user-select: none;
    }

    .kie-marquee__track {
        display: flex;
        width: max-content;
        animation: kie-marquee-scroll var(--kie-marquee-speed) linear infinite;
        will-change: transform;
    }

    .kie-marquee__group {
        display: flex;
        flex-shrink: 0;
        align-items: center;
    }

    .kie-marquee__item {
        font-family: var(--font-display, Georgia, serif);
        font-size: clamp(0.8rem, 1.4vw, 1.05rem);
        font-weight: 700;
        letter-spacing: 0.18em;
        line-height: 1;
        white-space: nowrap;
    }

    .kie-marquee__dot {
        margin: 0 clamp(0.7rem, 1.5vw, 1.25rem);
        font-size: clamp(0.8rem, 1.4vw, 1.05rem);
        line-height: 1;
        opacity: 0.85;
    }

    @keyframes kie-marquee-scroll {
        from { transform: translateX(0); }
        to   { transform: translateX(-50%); }
    }

    /* Hormati pengguna yang mematikan animasi di sistem operasinya. */
    @media (prefers-reduced-motion: reduce) {
        .kie-marquee__track {
            animation: none;
        }
    }
</style>

<style>
/* KIE slow running strip */
[class~="kie-marquee"]{animation-duration:120s !important;}
</style>
