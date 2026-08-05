{{--
    ==========================================================
    FEATURES / KEUNGGULAN — Homepage Karya Ide Edi
    ==========================================================
    Komponen UI murni (belum ada logika bisnis apapun), 3 kolom
    keunggulan singkat yang tampil tepat di bawah hero, sesuai
    referensi desain Figma.

    Isi teks & ikon MASIH statis (hardcode) — belum ada
    tabel/kolom di database untuk data ini, jadi gampang
    diganti nanti begitu ada sumbernya.

    Pemakaian:
        @include('partials.frontend.features')
    ==========================================================
--}}
@php
    $featureList = [
        ['icon' => 'fa-truck-fast',  'title' => 'Custom Sesuai Pesanan',  'desc' => 'Bebas request sesuai kebutuhan Anda'],
        ['icon' => 'fa-comment-dots','title' => 'Konsultasi via WhatsApp', 'desc' => 'Tanya produk, harga, dan booking langsung'],
        ['icon' => 'fa-headset',     'title' => 'Pembayaran Fleksibel',    'desc' => 'Berbagai pilihan pembayaran yang aman'],
    ];
@endphp

<section class="bg-admin-surface">
    <div class="mx-auto grid max-w-7xl grid-cols-1 gap-y-6 px-6 py-8 sm:grid-cols-3 sm:divide-x sm:divide-admin-border sm:px-8 lg:px-10">
        @foreach ($featureList as $feature)
            <div class="flex items-start gap-3 sm:px-8 sm:first:pl-0 sm:last:pr-0">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-admin-cream">
                    <i class="fa-solid {{ $feature['icon'] }} text-sm text-admin-ink"></i>
                </span>
                <div>
                    <p class="text-sm font-semibold text-admin-ink">{{ $feature['title'] }}</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-admin-ink-soft">{{ $feature['desc'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>