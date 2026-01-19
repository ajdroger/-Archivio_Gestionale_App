/**
 * accessibility.js
 * 
 * Gestisce l'accessibilità globale e l'internazionalizzazione (i18n).
 * - Theme Switcher (Dark/Light)
 * - Universal Language Engine (Google Translate Wrapper)
 */

const AccessControl = {
    // --- INIT ---
    init: () => {
        // 1. Load Theme
        const savedTheme = localStorage.getItem('global_theme') || 'dark';
        AccessControl.setTheme(savedTheme);

        // 2. Init Google Translate
        AccessControl.loadGoogleTranslate();
    },

    // --- THEME ENGINE ---
    toggleTheme: () => {
        const current = document.body.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
        const next = current === 'dark' ? 'light' : 'dark';
        AccessControl.setTheme(next);
    },

    setTheme: (theme) => {
        if (theme === 'light') {
            document.body.setAttribute('data-theme', 'light');
            document.body.classList.add('light-mode-global');
        } else {
            document.body.removeAttribute('data-theme');
            document.body.classList.remove('light-mode-global');
        }
        localStorage.setItem('global_theme', theme);

        const icon = document.getElementById('theme-toggle-icon');
        if (icon) {
            icon.className = theme === 'light' ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
        }
    },

    // --- UNIVERSAL LANGUAGE ENGINE (Google Translate) ---
    loadGoogleTranslate: () => {
        // Check if already loaded
        if (document.getElementById('gt-script')) return;

        // Define Config
        window.googleTranslateElementInit = () => {
            new google.translate.TranslateElement({
                pageLanguage: 'it',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');
        };

        // Inject Script
        const script = document.createElement('script');
        script.id = 'gt-script';
        script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
        script.async = true;
        document.body.appendChild(script);

        // Inject CSS to hide Google TopBar
        const style = document.createElement('style');
        style.innerHTML = `
            .goog-te-banner-frame.skiptranslate, 
            .goog-te-gadget-icon, 
            body > .skiptranslate { 
                display: none !important; 
            }
            body { top: 0px !important; }
            .goog-te-gadget-simple {
                background: transparent !important;
                border: none !important;
                padding: 0 !important;
                font-size: 0 !important;
            }
            .goog-te-gadget-simple .goog-te-menu-value span {
                color: transparent !important;
            }
            #google_translate_element {
                visibility: hidden;
                width: 1px;
                height: 1px;
                overflow: hidden;
                position: absolute;
                top: -9999px;
            }
        `;
        document.head.appendChild(style);
    },

    openLangSelector: () => {
        const modalEl = document.getElementById('modal-language-world');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    },

    setLang: (langCode) => {
        // Google Translate Cookie Hack to trigger translation
        // Cookie: googtrans=/source/target
        const cookieValue = `/it/${langCode}`;
        document.cookie = `googtrans=${cookieValue}; path=/; domain=${window.location.hostname}`;
        document.cookie = `googtrans=${cookieValue}; path=/`; // Fallback

        // Save preference
        localStorage.setItem('global_lang', langCode);

        // Update Flag Icon
        const flag = document.getElementById('lang-toggle-flag');
        if (flag) {
            // Map common codes to flagcdn
            const flagMap = {
                'en': 'gb', 'zh-CN': 'cn', 'ja': 'jp', 'ko': 'kr', 'hi': 'in',
                'ar': 'sa', 'ru': 'ru', 'es': 'es', 'fr': 'fr', 'de': 'de', 'pt': 'pt'
            };
            const cdnCode = flagMap[langCode] || langCode;
            flag.src = `https://flagcdn.com/w20/${cdnCode}.png`;
        }

        // Reload to apply
        location.reload();
    },

    resetLang: () => {
        // Clear cookies
        document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=' + window.location.hostname;
        document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/';

        localStorage.removeItem('global_lang');
        location.reload();
    }
};

// Auto-Init
document.addEventListener('DOMContentLoaded', AccessControl.init);

// Expose Global
window.AccessControl = AccessControl;
