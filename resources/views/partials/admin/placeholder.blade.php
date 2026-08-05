{{--
    Partial placeholder untuk halaman admin yang route & komponennya
    sudah aktif, tapi fiturnya (CRUD dll) belum dikerjakan.

    Dipakai lewat:
        @include('partials.admin.placeholder', [
            'icon' => 'fa-couch',
            'heading' => 'Manajemen Produk',
            'description' => 'Kelola katalog produk furniture di sini.',
            'features' => [
                'Tambah, ubah, dan hapus produk',
                'Upload foto & atur kategori',
            ],
        ])
--}}
@php $features = $features ?? []; @endphp

<div class="animate-fade-in-up space-y-6">
    <div>
        <h2 class="font-display text-xl font-semibold text-admin-ink sm:text-2xl">
            {{ $heading }}
        </h2>
        <p class="mt-1 text-sm text-admin-ink-soft">
            {{ $description }}
        </p>
    </div>

    <div class="relative overflow-hidden rounded-2xl border border-admin-border bg-admin-surface shadow-sm">
        <div class="h-1 w-full bg-linear-to-r from-admin-gold via-admin-accent to-admin-gold"></div>

        <div class="relative px-6 py-16 text-center sm:py-20">
            <div class="pointer-events-none absolute inset-0 opacity-[0.4]" style="background-image: radial-gradient(circle at 1px 1px, var(--color-admin-border) 1px, transparent 0); background-size: 24px 24px;"></div>

            <div class="relative">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-linear-to-br from-admin-gold to-admin-accent shadow-lg shadow-(--color-admin-accent)/20">
                    <i class="fa-solid {{ $icon }} text-xl text-white"></i>
                </span>
                <p class="mt-5 font-display text-base font-semibold text-admin-ink">
                    Fitur ini akan segera hadir
                </p>
                <p class="mx-auto mt-1.5 max-w-sm text-xs leading-relaxed text-admin-ink-soft">
                    Halaman "{{ $heading }}" sudah bisa diakses, isinya akan dikembangkan pada tahap berikutnya.
                </p>

                @if (count($features))
                    <div class="mx-auto mt-6 flex max-w-md flex-wrap items-center justify-center gap-2">
                        @foreach ($features as $feature)
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-admin-border bg-admin-canvas px-3 py-1.5 text-[11px] font-medium text-admin-ink-soft">
                                <i class="fa-regular fa-circle-check text-admin-accent"></i>
                                {{ $feature }}
                            </span>
                        @endforeach
                    </div>
                @endif

                <span class="mt-6 inline-flex items-center gap-1.5 rounded-full border border-admin-border bg-admin-cream/60 px-3 py-1 text-[11px] font-medium text-admin-ink-soft">
                    <i class="fa-solid fa-hammer text-admin-accent"></i>
                    Dalam pengembangan
                </span>
            </div>
        </div>
    </div>
</div>