<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lacak Pesanan {{ $transaction->order_code }} — {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="min-h-screen bg-white font-sans antialiased text-[#2A211B]">
    @include('partials.frontend.navbar')

    @php
        $statusStyle = match ($transaction->status) {
            'pending', 'confirmed', 'preparing' => ['label' => 'Menunggu Antrean', 'pill' => 'bg-slate-100 text-slate-600', 'dot' => 'bg-slate-400'],
            'processing' => ['label' => 'Sedang Diproses', 'pill' => 'bg-violet-50 text-violet-600', 'dot' => 'bg-violet-500'],
            'completed' => ['label' => 'Selesai', 'pill' => 'bg-emerald-50 text-emerald-600', 'dot' => 'bg-emerald-500'],
            'cancelled' => ['label' => 'Dibatalkan', 'pill' => 'bg-red-50 text-red-600', 'dot' => 'bg-red-500'],
            default => ['label' => ucfirst($transaction->status), 'pill' => 'bg-slate-100 text-slate-600', 'dot' => 'bg-slate-400'],
        };

        $isActiveQueue = in_array($transaction->status, \App\Models\Transaction::ACTIVE_STATUSES, true);
        $addressParts = array_filter([
            $transaction->alamat_lengkap,
            $transaction->kecamatan ? 'Kec. '.$transaction->kecamatan : null,
            $transaction->kota,
            $transaction->provinsi,
            $transaction->kode_pos ? 'Kode Pos '.$transaction->kode_pos : null,
        ]);
    @endphp

    <section class="bg-[#F6F9F6]">
        <div class="mx-auto flex min-h-43.75 max-w-295 items-center justify-center px-5 py-12 sm:px-7 lg:px-8">
            <div class="flex flex-col items-center text-center">
                <h1 class="font-display text-3xl font-semibold tracking-tight text-[#171717] sm:text-4xl">Lacak Pesanan</h1>
                <div class="mt-3 flex items-center gap-2 text-[11px] text-[#A29587]">
                    <a href="{{ route('home') }}" class="transition-colors hover:text-[#2A211B]">Home</a>
                    <span>/</span>
                    <span class="font-medium text-[#F28A22]">Lacak Pesanan</span>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto w-full px-5 py-12 sm:px-8 lg:px-12 xl:px-16">
        <div class="rounded-2xl border border-[#EFE7DC] bg-white p-6 shadow-sm sm:p-8">
            @unless ($isTrustedDevice)
                <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-relaxed text-amber-900">
                    <div class="flex items-start gap-2.5">
                        <i class="fa-solid fa-shield-halved mt-0.5"></i>
                        <div>
                            <p class="font-semibold">Akses terbatas pada perangkat ini</p>
                            <p class="mt-1">Link pesanan ini terdaftar pada perangkat pemiliknya. Demi privasi, detail pribadi, alamat, WhatsApp, jumlah, total, dan catatan pesanan tidak ditampilkan di perangkat ini.</p>
                        </div>
                    </div>
                </div>
            @endunless

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-[#A29587]">Kode Pesanan</p>
                    <p class="mt-1 font-display text-xl font-semibold text-[#2A211B]">{{ $transaction->order_code }}</p>
                    <p class="mt-1 text-xs text-[#8A7C6E]">{{ $transaction->created_at?->translatedFormat('d M Y, H:i') }}</p>
                </div>
                <span class="inline-flex w-fit items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusStyle['pill'] }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $statusStyle['dot'] }}"></span>
                    {{ $statusStyle['label'] }}
                </span>
            </div>

            @if ($isTrustedDevice && $isActiveQueue)
                <div class="mt-6 rounded-xl bg-[#F7F8F6] p-5 text-center">
                    <p class="text-[11px] uppercase tracking-wide text-[#A29587]">Posisi Antrean Anda</p>
                    <p class="mt-1 font-display text-4xl font-semibold text-[#F28A22]">#{{ $transaction->queue_number }}</p>
                    <p class="mt-2 text-xs leading-relaxed text-[#8A7C6E]">
                        @if ($transaction->status === 'processing')
                            Pesanan Anda sedang dikerjakan sekarang.
                        @else
                            Mohon ditunggu, pesanan Anda akan diproses sesuai urutan antrean.
                        @endif
                    </p>
                </div>
            @endif

            <div class="mt-6 border-t border-[#EFE7DC] pt-6">
                <h2 class="font-display text-base font-semibold text-[#2A211B]">Detail Pesanan</h2>
                <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @if ($isTrustedDevice)
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-[#A29587]">Nama Pemesan</p>
                            <p class="mt-1 text-sm font-semibold">{{ $transaction->customer_name }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-[#A29587]">WhatsApp</p>
                            <p class="mt-1 text-sm font-semibold">{{ $transaction->whatsapp ?: '—' }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-[#A29587]">Produk</p>
                        <p class="mt-1 text-sm font-semibold">{{ $transaction->product?->nama ?? '—' }}</p>
                    </div>
                    @if ($isTrustedDevice)
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-[#A29587]">Jumlah</p>
                            <p class="mt-1 text-sm font-semibold">{{ $transaction->quantity }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-[#A29587]">Total</p>
                            <p class="mt-1 text-sm font-semibold">Rp{{ number_format((float) $transaction->total, 0, ',', '.') }}</p>
                        </div>
                    @endif
                    @if ($isTrustedDevice && $transaction->catatan)
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-[#A29587]">Catatan Pesanan</p>
                            <p class="mt-1 text-sm font-semibold">{{ $transaction->catatan }}</p>
                        </div>
                    @endif
                </div>
            </div>

            @if ($isTrustedDevice)
            <div class="mt-6 border-t border-[#EFE7DC] pt-6">
                <h2 class="font-display text-base font-semibold text-[#2A211B]">Alamat Pengiriman</h2>
                <div class="mt-4 rounded-xl bg-[#F7F8F6] p-4 text-sm leading-relaxed text-[#5F554B]">
                    @if ($addressParts)
                        <p class="font-semibold text-[#2A211B]">{{ $transaction->nama_penerima ?: $transaction->customer_name }}</p>
                        <p class="mt-1">{{ implode(', ', $addressParts) }}</p>
                    @else
                        <p class="text-[#8A7C6E]">Alamat pengiriman belum tersedia pada pesanan ini.</p>
                    @endif
                </div>
            </div>
            @endif

            <div class="mt-6 flex flex-col gap-3 border-t border-[#EFE7DC] pt-6 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('tracking.index') }}" class="inline-flex items-center justify-center gap-2 rounded-full border border-[#E8DED1] px-5 py-2.5 text-sm font-semibold text-[#6E6257] transition-colors hover:bg-[#F7F8F6]">
                    <i class="fa-solid fa-clock-rotate-left text-xs"></i>
                    Pesanan Saya di Perangkat Ini
                </a>
                <p class="text-xs leading-relaxed text-[#A29587] sm:max-w-sm sm:text-right">
                    Link ini terikat pada perangkat pertama yang membukanya. Gunakan perangkat yang sama untuk melihat detail lengkap pesanan.
                </p>
            </div>
        </div>

        @if ($isTrustedDevice && $transaction->status === 'completed')
            <div class="mt-6 rounded-2xl border border-[#EFE7DC] bg-white p-6 shadow-sm sm:p-8">
                <h2 class="font-display text-base font-semibold text-[#2A211B]">Komentar</h2>
                <p class="mt-1 text-xs leading-relaxed text-[#8A7C6E]">
                    Opsional — boleh diisi, boleh juga dilewati. Komentar akan ditinjau admin dulu sebelum tampil sebagai testimoni publik.
                </p>

                @if (session('comment_status') === 'sent')
                    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                        <i class="fa-solid fa-circle-check mr-1.5"></i>
                        Terima kasih! Komentar Anda sudah terkirim dan sedang ditinjau admin.
                    </div>
                @elseif (session('comment_status') === 'update_sent')
                    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                        <i class="fa-solid fa-circle-check mr-1.5"></i>
                        Terima kasih! Update komentar Anda sudah terkirim dan sedang ditinjau admin.
                    </div>
                @elseif (session('comment_status') === 'update_blocked')
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
                        <i class="fa-solid fa-triangle-exclamation mr-1.5"></i>
                        Update komentar untuk pesanan ini sudah tidak bisa dikirim lagi (sudah pernah update sebelumnya, atau sudah melewati {{ $updateWindowDays }} hari).
                    </div>
                @endif

                {{-- Komentar yang sudah pernah dikirim (kalau ada), ditampilkan sebagai histori. --}}
                @if ($originalComment)
                    <div class="mt-4 space-y-3">
                        <div class="rounded-xl border border-[#E8DED1] bg-[#F7F8F6] p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-[#A29587]">Komentar Anda</p>

                            @if ($originalComment->displayAddress())
                                <p class="mt-1 text-[11px] text-[#A29587]">
                                    <i class="fa-solid fa-location-dot mr-1"></i>{{ $originalComment->displayAddress() }}
                                </p>
                            @endif

                            @if ($originalComment->rating)
                                <div class="mt-1.5 flex items-center gap-0.5 text-[#F0A321]">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="fa-solid fa-star text-xs {{ $i > $originalComment->rating ? 'text-[#E3DED7]' : '' }}"></i>
                                    @endfor
                                </div>
                            @endif

                            <p class="mt-1.5 text-sm leading-relaxed text-[#2A211B]">{{ $originalComment->comment }}</p>

                            @if (!empty($originalComment->photos))
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($originalComment->photos as $photo)
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo) }}"
                                            alt="Foto komentar"
                                            class="h-16 w-16 rounded-lg object-cover"
                                        >
                                    @endforeach
                                </div>
                            @endif

                            <p class="mt-2 text-[11px] text-[#A29587]">{{ $originalComment->created_at->translatedFormat('d M Y, H:i') }}</p>
                        </div>

                        @if ($updateComment)
                            <div class="rounded-xl border border-[#F28A22]/30 bg-[#FFF7ED] p-4">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-[#C46A1A]">
                                    <i class="fa-solid fa-arrow-turn-up mr-1"></i>Update Komentar
                                </p>

                                @if ($updateComment->rating)
                                    <div class="mt-1.5 flex items-center gap-0.5 text-[#F0A321]">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="fa-solid fa-star text-xs {{ $i > $updateComment->rating ? 'text-[#E3DED7]' : '' }}"></i>
                                        @endfor
                                    </div>
                                @endif

                                <p class="mt-1.5 text-sm leading-relaxed text-[#2A211B]">{{ $updateComment->comment }}</p>

                                @if (!empty($updateComment->photos))
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($updateComment->photos as $photo)
                                            <img
                                                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo) }}"
                                                alt="Foto update komentar"
                                                class="h-16 w-16 rounded-lg object-cover"
                                            >
                                        @endforeach
                                    </div>
                                @endif

                                <p class="mt-2 text-[11px] text-[#A29587]">{{ $updateComment->created_at->translatedFormat('d M Y, H:i') }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Form: kalau belum pernah komentar sama sekali, ATAU sudah komentar
                     tapi masih boleh kirim Update (belum pernah update & masih dalam
                     jendela waktu). Endpoint sama; TrackingController yang menentukan
                     ini dianggap komentar pertama atau komentar Update. --}}
                @if (! $originalComment || $canSubmitUpdate)
                    <form
                        method="POST"
                        action="{{ route('tracking.comment', $transaction->tracking_token) }}"
                        enctype="multipart/form-data"
                        class="mt-4"
                    >
                        @csrf

                        @if ($originalComment)
                            <p class="mb-3 text-xs font-semibold text-[#8A7C6E]">
                                Produk berubah setelah beberapa hari? Kirim Update Komentar (hanya bisa sekali, dalam {{ $updateWindowDays }} hari sejak komentar pertama).
                            </p>
                        @endif

                        {{-- Rating bintang — opsional, dipilih pembeli dengan klik. Nilainya
                             dikirim lewat hidden input "rating" (1-5) dan dipakai sebagai
                             bintang testimoni publik (lihat Testimonial::rating). --}}
                        <div x-data="{ rating: {{ (int) old('rating', 0) }}, hover: 0 }" class="mb-3">
                            <label class="text-xs font-semibold text-[#8A7C6E]">
                                Beri Rating <span class="font-normal text-[#A29587]">(opsional)</span>
                            </label>
                            <div class="mt-1.5 flex items-center gap-1">
                                <input type="hidden" name="rating" :value="rating || ''">
                                @for ($i = 1; $i <= 5; $i++)
                                    <button
                                        type="button"
                                        x-on:click="rating = (rating === {{ $i }} ? 0 : {{ $i }})"
                                        x-on:mouseenter="hover = {{ $i }}"
                                        x-on:mouseleave="hover = 0"
                                        class="p-0.5 text-xl leading-none transition-colors"
                                        :class="(hover || rating) >= {{ $i }} ? 'text-[#F0A321]' : 'text-[#E3DED7]'"
                                        aria-label="Beri {{ $i }} bintang"
                                    >
                                        <i class="fa-solid fa-star"></i>
                                    </button>
                                @endfor
                            </div>
                            @error('rating')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <textarea
                            name="comment"
                            rows="4"
                            maxlength="1000"
                            placeholder="Tulis komentar Anda tentang pesanan ini (opsional)..."
                            class="w-full rounded-xl border border-[#E8DED1] bg-[#F7F8F6] p-4 text-sm text-[#2A211B] placeholder:text-[#A29587] focus:border-[#F28A22] focus:outline-none focus:ring-2 focus:ring-[#F28A22]/20"
                        >{{ old('comment') }}</textarea>
                        @error('comment')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror

                        {{-- Alamat (Provinsi & Kabupaten/Kota) — sama seperti sensor nama di
                             bawah, hanya tampil & disimpan untuk komentar PERTAMA (yang
                             dijadikan kartu testimoni publik). Keduanya opsional; kalau
                             dikosongkan baris alamat tidak akan tampil di kartu publik.
                             Lihat Testimonial::displayAddress(). --}}
                        @unless ($originalComment)
                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="provinsi" class="text-xs font-semibold text-[#8A7C6E]">
                                        Provinsi <span class="font-normal text-[#A29587]">(opsional)</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="provinsi"
                                        name="provinsi"
                                        value="{{ old('provinsi') }}"
                                        maxlength="255"
                                        placeholder="mis. Jawa Tengah"
                                        class="mt-1.5 w-full rounded-xl border border-[#E8DED1] bg-[#F7F8F6] px-4 py-2.5 text-sm text-[#2A211B] placeholder:text-[#A29587] focus:border-[#F28A22] focus:outline-none focus:ring-2 focus:ring-[#F28A22]/20"
                                    >
                                    @error('provinsi')
                                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="kabupaten" class="text-xs font-semibold text-[#8A7C6E]">
                                        Kabupaten/Kota <span class="font-normal text-[#A29587]">(opsional)</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="kabupaten"
                                        name="kabupaten"
                                        value="{{ old('kabupaten') }}"
                                        maxlength="255"
                                        placeholder="mis. Semarang"
                                        class="mt-1.5 w-full rounded-xl border border-[#E8DED1] bg-[#F7F8F6] px-4 py-2.5 text-sm text-[#2A211B] placeholder:text-[#A29587] focus:border-[#F28A22] focus:outline-none focus:ring-2 focus:ring-[#F28A22]/20"
                                    >
                                    @error('kabupaten')
                                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        @endunless

                        {{-- Sensor nama — hanya tampil di komentar PERTAMA, karena hanya
                             komentar pertama (topLevel) yang dijadikan kartu testimoni
                             publik. Lihat Testimonial::displayName(). --}}
                        @unless ($originalComment)
                            <label class="mt-3 flex items-start gap-2.5 text-xs text-[#5C5147]">
                                <input
                                    type="checkbox"
                                    name="is_name_masked"
                                    value="1"
                                    {{ old('is_name_masked') ? 'checked' : '' }}
                                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-[#E8DED1] text-[#F28A22] focus:ring-[#F28A22]/30"
                                >
                                <span>
                                    Samarkan sebagian nama saya saat tampil sebagai testimoni publik
                                    <span class="text-[#A29587]">(mis. "{{ \Illuminate\Support\Str::of($transaction->customer_name)->explode(' ')->map(fn ($w) => \Illuminate\Support\Str::substr($w, 0, 1).str_repeat('*', max(\Illuminate\Support\Str::length($w) - 1, 1)))->implode(' ') }}")</span>
                                </span>
                            </label>
                        @endunless

                        <div class="mt-3">
                            <label class="text-xs font-semibold text-[#8A7C6E]">
                                Lampirkan Foto <span class="font-normal text-[#A29587]">(opsional, maks. {{ $maxPhotos }} foto, masing-masing maks. 2 MB)</span>
                            </label>
                            <input
                                type="file"
                                name="photos[]"
                                accept="image/jpeg,image/png,image/webp"
                                multiple
                                class="mt-1.5 block w-full text-xs text-[#8A7C6E] file:mr-3 file:rounded-full file:border-0 file:bg-[#2A211B] file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-[#403129]"
                            >
                            @error('photos')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                            @error('photos.*')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            class="mt-4 inline-flex items-center justify-center gap-2 rounded-full bg-[#2A211B] px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#403129]"
                        >
                            <i class="fa-regular fa-paper-plane text-xs"></i>
                            {{ $originalComment ? 'Kirim Update Komentar' : 'Kirim Komentar' }}
                        </button>
                    </form>
                @endif
            </div>
        @elseif ($isTrustedDevice)
            <div class="mt-6 rounded-2xl border border-dashed border-[#E8DED1] bg-[#FBF7F1] p-6 text-center sm:p-8">
                <i class="fa-regular fa-comment-dots mb-2 text-lg text-[#A29587]"></i>
                <p class="text-sm font-semibold text-[#2A211B]">Komentar belum bisa dikirim</p>
                <p class="mt-1 text-xs leading-relaxed text-[#8A7C6E]">
                    Kolom komentar baru terbuka setelah pesanan ini berstatus <span class="font-semibold">Selesai</span>.
                </p>
            </div>
        @endif
    </section>

    @include('partials.frontend.footer')
</body>
</html>
