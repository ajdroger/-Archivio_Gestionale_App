/**
 * MCAG Talking Archive Engine
 * Provides Text-to-Speech (TTS) capabilities for the application.
 * 
 * Features:
 * - Automatic language detection (defaults to Italian)
 * - Play/Pause/Stop controls
 * - Smart content parsing (reads hidden summaries if available)
 * - Integration with global UI
 */
class TalkingArchive {
    constructor() {
        this.synth = window.speechSynthesis;
        this.utterance = null;
        this.isPlaying = false;
        this.isPaused = false;
        this.contentSelector = '#readable-content';
        this.voiceLang = 'it-IT';

        // Only Launcher is guaranteed to exist on load
        this.launcher = document.getElementById('ta-launcher');
        this.container = null; // Will be created on demand

        this.init();
    }

    init() {
        // Check browser support
        if (!('speechSynthesis' in window)) {
            console.warn('MCAG Talking Archive: TTS not supported in this browser.');
            return;
        }

        // Check if page has readable content
        const summary = document.querySelector('.ta-smart-summary');
        const content = document.querySelector(this.contentSelector);

        this.launcher = document.getElementById('ta-launcher');

        if (!content && !summary) {
            // No content to read
            if (this.launcher) this.launcher.classList.add('d-none');
        } else {
            // Content available: Show LAUNCHER, keep CONTROLS hidden
            if (this.launcher) this.launcher.classList.remove('d-none');
            // Ensure controls are hidden initially and NOT flex
            if (this.container) {
                // FORCE CLOSED STATE via Hotfix Class & Inline Override
                this.container.classList.remove('ta-open');
                this.container.classList.remove('d-flex');
                this.container.classList.add('d-none');
                this.container.style.display = 'none'; // Enforce strict hiding
                this.container.style.setProperty('display', 'none', 'important');
            }
        }

        this.bindEvents();

        if (speechSynthesis.onvoiceschanged !== undefined) {
            speechSynthesis.onvoiceschanged = () => this.loadVoices();
        }
    }

    createWidget() {
        if (document.getElementById('talking-archive-widget')) return;

        const widgetHtml = `
            <div id="talking-archive-widget" class="d-none align-items-center gap-2" style="display: none !important; z-index: 9999; position: relative;">
                <span id="ta-status" class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 rounded-pill animate__animated animate__fadeIn" style="font-size: 0.7em;"></span>
                
                <button id="ta-play" class="btn btn-sm btn-outline-info rounded-pill px-3 d-flex align-items-center gap-2 shadow-sm" title="Ascolta questa pagina">
                    <i class="fa-solid fa-play"></i>
                    <span class="small fw-bold">Ascolta</span>
                </button>
                
                <button id="ta-pause" class="btn btn-sm btn-outline-warning rounded-pill px-3 d-flex align-items-center gap-2 shadow-sm d-none" title="Pausa lettura">
                    <i class="fa-solid fa-pause"></i>
                    <span class="small fw-bold">Pausa</span>
                </button>
                
                <button id="ta-stop" class="btn btn-sm btn-outline-danger rounded-circle shadow-sm d-flex align-items-center justify-content-center p-0" style="width: 32px; height: 32px;" title="Interrompi">
                    <i class="fa-solid fa-stop"></i>
                </button>
            </div>
        `;

        // Inject after Launcher
        if (this.launcher && this.launcher.parentElement) {
            this.launcher.insertAdjacentHTML('afterend', widgetHtml);
        }

        // Bind references
        this.container = document.getElementById('talking-archive-widget');
        this.btnPlay = document.getElementById('ta-play');
        this.btnPause = document.getElementById('ta-pause');
        this.btnStop = document.getElementById('ta-stop');
        this.statusBadge = document.getElementById('ta-status');

        // Bind Events for new elements
        if (this.btnPlay) this.btnPlay.addEventListener('click', () => this.play());
        if (this.btnPause) this.btnPause.addEventListener('click', () => this.pause());
        if (this.btnStop) this.btnStop.addEventListener('click', () => this.stopAndClose());
    }

    bindEvents() {
        // Launcher Click -> Create (if needed) & Open
        if (this.launcher) {
            this.launcher.addEventListener('click', () => {
                if (!this.container) {
                    this.createWidget();
                }
                this.openWidget();
            });
        }

        // Stop audio when navigating away
        window.addEventListener('beforeunload', () => this.stop());
    }

    openWidget() {
        this.launcher.classList.add('d-none');
        if (this.container) {
            this.container.classList.add('ta-open');
            this.container.style.display = 'flex';
            this.container.style.setProperty('display', 'flex', 'important');
            this.container.classList.add('animate__animated', 'animate__fadeInRight');
        }
    }

    stopAndClose() {
        this.stop();
        if (this.container) {
            this.container.classList.remove('ta-open');
            this.container.style.display = 'none';
            this.container.style.setProperty('display', 'none', 'important');
        }
        if (this.launcher) {
            this.launcher.classList.remove('d-none');
            this.launcher.classList.add('animate__animated', 'animate__fadeIn');
        }
    }

    loadVoices() {
        const voices = this.synth.getVoices();
        // Try to find a good Italian voice
        this.voice = voices.find(v => v.lang === 'it-IT' && v.name.includes('Google')) ||
            voices.find(v => v.lang === 'it-IT') ||
            voices[0];
    }

    getTextToRead() {
        // 1. Priority: Smart Narrative Summary (hidden element for AI/TTS)
        const summary = document.querySelector('.ta-smart-summary');
        if (summary) return summary.innerText;

        // 2. Standard Readable Content Container
        const content = document.querySelector(this.contentSelector);
        if (content) return content.innerText;

        return "Nessun contenuto leggibile trovato in questa pagina.";
    }

    play() {
        if (this.isPaused && this.utterance) {
            this.synth.resume();
            this.updateState('playing');
            return;
        }

        if (this.isPlaying) {
            this.stop();
        }

        const text = this.getTextToRead();
        this.utterance = new SpeechSynthesisUtterance(text);

        if (this.voice) {
            this.utterance.voice = this.voice;
        }

        this.utterance.lang = this.voiceLang;
        this.utterance.rate = 1.0; // Normal speed
        this.utterance.pitch = 1.0;

        this.utterance.onend = () => this.stop();
        this.utterance.onerror = (e) => {
            console.error('MCAG TTS Error:', e);
            this.stop();
        };

        this.synth.speak(this.utterance);
        this.updateState('playing');
    }

    pause() {
        if (this.synth.speaking && !this.synth.paused) {
            this.synth.pause();
            this.updateState('paused');
        }
    }

    stop() {
        this.synth.cancel();
        this.utterance = null;
        this.updateState('stopped');
    }

    updateState(state) {
        this.isPlaying = state === 'playing';
        this.isPaused = state === 'paused';

        // Update UI Icons
        if (this.btnPlay) {
            const icon = this.btnPlay.querySelector('i');
            if (state === 'playing') {
                this.btnPlay.classList.add('d-none');
                this.btnPause.classList.remove('d-none');
                if (this.statusBadge) {
                    this.statusBadge.textContent = 'In riproduzione...';
                    this.statusBadge.classList.replace('bg-secondary', 'bg-success');
                }
            } else if (state === 'paused') {
                this.btnPlay.classList.remove('d-none');
                this.btnPause.classList.add('d-none');
                if (this.statusBadge) {
                    this.statusBadge.textContent = 'In pausa';
                    this.statusBadge.classList.replace('bg-success', 'bg-warning');
                }
            } else {
                // Stopped
                this.btnPlay.classList.remove('d-none');
                this.btnPause.classList.add('d-none');
                this.btnPlay.innerHTML = '<i class="fa-solid fa-headphones me-2"></i>Ascolta';
                if (this.statusBadge) {
                    this.statusBadge.textContent = '';
                    this.statusBadge.classList.replace('bg-success', 'bg-secondary');
                    this.statusBadge.classList.replace('bg-warning', 'bg-secondary');
                }
            }
        }
    }
}

// Initialize on DOM Ready
document.addEventListener('DOMContentLoaded', () => {
    window.mcagThinkingArchive = new TalkingArchive();
});
