{{--
    Editor gradasi untuk "Warna Frame (Latar Section)" di Admin > Edit Web.

    Dipakai lewat:
        @include('partials.admin.frame-gradient', [
            'key' => 'mission',
            'gradient' => $missionGradient,
        ])

    $key = awalan property Livewire (mission, produkUnggulan, kategori,
    testimoni, lokasi) -- state-nya ada di App\Support\HasFrameGradients
    (property "{$key}Gradient") dan tampilan beranda membacanya lewat
    App\Support\FrameBackground::resolve().

    Bawaan frame = warna POLOS. Gradasi baru dipakai kalau tombol di bawah
    dinyalakan; kalau dimatikan, frame kembali ke warna polos di atas.
--}}
@php
    $gradient = \App\Support\FrameBackground::normalize($gradient ?? null);
    $property = $key.'Gradient';
    $frameGradientDirections = [0 => 'Atas', 45 => 'Kanan atas', 90 => 'Kanan', 135 => 'Kanan bawah', 180 => 'Bawah', 225 => 'Kiri bawah', 270 => 'Kiri', 315 => 'Kiri atas'];
@endphp

<div class="space-y-4 rounded-xl border border-admin-border p-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Gradasi (Opsional)</p>
            <p class="mt-1 text-xs text-admin-ink-soft">
                @if ($gradient['enabled'])
                    Gradasi menyala &mdash; frame memakai gradasi di bawah, warna polos di atas tidak dipakai.
                @else
                    Mati &mdash; frame memakai warna polos di atas. Nyalakan kalau ingin mencoba gradasi.
                @endif
            </p>
        </div>

        <button
            type="button"
            role="switch"
            aria-checked="{{ $gradient['enabled'] ? 'true' : 'false' }}"
            wire:click="toggleFrameGradient('{{ $key }}')"
            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition {{ $gradient['enabled'] ? 'border-admin-accent bg-admin-accent text-white' : 'border-admin-border bg-admin-surface text-admin-ink hover:border-admin-accent/60' }}"
        >
            <i class="fa-solid {{ $gradient['enabled'] ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
            {{ $gradient['enabled'] ? 'Gradasi Menyala' : 'Nyalakan Gradasi' }}
        </button>
    </div>

    @if ($gradient['enabled'])
        {{-- Pratinjau gradasi yang sedang diatur --}}
        <div
            class="h-16 w-full rounded-xl border border-admin-border"
            style="background: {{ \App\Support\FrameBackground::cssGradient($gradient) }};"
            role="img"
            aria-label="Pratinjau gradasi"
        ></div>

        {{-- Gradasi rekomendasi: sekali klik langsung terisi, lalu tetap bisa diubah di bawahnya. --}}
        <div>
            <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-admin-ink-soft">Atau pilih dari rekomendasi</p>
            <div class="flex flex-wrap gap-3">
                @foreach (\App\Support\FrameBackground::PRESETS as $index => $preset)
                    @php $presetAktif = \App\Support\FrameBackground::matchesPreset($gradient, $preset); @endphp
                    <button
                        type="button"
                        wire:click="applyFrameGradientPreset('{{ $key }}', {{ $index }})"
                        title="{{ $preset['label'] }}"
                        class="group flex flex-col items-center gap-1"
                    >
                        <span
                            class="block h-9 w-16 rounded-lg shadow-sm transition duration-200 group-hover:scale-105 group-hover:shadow-md {{ $presetAktif ? 'ring-2 ring-admin-accent ring-offset-2 ring-offset-admin-surface' : 'border border-admin-border' }}"
                            style="background: {{ \App\Support\FrameBackground::cssGradient(['enabled' => true, 'from' => $preset['from'], 'use_mid' => $preset['mid'] !== null, 'mid' => $preset['mid'], 'to' => $preset['to'], 'angle' => $preset['angle']]) }};"
                        ></span>
                        <span class="max-w-16 truncate text-[10px] text-admin-ink-soft">{{ $preset['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Warna gradasi: awal, akhir, dan (opsional) tengah. --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <label class="flex flex-col gap-1.5 text-xs font-medium text-admin-ink-soft">
                Warna awal
                <input
                    type="color" wire:model.live.change="{{ $property }}.from"
                    class="h-10 w-full cursor-pointer rounded-lg border border-admin-border"
                >
            </label>

            <label class="flex flex-col gap-1.5 text-xs font-medium text-admin-ink-soft">
                Warna akhir
                <input
                    type="color" wire:model.live.change="{{ $property }}.to"
                    class="h-10 w-full cursor-pointer rounded-lg border border-admin-border"
                >
            </label>

            <div class="flex flex-col gap-1.5 text-xs font-medium text-admin-ink-soft">
                <label class="flex items-center gap-2">
                    <input
                        type="checkbox" wire:model.live="{{ $property }}.use_mid"
                        class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent"
                    >
                    Tambah warna tengah
                </label>
                <input
                    type="color" wire:model.live.change="{{ $property }}.mid"
                    @disabled(! $gradient['use_mid'])
                    class="h-10 w-full cursor-pointer rounded-lg border border-admin-border disabled:cursor-not-allowed disabled:opacity-40"
                >
            </div>
        </div>

        <div>
            <button
                type="button"
                wire:click="swapFrameGradientColors('{{ $key }}')"
                class="inline-flex items-center gap-2 rounded-full border border-admin-border px-3 py-1.5 text-xs font-medium text-admin-ink transition hover:border-admin-accent/60"
            >
                <i class="fa-solid fa-right-left"></i>
                Tukar warna awal &amp; akhir
            </button>
        </div>

        {{-- Arah gradasi: 8 tombol arah + slider sudut bebas (0-360 derajat). --}}
        <div class="space-y-2">
            <p class="text-[11px] font-medium uppercase tracking-wide text-admin-ink-soft">Arah gradasi</p>

            <div class="flex flex-wrap gap-2">
                @foreach ($frameGradientDirections as $derajat => $namaArah)
                    <button
                        type="button"
                        wire:click="setFrameGradientAngle('{{ $key }}', {{ $derajat }})"
                        title="{{ $namaArah }}"
                        aria-label="Arah {{ $namaArah }}"
                        class="flex h-9 w-9 items-center justify-center rounded-lg border text-xs transition {{ $gradient['angle'] === $derajat ? 'border-admin-accent bg-admin-accent text-white' : 'border-admin-border text-admin-ink hover:border-admin-accent/60' }}"
                    >
                        <i class="fa-solid fa-arrow-up" style="transform: rotate({{ $derajat }}deg);"></i>
                    </button>
                @endforeach
            </div>

            <div class="flex items-center gap-3">
                <input
                    type="range" min="0" max="360" step="1"
                    wire:model.live.change="{{ $property }}.angle"
                    class="h-2 w-full cursor-pointer"
                    aria-label="Sudut gradasi"
                >
                <span class="w-12 shrink-0 text-right text-xs font-medium text-admin-ink">{{ $gradient['angle'] }}&deg;</span>
            </div>
        </div>

        <p class="text-xs text-admin-ink-soft">
            Warna judul, teks, dan kartu di section ini otomatis menyesuaikan (terang/gelap) mengikuti
            warna rata-rata gradasi, supaya tetap kebaca. Klik Simpan di bawah untuk menerapkan ke beranda.
        </p>
    @endif
</div>
