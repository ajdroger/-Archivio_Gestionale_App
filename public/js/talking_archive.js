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

        // UI Elements
        this.btnPlay = document.getElementById('ta-play');
        this.btnPause = document.getElementById('ta-pause');
        this.btnStop = document.getElementById('ta-stop');
        this.statusBadge = document.getElementById('ta-status');
        this.container = document.getElementById('talking-archive-controls');

        this.init();
    }

    init() {
        // Check browser support
        if (!('speechSynthesis' in window)) {
            console.warn('MCAG Talking Archive: TTS not supported in this browser.');
            if (this.container) this.container.style.display = 'none';
            return;
        }

        // Check if page has readable content
        const content = document.querySelector(this.contentSelector);
        if (!content && !document.querySelector('[data-ta-summary]')) {
            // No content to read, hide controls
            if (this.container) this.container.classList.add('d-none');
            return;
        } else {
            // Show controls
            if (this.container) this.container.classList.remove('d-none');
        }

        this.bindEvents();

        // Load voices (some browsers load async)
        if (speechSynthesis.onvoiceschanged !== undefined) {
            speechSynthesis.onvoiceschanged = () => this.loadVoices();
        }
    }

    bindEvents() {
        if (this.btnPlay) this.btnPlay.addEventListener('click', () => this.play());
        if (this.btnPause) this.btnPause.addEventListener('click', () => this.pause());
        if (this.btnStop) this.btnStop.addEventListener('click', () => this.stop());

        // Stop audio when navigating away (SPA / Turbolinks support if needed)
        window.addEventListener('beforeunload', () => this.stop());
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
                this.btnPlay.innerHTML = '<i class="fa-solid fa-headphones me-2"></i>Ascolta Pagina';
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
