<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ulasan {{ $product->nama }} ? {{ \App\Models\Setting::current()->site_name }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>
        /* ============================================
           HALAMAN SEMUA ULASAN ? CLEAN FINAL
           ============================================ */

        .kie-reviews-page {
            width: calc(100% - 96px);
            max-width: 1600px;
            margin: 0 auto;
            padding: 38px 0 68px;
        }

        .kie-reviews-page-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 28px;
            margin-bottom: 25px;
        }

        .kie-reviews-back {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 11px;
            color: #8A7C6E;
            font-size: 10px;
            text-decoration: none;
            transition: color .2s ease;
        }

        .kie-reviews-back:hover {
            color: #A86629;
        }

        .kie-reviews-title {
            margin: 0;
            color: #2A211B;
            font-family: Georgia, serif;
            font-size: 30px;
            line-height: 1.1;
            font-weight: 700;
        }

        .kie-reviews-product {
            margin: 7px 0 0;
            color: #8A7C6E;
            font-size: 11px;
        }

        .kie-reviews-score {
            flex: 0 0 auto;
            min-width: 94px;
            padding: 11px 13px;
            border: 1px solid #EDE3D8;
            border-radius: 15px;
            background: #FCFAF7;
            text-align: center;
        }

        .kie-reviews-score-number {
            display: block;
            color: #2A211B;
            font-size: 21px;
            line-height: 1;
            font-weight: 700;
        }

        .kie-reviews-score-stars {
            display: flex;
            justify-content: center;
            gap: 2px;
            margin-top: 6px;
            font-size: 9px;
        }

        .kie-reviews-score-count {
            display: block;
            margin-top: 5px;
            color: #9A8E82;
            font-size: 8px;
        }

        /* ============================================
           GRID
           ============================================ */

        .kie-reviews-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            align-items: stretch;
        }

        /* ============================================
           CARD
           ============================================ */

        .kie-review-card {
            box-sizing: border-box;
            position: relative;
            width: 100%;
            min-width: 0;
            height: 100%;
            padding: 18px;
            overflow: hidden;
            border: 1px solid #EDE3D8;
            border-radius: 18px;
            background: #FFF;
            box-shadow: 0 8px 26px rgba(42, 33, 27, .055);
        }

        .kie-review-card.has-photos {
            padding-right: 174px;
            min-height: 176px;
        }

        /* ============================================
           PROFILE
           ============================================ */

        .kie-review-profile {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 0;
        }

        .kie-review-avatar {
            width: 42px;
            height: 42px;
            min-width: 42px;
            flex: 0 0 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #E9DDCF;
            border-radius: 999px;
            background: #F8F3ED;
            color: #C97A1C;
            font-size: 12px;
            font-weight: 700;
        }

        .kie-review-user {
            min-width: 0;
        }

        .kie-review-name {
            margin: 0;
            overflow: hidden;
            color: #2A211B;
            font-size: 14px;
            line-height: 1.25;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .kie-review-rating {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-top: 5px;
        }

        .kie-review-stars {
            display: flex;
            gap: 2px;
            font-size: 11px;
        }

        .kie-review-rating-number {
            color: #756A60;
            font-size: 10px;
            font-weight: 600;
        }

        /* Sesuai permintaan: tanggal DI BAWAH rating */
        .kie-review-date {
            display: block;
            margin: 5px 0 0 53px;
            color: #A0958A;
            font-size: 9.5px;
            line-height: 1.2;
            white-space: nowrap;
        }

        /* ============================================
           LOKASI + KOMENTAR
           ============================================ */

        .kie-review-location {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            width: max-content;
            max-width: 100%;
            margin-top: 12px;
            padding: 5px 9px;
            border-radius: 999px;
            background: #F8F5F1;
            color: #84776B;
            font-size: 10px;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .kie-review-location i {
            color: #BE824A;
            font-size: 8px;
        }

        .kie-review-comment {
            margin: 12px 0 0;
            color: #50463E;
            font-size: 13px;
            line-height: 1.65;
            word-break: break-word;
        }

        /* ============================================
           FOTO
           ============================================ */

        .kie-review-photos {
            position: absolute;
            right: 18px;
            bottom: 18px;
            display: grid;
            grid-template-columns: repeat(2, 68px);
            gap: 8px;
            width: 144px;
        }

        .kie-review-photo {
            position: relative;
            display: block;
            width: 68px;
            height: 68px;
            overflow: hidden;
            border: 1px solid #E8DED3;
            border-radius: 12px;
            background: #F6F1EB;
        }

        .kie-review-photo img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .25s ease;
        }

        .kie-review-photo:hover img {
            transform: scale(1.05);
        }

        /* Maks 4 preview agar card tetap rapi */
        .kie-review-photo:nth-child(n+5) {
            display: none;
        }

        /* ============================================
           UPDATE KOMENTAR
           ============================================ */

        .kie-review-update {
            margin-top: 12px;
            padding: 10px 11px;
            border-radius: 10px;
            background: #FBF6EF;
        }

        .kie-review-update-title {
            margin: 0;
            color: #9B6E3F;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .kie-review-update-text {
            margin: 5px 0 0;
            color: #675C52;
            font-size: 10px;
            line-height: 1.55;
        }

        /* ============================================
           PAGINATION
           ============================================ */

        .kie-reviews-pagination {
            margin-top: 28px;
        }

        /* ============================================
           TABLET / NON DESKTOP = 2 KOLOM
           ============================================ */

        @media (max-width: 1199.98px) {
            .kie-reviews-page {
                width: calc(100% - 40px);
            }

            .kie-reviews-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .kie-review-card,
            .kie-review-card.has-photos {
                height: auto;
                min-height: 0;
                padding: 15px;
            }

            .kie-review-photos {
                position: static;
                width: auto;
                margin-top: 10px;
                display: flex;
                flex-wrap: wrap;
                gap: 7px;
            }
        }

        /* ============================================
           HP = TETAP 2 KOLOM
           ============================================ */

        @media (max-width: 639.98px) {
            .kie-reviews-page {
                width: calc(100% - 16px);
                padding-top: 24px;
            }

            .kie-reviews-page-head {
                align-items: flex-start;
                gap: 10px;
                margin-bottom: 18px;
            }

            .kie-reviews-title {
                font-size: 21px;
            }

            .kie-reviews-score {
                min-width: 72px;
                padding: 8px;
            }

            .kie-reviews-score-number {
                font-size: 17px;
            }

            .kie-reviews-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .kie-review-card,
            .kie-review-card.has-photos {
                padding: 11px;
                border-radius: 12px;
            }

            .kie-review-profile {
                gap: 7px;
            }

            .kie-review-avatar {
                width: 30px;
                height: 30px;
                min-width: 30px;
                flex-basis: 30px;
                font-size: 8px;
            }

            .kie-review-name {
                font-size: 10px;
            }

            .kie-review-stars {
                font-size: 8px;
            }

            .kie-review-rating-number {
                font-size: 7px;
            }

            .kie-review-date {
                margin-left: 37px;
                font-size: 7px;
            }

            .kie-review-location {
                margin-top: 7px;
                padding: 3px 6px;
                font-size: 7.5px;
            }

            .kie-review-comment {
                margin-top: 7px;
                font-size: 9px;
                line-height: 1.45;
            }

            .kie-review-photos {
                margin-top: 8px;
                gap: 5px;
            }

            .kie-review-photo {
                width: 44px;
                height: 44px;
                border-radius: 7px;
            }
        }
    </style>
</head>

<body class="min-h-screen bg-white font-sans antialiased text-[#2A211B]">

@include('partials.frontend.navbar')

<main class="kie-reviews-page">

    <header class="kie-reviews-page-head">
        <div>
            <a
                href="{{ route('products.show', $product) }}"
                class="kie-reviews-back"
            >
                <i class="fa-solid fa-arrow-left"></i>
                Kembali ke produk
            </a>

            <h1 class="kie-reviews-title">
                Semua Ulasan
            </h1>

            <p class="kie-reviews-product">
                {{ $product->nama }}
            </p>
        </div>

        <div class="kie-reviews-score">
            <strong class="kie-reviews-score-number">
                {{ number_format($averageRating, 1) }}
            </strong>

            <div class="kie-reviews-score-stars">
                @for ($i = 1; $i <= 5; $i++)
                    @php
                        $fullStar = $averageRating >= $i;
                        $halfStar = ! $fullStar
                            && $averageRating >= ($i - 0.5);
                    @endphp

                    @if ($fullStar)
                        <i
                            class="fa-solid fa-star"
                            style="color:#F2A01C;"
                        ></i>
                    @elseif ($halfStar)
                        <i
                            class="fa-solid fa-star-half-stroke"
                            style="color:#F2A01C;"
                        ></i>
                    @else
                        <i
                            class="fa-solid fa-star"
                            style="color:#DDD6CE;"
                        ></i>
                    @endif
                @endfor
            </div>

            <small class="kie-reviews-score-count">
                {{ $reviews->total() }} ulasan
            </small>
        </div>
    </header>

    <section class="kie-reviews-grid">
        @foreach ($reviews as $testimonial)
            @php
                $photos = collect($testimonial->photos ?? [])
                    ->filter()
                    ->take(5)
                    ->values();

                $name = $testimonial->displayName();

                $rating = (int) ($testimonial->rating ?? 0);

                $address = collect([
                    $testimonial->kabupaten,
                    $testimonial->provinsi,
                ])->filter()->implode(', ');
            @endphp

            <article class="kie-review-card {{ $photos->isNotEmpty() ? 'has-photos' : '' }}">

                <div class="kie-review-profile">
                    <span class="kie-review-avatar">
                        {{ strtoupper(mb_substr($name, 0, 1)) }}
                    </span>

                    <div class="kie-review-user">
                        <p class="kie-review-name">
                            {{ $name }}
                        </p>

                        <div class="kie-review-rating">
                            <div class="kie-review-stars">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i
                                        class="fa-solid fa-star"
                                        style="color: {{ $i <= $rating ? '#F2A01C' : '#DDD6CE' }};"
                                    ></i>
                                @endfor
                            </div>

                            @if ($testimonial->rating)
                                <span class="kie-review-rating-number">
                                    {{ number_format((float) $testimonial->rating, 1) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <span class="kie-review-date">
                    {{ $testimonial->created_at?->format('d/m/Y') }}
                </span>

                @if ($address !== '')
                    <div class="kie-review-location">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>{{ $address }}</span>
                    </div>
                @endif

                @if ($testimonial->comment)
                    <p class="kie-review-comment">
                        {{ $testimonial->comment }}
                    </p>
                @endif

                @if ($photos->isNotEmpty())
                    <div class="kie-review-photos">
                        @foreach ($photos as $photo)
                            <a
                                href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="kie-review-photo"
                            >
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo) }}"
                                    alt="Foto ulasan {{ $name }}"
                                >
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($testimonial->approvedUpdateComment?->comment)
                    <div class="kie-review-update">
                        <p class="kie-review-update-title">
                            Update dari pembeli
                        </p>

                        <p class="kie-review-update-text">
                            {{ $testimonial->approvedUpdateComment->comment }}
                        </p>
                    </div>
                @endif

            </article>
        @endforeach
    </section>

    @if ($reviews->hasPages())
        <div class="kie-reviews-pagination">
            {{ $reviews->links() }}
        </div>
    @endif

</main>

@include('partials.frontend.footer')

</body>
</html>