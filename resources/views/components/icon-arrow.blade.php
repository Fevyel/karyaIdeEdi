{{--
    ==========================================================
    <x-icon-arrow /> — SATU-SATUNYA sumber icon panah/arrow
    di seluruh project (Admin Panel & Frontend).
    ==========================================================

    Semua icon "arrow", "chevron", "angle", "caret", tombol
    back/next/previous, indikator dropdown/accordion/expand,
    dan sejenisnya WAJIB lewat komponen ini — bukan Heroicons,
    Lucide, SVG manual, atau library lain. Ini memastikan
    ukuran, warna, dan transisi selalu konsisten mengikuti
    tema, cukup diatur di satu tempat (file ini).

    Pemakaian:
        <x-icon-arrow direction="right" />
        <x-icon-arrow direction="left" class="mr-1" />
        <x-icon-arrow direction="chevron-down" size="text-[10px]" />
        <x-icon-arrow direction="right" class="transition-transform duration-300 group-hover:translate-x-1" />

    Props:
        direction (string, wajib) — salah satu dari daftar di bawah.
        size      (string, opsional) — class ukuran, default 'text-xs'.
                  Chevron/caret/angle kecil di select & dropdown
                  biasanya pakai 'text-[10px]'.

    Warna TIDAK diatur di sini secara default — icon mewarisi
    `currentColor` dari elemen pembungkusnya (link/button/dsb),
    sesuai gaya yang sudah dipakai di seluruh project ("warna
    mengikuti tema"). Kalau perlu warna spesifik, tambahkan lewat
    prop `class`, mis. class="text-admin-accent".

    Menambah varian arah baru: tambahkan mapping-nya di $map
    di bawah — jangan buat icon arrow baru di luar komponen ini.
    ==========================================================
--}}
@php
    $map = [
        // Arrow lurus
        'left' => 'fa-arrow-left',
        'right' => 'fa-arrow-right',
        'up' => 'fa-arrow-up',
        'down' => 'fa-arrow-down',

        // Arrow panjang (dipakai di tombol CTA/login)
        'long-left' => 'fa-arrow-left-long',
        'long-right' => 'fa-arrow-right-long',
        'long-up' => 'fa-arrow-up-long',
        'long-down' => 'fa-arrow-down-long',

        // Chevron — dropdown, accordion, expand/collapse
        'chevron-left' => 'fa-chevron-left',
        'chevron-right' => 'fa-chevron-right',
        'chevron-up' => 'fa-chevron-up',
        'chevron-down' => 'fa-chevron-down',

        // Angle — pagination, carousel
        'angle-left' => 'fa-angle-left',
        'angle-right' => 'fa-angle-right',
        'angle-up' => 'fa-angle-up',
        'angle-down' => 'fa-angle-down',

        // Caret — indikator dropdown kecil
        'caret-left' => 'fa-caret-left',
        'caret-right' => 'fa-caret-right',
        'caret-up' => 'fa-caret-up',
        'caret-down' => 'fa-caret-down',

        // Varian bermakna khusus (tetap keluarga arrow, FA)
        'external' => 'fa-arrow-up-right-from-square', // buka link baru
        'logout' => 'fa-arrow-right-from-bracket',      // keluar/logout
        'refresh' => 'fa-arrow-rotate-right',           // muat ulang
    ];

    $icon = $map[$direction] ?? 'fa-arrow-right';
    $size = $size ?? 'text-xs';
@endphp

<i {{ $attributes->merge(['class' => "fa-solid {$icon} {$size}"]) }}></i>
