<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pesanan Saya — {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-white font-sans antialiased text-[#2A211B]">
    @include('partials.frontend.navbar')

    <section class="bg-[#F6F9F6]">
        <div class="mx-auto flex min-h-43.75 max-w-295 items-center justify-center px-5 py-12 sm:px-7 lg:px-8">
            <div class="text-center">
                <h1 class="font-display text-3xl font-semibold tracking-tight text-[#171717] sm:text-4xl">Pesanan Saya</h1>
                <p class="mt-2 text-sm text-[#8A7C6E]">Pesanan yang pernah Anda buka di perangkat ini.</p>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-295 px-5 py-12 sm:px-7 lg:px-8">
        @if ($transactions->isEmpty())
            <div class="rounded-2xl border border-[#EFE7DC] bg-white p-10 text-center shadow-sm">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#F7F8F6] text-[#A29587]">
                    <i class="fa-solid fa-receipt"></i>
                </span>
                <h2 class="mt-4 font-display text-lg font-semibold">Belum ada pesanan tersimpan</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-[#8A7C6E]">
                    Buka link tracking pesanan yang dikirim Karya Ide Edi melalui WhatsApp. Setelah dibuka, pesanan tersebut akan tersimpan di perangkat ini.
                </p>
            </div>
        @else
            <div class="grid gap-4">
                @foreach ($transactions as $transaction)
                    @php
                        $statusStyle = match ($transaction->status) {
                            'processing' => ['label' => 'Sedang Diproses', 'pill' => 'bg-violet-50 text-violet-600', 'dot' => 'bg-violet-500'],
                            'completed' => ['label' => 'Selesai', 'pill' => 'bg-emerald-50 text-emerald-600', 'dot' => 'bg-emerald-500'],
                            'cancelled' => ['label' => 'Dibatalkan', 'pill' => 'bg-red-50 text-red-600', 'dot' => 'bg-red-500'],
                            default => ['label' => 'Menunggu Antrean', 'pill' => 'bg-slate-100 text-slate-600', 'dot' => 'bg-slate-400'],
                        };
                    @endphp
                    <a href="{{ route('tracking.show', $transaction->tracking_token) }}"
                       class="group rounded-2xl border border-[#EFE7DC] bg-white p-5 shadow-sm transition-all hover:-translate-y-0.5 hover:border-[#F28A22]/40 hover:shadow-md sm:p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-[11px] uppercase tracking-wide text-[#A29587]">Kode Pesanan</p>
                                <p class="mt-1 font-display text-lg font-semibold text-[#2A211B]">{{ $transaction->order_code }}</p>
                                <p class="mt-1 text-xs text-[#8A7C6E]">{{ $transaction->created_at?->translatedFormat('d M Y, H:i') }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusStyle['pill'] }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $statusStyle['dot'] }}"></span>
                                    {{ $statusStyle['label'] }}
                                </span>
                                <i class="fa-solid fa-arrow-right text-xs text-[#A29587] transition-transform group-hover:translate-x-1"></i>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-4 border-t border-[#EFE7DC] pt-4 sm:grid-cols-3">
                            <div>
                                <p class="text-[11px] text-[#A29587]">Produk</p>
                                <p class="mt-1 truncate text-sm font-semibold">{{ $transaction->product?->nama ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-[#A29587]">Jumlah</p>
                                <p class="mt-1 text-sm font-semibold">{{ $transaction->quantity }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-[#A29587]">Total</p>
                                <p class="mt-1 text-sm font-semibold">Rp{{ number_format((float) $transaction->total, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    @include('partials.frontend.footer')
</body>
</html>