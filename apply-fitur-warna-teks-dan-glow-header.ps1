# apply-fitur-warna-teks-dan-glow-header.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   powershell -ExecutionPolicy Bypass -File apply-fitur-warna-teks-dan-glow-header.ps1
#
# Yang ditambahkan (TIDAK mengubah tampilan default yang sudah ada,
# kalau admin tidak menyentuh opsi baru ini semuanya tetap seperti biasa):
# 1) Admin > Edit Web > Header -> tambah kontrol "Warna Teks" (checkbox
#    "Pakai warna teks manual" + pilihan Hitam/Putih) dan "Cahaya (glow)
#    di belakang teks" (checkbox opsional + pilihan Hitam/Putih).
# 2) Frontend hero.blade.php -> baca 2 setting baru itu, override warna
#    teks kalau diaktifkan, dan render lapisan glow blur di belakang
#    judul/deskripsi kalau diaktifkan.
#
# Tidak perlu migration baru -- data section Header disimpan di kolom
# JSON (tabel home_sections), jadi field baru cukup ditambah di array
# default + save().

$ErrorActionPreference = "Stop"

$editWebPath = "resources/views/pages/admin/edit-web.blade.php"
$heroPath    = "resources/views/partials/frontend/hero.blade.php"

if (-not (Test-Path $editWebPath)) { throw "Tidak ketemu: $editWebPath (jalankan script ini dari root project)" }
if (-not (Test-Path $heroPath))    { throw "Tidak ketemu: $heroPath (jalankan script ini dari root project)" }

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
Copy-Item $editWebPath "$editWebPath.bak-before-fitur-warna-teks-glow-$stamp"
Copy-Item $heroPath    "$heroPath.bak-before-fitur-warna-teks-glow-$stamp"

function Apply-Replace {
    param($Path, $Old, $New, $Label)

    $content = Get-Content $Path -Raw -Encoding UTF8

    if ($content -notmatch [regex]::Escape($Old)) {
        throw "[$Label] Blok yang dicari tidak cocok persis di $Path (kemungkinan file sudah pernah diubah sejak zip terakhir). Tidak ada yang ditimpa, cek manual."
    }

    $content = $content.Replace($Old, $New)
    Set-Content $Path -Value $content -Encoding UTF8 -NoNewline
}

# =====================================================================
# 1) edit-web.blade.php -- property baru (Livewire)
# =====================================================================
$old1 = @'
    public array $headerBgPresets = [
        ['label' => 'Krem Hangat (Default)', 'value' => '#F9F7F2', 'check' => '#3D2B1F'],
        ['label' => 'Putih Gading', 'value' => '#FFFDF8', 'check' => '#3D2B1F'],
        ['label' => 'Beige Pasir', 'value' => '#EFE3D0', 'check' => '#3D2B1F'],
        ['label' => 'Sage Hijau Lembut', 'value' => '#DCE3CE', 'check' => '#3D2B1F'],
        ['label' => 'Terracotta', 'value' => '#C97B5A', 'check' => '#FFFFFF'],
        ['label' => 'Coklat Kayu Tua', 'value' => '#3D2B1F', 'check' => '#FFFFFF'],
        ['label' => 'Navy Malam', 'value' => '#22303F', 'check' => '#FFFFFF'],
        ['label' => 'Hitam Elegan', 'value' => '#1A1A1A', 'check' => '#FFFFFF'],
    ];

    // ================= KEUNGGULAN (section Features) =================
'@

$new1 = @'
    public array $headerBgPresets = [
        ['label' => 'Krem Hangat (Default)', 'value' => '#F9F7F2', 'check' => '#3D2B1F'],
        ['label' => 'Putih Gading', 'value' => '#FFFDF8', 'check' => '#3D2B1F'],
        ['label' => 'Beige Pasir', 'value' => '#EFE3D0', 'check' => '#3D2B1F'],
        ['label' => 'Sage Hijau Lembut', 'value' => '#DCE3CE', 'check' => '#3D2B1F'],
        ['label' => 'Terracotta', 'value' => '#C97B5A', 'check' => '#FFFFFF'],
        ['label' => 'Coklat Kayu Tua', 'value' => '#3D2B1F', 'check' => '#FFFFFF'],
        ['label' => 'Navy Malam', 'value' => '#22303F', 'check' => '#FFFFFF'],
        ['label' => 'Hitam Elegan', 'value' => '#1A1A1A', 'check' => '#FFFFFF'],
    ];

    /**
     * Override warna teks (judul/deskripsi/statistik/divider) header secara
     * manual. Bawaan (false) tetap pakai kontras otomatis dari $heroBgColor
     * (lihat contrastTextColors() di partials/frontend/hero.blade.php) --
     * tidak mengubah tampilan situs yang sudah berjalan kalau admin tidak
     * menyentuh opsi ini sama sekali.
     */
    public bool $headerUseCustomTextColor = false;

    /** @var 'black'|'white' */
    public string $headerTextColor = 'black';

    /**
     * Cahaya (glow) opsional di belakang judul/deskripsi header, supaya
     * teks tetap kebaca kalau foto latar terlalu terang/ramai. Mati
     * secara bawaan.
     */
    public bool $headerTextGlowEnabled = false;

    /** @var 'black'|'white' */
    public string $headerTextGlowColor = 'white';

    // ================= KEUNGGULAN (section Features) =================
'@

Apply-Replace -Path $editWebPath -Old $old1 -New $new1 -Label "properti baru"

# =====================================================================
# 2) edit-web.blade.php -- headerDefaults()
# =====================================================================
$old2 = @'
    private function headerDefaults(): array
    {
        return [
            'bg_color' => null,
            'headline_prefix' => 'Furnitur',
'@

$new2 = @'
    private function headerDefaults(): array
    {
        return [
            'bg_color' => null,
            'text_color' => null,
            'text_glow' => null,
            'headline_prefix' => 'Furnitur',
'@

Apply-Replace -Path $editWebPath -Old $old2 -New $new2 -Label "headerDefaults()"

# =====================================================================
# 3) edit-web.blade.php -- mount()
# =====================================================================
$old3 = @'
        $this->headerUseCustomBg = filled($data['bg_color']);
        $this->headerBgColor = $data['bg_color'] ?: $this->headerBgColor;

        $this->headerHeadlinePrefix = $data['headline_prefix'];
'@

$new3 = @'
        $this->headerUseCustomBg = filled($data['bg_color']);
        $this->headerBgColor = $data['bg_color'] ?: $this->headerBgColor;

        $this->headerUseCustomTextColor = filled($data['text_color']);
        $this->headerTextColor = $data['text_color'] ?: $this->headerTextColor;

        $this->headerTextGlowEnabled = filled($data['text_glow']);
        $this->headerTextGlowColor = $data['text_glow'] ?: $this->headerTextGlowColor;

        $this->headerHeadlinePrefix = $data['headline_prefix'];
'@

Apply-Replace -Path $editWebPath -Old $old3 -New $new3 -Label "mount()"

# =====================================================================
# 4) edit-web.blade.php -- saveHeader() validasi
# =====================================================================
$old4 = @'
        $validated = $this->validate([
            'headerBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'headerHeadlinePrefix' => ['required', 'string', 'max:40'],
'@

$new4 = @'
        $validated = $this->validate([
            'headerBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'headerTextColor' => ['required', 'string', 'in:black,white'],
            'headerTextGlowColor' => ['required', 'string', 'in:black,white'],
            'headerHeadlinePrefix' => ['required', 'string', 'max:40'],
'@

Apply-Replace -Path $editWebPath -Old $old4 -New $new4 -Label "saveHeader() validasi"

# =====================================================================
# 5) edit-web.blade.php -- saveHeader() simpan data
# =====================================================================
$old5 = @'
        HomeSection::forSection('header')->update([
            'data' => [
                'bg_color' => $this->headerUseCustomBg ? $validated['headerBgColor'] : null,
                'headline_prefix' => $validated['headerHeadlinePrefix'],
'@

$new5 = @'
        HomeSection::forSection('header')->update([
            'data' => [
                'bg_color' => $this->headerUseCustomBg ? $validated['headerBgColor'] : null,
                'text_color' => $this->headerUseCustomTextColor ? $validated['headerTextColor'] : null,
                'text_glow' => $this->headerTextGlowEnabled ? $validated['headerTextGlowColor'] : null,
                'headline_prefix' => $validated['headerHeadlinePrefix'],
'@

Apply-Replace -Path $editWebPath -Old $old5 -New $new5 -Label "saveHeader() simpan data"

# =====================================================================
# 6) edit-web.blade.php -- UI baru, taruh setelah blok WARNA yang lama
# =====================================================================
$old6 = @'
                        <p class="text-xs text-admin-ink-soft">
                            Kalau tidak dicentang, section ini ikut warna tema default (bukan warna admin panel).
                            Warna judul, deskripsi, dan angka statistik tidak diatur terpisah -- otomatis
                            menyesuaikan (terang/gelap) mengikuti warna latar yang dipilih di sini, supaya
                            tetap kebaca dan tidak bertabrakan.
                        </p>
                    </div>

                    {{-- ISI TEKS --}}
'@

$new6 = @'
                        <p class="text-xs text-admin-ink-soft">
                            Kalau tidak dicentang, section ini ikut warna tema default (bukan warna admin panel).
                            Warna judul, deskripsi, dan angka statistik tidak diatur terpisah -- otomatis
                            menyesuaikan (terang/gelap) mengikuti warna latar yang dipilih di sini, supaya
                            tetap kebaca dan tidak bertabrakan.
                        </p>
                    </div>

                    {{--
                        WARNA TEKS -- override manual (opsional) dari kontras otomatis
                        di atas. Bawaan TETAP "Otomatis" supaya situs yang sudah
                        berjalan tidak berubah tampilannya kalau admin tidak
                        menyentuh opsi ini.
                    --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Teks</p>

                        <label class="flex items-center gap-2 text-sm text-admin-ink">
                            <input type="checkbox" wire:model.live="headerUseCustomTextColor" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                            Pakai warna teks manual (judul, deskripsi, statistik)
                        </label>

                        <div class="flex flex-wrap gap-2.5 {{ $headerUseCustomTextColor ? '' : 'pointer-events-none opacity-40' }}">
                            @foreach (['black' => 'Hitam', 'white' => 'Putih'] as $value => $label)
                                <button
                                    type="button"
                                    wire:click="$set('headerTextColor', '{{ $value }}')"
                                    @disabled(! $headerUseCustomTextColor)
                                    class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition
                                    {{ $headerTextColor === $value
                                        ? 'border-admin-accent bg-admin-accent/10 text-admin-ink'
                                        : 'border-admin-border text-admin-ink-soft hover:border-admin-accent/60' }}"
                                >
                                    <span class="h-4 w-4 rounded-full border border-admin-border" style="background: {{ $value === 'black' ? '#1A1208' : '#FFFFFF' }};"></span>
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        <p class="text-xs text-admin-ink-soft">
                            Kalau tidak dicentang, warna teks tetap otomatis mengikuti kontras warna latar di atas.
                        </p>

                        <div class="border-t border-admin-border pt-4">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="headerTextGlowEnabled" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Beri cahaya (glow) di belakang teks
                            </label>

                            <div class="mt-3 flex flex-wrap gap-2.5 {{ $headerTextGlowEnabled ? '' : 'pointer-events-none opacity-40' }}">
                                @foreach (['black' => 'Hitam', 'white' => 'Putih'] as $value => $label)
                                    <button
                                        type="button"
                                        wire:click="$set('headerTextGlowColor', '{{ $value }}')"
                                        @disabled(! $headerTextGlowEnabled)
                                        class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition
                                        {{ $headerTextGlowColor === $value
                                            ? 'border-admin-accent bg-admin-accent/10 text-admin-ink'
                                            : 'border-admin-border text-admin-ink-soft hover:border-admin-accent/60' }}"
                                    >
                                        <span class="h-4 w-4 rounded-full border border-admin-border" style="background: {{ $value === 'black' ? '#1A1208' : '#FFFFFF' }};"></span>
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>

                            <p class="mt-2 text-xs text-admin-ink-soft">
                                Cahaya lembut di belakang judul & deskripsi -- berguna kalau foto latar terlalu ramai/terang sehingga teks susah dibaca.
                            </p>
                        </div>
                    </div>

                    {{-- ISI TEKS --}}
'@

Apply-Replace -Path $editWebPath -Old $old6 -New $new6 -Label "UI Warna Teks & Glow"

# =====================================================================
# 7) hero.blade.php -- default array $headerSection
# =====================================================================
$old7 = @'
    $headerSection = \App\Models\HomeSection::dataFor('header', [
        'bg_color' => null,
        'headline_prefix' => 'Furnitur',
'@

$new7 = @'
    $headerSection = \App\Models\HomeSection::dataFor('header', [
        'bg_color' => null,
        'text_color' => null,
        'text_glow' => null,
        'headline_prefix' => 'Furnitur',
'@

Apply-Replace -Path $heroPath -Old $old7 -New $new7 -Label "default `$headerSection"

# =====================================================================
# 8) hero.blade.php -- override warna teks manual (kalau diaktifkan admin)
# =====================================================================
$old8 = @'
    $heroTextColors = $contrastTextColors($heroBgColor);
    $heroHeadingColor = $heroTextColors['heading'];
'@

$new8 = @'
    $heroTextColors = $contrastTextColors($heroBgColor);

    // Override manual dari Admin > Edit Web > Header ("Warna Teks"). Kalau
    // admin tidak mengaktifkannya, $headerSection['text_color'] tetap null
    // dan kontras otomatis di atas yang dipakai (tidak ada perubahan).
    if ($headerSection['text_color'] === 'black') {
        $heroTextColors = ['heading' => '#1A1208', 'body' => 'rgba(26, 18, 8, 0.72)', 'divider' => 'rgba(26, 18, 8, 0.12)', 'border' => 'rgba(26, 18, 8, 0.22)'];
    } elseif ($headerSection['text_color'] === 'white') {
        $heroTextColors = ['heading' => '#FFFFFF', 'body' => 'rgba(255, 255, 255, 0.78)', 'divider' => 'rgba(255, 255, 255, 0.18)', 'border' => 'rgba(255, 255, 255, 0.35)'];
    }

    $heroHeadingColor = $heroTextColors['heading'];
'@

Apply-Replace -Path $heroPath -Old $old8 -New $new8 -Label "override warna teks manual"

# =====================================================================
# 9) hero.blade.php -- render lapisan glow di belakang teks
# =====================================================================
$old9 = @'
    <div class="relative mx-auto w-full max-w-3xl px-6 py-14 text-center sm:px-8 sm:py-16 lg:px-10 lg:py-20">
        <div class="animate-fade-in-up">
'@

$new9 = @'
    <div class="relative mx-auto w-full max-w-3xl px-6 py-14 text-center sm:px-8 sm:py-16 lg:px-10 lg:py-20">
        {{-- Cahaya (glow) opsional di belakang judul/deskripsi -- diatur admin
             lewat Admin > Edit Web > Header ("Beri cahaya di belakang teks").
             Mati (tidak dirender) kalau $headerSection['text_glow'] null. --}}
        @if ($headerSection['text_glow'])
            <div class="pointer-events-none absolute inset-0 -z-10 flex items-center justify-center">
                <div
                    class="h-[70%] w-[95%] max-w-2xl rounded-full blur-3xl"
                    style="background: {{ $headerSection['text_glow'] === 'white' ? 'rgba(255,255,255,0.55)' : 'rgba(0,0,0,0.55)' }};"
                ></div>
            </div>
        @endif

        <div class="animate-fade-in-up">
'@

Apply-Replace -Path $heroPath -Old $old9 -New $new9 -Label "lapisan glow"

Write-Host ""
Write-Host "Selesai." -ForegroundColor Green
Write-Host "Diubah: $editWebPath"
Write-Host "Diubah: $heroPath"
Write-Host "Backup asli disimpan sebagai *.bak-before-fitur-warna-teks-glow-$stamp"
Write-Host ""
Write-Host "Tidak perlu npm run build (semua perubahan Blade/PHP)." -ForegroundColor Yellow
Write-Host "Cukup refresh browser, lalu cek Admin > Edit Web > Header -- ada 2 kontrol baru:"
Write-Host "  - Warna Teks (Hitam/Putih)"
Write-Host "  - Beri cahaya (glow) di belakang teks (Hitam/Putih)"
