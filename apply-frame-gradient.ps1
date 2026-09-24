# apply-frame-gradient.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
#   .\apply-frame-gradient.ps1
#
# Tujuan: "Warna Frame (Latar Section)" di Admin > Edit Web jadi POLOS (satu
# warna solid). Gradasi hanya kalau admin menyalakannya lewat tombol
# "Gradasi" tersendiri di tiap section, dengan warna awal, warna akhir,
# warna tengah (opsional), dan arah/sudut yang bisa diatur bebas.
#
# Section yang kena: Sejak Berdiri, Produk Unggulan, Kategori Produk,
# Testimoni Pelanggan, Lokasi. Header Beranda TIDAK disentuh.
#
# Yang berubah:
#  - File BARU : app/Support/FrameBackground.php
#                app/Support/HasFrameGradients.php
#                resources/views/partials/admin/frame-gradient.blade.php
#  - File DIUBAH (hanya bagian warna frame): edit-web.blade.php dan 5 partial
#    frontend (mission, products, categories, testimonials, lokasi).
#  - Di 3 section (Produk Unggulan, Kategori, Testimoni) lapisan "glow" bulat
#    dan tekstur tipis di atas latar dihapus, karena membuat latar tidak polos.
#
# Aman: semua perubahan dicek "tepat 1 kali cocok" dulu di memori. Kalau ada
# yang tidak cocok, script berhenti dan TIDAK ADA file yang ditulis. Backup
# file yang diubah disimpan di satu folder: .backup-frame-gradient-<waktu>/

$ErrorActionPreference = "Stop"

$root = (Get-Location).Path
if (-not (Test-Path (Join-Path $root "artisan"))) { throw "Jalankan script ini dari root project (folder yang ada file artisan-nya)." }

function Read-Lf([string]$RelPath) {
    $full = Join-Path $root $RelPath
    if (-not (Test-Path $full)) { throw "Tidak ketemu: $RelPath" }
    $raw = [System.IO.File]::ReadAllText($full)
    return @{ Text = ($raw -replace "`r`n", "`n"); CrLf = $raw.Contains("`r`n") }
}

function Write-Lf([string]$RelPath, [string]$Text, [bool]$CrLf) {
    $full = Join-Path $root $RelPath
    if ($CrLf) { $Text = $Text -replace "`n", "`r`n" }
    $dir = Split-Path $full -Parent
    if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
    [System.IO.File]::WriteAllText($full, $Text, (New-Object System.Text.UTF8Encoding($false)))
}

function Replace-ExactlyOnce([string]$Text, [string]$Old, [string]$New, [string]$Label) {
    $Old = $Old -replace "`r`n", "`n"
    $New = $New -replace "`r`n", "`n"
    $first = $Text.IndexOf($Old, [System.StringComparison]::Ordinal)
    if ($first -lt 0) { throw "[$Label] tidak cocok persis (mungkin sudah pernah diubah). Tidak ada file yang ditulis." }
    $second = $Text.IndexOf($Old, $first + $Old.Length, [System.StringComparison]::Ordinal)
    if ($second -ge 0) { throw "[$Label] cocok lebih dari 1 kali. Tidak ada file yang ditulis." }
    return $Text.Substring(0, $first) + $New + $Text.Substring($first + $Old.Length)
}

# ---------- 0. Pastikan belum pernah diterapkan ----------
foreach ($p in @("app/Support/FrameBackground.php", "app/Support/HasFrameGradients.php", "resources/views/partials/admin/frame-gradient.blade.php")) {
    if (Test-Path (Join-Path $root $p)) { throw "Sudah ada: $p (script ini sepertinya sudah pernah dijalankan). Tidak ada yang diubah." }
}

$pending = @()   # daftar file yang akan ditulis (setelah SEMUA pengecekan lolos)

# ---------- resources/views/partials/frontend/mission.blade.php ----------
$f1 = Read-Lf "resources/views/partials/frontend/mission.blade.php"
$f1Text = $f1.Text
$f1Text = Replace-ExactlyOnce $f1Text @'
    $missionBgColor = $missionSection['bg_color'] ?: '#FEEDD8';

'@ @'
    // Latar: warna POLOS secara bawaan; gradasi hanya kalau admin menyalakannya di
    // Edit Web > Sejak Berdiri > Gradasi (lihat App\Support\FrameBackground).
    // $missionBgColor = warna dasar (warna polos, atau rata-rata gradasi) untuk
    // perhitungan kontras teks di bawah.
    $missionFrame = \App\Support\FrameBackground::resolve($missionSection['bg_color'] ?? null, $missionSection['bg_gradient'] ?? null, '#FEEDD8');
    $missionBgColor = $missionFrame['base'];

'@ "mission: warna dasar"
$f1Text = Replace-ExactlyOnce $f1Text @'
style="background: linear-gradient(135deg, color-mix(in oklab, {{ $missionBgColor }} 100%, white 10%) 0%, {{ $missionBgColor }} 55%, color-mix(in oklab, {{ $missionBgColor }} 100%, black 14%) 100%);"
'@ @'
style="background: {{ $missionFrame['css'] }};"
'@ "mission: style section"
$pending += @{ Path = "resources/views/partials/frontend/mission.blade.php"; Text = $f1Text; CrLf = $f1.CrLf; Existing = $true }

# ---------- resources/views/partials/frontend/products.blade.php ----------
$f2 = Read-Lf "resources/views/partials/frontend/products.blade.php"
$f2Text = $f2.Text
$f2Text = Replace-ExactlyOnce $f2Text @'
    $produkUnggulanBgColor = $produkUnggulanSection['bg_color'] ?: '#FFFFFF';

'@ @'
    // Latar: warna POLOS secara bawaan; gradasi hanya kalau admin menyalakannya di
    // Edit Web > Produk Unggulan > Gradasi (lihat App\Support\FrameBackground).
    $produkUnggulanFrame = \App\Support\FrameBackground::resolve($produkUnggulanSection['bg_color'] ?? null, $produkUnggulanSection['bg_gradient'] ?? null, '#FFFFFF');
    $produkUnggulanBgColor = $produkUnggulanFrame['base'];

'@ "produk: warna dasar"
$f2Text = Replace-ExactlyOnce $f2Text @'
style="background: linear-gradient(135deg, color-mix(in oklab, {{ $produkUnggulanBgColor }} 100%, white 10%) 0%, {{ $produkUnggulanBgColor }} 55%, color-mix(in oklab, {{ $produkUnggulanBgColor }} 100%, black 14%) 100%);"
'@ @'
style="background: {{ $produkUnggulanFrame['css'] }};"
'@ "produk: style section"
$f2Text = Replace-ExactlyOnce $f2Text @'
    {{--
        Lapisan glow & tekstur tipis supaya latar tidak terasa flat walau
        admin belum ganti warnanya -- keduanya diturunkan dari
        $produkUnggulanBgColor (bukan warna baru yang di-hardcode), jadi
        otomatis ikut menyesuaikan tiap kali admin ganti warna di Edit Web.
        Pola sama persis dengan partials/frontend/categories.blade.php.
    --}}
    <div
        class="pointer-events-none absolute -left-24 -top-32 h-110 w-110 rounded-full blur-3xl"
        style="background: color-mix(in oklab, {{ $produkUnggulanBgColor }} 100%, white 60%); opacity: 0.4;"
    ></div>
    <div
        class="pointer-events-none absolute inset-0 opacity-[0.05] mix-blend-overlay"
        style="background-image: url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22140%22 height=%22140%22%3E%3Cfilter id=%22n%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.85%22 numOctaves=%222%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22140%22 height=%22140%22 filter=%22url(%23n)%22/%3E%3C/svg%3E');"
    ></div>

'@ @'
    {{-- Latar polos: tanpa lapisan glow/tekstur supaya warna pilihan admin tampil apa adanya. --}}

'@ "produk: hapus glow & tekstur"
$pending += @{ Path = "resources/views/partials/frontend/products.blade.php"; Text = $f2Text; CrLf = $f2.CrLf; Existing = $true }

# ---------- resources/views/partials/frontend/categories.blade.php ----------
$f3 = Read-Lf "resources/views/partials/frontend/categories.blade.php"
$f3Text = $f3.Text
$f3Text = Replace-ExactlyOnce $f3Text @'
    $kategoriBgColor = $kategoriSection['bg_color'] ?: '#FEEDD8';

'@ @'
    // Latar: warna POLOS secara bawaan; gradasi hanya kalau admin menyalakannya di
    // Edit Web > Kategori Produk > Gradasi (lihat App\Support\FrameBackground).
    $kategoriFrame = \App\Support\FrameBackground::resolve($kategoriSection['bg_color'] ?? null, $kategoriSection['bg_gradient'] ?? null, '#FEEDD8');
    $kategoriBgColor = $kategoriFrame['base'];

'@ "kategori: warna dasar"
$f3Text = Replace-ExactlyOnce $f3Text @'
style="background: linear-gradient(135deg, color-mix(in oklab, {{ $kategoriBgColor }} 100%, white 10%) 0%, {{ $kategoriBgColor }} 55%, color-mix(in oklab, {{ $kategoriBgColor }} 100%, black 14%) 100%);"
'@ @'
style="background: {{ $kategoriFrame['css'] }};"
'@ "kategori: style section"
$f3Text = Replace-ExactlyOnce $f3Text @'
        {{--
            Lapisan glow & tekstur tipis supaya latar tidak terasa flat
            walau admin belum ganti warnanya -- keduanya diturunkan dari
            $kategoriBgColor (bukan warna baru yang di-hardcode), jadi
            otomatis ikut menyesuaikan setiap kali admin ganti warna di
            Edit Web.
        --}}
        <div
            class="pointer-events-none absolute -right-24 -top-32 h-105 w-105 rounded-full blur-3xl"
            style="background: color-mix(in oklab, {{ $kategoriBgColor }} 100%, white 60%); opacity: 0.45;"
        ></div>
        <div
            class="pointer-events-none absolute inset-0 opacity-[0.05] mix-blend-overlay"
            style="background-image: url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22140%22 height=%22140%22%3E%3Cfilter id=%22n%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.85%22 numOctaves=%222%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22140%22 height=%22140%22 filter=%22url(%23n)%22/%3E%3C/svg%3E');"
        ></div>

'@ @'
        {{-- Latar polos: tanpa lapisan glow/tekstur supaya warna pilihan admin tampil apa adanya. --}}

'@ "kategori: hapus glow & tekstur"
$pending += @{ Path = "resources/views/partials/frontend/categories.blade.php"; Text = $f3Text; CrLf = $f3.CrLf; Existing = $true }

# ---------- resources/views/partials/frontend/testimonials.blade.php ----------
$f4 = Read-Lf "resources/views/partials/frontend/testimonials.blade.php"
$f4Text = $f4.Text
$f4Text = Replace-ExactlyOnce $f4Text @'
    $testimoniBgColor = $testimoniSection['bg_color'] ?: '#FAF8F4';

'@ @'
    // Latar: warna POLOS secara bawaan; gradasi hanya kalau admin menyalakannya di
    // Edit Web > Testimoni Pelanggan > Gradasi (lihat App\Support\FrameBackground).
    $testimoniFrame = \App\Support\FrameBackground::resolve($testimoniSection['bg_color'] ?? null, $testimoniSection['bg_gradient'] ?? null, '#FAF8F4');
    $testimoniBgColor = $testimoniFrame['base'];

'@ "testimoni: warna dasar"
$f4Text = Replace-ExactlyOnce $f4Text @'
style="background: linear-gradient(135deg, color-mix(in oklab, {{ $testimoniBgColor }} 100%, white 10%) 0%, {{ $testimoniBgColor }} 55%, color-mix(in oklab, {{ $testimoniBgColor }} 100%, black 14%) 100%);"
'@ @'
style="background: {{ $testimoniFrame['css'] }};"
'@ "testimoni: style section"
$f4Text = Replace-ExactlyOnce $f4Text @'
    {{-- Glow & tekstur tipis, pola sama dengan section Produk Unggulan & Kategori Produk. --}}
    <div
        class="pointer-events-none absolute -right-24 -top-32 h-105 w-105 rounded-full blur-3xl"
        style="background: color-mix(in oklab, {{ $testimoniBgColor }} 100%, white 60%); opacity: 0.4;"
    ></div>
    <div
        class="pointer-events-none absolute inset-0 opacity-[0.05] mix-blend-overlay"
        style="background-image: url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22140%22 height=%22140%22%3E%3Cfilter id=%22n%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.85%22 numOctaves=%222%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22140%22 height=%22140%22 filter=%22url(%23n)%22/%3E%3C/svg%3E');"
    ></div>

'@ @'
    {{-- Latar polos: tanpa lapisan glow/tekstur supaya warna pilihan admin tampil apa adanya. --}}

'@ "testimoni: hapus glow & tekstur"
$pending += @{ Path = "resources/views/partials/frontend/testimonials.blade.php"; Text = $f4Text; CrLf = $f4.CrLf; Existing = $true }

# ---------- resources/views/partials/frontend/lokasi.blade.php ----------
$f5 = Read-Lf "resources/views/partials/frontend/lokasi.blade.php"
$f5Text = $f5.Text
$f5Text = Replace-ExactlyOnce $f5Text @'
    $lokasiBgColor = $lokasiSection['bg_color'] ?: '#FFFFFF';
    $lokasiPakaiWarnaKhusus = strtoupper(ltrim($lokasiBgColor, '#')) !== 'FFFFFF';

'@ @'
    // Latar POLOS secara bawaan; gradasi hanya kalau admin menyalakannya di Edit Web >
    // Lokasi > Gradasi (lihat App\Support\FrameBackground).
    $lokasiFrame = \App\Support\FrameBackground::resolve($lokasiSection['bg_color'] ?? null, $lokasiSection['bg_gradient'] ?? null, '#FFFFFF');
    $lokasiBgColor = $lokasiFrame['base'];
    $lokasiPakaiWarnaKhusus = $lokasiFrame['is_gradient'] || strtoupper(ltrim($lokasiBgColor, '#')) !== 'FFFFFF';

'@ "lokasi: warna dasar"
$f5Text = Replace-ExactlyOnce $f5Text @'
style="background: linear-gradient(135deg, color-mix(in oklab, {{ $lokasiBgColor }} 100%, white 10%) 0%, {{ $lokasiBgColor }} 55%, color-mix(in oklab, {{ $lokasiBgColor }} 100%, black 14%) 100%);"
'@ @'
style="background: {{ $lokasiFrame['css'] }};"
'@ "lokasi: style section"
$pending += @{ Path = "resources/views/partials/frontend/lokasi.blade.php"; Text = $f5Text; CrLf = $f5.CrLf; Existing = $true }

# ---------- resources/views/pages/admin/edit-web.blade.php ----------
$f6 = Read-Lf "resources/views/pages/admin/edit-web.blade.php"
$f6Text = $f6.Text
$f6Text = Replace-ExactlyOnce $f6Text @'
    use WithFileUploads;

'@ @'
    use WithFileUploads;

    // State & aksi gradasi "Warna Frame" (tombol Gradasi di tiap section) --
    // lihat app/Support/HasFrameGradients.php.
    use \App\Support\HasFrameGradients;

'@ "trait di class"
$f6Text = Replace-ExactlyOnce $f6Text @'
        $this->dokumentasiVideoPathLama = $dokumentasiData['video_path'] ?? null;

'@ @'
        $this->dokumentasiVideoPathLama = $dokumentasiData['video_path'] ?? null;

        // Gradasi "Warna Frame" tiap section (kalau belum pernah diatur -> mati, frame polos).
        $this->loadFrameGradient('mission', $missionData['bg_gradient'] ?? null);
        $this->loadFrameGradient('produkUnggulan', $produkUnggulanData['bg_gradient'] ?? null);
        $this->loadFrameGradient('kategori', $kategoriData['bg_gradient'] ?? null);
        $this->loadFrameGradient('testimoni', $testimoniData['bg_gradient'] ?? null);
        $this->loadFrameGradient('lokasi', $lokasiData['bg_gradient'] ?? null);

'@ "mount: muat gradasi"
$f6Text = Replace-ExactlyOnce $f6Text @'
            'missionBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],

'@ @'
            'missionBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ...$this->frameGradientRules('mission'),

'@ "mission: aturan validasi"
$f6Text = Replace-ExactlyOnce $f6Text @'
                'bg_color' => $this->missionUseCustomBg ? $validated['missionBgColor'] : null,

'@ @'
                'bg_color' => $this->missionUseCustomBg ? $validated['missionBgColor'] : null,
                'bg_gradient' => $this->frameGradientPayload('mission'),

'@ "mission: simpan gradasi"
$f6Text = Replace-ExactlyOnce $f6Text @'
            'produkUnggulanBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],

'@ @'
            'produkUnggulanBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ...$this->frameGradientRules('produkUnggulan'),

'@ "produkUnggulan: aturan validasi"
$f6Text = Replace-ExactlyOnce $f6Text @'
                'bg_color' => $this->produkUnggulanUseCustomBg ? $validated['produkUnggulanBgColor'] : null,

'@ @'
                'bg_color' => $this->produkUnggulanUseCustomBg ? $validated['produkUnggulanBgColor'] : null,
                'bg_gradient' => $this->frameGradientPayload('produkUnggulan'),

'@ "produkUnggulan: simpan gradasi"
$f6Text = Replace-ExactlyOnce $f6Text @'
            'kategoriBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],

'@ @'
            'kategoriBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ...$this->frameGradientRules('kategori'),

'@ "kategori: aturan validasi"
$f6Text = Replace-ExactlyOnce $f6Text @'
                'bg_color' => $this->kategoriUseCustomBg ? $validated['kategoriBgColor'] : null,

'@ @'
                'bg_color' => $this->kategoriUseCustomBg ? $validated['kategoriBgColor'] : null,
                'bg_gradient' => $this->frameGradientPayload('kategori'),

'@ "kategori: simpan gradasi"
$f6Text = Replace-ExactlyOnce $f6Text @'
            'testimoniBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],

'@ @'
            'testimoniBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ...$this->frameGradientRules('testimoni'),

'@ "testimoni: aturan validasi"
$f6Text = Replace-ExactlyOnce $f6Text @'
                'bg_color' => $this->testimoniUseCustomBg ? $validated['testimoniBgColor'] : null,

'@ @'
                'bg_color' => $this->testimoniUseCustomBg ? $validated['testimoniBgColor'] : null,
                'bg_gradient' => $this->frameGradientPayload('testimoni'),

'@ "testimoni: simpan gradasi"
$f6Text = Replace-ExactlyOnce $f6Text @'
            'lokasiDescription' => ['required', 'string', 'max:500'],

'@ @'
            'lokasiDescription' => ['required', 'string', 'max:500'],
            ...$this->frameGradientRules('lokasi'),

'@ "lokasi: aturan validasi"
$f6Text = Replace-ExactlyOnce $f6Text @'
                'bg_color' => $this->lokasiUseCustomBg ? $this->lokasiBgColor : null,

'@ @'
                'bg_color' => $this->lokasiUseCustomBg ? $this->lokasiBgColor : null,
                'bg_gradient' => $this->frameGradientPayload('lokasi'),

'@ "lokasi: simpan gradasi"
$f6Text = Replace-ExactlyOnce $f6Text @'
mengikuti warna latar yang dipilih, supaya tetap kebaca.
                        </p>
                    </div>

'@ @'
mengikuti warna latar yang dipilih, supaya tetap kebaca.
                        </p>
                    </div>

                    @include('partials.admin.frame-gradient', [
                        'key' => 'mission',
                        'gradient' => $missionGradient,
                    ])

'@ "mission: editor gradasi di UI"
$f6Text = Replace-ExactlyOnce $f6Text @'
berwarna krem terang seperti biasa, tidak ikut berubah.
                        </p>
                    </div>

'@ @'
berwarna krem terang seperti biasa, tidak ikut berubah.
                        </p>
                    </div>

                    @include('partials.admin.frame-gradient', [
                        'key' => 'produkUnggulan',
                        'gradient' => $produkUnggulanGradient,
                    ])

'@ "produkUnggulan: editor gradasi di UI"
$f6Text = Replace-ExactlyOnce $f6Text @'
warna latar (tidak ada kartu putih di belakangnya seperti section Produk Unggulan).
                        </p>
                    </div>

'@ @'
warna latar (tidak ada kartu putih di belakangnya seperti section Produk Unggulan).
                        </p>
                    </div>

                    @include('partials.admin.frame-gradient', [
                        'key' => 'kategori',
                        'gradient' => $kategoriGradient,
                    ])

'@ "kategori: editor gradasi di UI"
$f6Text = Replace-ExactlyOnce $f6Text @'
Kalau tidak dicentang, section ini ikut warna krem kanvas bawaan.
                        </p>
                    </div>

'@ @'
Kalau tidak dicentang, section ini ikut warna krem kanvas bawaan.
                        </p>
                    </div>

                    @include('partials.admin.frame-gradient', [
                        'key' => 'testimoni',
                        'gradient' => $testimoniGradient,
                    ])

'@ "testimoni: editor gradasi di UI"
$f6Text = Replace-ExactlyOnce $f6Text @'
dan alamat otomatis menyesuaikan terang/gelap mengikuti warna latar supaya tetap kebaca.
                        </p>
                    </div>

'@ @'
dan alamat otomatis menyesuaikan terang/gelap mengikuti warna latar supaya tetap kebaca.
                        </p>
                    </div>

                    @include('partials.admin.frame-gradient', [
                        'key' => 'lokasi',
                        'gradient' => $lokasiGradient,
                    ])

'@ "lokasi: editor gradasi di UI"
$pending += @{ Path = "resources/views/pages/admin/edit-web.blade.php"; Text = $f6Text; CrLf = $f6.CrLf; Existing = $true }

# ---------- File baru ----------
$pending += @{ Path = "app/Support/FrameBackground.php"; Existing = $false; CrLf = $false; Text = @'
<?php

namespace App\Support;

/**
 * Latar ("frame") section Beranda yang diatur admin lewat Edit Web.
 *
 * - Bawaan: warna POLOS (satu warna solid), tanpa gradasi otomatis.
 * - Gradasi hanya dipakai kalau admin menyalakannya lewat tombol "Gradasi"
 *   di Edit Web -- lengkap dengan warna awal, warna akhir, warna tengah
 *   (opsional), dan arah/sudutnya. Selama gradasi menyala, ia menggantikan
 *   warna polos; begitu dimatikan, frame kembali ke warna polos.
 *
 * Bentuk data gradasi yang disimpan di home_sections.data['bg_gradient']:
 * ['enabled' => bool, 'from' => '#RRGGBB', 'use_mid' => bool,
 *  'mid' => '#RRGGBB', 'to' => '#RRGGBB', 'angle' => 0..360]
 */
final class FrameBackground
{
    /**
     * Gradasi awal yang tampil saat admin pertama kali menyalakannya.
     *
     * @var array{enabled: bool, from: string, use_mid: bool, mid: string, to: string, angle: int}
     */
    public const DEFAULT_GRADIENT = [
        'enabled' => false,
        'from' => '#FEEDD8',
        'use_mid' => false,
        'mid' => '#F1D3AE',
        'to' => '#D9B58A',
        'angle' => 135,
    ];

    /**
     * Gradasi rekomendasi -- sekali klik langsung terisi, lalu tetap bisa
     * diubah warna & arahnya. 'mid' null = gradasi dua warna.
     *
     * @var array<int, array{label: string, from: string, mid: string|null, to: string, angle: int}>
     */
    public const PRESETS = [
        ['label' => 'Senja Hangat', 'from' => '#FEEDD8', 'mid' => null, 'to' => '#E3B98A', 'angle' => 135],
        ['label' => 'Pasir ke Terracotta', 'from' => '#EFE3D0', 'mid' => null, 'to' => '#C97B5A', 'angle' => 135],
        ['label' => 'Sage Lembut', 'from' => '#F4F7EC', 'mid' => null, 'to' => '#B9C8A0', 'angle' => 160],
        ['label' => 'Fajar', 'from' => '#FFF4E6', 'mid' => '#FBD9B5', 'to' => '#E9A57F', 'angle' => 120],
        ['label' => 'Krem ke Putih', 'from' => '#F9F7F2', 'mid' => null, 'to' => '#FFFFFF', 'angle' => 180],
        ['label' => 'Rose Kayu', 'from' => '#F3D9D0', 'mid' => null, 'to' => '#C48B7A', 'angle' => 145],
        ['label' => 'Kayu Tua', 'from' => '#5A3D2B', 'mid' => null, 'to' => '#2A1B12', 'angle' => 150],
        ['label' => 'Malam Navy', 'from' => '#2F4257', 'mid' => null, 'to' => '#151D27', 'angle' => 160],
    ];

    /**
     * Rapikan sebuah nilai jadi hex '#RRGGBB' huruf besar, atau null kalau tidak valid.
     */
    public static function hex(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? strtoupper($value) : null;
    }

    /**
     * Ubah data gradasi apa pun (null, sebagian, atau rusak) jadi bentuk
     * lengkap yang selalu valid -- nilai yang hilang/tidak valid diisi
     * dari DEFAULT_GRADIENT.
     *
     * @return array{enabled: bool, from: string, use_mid: bool, mid: string, to: string, angle: int}
     */
    public static function normalize(mixed $gradient): array
    {
        $gradient = is_array($gradient) ? $gradient : [];
        $default = self::DEFAULT_GRADIENT;

        $angle = $gradient['angle'] ?? $default['angle'];
        $angle = is_numeric($angle) ? (int) round((float) $angle) : $default['angle'];

        return [
            'enabled' => filter_var($gradient['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'from' => self::hex($gradient['from'] ?? null) ?? $default['from'],
            'use_mid' => filter_var($gradient['use_mid'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'mid' => self::hex($gradient['mid'] ?? null) ?? $default['mid'],
            'to' => self::hex($gradient['to'] ?? null) ?? $default['to'],
            'angle' => max(0, min(360, $angle)),
        ];
    }

    /**
     * Nilai CSS `linear-gradient(...)` dari data gradasi (dipakai juga untuk pratinjau di admin).
     */
    public static function cssGradient(mixed $gradient): string
    {
        $g = self::normalize($gradient);

        $stops = $g['use_mid']
            ? "{$g['from']} 0%, {$g['mid']} 50%, {$g['to']} 100%"
            : "{$g['from']} 0%, {$g['to']} 100%";

        return "linear-gradient({$g['angle']}deg, {$stops})";
    }

    /**
     * Rata-rata warna sebuah daftar hex -- dipakai sebagai "warna dasar"
     * gradasi untuk menghitung kontras teks & warna turunan di section.
     *
     * @param  array<int, string>  $hexes
     */
    public static function average(array $hexes): string
    {
        $r = $g = $b = 0;

        foreach ($hexes as $hex) {
            $hex = ltrim($hex, '#');
            $r += hexdec(substr($hex, 0, 2));
            $g += hexdec(substr($hex, 2, 2));
            $b += hexdec(substr($hex, 4, 2));
        }

        $n = max(1, count($hexes));

        return sprintf('#%02X%02X%02X', (int) round($r / $n), (int) round($g / $n), (int) round($b / $n));
    }

    /**
     * Tentukan latar akhir sebuah section Beranda.
     *
     * - `css`  : nilai untuk properti `background` (warna polos atau linear-gradient).
     * - `base` : satu warna hex representatif (warna polosnya, atau rata-rata
     *            gradasi) untuk perhitungan kontras teks di section.
     * - `is_gradient` : true kalau gradasi sedang menyala.
     *
     * @param  string|null  $solid  Warna polos pilihan admin (null = pakai $fallback).
     * @param  mixed  $gradient  Data 'bg_gradient' dari home_sections (boleh null).
     * @param  string  $fallback  Warna polos bawaan section.
     * @return array{css: string, base: string, is_gradient: bool}
     */
    public static function resolve(?string $solid, mixed $gradient, string $fallback): array
    {
        $g = self::normalize($gradient);

        if (is_array($gradient) && $g['enabled']) {
            $stops = $g['use_mid'] ? [$g['from'], $g['mid'], $g['to']] : [$g['from'], $g['to']];

            return [
                'css' => self::cssGradient($g),
                'base' => self::average($stops),
                'is_gradient' => true,
            ];
        }

        $color = self::hex($solid) ?? self::hex($fallback) ?? '#FFFFFF';

        return ['css' => $color, 'base' => $color, 'is_gradient' => false];
    }

    /**
     * Apakah gradasi saat ini sama persis dengan salah satu preset rekomendasi
     * (untuk menandai preset yang sedang dipakai di admin).
     *
     * @param  array{label: string, from: string, mid: string|null, to: string, angle: int}  $preset
     */
    public static function matchesPreset(mixed $gradient, array $preset): bool
    {
        $g = self::normalize($gradient);

        return $g['from'] === $preset['from']
            && $g['to'] === $preset['to']
            && $g['angle'] === $preset['angle']
            && $g['use_mid'] === ($preset['mid'] !== null)
            && ($preset['mid'] === null || $g['mid'] === $preset['mid']);
    }
}

'@ }

$pending += @{ Path = "app/Support/HasFrameGradients.php"; Existing = $false; CrLf = $false; Text = @'
<?php

namespace App\Support;

/**
 * State & aksi gradasi "Warna Frame" untuk halaman Admin > Edit Web
 * (pages/admin/edit-web.blade.php). Satu array per section, bentuknya sama
 * dengan App\Support\FrameBackground::DEFAULT_GRADIENT.
 *
 * Sisi tampilan beranda memakai App\Support\FrameBackground::resolve().
 */
trait HasFrameGradients
{
    public array $missionGradient = FrameBackground::DEFAULT_GRADIENT;

    public array $produkUnggulanGradient = FrameBackground::DEFAULT_GRADIENT;

    public array $kategoriGradient = FrameBackground::DEFAULT_GRADIENT;

    public array $testimoniGradient = FrameBackground::DEFAULT_GRADIENT;

    public array $lokasiGradient = FrameBackground::DEFAULT_GRADIENT;

    /**
     * Isi state gradasi sebuah section dari data yang tersimpan (dipanggil di mount()).
     */
    protected function loadFrameGradient(string $section, mixed $stored): void
    {
        $this->{$this->frameGradientProperty($section)} = FrameBackground::normalize($stored);
    }

    /**
     * Tombol nyala/mati gradasi.
     */
    public function toggleFrameGradient(string $section): void
    {
        $property = $this->frameGradientProperty($section);

        $gradient = FrameBackground::normalize($this->{$property});
        $gradient['enabled'] = ! $gradient['enabled'];

        $this->{$property} = $gradient;
    }

    /**
     * Klik salah satu gradasi rekomendasi -- terisi otomatis dan gradasi menyala.
     */
    public function applyFrameGradientPreset(string $section, int $index): void
    {
        $property = $this->frameGradientProperty($section);
        $preset = FrameBackground::PRESETS[$index] ?? null;

        if ($preset === null) {
            return;
        }

        $gradient = FrameBackground::normalize($this->{$property});

        $this->{$property} = array_merge($gradient, [
            'enabled' => true,
            'from' => $preset['from'],
            'use_mid' => $preset['mid'] !== null,
            'mid' => $preset['mid'] ?? $gradient['mid'],
            'to' => $preset['to'],
            'angle' => $preset['angle'],
        ]);
    }

    /**
     * Tukar warna awal dengan warna akhir.
     */
    public function swapFrameGradientColors(string $section): void
    {
        $property = $this->frameGradientProperty($section);

        $gradient = FrameBackground::normalize($this->{$property});
        [$gradient['from'], $gradient['to']] = [$gradient['to'], $gradient['from']];

        $this->{$property} = $gradient;
    }

    /**
     * Klik salah satu tombol arah (0 = ke atas, 90 = ke kanan, 180 = ke bawah, 270 = ke kiri).
     */
    public function setFrameGradientAngle(string $section, int $angle): void
    {
        $property = $this->frameGradientProperty($section);

        $gradient = FrameBackground::normalize($this->{$property});
        $gradient['angle'] = max(0, min(360, $angle));

        $this->{$property} = $gradient;
    }

    /**
     * Aturan validasi gradasi sebuah section -- disatukan ke validate() di method save section itu.
     *
     * @return array<string, array<int, string>>
     */
    protected function frameGradientRules(string $section): array
    {
        $property = $this->frameGradientProperty($section);
        $hex = ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        return [
            "{$property}.enabled" => ['boolean'],
            "{$property}.from" => $hex,
            "{$property}.use_mid" => ['boolean'],
            "{$property}.mid" => $hex,
            "{$property}.to" => $hex,
            "{$property}.angle" => ['required', 'integer', 'between:0,360'],
        ];
    }

    /**
     * Data gradasi yang siap disimpan ke home_sections.data['bg_gradient'].
     *
     * @return array{enabled: bool, from: string, use_mid: bool, mid: string, to: string, angle: int}
     */
    protected function frameGradientPayload(string $section): array
    {
        return FrameBackground::normalize($this->{$this->frameGradientProperty($section)});
    }

    /**
     * Nama property untuk sebuah section -- sekaligus daftar putih supaya
     * method publik di atas tidak bisa dipakai menyentuh property lain.
     */
    private function frameGradientProperty(string $section): string
    {
        abort_unless(
            in_array($section, ['mission', 'produkUnggulan', 'kategori', 'testimoni', 'lokasi'], true),
            404,
        );

        return $section.'Gradient';
    }
}

'@ }

$pending += @{ Path = "resources/views/partials/admin/frame-gradient.blade.php"; Existing = $false; CrLf = $false; Text = @'
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

'@ }

# ---------- Tulis: backup dulu, lalu timpa ----------
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupRoot = Join-Path $root ".backup-frame-gradient-$stamp"

foreach ($item in $pending) {
    if ($item.Existing) {
        $src = Join-Path $root $item.Path
        $dst = Join-Path $backupRoot $item.Path
        $dstDir = Split-Path $dst -Parent
        if (-not (Test-Path $dstDir)) { New-Item -ItemType Directory -Path $dstDir -Force | Out-Null }
        Copy-Item $src $dst
    }
}

foreach ($item in $pending) {
    Write-Lf $item.Path $item.Text $item.CrLf
    if ($item.Existing) { Write-Host ("  diubah : " + $item.Path) } else { Write-Host ("  baru   : " + $item.Path) }
}

Write-Host ""
Write-Host "Selesai. Backup file yang diubah: .backup-frame-gradient-$stamp"
Write-Host "Langkah berikutnya: npm run build  ->  php artisan view:clear"
