# ============================================================
# FIX: Modal "Tambah Pesanan" (admin/pesanan) - hapus kartu
#      "Detail Custom" (Tinggi/Lebar/Panjang), pindahkan
#      "Deskripsi Custom" ke bawah "Tipe Pesanan", field
#      "Harga Hasil Diskusi dengan Customer" TETAP ADA.
#
# Perubahan di resources/views/pages/admin/pesanan.blade.php:
# 1) Textarea "Deskripsi Custom" dipindah dari bawah pilihan
#    Produk, ke bawah blok "Tipe Pesanan" (menggantikan posisi
#    kartu "Detail Custom" yang dihapus).
# 2) Kartu "Detail Custom" (Tinggi/Lebar/Panjang) dihapus total.
# 3) Field "Harga Hasil Diskusi dengan Customer" TETAP ADA, cuma
#    style-nya disesuaikan (tidak lagi di dalam kartu terpisah).
# 4) Validasi (rules()/messages()) untuk custom_tinggi,
#    custom_lebar, custom_panjang DIHAPUS -- karena sudah tidak
#    ada input-nya di form. Kalau ini TIDAK dihapus, tombol Simpan
#    untuk pesanan Custom akan selalu gagal validasi (field itu
#    "required_if" tapi tidak ada tempat mengisinya lagi).
#    Validasi "custom_harga_satuan" (wajib diisi untuk custom)
#    TIDAK diubah -- tetap wajib seperti sebelumnya.
#
# Logika save() TIDAK diubah -- custom_tinggi/lebar/panjang akan
# otomatis tersimpan null untuk pesanan custom (karena memang
# sudah tidak ada input-nya), tidak ada dampak lain.
#
# Cara pakai (dari VS Code integrated terminal, di root project):
#   .\apply-hapus-detail-custom-pesanan.ps1
#
# Setelah itu jalankan: php artisan view:clear
# (murni logic PHP/Blade, tidak ada perubahan Tailwind/JS, jadi
# TIDAK perlu npm run build)
# ============================================================

$ErrorActionPreference = "Stop"

function Replace-ExactlyOnce {
    param(
        [string]$Content,
        [string]$Old,
        [string]$New,
        [string]$Label
    )

    $occurrences = ([regex]::Matches($Content, [regex]::Escape($Old))).Count
    if ($occurrences -eq 0) {
        Write-Host "[ERROR] Bagian '$Label' tidak ditemukan persis di file." -ForegroundColor Red
        Write-Host "        Kemungkinan file ini sudah beda dari yang saya kira -- SAYA BERHENTI, tidak ada yang diubah." -ForegroundColor Red
        exit 1
    }
    if ($occurrences -gt 1) {
        Write-Host "[ERROR] Bagian '$Label' muncul $occurrences kali (harusnya cuma 1)." -ForegroundColor Red
        Write-Host "        SAYA BERHENTI supaya tidak salah ganti bagian yang lain." -ForegroundColor Red
        exit 1
    }

    return $Content.Replace($Old, $New)
}

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Hapus Detail Custom - Modal Tambah Pesanan" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

$targetPath = "resources\views\pages\admin\pesanan.blade.php"

if (-not (Test-Path $targetPath)) {
    Write-Host "[ERROR] File tidak ditemukan: $targetPath" -ForegroundColor Red
    exit 1
}

$content = [System.IO.File]::ReadAllText((Join-Path (Get-Location) $targetPath))

# ------------------------------------------------------------
# 1) Buang "Deskripsi Custom" dari bawah pilihan Produk
# ------------------------------------------------------------
Write-Host "[1/4] Membuang 'Deskripsi Custom' dari bawah pilihan Produk ..." -ForegroundColor Yellow

$old1 = @'

                        {{-- Khusus pesanan custom: produk boleh dikosongkan, jadi admin
                             menjelaskan sendiri bahan/warna/finishing/dll yang sudah
                             didiskusikan dengan customer di sini. --}}
                        @if ($order_type === 'custom')
                            <div class="mt-3">
                                <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Deskripsi Custom</label>
                                <textarea
                                    wire:model="custom_deskripsi"
                                    rows="3"
                                    placeholder="Mis. bahan kayu jati, warna natural, finishing doff, dll sesuai hasil diskusi dengan customer"
                                    class="w-full rounded-xl border border-admin-border bg-admin-canvas px-4 py-2.5 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15"
                                ></textarea>
                                @error('custom_deskripsi')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
                            </div>
                        @endif
                    </div>
'@

$new1 = @'

                    </div>
'@

$content = Replace-ExactlyOnce -Content $content -Old $old1 -New $new1 -Label "Deskripsi Custom (di bawah Produk)"

# ------------------------------------------------------------
# 2) Ganti kartu "Detail Custom" -> Deskripsi Custom (pindahan) + Harga (tetap)
# ------------------------------------------------------------
Write-Host "[2/4] Mengganti kartu 'Detail Custom' dengan Deskripsi Custom + Harga ..." -ForegroundColor Yellow

$old2 = @'
                    {{-- Field khusus pesanan custom: ukuran mebel + harga hasil diskusi dengan customer. --}}
                    @if ($order_type === 'custom')
                        <div class="rounded-2xl border border-admin-border bg-admin-canvas p-4">
                            <div class="mb-3 flex items-center gap-2">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-admin-accent/10 text-admin-accent">
                                    <i class="fa-solid fa-ruler-combined text-xs"></i>
                                </span>
                                <div>
                                    <p class="text-xs font-semibold text-admin-ink">Detail Custom</p>
                                    <p class="text-[11px] text-admin-ink-soft">Ukuran mebel dan harga hasil diskusi dengan customer.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Tinggi (cm)</label>
                                    <input type="number" step="0.01" min="0" wire:model="custom_tinggi" placeholder="0" class="w-full rounded-xl border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15">
                                    @error('custom_tinggi')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Lebar (cm)</label>
                                    <input type="number" step="0.01" min="0" wire:model="custom_lebar" placeholder="0" class="w-full rounded-xl border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15">
                                    @error('custom_lebar')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Panjang (cm)</label>
                                    <input type="number" step="0.01" min="0" wire:model="custom_panjang" placeholder="0" class="w-full rounded-xl border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15">
                                    @error('custom_panjang')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Harga Hasil Diskusi dengan Customer</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm text-admin-ink-soft">Rp</span>
                                    <input type="number" step="1" min="0" wire:model.live="custom_harga_satuan" placeholder="0" class="w-full rounded-xl border border-admin-border bg-admin-surface py-2.5 pl-10 pr-4 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15">
                                </div>
                                <p class="mt-1 text-[11px] text-admin-ink-soft">Harga satuan per item, bukan total. Menggantikan harga baku produk khusus untuk pesanan ini.</p>
                                @error('custom_harga_satuan')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    @endif
'@

$new2 = @'
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
'@

$content = Replace-ExactlyOnce -Content $content -Old $old2 -New $new2 -Label "Kartu Detail Custom"

# ------------------------------------------------------------
# 3) rules() -- buang required_if utk custom_tinggi/lebar/panjang
# ------------------------------------------------------------
Write-Host "[3/4] Melonggarkan validasi (Tinggi/Lebar/Panjang sudah tidak punya input) ..." -ForegroundColor Yellow

$old3 = @'
            'custom_tinggi' => ['required_if:order_type,custom', 'nullable', 'numeric', 'min:0.01', 'max:9999.99'],
            'custom_lebar' => ['required_if:order_type,custom', 'nullable', 'numeric', 'min:0.01', 'max:9999.99'],
            'custom_panjang' => ['required_if:order_type,custom', 'nullable', 'numeric', 'min:0.01', 'max:9999.99'],
            'custom_harga_satuan' => ['required_if:order_type,custom', 'nullable', 'numeric', 'min:1'],
'@

$new3 = @'
            'custom_harga_satuan' => ['required_if:order_type,custom', 'nullable', 'numeric', 'min:1'],
'@

$content = Replace-ExactlyOnce -Content $content -Old $old3 -New $new3 -Label "rules() Tinggi/Lebar/Panjang"

# ------------------------------------------------------------
# 4) messages() -- buang pesan error utk custom_tinggi/lebar/panjang
# ------------------------------------------------------------
Write-Host "[4/4] Membuang pesan error Tinggi/Lebar/Panjang yang sudah tidak dipakai ..." -ForegroundColor Yellow

$old4 = @'
            'custom_tinggi.required_if' => 'Tinggi wajib diisi untuk pesanan custom.',
            'custom_tinggi.numeric' => 'Tinggi harus berupa angka.',
            'custom_lebar.required_if' => 'Lebar wajib diisi untuk pesanan custom.',
            'custom_lebar.numeric' => 'Lebar harus berupa angka.',
            'custom_panjang.required_if' => 'Panjang wajib diisi untuk pesanan custom.',
            'custom_panjang.numeric' => 'Panjang harus berupa angka.',
            'custom_harga_satuan.required_if' => 'Harga hasil diskusi dengan customer wajib diisi untuk pesanan custom.',
'@

$new4 = @'
            'custom_harga_satuan.required_if' => 'Harga hasil diskusi dengan customer wajib diisi untuk pesanan custom.',
'@

$content = Replace-ExactlyOnce -Content $content -Old $old4 -New $new4 -Label "messages() Tinggi/Lebar/Panjang"

# ------------------------------------------------------------
# Backup lalu tulis file (sekali saja, setelah semua replace lolos)
# ------------------------------------------------------------
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupPath = "$targetPath.bak-before-hapus-detail-custom-$stamp"
Copy-Item -Path $targetPath -Destination $backupPath -Force
Write-Host "  Backup dibuat: $backupPath" -ForegroundColor DarkGray

$encoding = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText((Join-Path (Get-Location) $targetPath), $content, $encoding)

Write-Host ""
Write-Host "Selesai." -ForegroundColor Green
Write-Host "Diubah: $targetPath"
Write-Host ""
Write-Host "Jalankan: php artisan view:clear" -ForegroundColor Cyan
