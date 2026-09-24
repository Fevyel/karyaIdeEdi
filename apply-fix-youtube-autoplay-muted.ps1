<#
==========================================================
FIX: Video YouTube "Kenapa Pilih Kami" macet di layar hitam
==========================================================
Akar masalah:
  Saat section masuk layar, kode lama langsung minta YouTube
  autoplay SEKALIGUS bersuara (mute=0). Browser modern (Chrome,
  dll) tidak pernah mengizinkan itu di dalam iframe tanpa
  interaksi pengguna -- dan beda dari <video> biasa, video
  YouTube TIDAK otomatis fallback ke mode senyap kalau ditolak.
  Videonya cuma diam di posisi "cued" (siap tapi belum main),
  makanya kelihatan hitam terus walau link videonya valid.

Perbaikan:
  Video YouTube sekarang SELALU dimulai dalam kondisi senyap
  (mute=1) supaya autoplay pasti diizinkan browser -- persis
  sama seperti pola yang sudah dipakai video upload biasa
  (keahlianVideoPlayer). Lalu ~0.6 detik kemudian baru dicoba
  dibunyikan otomatis (kalau pengunjung belum mematikan suara
  manual sendiri).

Cara pakai:
  1. Simpan file ini di root project (sejajar dengan artisan,
     composer.json, dll -- sama seperti file apply-fix-*.ps1
     lain yang sudah ada).
  2. Jalankan dari terminal VS Code, tepat di folder project:
       powershell -ExecutionPolicy Bypass -File .\apply-fix-youtube-autoplay-muted.ps1
  3. Setelah berhasil, WAJIB build ulang asset (karena kamu
     pakai php artisan serve tanpa npm run dev):
       npm run build
  4. Refresh halaman Beranda (hard refresh: Ctrl+Shift+R) lalu
     tes ulang video YouTube-nya.

Script ini HANYA mengubah 1 blok kecil di resources/js/app.js
(fungsi enter() di dalam keahlianYoutubePlayer). Tidak ada file
lain yang disentuh.
==========================================================
#>

$ErrorActionPreference = 'Stop'

$file = 'resources/js/app.js'

if (-not (Test-Path $file)) {
    Write-Host "GAGAL: file $file tidak ditemukan. Jalankan script ini dari root folder project (yang ada file artisan-nya)." -ForegroundColor Red
    exit 1
}

$content = Get-Content -Path $file -Raw -Encoding UTF8

$old = @"
        enter() {
            clearInterval(this.fadeTimer);

            if (!this.loaded) {
                this.loaded = true;
                this.`$refs.iframe.src = embedUrl + '&autoplay=1&mute=' + (this.manuallyMuted ? '1' : '0');
                return;
            }

            this.command('playVideo');
            if (!this.manuallyMuted) {
                this.command('unMute');
                this.command('setVolume', [100]);
                this.muted = false;
            }
        },
"@

$new = @"
        enter() {
            clearInterval(this.fadeTimer);

            if (!this.loaded) {
                this.loaded = true;

                // Selalu mulai SENYAP -- kebijakan autoplay browser cuma
                // mengizinkan video autoplay kalau videonya mute. Kalau
                // langsung minta mute=0 di sini, browser diam-diam MENOLAK
                // autoplay-nya sama sekali (video berhenti di posisi
                // "cued"/layar hitam, tidak ada fallback otomatis seperti
                // tag <video> biasa).
                this.`$refs.iframe.src = embedUrl + '&autoplay=1&mute=1';

                if (!this.manuallyMuted) {
                    // Kita tidak memuat skrip resmi iframe_api Google (biar
                    // ringan), jadi tidak ada event "player sudah siap" yang
                    // bisa didengar -- pakai jeda singkat sebagai perkiraan
                    // aman sebelum mencoba membunyikan otomatis.
                    setTimeout(() => {
                        if (!this.manuallyMuted && this.loaded) {
                            this.command('unMute');
                            this.command('setVolume', [100]);
                            this.muted = false;
                        }
                    }, 600);
                }

                return;
            }

            this.command('playVideo');
            if (!this.manuallyMuted) {
                this.command('unMute');
                this.command('setVolume', [100]);
                this.muted = false;
            }
        },
"@

if ($content -notmatch [regex]::Escape($old)) {
    Write-Host "GAGAL: potongan kode yang dicari tidak ketemu persis di $file." -ForegroundColor Red
    Write-Host "Kemungkinan file ini sudah pernah diubah sebelumnya, atau isinya beda dari yang diharapkan. Tidak ada perubahan dilakukan." -ForegroundColor Yellow
    exit 1
}

$content = $content.Replace($old, $new)
Set-Content -Path $file -Value $content -NoNewline -Encoding UTF8

Write-Host "BERHASIL: $file sudah diperbaiki." -ForegroundColor Green
Write-Host "Langkah selanjutnya: jalankan 'npm run build' lalu hard refresh browser (Ctrl+Shift+R)." -ForegroundColor Cyan
