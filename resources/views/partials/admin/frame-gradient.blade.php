{{--
    Editor gradasi Admin > Edit Web.
    Preview berjalan sepenuhnya di browser (Alpine),
    sehingga slider / warna / preset tidak menunggu request Livewire.
--}}
@php
    $gradient = \App\Support\FrameBackground::normalize($gradient ?? null);
    $property = $key.'Gradient';

    $frameGradientDirections = [
        0   => 'Atas',
        45  => 'Kanan atas',
        90  => 'Kanan',
        135 => 'Kanan bawah',
        180 => 'Bawah',
        225 => 'Kiri bawah',
        270 => 'Kiri',
        315 => 'Kiri atas',
    ];
@endphp

<div
    class="space-y-4 rounded-xl border border-admin-border p-4"
    x-data="{
        from: @js($gradient['from']),
        to: @js($gradient['to']),
        mid: @js($gradient['mid']),
        useMid: @js((bool) $gradient['use_mid']),
        angle: @js((int) $gradient['angle']),

        previewCss() {
            const stops = this.useMid
                ? `${this.from} 0%, ${this.mid} 50%, ${this.to} 100%`
                : `${this.from} 0%, ${this.to} 100%`;

            return `linear-gradient(${this.angle}deg, ${stops})`;
        },

        sync() {
            /*
             * false = update state Livewire lokal tanpa request server.
             * Jadi editor tidak lag.
             * State terbaru tetap ikut saat tombol Simpan ditekan.
             */
            $wire.set(@js($property.'.from'), this.from, false);
            $wire.set(@js($property.'.to'), this.to, false);
            $wire.set(@js($property.'.mid'), this.mid, false);
            $wire.set(@js($property.'.use_mid'), this.useMid, false);
            $wire.set(@js($property.'.angle'), Number(this.angle), false);
        },

        setAngle(value) {
            this.angle = Number(value);
            this.sync();
        },

        swapColors() {
            const oldFrom = this.from;
            this.from = this.to;
            this.to = oldFrom;
            this.sync();
        },

        preset(from, mid, to, angle) {
            this.from = from;
            this.to = to;
            this.useMid = mid !== null;

            if (mid !== null) {
                this.mid = mid;
            }

            this.angle = Number(angle);

            this.sync();
        }
    }"
>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">
                Gradasi (Opsional)
            </p>

            <p class="mt-1 text-xs text-admin-ink-soft">
                @if ($gradient['enabled'])
                    Gradasi menyala - frame memakai gradasi di bawah,
                    warna polos di atas tidak dipakai.
                @else
                    Mati - frame memakai warna polos di atas.
                    Nyalakan kalau ingin mencoba gradasi.
                @endif
            </p>
        </div>

        <button
            type="button"
            role="switch"
            aria-checked="{{ $gradient['enabled'] ? 'true' : 'false' }}"
            wire:click="toggleFrameGradient('{{ $key }}')"
            wire:loading.attr="disabled"
            wire:target="toggleFrameGradient"
            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition
                {{ $gradient['enabled']
                    ? 'border-admin-accent bg-admin-accent text-white'
                    : 'border-admin-border bg-admin-surface text-admin-ink hover:border-admin-accent/60'
                }}"
        >
            <i class="fa-solid {{ $gradient['enabled'] ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>

            {{ $gradient['enabled']
                ? 'Gradasi Menyala'
                : 'Nyalakan Gradasi'
            }}
        </button>

    </div>

    @if ($gradient['enabled'])

        {{-- =====================================================
             PREVIEW REAL-TIME
        ====================================================== --}}
        <div
            class="h-20 w-full rounded-xl border border-admin-border shadow-inner"
            :style="{ background: previewCss() }"
            style="background: {{ \App\Support\FrameBackground::cssGradient($gradient) }};"
            role="img"
            aria-label="Pratinjau gradasi"
        ></div>


        {{-- =====================================================
             PRESET
        ====================================================== --}}
        <div>
            <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-admin-ink-soft">
                Atau pilih dari rekomendasi
            </p>

            <div class="flex flex-wrap gap-3">

                @foreach (\App\Support\FrameBackground::PRESETS as $preset)

                    <button
                        type="button"
                        @click.prevent="
                            preset(
                                @js($preset['from']),
                                @js($preset['mid']),
                                @js($preset['to']),
                                {{ (int) $preset['angle'] }}
                            )
                        "
                        title="{{ $preset['label'] }}"
                        class="group flex flex-col items-center gap-1"
                    >
                        <span
                            class="block h-9 w-16 rounded-lg border border-admin-border shadow-sm transition duration-150 group-hover:scale-105 group-hover:border-admin-accent group-hover:shadow-md"
                            style="background:
                                {{ \App\Support\FrameBackground::cssGradient([
                                    'enabled' => true,
                                    'from' => $preset['from'],
                                    'use_mid' => $preset['mid'] !== null,
                                    'mid' => $preset['mid'],
                                    'to' => $preset['to'],
                                    'angle' => $preset['angle'],
                                ]) }};
                            "
                        ></span>

                        <span class="max-w-16 truncate text-[10px] text-admin-ink-soft">
                            {{ $preset['label'] }}
                        </span>
                    </button>

                @endforeach

            </div>
        </div>


        {{-- =====================================================
             WARNA
        ====================================================== --}}
        <div class="grid gap-4 sm:grid-cols-3">

            <label class="flex flex-col gap-1.5 text-xs font-medium text-admin-ink-soft">
                Warna awal

                <input
                    type="color"
                    :value="from"
                    @input="from = $event.target.value"
                    @change="sync()"
                    class="h-10 w-full cursor-pointer rounded-lg border border-admin-border"
                >
            </label>


            <label class="flex flex-col gap-1.5 text-xs font-medium text-admin-ink-soft">
                Warna akhir

                <input
                    type="color"
                    :value="to"
                    @input="to = $event.target.value"
                    @change="sync()"
                    class="h-10 w-full cursor-pointer rounded-lg border border-admin-border"
                >
            </label>


            <div class="flex flex-col gap-1.5 text-xs font-medium text-admin-ink-soft">

                <label class="flex cursor-pointer items-center gap-2">
                    <input
                        type="checkbox"
                        :checked="useMid"
                        @change="
                            useMid = $event.target.checked;
                            sync();
                        "
                        class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent"
                    >

                    Tambah warna tengah
                </label>

                <input
                    type="color"
                    :value="mid"
                    :disabled="!useMid"
                    @input="mid = $event.target.value"
                    @change="sync()"
                    class="h-10 w-full cursor-pointer rounded-lg border border-admin-border disabled:cursor-not-allowed disabled:opacity-40"
                >

            </div>

        </div>


        {{-- =====================================================
             TUKAR WARNA
        ====================================================== --}}
        <div>
            <button
                type="button"
                @click.prevent="swapColors()"
                class="inline-flex items-center gap-2 rounded-full border border-admin-border px-3 py-1.5 text-xs font-medium text-admin-ink transition active:scale-95 hover:border-admin-accent/60"
            >
                <i class="fa-solid fa-right-left"></i>
                Tukar warna awal & akhir
            </button>
        </div>


        {{-- =====================================================
             ARAH GRADASI
        ====================================================== --}}
        <div class="space-y-3">

            <p class="text-[11px] font-medium uppercase tracking-wide text-admin-ink-soft">
                Arah gradasi
            </p>


            {{-- Tombol arah --}}
            <div class="flex flex-wrap gap-2">

                @foreach ($frameGradientDirections as $derajat => $namaArah)

                    <button
                        type="button"
                        @click.prevent="setAngle({{ $derajat }})"
                        title="{{ $namaArah }}"
                        aria-label="Arah {{ $namaArah }}"
                        :class="
                            Number(angle) === {{ $derajat }}
                                ? 'border-admin-accent bg-admin-accent text-white'
                                : 'border-admin-border text-admin-ink hover:border-admin-accent/60'
                        "
                        class="flex h-10 w-10 items-center justify-center rounded-lg border text-xs transition active:scale-90"
                    >
                        <i
                            class="fa-solid fa-arrow-up"
                            style="transform: rotate({{ $derajat }}deg);"
                        ></i>
                    </button>

                @endforeach

            </div>


            {{-- Slider --}}
            <div class="flex items-center gap-3">

                <input
                    type="range"
                    min="0"
                    max="360"
                    step="1"

                    :value="angle"

                    @input="
                        angle = Number($event.target.value);
                    "

                    @change="
                        angle = Number($event.target.value);
                        sync();
                    "

                    class="h-2 w-full cursor-pointer accent-admin-accent"

                    style="
                        touch-action: none;
                        pointer-events: auto;
                    "

                    aria-label="Sudut gradasi"
                >

                <span class="w-14 shrink-0 text-right text-xs font-semibold text-admin-ink">
                    <span
                        x-text="Math.round(angle)"
                    >{{ $gradient['angle'] }}</span><span>&deg;</span>
                </span>

            </div>


            <p class="text-[10px] text-admin-ink-soft">
                Geser slider - preview di atas berubah langsung tanpa menunggu server.
            </p>

        </div>


        <p class="text-xs leading-relaxed text-admin-ink-soft">
            Warna judul, teks, dan kartu di section ini otomatis menyesuaikan
            terang/gelap mengikuti warna rata-rata gradasi supaya tetap terbaca.
            Klik <strong>Simpan</strong> untuk menerapkan hasilnya ke beranda.
        </p>

    @endif
</div>