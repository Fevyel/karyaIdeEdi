$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Fix sambungan Edit Web -> halaman masing-masing" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan

$Root = (Get-Location).Path
if (-not (Test-Path (Join-Path $Root 'artisan'))) {
    throw "Jalankan script ini dari root project Laravel (folder yang berisi file artisan). Lokasi saat ini: $Root"
}

$EditWebPath   = Join-Path $Root 'resources\views\pages\admin\edit-web.blade.php'
$ExpertisePath = Join-Path $Root 'resources\views\partials\frontend\expertise.blade.php'
$DokPath       = Join-Path $Root 'resources\views\pages\frontend\booking.blade.php'

foreach ($path in @($EditWebPath, $ExpertisePath, $DokPath)) {
    if (-not (Test-Path $path)) {
        throw "File wajib tidak ditemukan: $path"
    }
}

function Read-ProjectText([string]$Path) {
    $bytes = [System.IO.File]::ReadAllBytes($Path)
    $hasBom = $bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF
    $text = [System.IO.File]::ReadAllText($Path)
    $eol = if ($text.Contains("`r`n")) { "`r`n" } else { "`n" }

    [pscustomobject]@{
        Path   = $Path
        Text   = $text.Replace("`r`n", "`n")
        Eol    = $eol
        HasBom = $hasBom
    }
}

function Write-ProjectText($FileInfo, [string]$Text) {
    $out = if ($FileInfo.Eol -eq "`r`n") { $Text.Replace("`n", "`r`n") } else { $Text }
    $encoding = New-Object System.Text.UTF8Encoding($FileInfo.HasBom)
    [System.IO.File]::WriteAllText($FileInfo.Path, $out, $encoding)
}

function Replace-OnceOrAlready([string]$Text, [string]$Old, [string]$New, [string]$Label) {
    $oldCount = ([regex]::Matches($Text, [regex]::Escape($Old))).Count
    $newCount = ([regex]::Matches($Text, [regex]::Escape($New))).Count

    if ($oldCount -eq 1) {
        Write-Host "  [OK] $Label" -ForegroundColor Green
        return $Text.Replace($Old, $New)
    }

    if ($oldCount -eq 0 -and $newCount -ge 1) {
        Write-Host "  [SKIP] $Label (sudah terpasang)" -ForegroundColor DarkYellow
        return $Text
    }

    throw "Pola '$Label' tidak aman untuk dipatch (old=$oldCount, new=$newCount). Script berhenti sebelum menulis file apa pun."
}

$editInfo = Read-ProjectText $EditWebPath
$expInfo  = Read-ProjectText $ExpertisePath
$dokInfo  = Read-ProjectText $DokPath

$editNew = $editInfo.Text
$expNew  = $expInfo.Text
$dokNew  = $dokInfo.Text

Write-Host "[1/5] Memisahkan media Kenapa Pilih Kami dari Galeri Dokumentasi ..." -ForegroundColor Yellow
$oldLeak = @'
        $keahlianVideoPath = HomeSection::dataFor('keahlian', ['media_type' => null, 'video_path' => null])['video_path'] ?? null;

        if ($keahlianVideoPath && count($items) > 0) {
            $items[count($items) - 1] = [
                'tipe' => 'video',
                'path' => $keahlianVideoPath,
                'keterangan' => 'Proses & keahlian kami',
            ];
        }

'@
$oldLeakCount = ([regex]::Matches($editNew, [regex]::Escape($oldLeak))).Count
$leakNeedle = '$keahlianVideoPath = HomeSection::dataFor(''keahlian'''
if ($oldLeakCount -eq 1) {
    Write-Host "  [OK] hapus cross-link keahlian -> dokumentasi" -ForegroundColor Green
    $editNew = $editNew.Replace($oldLeak, '')
} elseif ($oldLeakCount -eq 0 -and -not $editNew.Contains($leakNeedle)) {
    Write-Host "  [SKIP] hapus cross-link keahlian -> dokumentasi (sudah terpasang)" -ForegroundColor DarkYellow
} else {
    throw "Pola 'hapus cross-link keahlian -> dokumentasi' tidak aman untuk dipatch. Script berhenti sebelum menulis file apa pun."
}

Write-Host "[2/5] Menormalkan media lama di Edit Web ..." -ForegroundColor Yellow
$oldKeahlianMount = @'
        $this->keahlianMediaType = in_array($keahlianData['media_type'] ?? null, ['video_url', 'video_upload'], true)
            ? $keahlianData['media_type']
            : 'video_url';
'@
$newKeahlianMount = @'
        // Data lama sempat punya media_type "photo" walaupun video upload
        // sudah tersimpan. Kalau itu terjadi, utamakan video_path yang memang
        // milik section "keahlian" agar video kembali tampil di Beranda.
        $this->keahlianMediaType = in_array($keahlianData['media_type'] ?? null, ['video_url', 'video_upload'], true)
            ? $keahlianData['media_type']
            : (filled($keahlianData['video_path'] ?? null) ? 'video_upload' : 'video_url');
'@
$editNew = Replace-OnceOrAlready $editNew $oldKeahlianMount $newKeahlianMount 'fallback media Kenapa Pilih Kami'

$oldDokMount = @'
        $this->dokumentasiMediaType = in_array($dokumentasiData['media_type'] ?? null, ['video_url', 'video_upload'], true)
            ? $dokumentasiData['media_type']
            : 'video_url';
'@
$newDokMount = @'
        // Sama seperti "Kenapa Pilih Kami": kalau ada data lama tanpa
        // media_type yang valid tetapi video upload milik Dokumentasi sudah
        // tersimpan, jangan salah mengarahkannya ke tab URL.
        $this->dokumentasiMediaType = in_array($dokumentasiData['media_type'] ?? null, ['video_url', 'video_upload'], true)
            ? $dokumentasiData['media_type']
            : (filled($dokumentasiData['video_path'] ?? null) ? 'video_upload' : 'video_url');
'@
$editNew = Replace-OnceOrAlready $editNew $oldDokMount $newDokMount 'fallback media Dokumentasi'

$oldDokHelp = @'
                        <p class="text-xs text-admin-ink-soft">
                            Judul, subjudul, dan deskripsi yang tampil di bagian atas halaman
                            "Dokumentasi". Kolom kanan halaman itu sengaja dikosongkan, jadi tidak
                            ada foto untuk diedit di sini.
                        </p>
'@
$newDokHelp = @'
                        <p class="text-xs text-admin-ink-soft">
                            Judul, subjudul, deskripsi, dan video yang tampil di bagian atas halaman
                            "Dokumentasi". Media di bagian ini hanya terhubung ke section Dokumentasi
                            dan tidak mengambil media dari section Beranda.
                        </p>
'@
$editNew = Replace-OnceOrAlready $editNew $oldDokHelp $newDokHelp 'keterangan panel Dokumentasi'

Write-Host "[3/5] Memastikan Beranda > Kenapa Pilih Kami membaca media miliknya sendiri ..." -ForegroundColor Yellow
$oldExpDefault = @'
        'image_path' => null,
        'media_type' => 'photo',
        'video_url' => null,
'@
$newExpDefault = @'
        'image_path' => null,
        'media_type' => 'video_url',
        'video_url' => null,
'@
$expNew = Replace-OnceOrAlready $expNew $oldExpDefault $newExpDefault 'default media expertise = video'

$oldExpLogic = @'
    $keahlianVideo = null;

    if ($keahlianData['media_type'] === 'video_upload' && $keahlianVideoUploadUrl) {
        $keahlianVideo = ['provider' => 'direct', 'embed_url' => $keahlianVideoUploadUrl];
    } elseif ($keahlianData['media_type'] === 'video_url' && $keahlianData['video_url']) {
        $keahlianVideo = \App\Models\HomeSection::classifyVideoUrl($keahlianData['video_url']);
    }
'@
$newExpLogic = @'
    $keahlianVideo = null;

    // Normalisasi data lama: sebelumnya section ini pernah memakai media_type
    // "photo". Kalau file video upload sudah ada, file itu tetap harus dianggap
    // milik "Kenapa Pilih Kami" dan dirender di Beranda, bukan jatuh ke fallback
    // foto atau ikut terbawa ke section lain.
    $keahlianMediaType = in_array($keahlianData['media_type'] ?? null, ['video_url', 'video_upload'], true)
        ? $keahlianData['media_type']
        : ($keahlianVideoUploadUrl ? 'video_upload' : (($keahlianData['video_url'] ?? null) ? 'video_url' : null));

    if ($keahlianMediaType === 'video_upload' && $keahlianVideoUploadUrl) {
        $keahlianVideo = ['provider' => 'direct', 'embed_url' => $keahlianVideoUploadUrl];
    } elseif ($keahlianMediaType === 'video_url' && ($keahlianData['video_url'] ?? null)) {
        $keahlianVideo = \App\Models\HomeSection::classifyVideoUrl($keahlianData['video_url']);
    }
'@
$expNew = Replace-OnceOrAlready $expNew $oldExpLogic $newExpLogic 'render media Kenapa Pilih Kami'

Write-Host "[4/5] Memastikan halaman Dokumentasi hanya membaca media Dokumentasi ..." -ForegroundColor Yellow
$oldDokLogic = @'
        $dokumentasiVideo = null;

        if ($dokumentasiHero['media_type'] === 'video_upload' && $dokumentasiVideoUploadUrl) {
            $dokumentasiVideo = ['provider' => 'direct', 'embed_url' => $dokumentasiVideoUploadUrl];
        } elseif ($dokumentasiHero['media_type'] === 'video_url' && $dokumentasiHero['video_url']) {
            $dokumentasiVideo = \App\Models\HomeSection::classifyVideoUrl($dokumentasiHero['video_url']);
        }
'@
$newDokLogic = @'
        $dokumentasiVideo = null;

        // Normalisasi data lama supaya video milik Dokumentasi tetap membaca
        // field Dokumentasi sendiri. Tidak ada fallback ke media section lain.
        $dokumentasiMediaType = in_array($dokumentasiHero['media_type'] ?? null, ['video_url', 'video_upload'], true)
            ? $dokumentasiHero['media_type']
            : ($dokumentasiVideoUploadUrl ? 'video_upload' : (($dokumentasiHero['video_url'] ?? null) ? 'video_url' : null));

        if ($dokumentasiMediaType === 'video_upload' && $dokumentasiVideoUploadUrl) {
            $dokumentasiVideo = ['provider' => 'direct', 'embed_url' => $dokumentasiVideoUploadUrl];
        } elseif ($dokumentasiMediaType === 'video_url' && ($dokumentasiHero['video_url'] ?? null)) {
            $dokumentasiVideo = \App\Models\HomeSection::classifyVideoUrl($dokumentasiHero['video_url']);
        }
'@
$dokNew = Replace-OnceOrAlready $dokNew $oldDokLogic $newDokLogic 'render media Dokumentasi'

# Semua pola sudah lolos. Baru sekarang backup + tulis file.
$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupRoot = Join-Path $Root ".backup-fix-edit-web-wiring-$timestamp"

foreach ($src in @($EditWebPath, $ExpertisePath, $DokPath)) {
    $relative = $src.Substring($Root.Length).TrimStart([char[]]"\/")
    $dst = Join-Path $backupRoot $relative
    New-Item -ItemType Directory -Path (Split-Path $dst -Parent) -Force | Out-Null
    Copy-Item $src $dst -Force
}

Write-ProjectText $editInfo $editNew
Write-ProjectText $expInfo  $expNew
Write-ProjectText $dokInfo  $dokNew

Write-Host "[5/5] Merapikan data lama yang terlanjur silang di database ..." -ForegroundColor Yellow
$cleanupPath = Join-Path $Root 'storage\app\fix-edit-web-wiring-cleanup.php'
$cleanupCode = @'
<?php

use App\Models\HomeSection;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$backupDir = $argv[1] ?? null;
if (! $backupDir || ! is_dir($backupDir)) {
    fwrite(STDERR, "Backup directory tidak valid.\n");
    exit(2);
}

$rows = HomeSection::query()
    ->whereIn('section_key', ['keahlian', 'dokumentasi'])
    ->get()
    ->map(fn ($row) => [
        'section_key' => $row->section_key,
        'data' => $row->data,
        'updated_at' => optional($row->updated_at)->toIso8601String(),
    ])
    ->values()
    ->all();

file_put_contents(
    rtrim($backupDir, "\\/").DIRECTORY_SEPARATOR.'home_sections-keahlian-dokumentasi-before.json',
    json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
);

$result = DB::transaction(function () {
    $changed = [];

    $keahlian = HomeSection::query()->where('section_key', 'keahlian')->lockForUpdate()->first();
    $dokumentasi = HomeSection::query()->where('section_key', 'dokumentasi')->lockForUpdate()->first();

    $keahlianData = is_array($keahlian?->data) ? $keahlian->data : [];
    $dokData = is_array($dokumentasi?->data) ? $dokumentasi->data : [];

    // 1) Normalisasi data lama Kenapa Pilih Kami.
    if ($keahlian) {
        $mediaType = $keahlianData['media_type'] ?? null;
        if (! in_array($mediaType, ['video_url', 'video_upload'], true)) {
            if (! empty($keahlianData['video_path'])) {
                $keahlianData['media_type'] = 'video_upload';
                $keahlian->update(['data' => $keahlianData]);
                $changed[] = 'keahlian.media_type -> video_upload';
            } elseif (! empty($keahlianData['video_url'])) {
                $keahlianData['media_type'] = 'video_url';
                $keahlian->update(['data' => $keahlianData]);
                $changed[] = 'keahlian.media_type -> video_url';
            }
        }
    }

    // 2) Normalisasi data lama hero Dokumentasi.
    if ($dokumentasi) {
        $mediaType = $dokData['media_type'] ?? null;
        if (! in_array($mediaType, ['video_url', 'video_upload'], true)) {
            if (! empty($dokData['video_path'])) {
                $dokData['media_type'] = 'video_upload';
                $changed[] = 'dokumentasi.media_type -> video_upload';
            } elseif (! empty($dokData['video_url'])) {
                $dokData['media_type'] = 'video_url';
                $changed[] = 'dokumentasi.media_type -> video_url';
            }
        }

        // 3) Hapus HANYA item silang yang dulu dibuat otomatis oleh kode lama.
        //    File fisiknya TIDAK dihapus karena file itu tetap milik Keahlian.
        $keahlianVideoPath = $keahlianData['video_path'] ?? null;
        $galeri = isset($dokData['galeri']) && is_array($dokData['galeri']) ? $dokData['galeri'] : [];

        if ($keahlianVideoPath && $galeri) {
            $before = count($galeri);
            $galeri = array_values(array_filter($galeri, function ($item) use ($keahlianVideoPath) {
                if (! is_array($item)) {
                    return true;
                }

                $isKnownLeak = ($item['tipe'] ?? null) === 'video'
                    && ($item['path'] ?? null) === $keahlianVideoPath
                    && trim((string) ($item['keterangan'] ?? '')) === 'Proses & keahlian kami';

                return ! $isKnownLeak;
            }));

            if (count($galeri) !== $before) {
                $dokData['galeri'] = $galeri;
                $changed[] = 'hapus video Keahlian yang nyasar dari galeri Dokumentasi';
            }
        }

        if ($dokumentasi->data !== $dokData) {
            $dokumentasi->update(['data' => $dokData]);
        }
    }

    return $changed;
});

echo "CLEANUP_OK\n";
if ($result === []) {
    echo "Database: tidak ada data silang lama yang perlu diubah.\n";
} else {
    foreach ($result as $line) {
        echo "Database: {$line}\n";
    }
}
'@

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($cleanupPath, $cleanupCode, $utf8NoBom)

try {
    & php $cleanupPath $backupRoot
    if ($LASTEXITCODE -ne 0) {
        throw "Cleanup database gagal dengan exit code $LASTEXITCODE"
    }
}
catch {
    Write-Host "" -ForegroundColor Red
    Write-Host "[ERROR] Cleanup database gagal. File kode akan dikembalikan dari backup." -ForegroundColor Red

    foreach ($dst in @($EditWebPath, $ExpertisePath, $DokPath)) {
        $relative = $dst.Substring($Root.Length).TrimStart([char[]]"\/")
        $src = Join-Path $backupRoot $relative
        if (Test-Path $src) { Copy-Item $src $dst -Force }
    }

    throw
}
finally {
    if (Test-Path $cleanupPath) { Remove-Item $cleanupPath -Force }
}

# Audit sambungan inti setelah patch.
$editCheck = [System.IO.File]::ReadAllText($EditWebPath)
$expCheck  = [System.IO.File]::ReadAllText($ExpertisePath)
$dokCheck  = [System.IO.File]::ReadAllText($DokPath)

if ($editCheck -notmatch "HomeSection::forSection\('keahlian'\)" -or
    $expCheck -notmatch "HomeSection::dataFor\('keahlian'" -or
    $editCheck -notmatch "HomeSection::forSection\('dokumentasi'\)" -or
    $dokCheck -notmatch "HomeSection::dataFor\('dokumentasi'") {
    throw 'Audit sambungan section inti gagal.'
}

$leakNeedleCheck = '$keahlianVideoPath = HomeSection::dataFor(''keahlian'''
if ($editCheck.Contains($leakNeedleCheck)) {
    throw 'Audit gagal: masih ditemukan cross-link Keahlian -> Dokumentasi.'
}

Write-Host "" 
Write-Host "SELESAI" -ForegroundColor Green
Write-Host "Backup: $backupRoot" -ForegroundColor DarkGray
Write-Host "Yang diperbaiki:" -ForegroundColor Cyan
Write-Host "  - Kenapa Pilih Kami membaca video miliknya sendiri di Beranda."
Write-Host "  - Dokumentasi tidak lagi mengambil video Kenapa Pilih Kami sebagai galeri bawaan."
Write-Host "  - Data lama media_type dinormalkan secara aman."
Write-Host "  - Item video silang bawaan lama dibuang dari data Dokumentasi tanpa menghapus file videonya."
Write-Host "  - Tidak ada JS/CSS, migration, produk, pesanan, atau bagian lain yang diubah."
