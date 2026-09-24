<?php

use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin-panel')] #[Title('Pesanan')] class extends Component
{
    use WithPagination;

    public string $search = '';

    /** 'all' atau salah satu dari Transaction::STATUSES. */
    public string $statusFilter = 'all';

    public bool $showAddModal = false;

    public bool $showDetail = false;

    public ?int $detailId = null;

    // ============ Form "Tambah Pesanan" ============
    public string $customer_name = '';

    public string $whatsapp = '';

    public string $nama_penerima = '';

    public string $alamat_lengkap = '';

    public string $kecamatan = '';

    public string $kota = '';

    public string $provinsi = '';

    public string $kode_pos = '';

    public ?int $product_id = null;

    /** 'tetap' (harga produk dari database, default) atau 'custom' (ukuran & harga manual). */
    public string $order_type = 'tetap';

    public ?float $custom_tinggi = null;

    public ?float $custom_lebar = null;

    public ?float $custom_panjang = null;

    /** Harga satuan hasil negosiasi dengan customer - hanya dipakai saat order_type = 'custom'. */
    public ?float $custom_harga_satuan = null;

    /**
     * Deskripsi bebas untuk pesanan custom - bahan, warna, finishing, dll
     * yang sudah didiskusikan dengan customer. Muncul di bawah pilihan
     * Produk saat order_type = 'custom', karena untuk pesanan custom
     * produk itu sendiri sifatnya opsional (hanya sebagai referensi jenis
     * mebel jika memang mirip salah satu produk yang ada).
     */
    public string $custom_deskripsi = '';

    public ?int $quantity = 1;

    public string $catatan = '';

    /** Begitu Pesanan dibuka, semua pesanan yang belum dibaca langsung ditandai sudah dibaca. */
    public function mount(): void
    {
        auth()->user()?->markPesananRead();
        $this->dispatch('admin-notifications-updated');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /** BUG FIX AUDIT: dropdown filter pakai wire:model.live="statusFilter"
     * langsung (bukan wire:click="setFilter(...)"), jadi tanpa hook ini
     * pagination TIDAK reset ke halaman 1 saat filter diganti. */
    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function setFilter(string $status): void
    {
        $valid = array_merge(['all'], Transaction::ACTIVE_STATUSES);
        $this->statusFilter = in_array($status, $valid, true) ? $status : 'all';
        $this->resetPage();
    }

    public function openAddModal(): void
    {
        $this->resetForm();
        $this->showAddModal = true;
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['customer_name', 'whatsapp', 'nama_penerima', 'alamat_lengkap', 'kecamatan', 'kota', 'provinsi', 'kode_pos', 'product_id', 'quantity', 'catatan', 'order_type', 'custom_tinggi', 'custom_lebar', 'custom_panjang', 'custom_harga_satuan', 'custom_deskripsi']);
        $this->quantity = 1;
        $this->order_type = 'tetap';
        $this->resetErrorBag();
        $this->resetValidation();
    }

    /**
     * Begitu admin ganti Tetap <-> Custom, kosongkan field yang tidak lagi
     * relevan supaya tidak diam-diam ikut tersimpan/tervalidasi. Beralih ke
     * 'tetap' menghapus ukuran & harga custom; beralih ke 'custom' tidak
     * mengubah pilihan produk (tetap dipakai sebagai referensi jenis mebel).
     */
    public function updatedOrderType(): void
    {
        if ($this->order_type === 'tetap') {
            $this->reset(['custom_tinggi', 'custom_lebar', 'custom_panjang', 'custom_harga_satuan', 'custom_deskripsi']);
        }

        $this->resetErrorBag(['custom_tinggi', 'custom_lebar', 'custom_panjang', 'custom_harga_satuan', 'custom_deskripsi', 'product_id']);
    }

    /**
     * Begitu admin mengganti produk, jumlah yang sudah diisi disesuaikan
     * ulang terhadap stok produk BARU - supaya tidak diam-diam menyimpan
     * jumlah dari produk sebelumnya yang mungkin melebihi stok produk ini.
     */
    public function updatedProductId(): void
    {
        $product = $this->product_id
            ? Product::query()->where('status', 'aktif')->find($this->product_id)
            : null;

        // Pesanan custom boleh tidak merujuk produk manapun (lihat
        // updatedOrderType()/rules()) - tanpa produk, tidak ada batas stok
        // untuk dicek, jadi cukup pastikan jumlah tetap minimal 1.
        if (! $product) {
            if ($this->quantity === null || $this->quantity < 1) {
                $this->quantity = 1;
            }

            $this->resetErrorBag('quantity');

            return;
        }

        $stok = (int) $product->stok;

        if ($stok <= 0) {
            // Stok habis: jangan pura-pura kasih quantity 1. Tombol submit
            // dinonaktifkan lewat $stokHabis di bawah (lihat with()/markup).
            $this->quantity = 0;
        } elseif ($this->quantity === null || $this->quantity < 1) {
            $this->quantity = 1;
        } elseif ($this->quantity > $stok) {
            $this->quantity = $stok;
        }

        $this->resetErrorBag('quantity');
    }

    /**
     * Dipanggil otomatis setiap kali admin mengetik di field Jumlah
     * (wire:model.live). Ini pertahanan realtime di sisi Livewire supaya
     * quantity TIDAK PERNAH tersimpan melebihi stok produk yang sedang
     * dipilih - atribut HTML min/max saja tidak cukup karena cuma hint
     * visual, tidak benar-benar mencegah nilai lain masuk.
     */
    public function updatedQuantity(): void
    {
        // Biarkan kosong sementara selagi admin masih mengetik ulang -
        // tidak boleh memicu error Livewire/JS. Validasi "wajib diisi"
        // baru ditegakkan saat submit lewat rules().
        if ($this->quantity === null) {
            return;
        }

        $product = $this->product_id
            ? Product::query()->where('status', 'aktif')->find($this->product_id)
            : null;

        if (! $product) {
            if ($this->quantity < 1) {
                $this->quantity = 1;
            }

            $this->resetErrorBag('quantity');

            return;
        }

        $stok = (int) $product->stok;

        if ($stok <= 0) {
            $this->quantity = 0;
        } elseif ($this->quantity < 1) {
            $this->quantity = 1;
        } elseif ($this->quantity > $stok) {
            $this->quantity = $stok;
        }

        $this->resetErrorBag('quantity');
    }

    public function openDetail(int $transactionId): void
    {
        $this->detailId = $transactionId;
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->detailId = null;
    }

    /**
     * Tombol "Batalkan Pesanan" di nota. Hanya boleh untuk pesanan yang
     * BELUM sedang dikerjakan (bukan 'processing') dan belum final.
     * Stok yang sudah dikurangi saat pesanan dibuat dikembalikan SATU KALI
     * di sini (lockForUpdate baris produk, sama seperti saat pengurangan),
     * lalu antrean hari itu dirapatkan supaya tidak ada celah nomor.
     */
    public function cancelTransaction(int $transactionId): void
    {
        try {
            DB::transaction(function () use ($transactionId) {
                $transaction = Transaction::query()->lockForUpdate()->findOrFail($transactionId);

                if (in_array($transaction->status, Transaction::FINAL_STATUSES, true)) {
                    throw new \RuntimeException('Pesanan ini sudah tidak aktif (sudah selesai/dibatalkan).');
                }

                if ($transaction->status === 'processing') {
                    throw new \RuntimeException('Pesanan sedang dikerjakan (antrean #1) dan tidak dapat dibatalkan.');
                }

                $transaction->forceFill(['status' => 'cancelled', 'queue_number' => null])->save();

                if ($transaction->product_id) {
                    $product = Product::query()->lockForUpdate()->find($transaction->product_id);
                    $product?->increment('stok', $transaction->quantity);
                }

                // PERBAIKAN NOMOR ANTREAN: compactQueue()/promoteQueueFront()
                // sekarang GLOBAL (lintas tanggal), tidak butuh $queueDate lagi.
                Transaction::compactQueue();
                Transaction::promoteQueueFront();
            });
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Pesanan gagal dibatalkan karena kendala sistem.');

            return;
        }

        session()->flash('status', 'Pesanan dibatalkan dan antrean dirapatkan otomatis.');
        $this->closeDetail();
    }

    /**
     * Tombol "Pesanan Selesai" di nota. Hanya boleh untuk pesanan yang
     * MEMANG berada di posisi antrean #1 (queue_number terkecil di antara
     * yang masih aktif) dan berstatus 'processing'. Setelah selesai:
     * antrean dirapatkan, dan pesanan yang sekarang #1 otomatis jadi
     * 'processing'. order_code & tracking_token tidak pernah berubah.
     */
    public function completeTransaction(int $transactionId): void
    {
        try {
            DB::transaction(function () use ($transactionId) {
                $transaction = Transaction::query()->lockForUpdate()->findOrFail($transactionId);

                if (in_array($transaction->status, Transaction::FINAL_STATUSES, true)) {
                    throw new \RuntimeException('Pesanan ini sudah tidak aktif (sudah selesai/dibatalkan).');
                }

                // PERBAIKAN NOMOR ANTREAN: posisi terdepan sekarang dicek GLOBAL
                // (lintas tanggal), bukan cuma di antara pesanan tanggal yang sama.
                $frontQueueNumber = Transaction::query()
                    ->activeQueue()
                    ->min('queue_number');

                if ($transaction->status !== 'processing' || (int) $transaction->queue_number !== (int) $frontQueueNumber) {
                    throw new \RuntimeException('Hanya pesanan antrean #1 yang sedang diproses yang bisa ditandai selesai.');
                }

                $transaction->forceFill(['status' => 'completed', 'queue_number' => null])->save();

                Transaction::compactQueue();
                Transaction::promoteQueueFront();
            });
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Pesanan gagal ditandai selesai karena kendala sistem.');

            return;
        }

        session()->flash('status', 'Pesanan ditandai selesai. Antrean berikutnya otomatis naik & mulai diproses.');
    }

    protected function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'whatsapp' => ['required', 'digits_between:10,15'],
            'nama_penerima' => ['required', 'string', 'max:255'],
            'alamat_lengkap' => ['required', 'string', 'max:2000'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'kota' => ['required', 'string', 'max:100'],
            'provinsi' => ['required', 'string', 'max:100'],
            'kode_pos' => ['required', 'digits:5'],
            // Produk wajib diisi untuk pesanan 'tetap' (harga & stok diambil
            // dari sana). Untuk pesanan 'custom', produk sifatnya opsional -
            // admin bisa memilihnya sekadar sebagai referensi jenis mebel,
            // atau mengosongkannya dan menjelaskan detailnya lewat
            // custom_deskripsi di bawah.
            'product_id' => ['nullable', 'required_if:order_type,tetap', Rule::exists('products', 'id')->where('status', 'aktif')],
            'order_type' => ['required', Rule::in(Transaction::ORDER_TYPES)],
            'custom_harga_satuan' => ['required_if:order_type,custom', 'nullable', 'numeric', 'min:1'],
            'custom_deskripsi' => ['required_if:order_type,custom', 'nullable', 'string', 'max:2000'],
            'quantity' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $product = $this->product_id ? Product::find($this->product_id) : null;

                    if (! $product) {
                        return;
                    }

                    if ((int) $product->stok <= 0) {
                        $fail('Produk ini sedang tidak memiliki stok.');

                        return;
                    }

                    if ((int) $value > (int) $product->stok) {
                        $fail("Jumlah pesanan tidak boleh melebihi stok tersedia. Stok saat ini: {$product->stok}.");
                    }
                },
                'min:1',
            ],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'customer_name.required' => 'Nama customer wajib diisi.',
            'whatsapp.required' => 'Nomor WhatsApp customer wajib diisi.',
            'nama_penerima.required' => 'Nama penerima wajib diisi.',
            'alamat_lengkap.required' => 'Alamat lengkap wajib diisi.',
            'kecamatan.required' => 'Kecamatan wajib diisi.',
            'kota.required' => 'Kota/Kabupaten wajib diisi.',
            'provinsi.required' => 'Provinsi wajib diisi.',
            'kode_pos.required' => 'Kode pos wajib diisi.',
            'kode_pos.digits' => 'Kode pos harus 5 digit.',
            'whatsapp.digits_between' => 'Nomor WhatsApp harus berupa angka saja (tanpa spasi/simbol), 10-15 digit.',
            'product_id.required_if' => 'Produk wajib dipilih untuk pesanan tetap.',
            'product_id.exists' => 'Produk yang dipilih tidak valid atau sudah tidak aktif.',
            'custom_harga_satuan.required_if' => 'Harga hasil diskusi dengan customer wajib diisi untuk pesanan custom.',
            'custom_harga_satuan.numeric' => 'Harga harus berupa angka.',
            'custom_harga_satuan.min' => 'Harga harus lebih besar dari 0.',
            'custom_deskripsi.required_if' => 'Deskripsi custom wajib diisi untuk pesanan custom.',
            'custom_deskripsi.max' => 'Deskripsi maksimal 2000 karakter.',
            'quantity.required' => 'Jumlah wajib diisi.',
            'quantity.integer' => 'Jumlah harus berupa angka bulat.',
            'quantity.min' => 'Jumlah minimal 1.',
            'catatan.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }

    /**
     * Simpan pesanan baru. Harga TIDAK PERNAH dipercaya dari input/frontend -
     * selalu diambil ulang dari tabel Product di sini, langsung sebelum
     * disimpan, supaya tidak bisa dimanipulasi.
     *
     * Validasi stok + pengurangan stok dibungkus DB::transaction() dengan
     * lockForUpdate() pada baris produk: baris dikunci dulu, BARU stok
     * aktualnya dibaca dan divalidasi, baru pesanan dibuat, baru stok
     * dikurangi - semua dalam satu transaction atomic. Ini mencegah race
     * condition: kalau dua pesanan untuk produk yang sama disimpan hampir
     * bersamaan, proses kedua menunggu proses pertama selesai (commit/
     * rollback) sebelum ikut membaca stok, jadi stok tidak mungkin jadi
     * negatif / overselling.
     *
     * Kalau stok tidak cukup (atau berubah di database setelah form
     * dibuka), ValidationException dilempar dari dalam transaction -
     * otomatis rollback (aman, belum ada yang tersimpan), lalu Livewire
     * menampilkannya sebagai error field seperti validasi biasa.
     */
    public function save(): void
    {
        $this->validate();

        try {
            $transaction = DB::transaction(function () {
                // Produk hanya wajib ada untuk pesanan 'tetap' (sudah
                // ditegakkan di rules()). Untuk pesanan 'custom', admin
                // boleh tidak memilih produk sama sekali - $product tetap
                // null dan tidak ada pengecekan/pengurangan stok.
                $product = $this->product_id
                    ? Product::query()->where('status', 'aktif')->lockForUpdate()->find($this->product_id)
                    : null;

                if ($this->order_type === 'tetap' && ! $product) {
                    throw ValidationException::withMessages([
                        'product_id' => 'Produk yang dipilih tidak valid atau sudah tidak aktif.',
                    ]);
                }

                if ($this->product_id && ! $product) {
                    // Custom, tapi produk referensi yang tadinya dipilih
                    // ternyata sudah tidak valid/aktif lagi.
                    throw ValidationException::withMessages([
                        'product_id' => 'Produk yang dipilih tidak valid atau sudah tidak aktif.',
                    ]);
                }

                if ($product) {
                    if ((int) $product->stok <= 0) {
                        throw ValidationException::withMessages([
                            'quantity' => 'Produk ini sedang tidak memiliki stok.',
                        ]);
                    }

                    if ((int) $this->quantity > (int) $product->stok) {
                        throw ValidationException::withMessages([
                            'quantity' => "Jumlah pesanan tidak boleh melebihi stok tersedia. Stok saat ini: {$product->stok}.",
                        ]);
                    }
                }

                // Harga satuan: untuk 'tetap', selalu diambil ulang dari
                // Product seperti sebelumnya (tidak pernah dipercaya dari
                // input). Untuk 'custom', pakai harga hasil diskusi dengan
                // customer yang diinput admin - karena barangnya memang
                // dibuat sesuai ukuran custom, bukan harga baku produk.
                $hargaSatuan = $this->order_type === 'custom'
                    ? (float) $this->custom_harga_satuan
                    : (float) (($product->harga_diskon && (float) $product->harga_diskon > 0)
                        ? $product->harga_diskon
                        : $product->harga);

                $newTransaction = Transaction::create([
                    'product_id' => $product?->id,
                    'order_type' => $this->order_type,
                    'custom_tinggi' => $this->order_type === 'custom' ? $this->custom_tinggi : null,
                    'custom_lebar' => $this->order_type === 'custom' ? $this->custom_lebar : null,
                    'custom_panjang' => $this->order_type === 'custom' ? $this->custom_panjang : null,
                    'custom_harga_satuan' => $this->order_type === 'custom' ? $this->custom_harga_satuan : null,
                    'custom_deskripsi' => $this->order_type === 'custom' && trim($this->custom_deskripsi) !== ''
                        ? $this->custom_deskripsi
                        : null,
                    'customer_name' => $this->customer_name,
                    'whatsapp' => $this->whatsapp,
                    'nama_penerima' => $this->nama_penerima,
                    'alamat_lengkap' => $this->alamat_lengkap,
                    'kecamatan' => $this->kecamatan,
                    'kota' => $this->kota,
                    'provinsi' => $this->provinsi,
                    'kode_pos' => $this->kode_pos,
                    'quantity' => $this->quantity,
                    'catatan' => $this->catatan !== '' ? $this->catatan : null,
                    'total' => $hargaSatuan * $this->quantity,
                    'status' => 'pending',
                    // is_read_admin sengaja TIDAK di-set di sini — biarkan pakai
                    // default kolom (false/belum dibaca), sesuai desain sistem
                    // badge: baris baru selalu unread sampai admin membuka
                    // halaman Pesanan lagi (lihat User::markPesananRead()).
                    // Berlaku juga untuk pesanan yang dibuat manual oleh admin
                    // sendiri di sini — supaya badge tetap muncul sebagai
                    // pengingat "ada pesanan yang belum diproses".
                ]);

                // Baris produk sudah dikunci (lockForUpdate) di atas, jadi
                // pengurangan stok ini aman dari race condition. Kalau
                // pesanan custom tidak merujuk produk manapun, tidak ada
                // stok yang perlu dikurangi.
                $product?->decrement('stok', $this->quantity);

                // Kalau antrean aktif sebelumnya kosong, pesanan ini otomatis jadi
                // #1 dan langsung berstatus 'processing'. Kalau sudah ada antrean
                // lain di depan (termasuk sisa dari tanggal sebelumnya), pesanan
                // ini tetap 'pending' menunggu giliran. PERBAIKAN NOMOR ANTREAN:
                // promoteQueueFront() sekarang global, tidak lagi discope per
                // queue_date pesanan yang baru dibuat.
                Transaction::promoteQueueFront();

                return $newTransaction;
            });
        } catch (ValidationException $e) {
            // Dilempar ulang supaya ditangani Livewire persis seperti hasil
            // $this->validate() - pesan error muncul di field yang tepat,
            // modal tetap terbuka, input yang sudah diisi admin tidak hilang.
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Pesanan gagal disimpan karena kendala sistem. Data yang sudah diisi tidak hilang - silakan coba lagi.');

            return;
        }

        session()->flash(
            'status',
            "Pesanan berhasil dibuat. Kode pesanan: {$transaction->order_code}, nomor antrean: #{$transaction->queue_number}."
        );

        $this->closeAddModal();
        $this->resetPage();
    }

    public function with(): array
    {
        $query = Transaction::query()
            ->with('product:id,nama')
            // History Pesanan: status final (completed/cancelled) sudah
            // punya halaman sendiri (/admin/pesanan/history) — halaman ini
            // fokus cuma ke antrean yang masih perlu ditangani admin.
            ->whereIn('status', Transaction::ACTIVE_STATUSES)
            ->when($this->search !== '', function ($q) {
                $keyword = $this->search;
                $q->where(function ($sub) use ($keyword) {
                    $sub->where('order_code', 'like', "%{$keyword}%")
                        ->orWhere('customer_name', 'like', "%{$keyword}%")
                        ->orWhereHas('product', fn ($p) => $p->where('nama', 'like', "%{$keyword}%"));
                });
            })
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            // Antrean bersifat GLOBAL lintas tanggal, jadi tampilan wajib
            // mengikuti queue_number yang sudah dirapatkan: #1, #2, #3, ...
            ->orderBy('queue_number', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');

        $selectedProduct = $this->product_id
            ? Product::query()->where('status', 'aktif')->find($this->product_id)
            : null;

        $stokHabis = $selectedProduct && (int) $selectedProduct->stok <= 0;

        return [
            'transactions' => $query->paginate(10),
            'totalPesanan' => Transaction::query()->whereIn('status', Transaction::ACTIVE_STATUSES)->count(),
            'produkList' => Product::query()->where('status', 'aktif')->orderBy('nama')->get(['id', 'nama', 'harga', 'harga_diskon', 'stok', 'thumbnail']),
            'selectedProduct' => $selectedProduct,
            'stokHabis' => $stokHabis,
            'detailItem' => $this->showDetail && $this->detailId
                ? Transaction::query()->with('product:id,nama')->find($this->detailId)
                : null,
        ];
    }
};
?>

<div class="space-y-6">

    @if (session('status'))
        <div class="mb-4 flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 shadow-sm">
            <i class="fa-solid fa-circle-check"></i>
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 flex items-center gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600 shadow-sm">
            <i class="fa-solid fa-triangle-exclamation"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- ================= HEADER ================= --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-xl font-semibold text-admin-ink sm:text-2xl">
                Pesanan
            </h2>
            <p class="mt-1 text-sm text-admin-ink-soft">
                {{ $totalPesanan }} pesanan tercatat. Pantau, cari, dan filter pesanan pelanggan dari sini.
            </p>
        </div>
        <button
            type="button"
            wire:click="openAddModal"
            class="inline-flex items-center justify-center gap-2 rounded-full bg-admin-panel px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-(--color-admin-panel)/20 transition-all duration-300 hover:-translate-y-0.5 hover:bg-admin-accent-strong"
        >
            <i class="fa-solid fa-plus text-xs"></i>
            Tambah Pesanan
        </button>
    </div>

    {{-- ================= SEARCH & FILTER STATUS (fungsional) ================= --}}
    <div class="flex flex-col gap-3 rounded-2xl border border-admin-border bg-admin-surface p-4 shadow-sm lg:flex-row lg:items-center">
        <div class="relative flex-1">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-xs text-admin-ink-soft"></i>
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Cari kode pesanan, nama customer, atau nama produk..."
                class="w-full rounded-xl border border-admin-border bg-admin-canvas py-2.5 pl-10 pr-4 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15"
            >
        </div>

        <div class="relative lg:w-56 lg:shrink-0">
            <i class="fa-solid fa-filter pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[11px] text-admin-accent"></i>
            <select
                wire:model.live="statusFilter"
                class="w-full cursor-pointer appearance-none rounded-xl border border-admin-border bg-admin-canvas py-2.5 pl-8 pr-8 text-xs font-medium text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15 sm:text-sm"
            >
                <option value="all">Semua Status</option>
                @foreach (\App\Models\Transaction::ACTIVE_STATUSES as $statusOption)
                    <option value="{{ $statusOption }}">{{ ucfirst($statusOption) }}</option>
                @endforeach
            </select>
            <x-icon-arrow direction="chevron-down" size="text-[10px]" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-admin-ink-soft" />
        </div>
    </div>

    {{-- ================= TABEL PESANAN ================= --}}
    @if ($transactions->isEmpty())
        <div class="relative overflow-hidden rounded-2xl border border-admin-border bg-admin-surface px-6 py-20 text-center shadow-sm">
            <div class="pointer-events-none absolute inset-0 opacity-[0.4]" style="background-image: radial-gradient(circle at 1px 1px, var(--color-admin-border) 1px, transparent 0); background-size: 24px 24px;"></div>

            <div class="relative">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-linear-to-br from-admin-gold to-admin-accent shadow-lg shadow-(--color-admin-accent)/20">
                    <i class="fa-solid fa-receipt text-xl text-white"></i>
                </span>
                <p class="mt-5 font-display text-base font-semibold text-admin-ink">
                    {{ $search !== '' || $statusFilter !== 'all' ? 'Tidak ada pesanan yang cocok' : 'Belum ada pesanan' }}
                </p>
                <p class="mx-auto mt-1.5 max-w-sm text-xs leading-relaxed text-admin-ink-soft">
                    @if ($search !== '' || $statusFilter !== 'all')
                        Coba ubah kata kunci pencarian atau filter status di atas.
                    @else
                        Pesanan pelanggan yang masuk akan muncul di sini. Klik "Tambah Pesanan" untuk mencatat pesanan pertama secara manual.
                    @endif
                </p>
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-admin-border bg-admin-surface shadow-sm">
            <div class="admin-scroll overflow-x-auto">
                <table class="w-full min-w-260 text-left text-sm">
                    <thead>
                        <tr class="border-b border-admin-border bg-admin-canvas text-[11px] font-semibold uppercase tracking-wide text-admin-ink-soft">
                            <th class="px-5 py-3 font-semibold">Kode Pesanan</th>
                            <th class="px-3 py-3 font-semibold">Customer</th>
                            <th class="px-3 py-3 font-semibold">Produk</th>
                            <th class="px-3 py-3 font-semibold">Jumlah</th>
                            <th class="px-3 py-3 font-semibold">Total</th>
                            <th class="px-3 py-3 font-semibold">No. Antrean</th>
                            <th class="px-3 py-3 font-semibold">Status</th>
                            <th class="px-3 py-3 font-semibold">Tanggal</th>
                            <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-admin-border">
                        @foreach ($transactions as $transaction)
                            @php
                                $statusStyle = match ($transaction->status) {
                                    'pending' => ['dot' => 'bg-slate-400', 'pill' => 'bg-slate-100 text-slate-600'],
                                    'confirmed' => ['dot' => 'bg-blue-500', 'pill' => 'bg-blue-50 text-blue-600'],
                                    'processing' => ['dot' => 'bg-violet-500', 'pill' => 'bg-violet-50 text-violet-600'],
                                    'preparing' => ['dot' => 'bg-amber-500', 'pill' => 'bg-amber-50 text-amber-600'],
                                    'completed' => ['dot' => 'bg-admin-success', 'pill' => 'bg-admin-success/10 text-admin-success'],
                                    'cancelled' => ['dot' => 'bg-admin-danger', 'pill' => 'bg-admin-danger/10 text-admin-danger'],
                                    default => ['dot' => 'bg-slate-400', 'pill' => 'bg-slate-100 text-slate-600'],
                                };
                            @endphp
                            <tr class="transition-colors duration-200 hover:bg-admin-canvas">
                                <td class="px-5 py-3 font-medium text-admin-ink">
                                    {{ $transaction->order_code }}
                                </td>
                                <td class="px-3 py-3 text-admin-ink">
                                    {{ $transaction->customer_name }}
                                </td>
                                <td class="px-3 py-3 text-admin-ink-soft">
                                    {{ $transaction->product?->nama ?? '—' }}
                                    @if ($transaction->order_type === 'custom')
                                        <span class="ml-1.5 inline-flex items-center rounded-full bg-admin-accent/10 px-2 py-0.5 text-[10px] font-semibold text-admin-accent">Custom</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-admin-ink">
                                    {{ $transaction->quantity }}
                                </td>
                                <td class="px-3 py-3 font-semibold text-admin-ink">
                                    Rp{{ number_format((float) $transaction->total, 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 text-admin-ink-soft">
                                    @if ($transaction->queue_number)
                                        <span class="font-medium text-admin-ink">#{{ $transaction->queue_number }}</span>
                                        <span class="block text-[11px]">{{ $transaction->queue_date?->translatedFormat('d M Y') }}</span>
                                    @else
                                        &mdash;
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusStyle['pill'] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $statusStyle['dot'] }}"></span>
                                        {{ ucfirst($transaction->status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-xs text-admin-ink-soft">
                                    {{ $transaction->created_at?->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1.5" wire:key="aksi-{{ $transaction->id }}">
                                        <button
                                            type="button"
                                            title="Lihat detail pesanan"
                                            wire:click="openDetail({{ $transaction->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg text-admin-ink-soft transition-colors duration-200 hover:bg-admin-cream hover:text-admin-accent"
                                        >
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-admin-border px-5 py-3.5">
                {{ $transactions->onEachSide(1)->links() }}
            </div>
        </div>
    @endif

    {{-- ================= MODAL: DETAIL PESANAN (read-only) ================= --}}
    @if ($showDetail && $detailItem)
        @php
            $detailStatusStyle = match ($detailItem->status) {
                'pending' => ['dot' => 'bg-slate-400', 'pill' => 'bg-slate-100 text-slate-600'],
                'confirmed' => ['dot' => 'bg-blue-500', 'pill' => 'bg-blue-50 text-blue-600'],
                'processing' => ['dot' => 'bg-violet-500', 'pill' => 'bg-violet-50 text-violet-600'],
                'preparing' => ['dot' => 'bg-amber-500', 'pill' => 'bg-amber-50 text-amber-600'],
                'completed' => ['dot' => 'bg-admin-success', 'pill' => 'bg-admin-success/10 text-admin-success'],
                'cancelled' => ['dot' => 'bg-admin-danger', 'pill' => 'bg-admin-danger/10 text-admin-danger'],
                default => ['dot' => 'bg-slate-400', 'pill' => 'bg-slate-100 text-slate-600'],
            };
        @endphp
        <div class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
            <div wire:click="closeDetail" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

            <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-admin-surface shadow-2xl">
            <div class="modal-scroll max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-admin-border bg-admin-surface px-6 py-4">
                    <h3 class="font-display text-lg font-semibold text-admin-ink">Detail Pesanan</h3>
                    <button type="button" wire:click="closeDetail" class="flex h-8 w-8 items-center justify-center rounded-lg text-admin-ink-soft transition-colors duration-200 hover:bg-admin-cream">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="space-y-5 px-6 py-5">
                    @if (session('error'))
                        <div class="mb-4 flex items-center gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600 shadow-sm">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Kode Pesanan</p>
                            <p class="mt-0.5 text-sm font-semibold text-admin-ink">{{ $detailItem->order_code }}</p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $detailStatusStyle['pill'] }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $detailStatusStyle['dot'] }}"></span>
                            {{ ucfirst($detailItem->status) }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 rounded-xl border border-admin-border bg-admin-canvas p-4">
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Customer</p>
                            <p class="mt-1 text-sm font-semibold text-admin-ink">{{ $detailItem->customer_name }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">WhatsApp</p>
                            <p class="mt-1 text-sm font-semibold text-admin-ink">{{ $detailItem->whatsapp ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Produk</p>
                            <p class="mt-1 text-sm font-semibold text-admin-ink">{{ $detailItem->product?->nama ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Tipe Pesanan</p>
                            <p class="mt-1 text-sm font-semibold text-admin-ink">
                                {{ $detailItem->order_type === 'custom' ? 'Custom' : 'Tetap' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Jumlah</p>
                            <p class="mt-1 text-sm font-semibold text-admin-ink">{{ $detailItem->quantity }}</p>
                        </div>
                        @if ($detailItem->order_type === 'custom')
                            <div class="col-span-2">
                                <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Ukuran Custom (T x L x P)</p>
                                <p class="mt-1 text-sm font-semibold text-admin-ink">
                                    {{ rtrim(rtrim(number_format((float) $detailItem->custom_tinggi, 2, ',', '.'), '0'), ',') }} cm
                                    &times;
                                    {{ rtrim(rtrim(number_format((float) $detailItem->custom_lebar, 2, ',', '.'), '0'), ',') }} cm
                                    &times;
                                    {{ rtrim(rtrim(number_format((float) $detailItem->custom_panjang, 2, ',', '.'), '0'), ',') }} cm
                                </p>
                            </div>
                        @endif
                        @if ($detailItem->order_type === 'custom' && filled($detailItem->custom_deskripsi))
                            <div class="col-span-2">
                                <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Deskripsi Custom</p>
                                <p class="mt-1 whitespace-pre-line text-sm font-semibold text-admin-ink">{{ $detailItem->custom_deskripsi }}</p>
                            </div>
                        @endif
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Total</p>
                            <p class="mt-1 text-sm font-semibold text-admin-ink">Rp{{ number_format((float) $detailItem->total, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Posisi Antrean</p>
                            <p class="mt-1 text-sm font-semibold text-admin-ink">
                                @if (in_array($detailItem->status, \App\Models\Transaction::FINAL_STATUSES, true))
                                    {{ $detailItem->status === 'completed' ? 'Selesai' : 'Dibatalkan' }} &mdash; sudah keluar dari antrean
                                @else
                                    {{ $detailItem->queue_number ? '#'.$detailItem->queue_number : '—' }}
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Tanggal Pesanan</p>
                            <p class="mt-1 text-sm font-semibold text-admin-ink">{{ $detailItem->created_at?->translatedFormat('d M Y, H:i') }}</p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-admin-border bg-admin-canvas p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-admin-ink-soft">Alamat Pengiriman</p>
                        <div class="mt-3 space-y-1 text-sm text-admin-ink">
                            <p><span class="font-semibold">Penerima:</span> {{ $detailItem->nama_penerima ?: $detailItem->customer_name }}</p>
                            @if ($detailItem->alamat_lengkap)
                                <p><span class="font-semibold">Alamat:</span> {{ $detailItem->alamat_lengkap }}</p>
                                <p><span class="font-semibold">Wilayah:</span> {{ collect([$detailItem->kecamatan ? 'Kec. '.$detailItem->kecamatan : null, $detailItem->kota, $detailItem->provinsi, $detailItem->kode_pos ? 'Kode Pos '.$detailItem->kode_pos : null])->filter()->implode(', ') }}</p>
                            @else
                                <p class="text-admin-ink-soft">Alamat belum tersedia pada pesanan ini.</p>
                            @endif
                        </div>
                    </div>

                    @php
                        $isProcessingFront = $detailItem->status === 'processing';
                        $isCancellable = ! in_array($detailItem->status, \App\Models\Transaction::FINAL_STATUSES, true) && ! $isProcessingFront;
                        $isCompleted = $detailItem->status === 'completed';

                        $trackingUrl = $detailItem->tracking_token
                            ? route('tracking.show', $detailItem->tracking_token)
                            : null;

                        $waNumberDigits = $detailItem->whatsapp ? preg_replace('/\D/', '', $detailItem->whatsapp) : null;
                        if ($waNumberDigits && str_starts_with($waNumberDigits, '0')) {
                            $waNumberDigits = '62'.substr($waNumberDigits, 1);
                        } elseif ($waNumberDigits && str_starts_with($waNumberDigits, '8')) {
                            $waNumberDigits = '62'.$waNumberDigits;
                        }
                        $statusLabel = match ($detailItem->status) {
                            'processing' => 'sedang diproses',
                            'completed' => 'telah selesai',
                            'cancelled' => 'dibatalkan',
                            default => 'masih menunggu antrean',
                        };
                        $waMessage = $trackingUrl
                            ? "Halo {$detailItem->customer_name}, pesanan Anda di Karya Ide Edi dengan kode {$detailItem->order_code} {$statusLabel}.\n\nAnda dapat melihat detail dan status pesanan melalui link berikut:\n{$trackingUrl}\n\nSilakan simpan link tersebut untuk memantau pesanan Anda.\n\nTerima kasih,\nKarya Ide Edi"
                            : null;
                        $waSendUrl = ($waNumberDigits && $waMessage)
                            ? 'https://wa.me/'.$waNumberDigits.'?text='.urlencode($waMessage)
                            : null;
                    @endphp

                    {{-- Bagian action antrean: TIDAK mengubah desain nota di atas,
                         hanya menambah section baru di bawahnya sesuai status. --}}
                    @if (! $isCompleted && $detailItem->status !== 'cancelled')
                        <div class="rounded-xl border border-admin-border bg-admin-canvas p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-admin-ink-soft">Aksi Antrean</p>

                            @if ($isProcessingFront)
                                <p class="mt-2 flex items-start gap-1.5 text-xs leading-relaxed text-admin-ink-soft">
                                    <i class="fa-solid fa-circle-info mt-0.5 text-admin-accent"></i>
                                    Pesanan ini sedang diproses (antrean #1) sehingga tidak dapat dibatalkan. Tandai selesai kalau pesanan sudah rampung.
                                </p>
                                <button
                                    type="button"
                                    wire:click="completeTransaction({{ $detailItem->id }})"
                                    wire:confirm="Tandai pesanan {{ $detailItem->order_code }} sebagai selesai? Antrean berikutnya akan otomatis naik & mulai diproses."
                                    class="mt-3 w-full rounded-full bg-admin-success px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:opacity-90"
                                >
                                    <i class="fa-solid fa-check mr-1.5"></i> Pesanan Selesai
                                </button>
                            @elseif ($isCancellable)
                                <button
                                    type="button"
                                    wire:click="cancelTransaction({{ $detailItem->id }})"
                                    wire:confirm="Batalkan pesanan {{ $detailItem->order_code }}? Antrean akan dirapatkan otomatis."
                                    class="mt-3 w-full rounded-full border border-admin-danger px-5 py-2.5 text-sm font-semibold text-admin-danger transition-colors duration-200 hover:bg-admin-danger/10"
                                >
                                    <i class="fa-solid fa-ban mr-1.5"></i> Batalkan Pesanan
                                </button>
                            @endif
                        </div>
                    @endif

                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-admin-ink-soft">Tracking Token</p>

                        @if ($trackingUrl)
                            <div class="mt-1 flex flex-col gap-2 sm:flex-row">
                                <a
                                    href="{{ $waSendUrl ?? '#' }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex h-9 shrink-0 items-center justify-center gap-1.5 rounded-lg bg-emerald-500 px-3 text-xs font-semibold text-white transition-colors duration-200 hover:bg-emerald-600 {{ $waSendUrl ? '' : 'pointer-events-none opacity-50' }}"
                                    title="Kirim link tracking ke WhatsApp customer"
                                >
                                    <i class="fa-brands fa-whatsapp"></i> Kirim Tracking
                                </a>
                                <a
                                    href="{{ $trackingUrl }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="min-w-0 flex-1 truncate rounded-lg bg-admin-canvas px-3 py-2 font-mono text-xs text-admin-accent underline decoration-dotted"
                                >
                                    {{ $trackingUrl }}
                                </a>
                            </div>
                            @if (! $waSendUrl)
                                <p class="mt-1.5 text-[11px] text-admin-danger">Nomor WhatsApp tidak valid untuk membuat link kirim.</p>
                            @endif
                        @else
                            <p class="mt-1 break-all rounded-lg bg-admin-canvas px-3 py-2 font-mono text-xs text-admin-ink-soft">Tracking belum tersedia.</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-admin-border px-6 py-4">
                    <button type="button" wire:click="closeDetail" class="rounded-full border border-admin-border px-5 py-2.5 text-sm font-semibold text-admin-ink-soft transition-colors duration-200 hover:bg-admin-cream">
                        Tutup
                    </button>
                </div>
            </div>
            </div>
        </div>
    @endif

    {{-- ================= MODAL: TAMBAH PESANAN ================= --}}
    @if ($showAddModal)
        <div class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
            <div wire:click="closeAddModal" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

            <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-admin-surface shadow-2xl">
            <div class="modal-scroll max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-admin-border bg-admin-surface px-6 py-4">
                    <h3 class="font-display text-lg font-semibold text-admin-ink">Tambah Pesanan</h3>
                    <button type="button" wire:click="closeAddModal" class="flex h-8 w-8 items-center justify-center rounded-lg text-admin-ink-soft transition-colors duration-200 hover:bg-admin-cream">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form wire:submit="save" class="space-y-4 px-6 py-5">

                    @if ($produkList->isEmpty())
                        <p class="rounded-xl border border-dashed border-admin-border bg-admin-canvas px-4 py-3 text-xs leading-relaxed text-admin-ink-soft">
                            <i class="fa-solid fa-circle-info mr-1.5 text-admin-accent"></i>
                            Belum ada produk aktif. Tambahkan/aktifkan produk dulu di menu Produk sebelum membuat pesanan.
                        </p>
                    @endif

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Nama Customer</label>
                        <input
                            type="text"
                            wire:model="customer_name"
                            placeholder="Nama pelanggan..."
                            class="w-full rounded-xl border border-admin-border bg-admin-canvas px-4 py-2.5 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15"
                        >
                        @error('customer_name')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Nomor WhatsApp Customer</label>
                        <input
                            type="text"
                            wire:model="whatsapp"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="15"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 15)"
                            placeholder="08xxxxxxxxxx"
                            class="w-full rounded-xl border border-admin-border bg-admin-canvas px-4 py-2.5 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15"
                        >
                        @error('whatsapp')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
                    </div>

                    <div class="rounded-2xl border border-admin-border bg-admin-canvas p-4">
                        <div class="mb-3 flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-admin-accent/10 text-admin-accent">
                                <i class="fa-solid fa-location-dot text-xs"></i>
                            </span>
                            <div>
                                <p class="text-xs font-semibold text-admin-ink">Alamat Pengiriman</p>
                                <p class="text-[11px] text-admin-ink-soft">Alamat asli penerima untuk kebutuhan pengiriman mebel.</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Nama Penerima</label>
                                <input type="text" wire:model="nama_penerima" placeholder="Nama penerima..." class="w-full rounded-xl border border-admin-border bg-admin-surface px-4 py-2.5 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15">
                                @error('nama_penerima')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Alamat Lengkap</label>
                                <textarea wire:model="alamat_lengkap" rows="3" placeholder="Nama jalan, nomor rumah, RT/RW, patokan..." class="w-full rounded-xl border border-admin-border bg-admin-surface px-4 py-2.5 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15"></textarea>
                                @error('alamat_lengkap')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Kecamatan</label>
                                    <input type="text" wire:model="kecamatan" placeholder="Kecamatan" class="w-full rounded-xl border border-admin-border bg-admin-surface px-4 py-2.5 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15">
                                    @error('kecamatan')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Kota/Kabupaten</label>
                                    <input type="text" wire:model="kota" placeholder="Kota/Kabupaten" class="w-full rounded-xl border border-admin-border bg-admin-surface px-4 py-2.5 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15">
                                    @error('kota')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Provinsi</label>
                                    <input type="text" wire:model="provinsi" placeholder="Provinsi" class="w-full rounded-xl border border-admin-border bg-admin-surface px-4 py-2.5 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15">
                                    @error('provinsi')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Kode Pos</label>
                                    <input type="text" wire:model="kode_pos" inputmode="numeric" maxlength="5" oninput="this.value=this.value.replace(/\D/g,'').slice(0,5)" placeholder="12345" class="w-full rounded-xl border border-admin-border bg-admin-surface px-4 py-2.5 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15">
                                    @error('kode_pos')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-admin-ink">
                            Produk
                            @if ($order_type === 'custom')
                                <span class="font-normal text-admin-ink-soft">(opsional untuk pesanan custom)</span>
                            @endif
                        </label>
                        <div class="relative">
                            <select
                                wire:model.live="product_id"
                                class="w-full cursor-pointer appearance-none rounded-xl border border-admin-border bg-admin-canvas px-4 py-2.5 pr-8 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15"
                            >
                                <option value="">Pilih produk...</option>
                                @foreach ($produkList as $produkItem)
                                    <option value="{{ $produkItem->id }}">{{ $produkItem->nama }}</option>
                                @endforeach
                            </select>
                            <x-icon-arrow direction="chevron-down" size="text-[10px]" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-admin-ink-soft" />
                        </div>
                        @error('product_id')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror

                    </div>

                    {{-- Tetap / Custom: pesanan sesuai harga produk yang sudah ditetapkan,
                         atau custom (ukuran & harga hasil diskusi dengan customer). --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Tipe Pesanan</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold transition-colors duration-200 {{ $order_type === 'tetap' ? 'border-admin-accent bg-admin-accent/10 text-admin-accent' : 'border-admin-border bg-admin-canvas text-admin-ink-soft' }}">
                                <input type="radio" wire:model.live="order_type" value="tetap" class="hidden">
                                <i class="fa-solid fa-tag text-xs"></i> Tetap
                            </label>
                            <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold transition-colors duration-200 {{ $order_type === 'custom' ? 'border-admin-accent bg-admin-accent/10 text-admin-accent' : 'border-admin-border bg-admin-canvas text-admin-ink-soft' }}">
                                <input type="radio" wire:model.live="order_type" value="custom" class="hidden">
                                <i class="fa-solid fa-ruler-combined text-xs"></i> Custom
                            </label>
                        </div>
                        @error('order_type')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
                    </div>

                    {{-- Field khusus pesanan custom: deskripsi & harga hasil diskusi dengan customer. --}}
                    @if ($order_type === 'custom')
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Deskripsi Custom</label>
                            <textarea
                                wire:model="custom_deskripsi"
                                rows="3"
                                placeholder="Mis. bahan kayu jati, warna natural, finishing doff, dll sesuai hasil diskusi dengan customer"
                                class="w-full rounded-xl border border-admin-border bg-admin-canvas px-4 py-2.5 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15"
                            ></textarea>
                            @error('custom_deskripsi')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Harga Hasil Diskusi dengan Customer</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm text-admin-ink-soft">Rp</span>
                                <input type="number" step="1" min="0" wire:model.live="custom_harga_satuan" placeholder="0" class="w-full rounded-xl border border-admin-border bg-admin-canvas py-2.5 pl-10 pr-4 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15">
                            </div>
                            <p class="mt-1 text-[11px] text-admin-ink-soft">Harga satuan per item, bukan total. Menggantikan harga baku produk khusus untuk pesanan ini.</p>
                            @error('custom_harga_satuan')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    {{-- Harga satuan dihitung di sini (bukan di dalam @if selectedProduct)
                         supaya kolom Total di bawah tetap benar untuk pesanan custom
                         yang tidak merujuk produk manapun. --}}
                    @php
                        $hargaSatuanPreview = $order_type === 'custom'
                            ? (float) ($custom_harga_satuan ?? 0)
                            : (float) ($selectedProduct
                                ? (($selectedProduct->harga_diskon && (float) $selectedProduct->harga_diskon > 0)
                                    ? $selectedProduct->harga_diskon
                                    : $selectedProduct->harga)
                                : 0);
                    @endphp

                    {{-- Kartu ringkasan produk terpilih: foto, harga otomatis dari database (admin TIDAK mengetik harga manual) --}}
                    @if ($selectedProduct)
                        @php
                            $thumbnailPreviewUrl = ($selectedProduct->thumbnail && \Illuminate\Support\Facades\Storage::disk('public')->exists($selectedProduct->thumbnail))
                                ? \Illuminate\Support\Facades\Storage::disk('public')->url($selectedProduct->thumbnail)
                                : null;
                        @endphp
                        <div class="flex items-center gap-3 rounded-xl border border-admin-border bg-admin-canvas p-3">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-admin-cream">
                                @if ($thumbnailPreviewUrl)
                                    <img src="{{ $thumbnailPreviewUrl }}" alt="{{ $selectedProduct->nama }}" class="h-full w-full object-contain">
                                @else
                                    <i class="fa-solid fa-couch text-admin-ink-soft/40"></i>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-admin-ink">{{ $selectedProduct->nama }}</p>
                                <p class="text-xs text-admin-ink-soft">
                                    Harga satuan: <span class="font-semibold text-admin-ink">Rp{{ number_format($hargaSatuanPreview, 0, ',', '.') }}</span>
                                </p>
                            </div>
                        </div>

                        @if ($stokHabis)
                            <p class="flex items-center gap-1.5 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-600">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                Produk ini sedang tidak memiliki stok. Pesanan tidak bisa dibuat untuk produk ini.
                            </p>
                        @endif
                    @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Jumlah</label>
                            <input
                                type="number"
                                wire:model.live="quantity"
                                min="1"
                                max="{{ $selectedProduct->stok ?? 9999 }}"
                                placeholder="1"
                                @disabled($stokHabis)
                                class="w-full rounded-xl border border-admin-border bg-admin-canvas px-4 py-2.5 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                            @error('quantity')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Total</label>
                            <div class="flex h-10.5 items-center rounded-xl border border-admin-border bg-admin-cream px-4 text-sm font-semibold text-admin-ink">
                                Rp{{ number_format($hargaSatuanPreview * (int) ($quantity ?? 0), 0, ',', '.') }}
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Catatan Pesanan</label>
                        <textarea
                            wire:model="catatan"
                            rows="3"
                            placeholder="Mis. permintaan warna, ukuran custom, dll (opsional)"
                            class="w-full rounded-xl border border-admin-border bg-admin-canvas px-4 py-2.5 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15"
                        ></textarea>
                        @error('catatan')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-admin-border pt-4">
                        <button type="button" wire:click="closeAddModal" class="rounded-full border border-admin-border px-5 py-2.5 text-sm font-semibold text-admin-ink-soft transition-colors duration-200 hover:bg-admin-cream">
                            Batal
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="save"
                            @disabled($stokHabis)
                            class="inline-flex items-center gap-2 rounded-full bg-admin-panel px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-admin-accent-strong disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <span wire:loading wire:target="save" class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                            Buat Pesanan
                        </button>
                    </div>
                </form>
            </div>
            </div>
        </div>
    @endif
</div>


