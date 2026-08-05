{{--
    Partial logo terpusat — SATU sumber untuk logo di seluruh project
    (Sidebar Admin, Login Admin, Navbar Frontend, Footer, dsb).

    Data diambil dari Pengaturan > Identitas Website (App\Models\Setting).
    Kalau admin belum unggah logo, otomatis pakai ikon Font Awesome
    sebagai fallback — desain tiap lokasi tetap seperti semula karena
    class-nya bisa disesuaikan lewat parameter di bawah.

    Dipakai lewat:
        @include('partials.logo', [
            'boxSize'  => 'h-11 w-11',
            'rounded'  => 'rounded-2xl',
            'boxClass' => 'bg-linear-to-br from-admin-gold to-admin-accent shadow-lg shadow-black/30 ring-1 ring-white/10',
            'imgClass' => 'shadow-lg shadow-black/30 ring-1 ring-white/10',
            'iconClass'=> 'text-lg text-white',
            'icon'     => 'fa-couch',
        ])

    Semua parameter opsional (ada nilai default di bawah).
--}}
@php
    $setting = \App\Models\Setting::current();

    $boxSize = $boxSize ?? 'h-10 w-10';
    $rounded = $rounded ?? 'rounded-xl';
    $boxClass = $boxClass ?? 'bg-admin-panel';
    $imgClass = $imgClass ?? '';
    $iconClass = $iconClass ?? 'text-white';
    $icon = $icon ?? 'fa-couch';
@endphp

@if ($setting->logoUrl())
    <img
        src="{{ $setting->logoUrl() }}"
        alt="Logo {{ $setting->site_name }}"
        class="{{ $boxSize }} {{ $rounded }} {{ $imgClass }} shrink-0 object-cover"
    >
@else
    <span class="flex {{ $boxSize }} {{ $rounded }} {{ $boxClass }} shrink-0 items-center justify-center">
        <i class="fa-solid {{ $icon }} {{ $iconClass }}"></i>
    </span>
@endif
