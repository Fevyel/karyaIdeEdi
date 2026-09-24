$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path '.\artisan')) {
    throw "File 'artisan' tidak ditemukan. Jalankan script dari root project: C:\xampp\htdocs\karyaIdeEdi"
}

$file = '.\resources\views\pages\frontend\booking.blade.php'
if (-not (Test-Path $file)) {
    throw "File tidak ditemukan: $file"
}

Write-Host '=============================================================' -ForegroundColor Yellow
Write-Host ' Pita Dokumentasi V2 - Besar + Blend Antar File' -ForegroundColor Yellow
Write-Host '=============================================================' -ForegroundColor Yellow

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = "$file.bak-docmedia-blend-v2-$stamp"

Step '[1/4] Backup file ...'
Copy-Item $file $backup -Force
Write-Host "  Backup: $backup" -ForegroundColor DarkGray

Step '[2/4] Patch KHUSUS section pita foto/video ...'
$tmpPhp = Join-Path $env:TEMP "patch-docmedia-blend-v2-$stamp.php"

@'
<?php
$path = getcwd() . DIRECTORY_SEPARATOR . 'resources/views/pages/frontend/booking.blade.php';
$content = file_get_contents($path);
if ($content === false) {
    throw new RuntimeException('Gagal membaca booking.blade.php');
}

$sectionStart = strpos($content, '<section class="kie-docmedia"');
if ($sectionStart === false) {
    throw new RuntimeException('Section pita .kie-docmedia tidak ditemukan. Tidak ada perubahan dilakukan.');
}

$styleEnd = strpos($content, '</style>', $sectionStart);
if ($styleEnd === false) {
    throw new RuntimeException('Penutup style pita tidak ditemukan. Tidak ada perubahan dilakukan.');
}
$styleEnd += strlen('</style>');

$before = substr($content, 0, $sectionStart);
$block = substr($content, $sectionStart, $styleEnd - $sectionStart);
$after = substr($content, $styleEnd);

function replaceRule(string $block, string $pattern, string $replacement, string $label): string
{
    $count = 0;
    $new = preg_replace($pattern, $replacement, $block, 1, $count);
    if ($new === null) {
        throw new RuntimeException("Regex error saat memproses: {$label}");
    }
    if ($count !== 1) {
        throw new RuntimeException("Rule '{$label}' tidak ditemukan tepat 1x (ditemukan {$count}x). Tidak ada perubahan ditulis.");
    }
    echo "  - {$label}: OK\n";
    return $new;
}

// Besar, full-bleed, tanpa padding/border/outline.
$block = replaceRule(
    $block,
    '~\.kie-docmedia\{[^}]*\}~',
    '.kie-docmedia{position:relative;overflow:hidden;width:100%;background:#24150e;padding:0;isolation:isolate}',
    'wadah pita tanpa outline/padding'
);

// Gerak tetap dari kiri ke kanan; sedikit diperlambat agar nyaman karena item lebih besar.
$block = replaceRule(
    $block,
    '~\.kie-docmedia__track\{[^}]*\}~',
    '.kie-docmedia__track{display:flex;width:max-content;animation:kie-docmedia-ltr 88s linear infinite;will-change:transform}',
    'track berjalan kiri ke kanan'
);

$block = replaceRule(
    $block,
    '~\.kie-docmedia__group\{[^}]*\}~',
    '.kie-docmedia__group{display:flex;flex-shrink:0;align-items:stretch}',
    'group media'
);

// KUNCI BLEND: media dibuat besar + overlap kuat, sementara sisi file dimask
// transparan. Karena item bertumpuk, sisi transparan memperlihatkan FILE
// tetangganya di bawah, bukan blok background; hasilnya terlihat menyatu.
$block = replaceRule(
    $block,
    '~\.kie-docmedia__item\{[^}]*\}~',
    '.kie-docmedia__item{position:relative;flex:0 0 clamp(480px,34vw,700px);height:clamp(300px,22vw,430px);overflow:hidden;margin:0 -6.5rem 0 0;background:transparent;-webkit-mask-image:linear-gradient(to right,transparent 0%,rgba(0,0,0,.35) 6%,#000 18%,#000 82%,rgba(0,0,0,.35) 94%,transparent 100%);mask-image:linear-gradient(to right,transparent 0%,rgba(0,0,0,.35) 6%,#000 18%,#000 82%,rgba(0,0,0,.35) 94%,transparent 100%)}',
    'ukuran besar + overlap + gradasi antar FILE'
);

$block = replaceRule(
    $block,
    '~\.kie-docmedia__item img,\.kie-docmedia__item video\{[^}]*\}~',
    '.kie-docmedia__item img,.kie-docmedia__item video{width:100%;height:100%;object-fit:cover;display:block;transition:transform .7s ease,filter .45s ease}',
    'media memenuhi area'
);

$block = replaceRule(
    $block,
    '~\.kie-docmedia__item:hover img,\.kie-docmedia__item:hover video\{[^}]*\}~',
    '.kie-docmedia__item:hover img,.kie-docmedia__item:hover video{transform:scale(1.025);filter:saturate(1.05)}',
    'hover halus'
);

$block = replaceRule(
    $block,
    '~\.kie-docmedia__item:after\{[^}]*\}~',
    ".kie-docmedia__item:after{content:'';position:absolute;inset:0;background:linear-gradient(to top,rgba(16,9,6,.58) 0%,rgba(16,9,6,.16) 34%,transparent 58%);pointer-events:none}",
    'overlay hanya untuk caption'
);

$block = replaceRule(
    $block,
    '~\.kie-docmedia__item figcaption\{[^}]*\}~',
    '.kie-docmedia__item figcaption{position:absolute;z-index:2;left:2rem;right:2rem;bottom:1.55rem;color:#fff;font-size:1.12rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-shadow:0 3px 12px rgba(0,0,0,.42)}',
    'caption lebih besar'
);

// Fade global kiri/kanan dimatikan. Yang dipakai sekarang hanya gradasi
// dari masing-masing FILE sehingga tidak ada blok cokelat besar di tepi.
$block = replaceRule(
    $block,
    '~\.kie-docmedia__fade\{[^}]*\}~',
    '.kie-docmedia__fade{display:none}',
    'hapus fade/background global'
);

// Pastikan video pita benar-benar meminta autoplay segera setelah siap.
$videoPattern = '~<video\s+src="\{\{ \$item\[\'src\'\] \}\}"\s+autoplay\s+muted\s+loop\s+playsinline\s+preload="auto"(?:\s+onloadeddata="[^"]*")?(?:\s+onloadedmetadata="[^"]*")?(?:\s+oncanplay="[^"]*")?\s*></video>~';
$videoReplacement = '<video src="{{ $item[\'src\'] }}" autoplay muted loop playsinline preload="auto" onloadedmetadata="this.muted=true; this.play().catch(() => {})" oncanplay="this.muted=true; this.play().catch(() => {})"></video>';
$countVideo = 0;
$blockNew = preg_replace($videoPattern, $videoReplacement, $block, 1, $countVideo);
if ($blockNew === null) {
    throw new RuntimeException('Regex video autoplay gagal. Tidak ada perubahan ditulis.');
}
if ($countVideo === 1) {
    $block = $blockNew;
    echo "  - autoplay video pita: OK\n";
} else {
    // Jika markup video sudah berbeda tetapi atribut autoplay/muted/loop ada,
    // jangan gagalkan seluruh patch visual.
    if (strpos($block, "autoplay muted loop playsinline") === false) {
        throw new RuntimeException('Markup autoplay video pita tidak dikenali. Tidak ada perubahan ditulis.');
    }
    echo "  - autoplay video pita: sudah aktif, markup dibiarkan\n";
}

// Responsive khusus mobile/tablet. Ini ditambahkan tepat sebelum keyframes.
$responsive = '@media(max-width:767px){.kie-docmedia__item{flex-basis:88vw;height:270px;margin-right:-3.75rem;-webkit-mask-image:linear-gradient(to right,transparent 0%,#000 15%,#000 85%,transparent 100%);mask-image:linear-gradient(to right,transparent 0%,#000 15%,#000 85%,transparent 100%)}.kie-docmedia__item figcaption{left:1.35rem;right:1.35rem;bottom:1.15rem;font-size:1rem}}';
if (strpos($block, '@media(max-width:767px){.kie-docmedia__item') === false) {
    $keyframePos = strpos($block, '@keyframes kie-docmedia-ltr');
    if ($keyframePos === false) {
        throw new RuntimeException('Keyframes pita tidak ditemukan. Tidak ada perubahan ditulis.');
    }
    $block = substr($block, 0, $keyframePos) . $responsive . substr($block, $keyframePos);
    echo "  - responsive mobile: OK\n";
}

$newContent = $before . $block . $after;
file_put_contents($path, $newContent);
echo "PATCH_OK\n";
'@ | Set-Content -Path $tmpPhp -Encoding UTF8

php $tmpPhp
Remove-Item $tmpPhp -Force

Step '[3/4] Validasi syntax ...'
php -l $file | Out-Host

Step '[4/4] Selesai ...'
Write-Host 'SELESAI' -ForegroundColor Green
Write-Host 'Yang diubah hanya section pita foto/video di booking.blade.php.' -ForegroundColor Green
Write-Host 'Hero, Galeri Video utama, Galeri Foto utama, Edit Web, database, dan file lain tidak disentuh.' -ForegroundColor DarkGray
