/**
 * ==========================================================
 * Pemutar video HERO halaman Dokumentasi (/booking)
 * ==========================================================
 * Cara kerjanya MENIRU "Kenapa Pilih Kami" di Beranda
 * (keahlianVideoPlayer / keahlianYoutubePlayer di app.js),
 * tapi berdiri SENDIRI -- tidak memakai atau mengubah komponen
 * milik Beranda. Halaman Beranda dan Dokumentasi terpisah.
 *
 * Perilaku:
 *  - Video otomatis MAIN begitu section-nya masuk layar
 *    (sekitar 30% terlihat), dan PAUSE saat keluar layar.
 *  - Selalu mulai senyap (satu-satunya cara autoplay lolos
 *    dari kebijakan browser), lalu dibunyikan otomatis HANYA
 *    kalau browser mengizinkan (pengunjung sudah berinteraksi
 *    dengan halaman: klik / tap / tekan tombol).
 *  - Kalau belum boleh, video tetap jalan senyap dan langsung
 *    dibunyikan begitu pengunjung berinteraksi pertama kali.
 *  - Suara meredup pelan saat video keluar layar.
 *  - Tombol speaker manual tetap berfungsi.
 *  - YouTube: kalau browser diam-diam mem-pause video saat
 *    dibunyikan (autoplay bersuara ditolak), pemutar otomatis
 *    kembali ke mode senyap dan lanjut main -- tidak berhenti
 *    di layar thumbnail seperti sebelumnya.
 *  - YouTube: video mengisi penuh area hero (seperti object-cover,
 *    menyesuaikan ukuran layar otomatis, tanpa garis hitam),
 *    tanpa kontrol YouTube (pause/seek/fullscreen/judul) dan tidak
 *    bisa diklik. Satu-satunya kontrol: tombol suara kanan-bawah.
 */
document.addEventListener('alpine:init', () => {
    const GESTURE_EVENTS = ['pointerdown', 'pointerup', 'mousedown', 'touchend', 'keydown'];

    // Parameter tambahan embed YouTube supaya tidak ada kontrol/overlay:
    // controls=0 (tanpa bar kontrol), disablekb=1 (tanpa shortcut keyboard),
    // fs=0 (tanpa tombol fullscreen), iv_load_policy=3 (tanpa anotasi).
    const YOUTUBE_CLEAN_PARAMS = '&controls=0&disablekb=1&fs=0&iv_load_policy=3';

    // Rasio video YouTube dan sedikit "overscan" (video dibesarkan tipis
    // dan dipotong di tepi) untuk menyembunyikan sisa bayangan/tepi UI YouTube.
    const YOUTUBE_RATIO = 16 / 9;
    const YOUTUBE_OVERSCAN = 1.1;

    // Pengunjung sudah berinteraksi dengan halaman ini?
    const hasUserActivation = () =>
        !! (navigator.userActivation && navigator.userActivation.hasBeenActive);

    // Pasang pendengar "interaksi pertama". Callback dipanggil sekali,
    // tepat saat browser menganggap event itu interaksi sungguhan.
    const onFirstGesture = (callback) => {
        const handler = () => {
            if (navigator.userActivation && ! navigator.userActivation.isActive) return;

            GESTURE_EVENTS.forEach((name) => window.removeEventListener(name, handler, true));
            callback();
        };

        GESTURE_EVENTS.forEach((name) => window.addEventListener(name, handler, true));
    };

    /* ------------------------------------------------------
     * Video langsung (upload dari perangkat / tautan file)
     * ---------------------------------------------------- */
    Alpine.data('dokumentasiVideoPlayer', () => ({
        muted: true,
        manuallyMuted: false,
        inView: false,
        started: false,
        fadeTimer: null,

        init() {
            if (this.started) return;
            this.started = true;

            const video = this.$refs.video;
            if (! video) return;

            new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) {
                    this.enter(video);
                } else {
                    this.exit(video);
                }
            }, { threshold: 0.3 }).observe(this.$el);

            onFirstGesture(() => {
                if (this.inView && ! this.manuallyMuted && this.muted) {
                    this.tryUnmute(video);
                }
            });
        },

        tryUnmute(video) {
            video.volume = 1;
            video.muted = false;
            this.muted = false;

            video.play().catch(() => {
                // Browser menolak bersuara -- lanjut main senyap.
                video.muted = true;
                this.muted = true;
                video.play().catch(() => {});
            });
        },

        enter(video) {
            clearInterval(this.fadeTimer);
            this.inView = true;
            video.volume = 1;

            // Mulai senyap dulu supaya autoplay pasti lolos.
            video.muted = true;
            this.muted = true;

            video.play().then(() => {
                if (! this.manuallyMuted && hasUserActivation()) {
                    this.tryUnmute(video);
                }
            }).catch(() => {});
        },

        exit(video) {
            clearInterval(this.fadeTimer);
            this.inView = false;

            if (video.muted || video.volume === 0) {
                video.pause();
                return;
            }

            const steps = 20;
            let step = 0;
            this.fadeTimer = setInterval(() => {
                step += 1;
                video.volume = Math.max(0, 1 - step / steps);
                if (step >= steps) {
                    clearInterval(this.fadeTimer);
                    video.pause();
                }
            }, 40); // 20 x 40ms = ~800ms
        },

        toggleMute() {
            const video = this.$refs.video;
            if (! video) return;

            this.manuallyMuted = ! this.muted;
            this.muted = this.manuallyMuted;
            video.muted = this.muted;

            if (! this.muted) {
                video.volume = 1;
                video.play().catch(() => {});
            }
        },
    }));

    /* ------------------------------------------------------
     * YouTube (pakai postMessage API; embed_url sudah memuat
     * enablejsapi=1 dari App\Models\HomeSection::classifyVideoUrl)
     * ---------------------------------------------------- */
    Alpine.data('dokumentasiYoutubePlayer', (embedUrl) => ({
        muted: true,
        manuallyMuted: false,
        inView: false,
        loaded: false,
        started: false,
        heardPlayer: false,
        revealed: false,
        unmuteBlocked: false,
        retries: 0,
        fadeTimer: null,

        init() {
            if (this.started) return;
            this.started = true;

            const iframe = this.$refs.iframe;
            if (! iframe) return;

            // Tampilan: video mengisi hero (cover), tidak bisa diklik/difokus,
            // dan baru muncul (fade) setelah benar-benar main supaya layar
            // thumbnail + tombol play YouTube tidak sempat terlihat.
            this.$el.style.overflow = 'hidden';
            iframe.tabIndex = -1;
            Object.assign(iframe.style, {
                position: 'absolute',
                left: '50%',
                top: '50%',
                right: 'auto',
                bottom: 'auto',
                transform: 'translate(-50%, -50%)',
                pointerEvents: 'none',
                opacity: '0',
                transition: 'opacity 700ms ease',
            });
            this.fitCover();
            new ResizeObserver(() => this.fitCover()).observe(this.$el);

            // Status pemutar (playing / paused / dll.) dari YouTube.
            window.addEventListener('message', (event) => this.onPlayerMessage(event));

            // Begitu iframe selesai memuat, minta YouTube mulai mengirim status.
            iframe.addEventListener('load', () => {
                if (! iframe.getAttribute('src')) return;

                this.post({ event: 'listening', id: 1, channel: 'widget' });

                // Cadangan: kalau status "playing" tidak pernah terdeteksi,
                // tetap tampilkan iframe setelah 3 detik.
                setTimeout(() => this.reveal(), 3000);

                setTimeout(() => this.kick(), 500);
                setTimeout(() => {
                    this.kick();
                    // Kalau YouTube tidak mengirim status apa pun, tetap coba
                    // bunyikan (perilaku sama dengan Beranda).
                    if (! this.heardPlayer && this.canAutoUnmute()) {
                        this.unmute();
                    }
                }, 1500);
            });

            new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) {
                    this.enter();
                } else {
                    this.exit();
                }
            }, { threshold: 0.3 }).observe(this.$el);

            // Interaksi pertama pengunjung = izin browser untuk bersuara.
            onFirstGesture(() => {
                this.unmuteBlocked = false;
                if (this.loaded && this.inView && ! this.manuallyMuted && this.muted) {
                    this.unmute();
                }
            });
        },

        // Ukuran iframe = "cover": selalu menutupi seluruh area hero pada
        // rasio 16:9, kelebihannya terpotong (tengah video yang terlihat).
        fitCover() {
            const iframe = this.$refs.iframe;
            const w = this.$el.clientWidth;
            const h = this.$el.clientHeight;
            if (! iframe || ! w || ! h) return;

            let width = w;
            let height = w / YOUTUBE_RATIO;
            if (height < h) {
                height = h;
                width = h * YOUTUBE_RATIO;
            }

            iframe.style.width = Math.ceil(width * YOUTUBE_OVERSCAN) + 'px';
            iframe.style.height = Math.ceil(height * YOUTUBE_OVERSCAN) + 'px';
        },

        reveal() {
            if (this.revealed) return;
            this.revealed = true;
            const iframe = this.$refs.iframe;
            if (iframe) iframe.style.opacity = '1';
        },

        post(message) {
            const iframe = this.$refs.iframe;
            if (! iframe || ! iframe.contentWindow) return;
            iframe.contentWindow.postMessage(JSON.stringify(message), '*');
        },

        command(func, args = []) {
            this.post({ event: 'command', func, args });
        },

        canAutoUnmute() {
            return ! this.manuallyMuted && ! this.unmuteBlocked && hasUserActivation();
        },

        unmute() {
            this.command('unMute');
            this.command('setVolume', [100]);
            this.muted = false;
        },

        forceMutedPlay() {
            this.muted = true;
            this.command('mute');
            this.command('playVideo');
        },

        // Dorong video supaya main selama section terlihat.
        kick() {
            if (! this.loaded || ! this.inView) return;
            this.command('playVideo');
            if (this.canAutoUnmute() && this.muted) {
                this.unmute();
            }
        },

        onPlayerMessage(event) {
            const iframe = this.$refs.iframe;
            if (! iframe || event.source !== iframe.contentWindow) return;

            let data = event.data;
            if (typeof data === 'string') {
                try {
                    data = JSON.parse(data);
                } catch (e) {
                    return;
                }
            }
            if (! data || typeof data !== 'object') return;

            this.heardPlayer = true;

            if (data.event === 'onReady') {
                this.kick();
                return;
            }

            let state = null;
            if (data.event === 'onStateChange' && typeof data.info === 'number') {
                state = data.info;
            } else if (data.event === 'infoDelivery' && data.info && typeof data.info.playerState === 'number') {
                state = data.info.playerState;
            }
            if (state === null || ! this.inView) return;

            // 1 = sedang main -> bunyikan kalau browser mengizinkan.
            if (state === 1) {
                this.retries = 0;
                this.reveal();
                if (this.muted && this.canAutoUnmute()) {
                    this.unmute();
                }
                return;
            }

            // 2 = paused, 5 = cued, -1 = belum mulai, padahal section terlihat.
            if (state === 2 || state === 5 || state === -1) {
                if (! this.muted) {
                    // Browser menolak suara dan mem-pause video -> balik senyap & lanjut.
                    this.unmuteBlocked = true;
                    this.forceMutedPlay();
                } else if (this.retries < 5) {
                    this.retries += 1;
                    this.command('playVideo');
                }
            }
        },

        enter() {
            clearInterval(this.fadeTimer);
            this.inView = true;
            this.retries = 0;

            if (! this.loaded) {
                this.loaded = true;

                // Selalu mulai SENYAP -- autoplay bersuara ditolak browser.
                const origin = encodeURIComponent(window.location.origin);
                this.$refs.iframe.src = embedUrl + YOUTUBE_CLEAN_PARAMS + '&autoplay=1&mute=1&origin=' + origin;
                return;
            }

            this.command('playVideo');
            if (this.canAutoUnmute()) {
                this.unmute();
            }
        },

        exit() {
            clearInterval(this.fadeTimer);
            this.inView = false;

            if (! this.loaded || this.muted) {
                this.command('pauseVideo');
                return;
            }

            const steps = 20;
            let step = 0;
            this.fadeTimer = setInterval(() => {
                step += 1;
                this.command('setVolume', [Math.max(0, 100 - Math.round((100 * step) / steps))]);
                if (step >= steps) {
                    clearInterval(this.fadeTimer);
                    this.command('pauseVideo');
                }
            }, 40);
        },

        toggleMute() {
            this.manuallyMuted = ! this.muted;
            this.muted = this.manuallyMuted;
            this.command(this.muted ? 'mute' : 'unMute');

            if (! this.muted) {
                this.unmuteBlocked = false;
                this.command('setVolume', [100]);
                this.command('playVideo');
            }
        },
    }));
});
